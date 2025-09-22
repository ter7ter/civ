<?php

require_once __DIR__ . '/../TestBase.php';
require_once __DIR__ . '/../DatabaseMocks.php';

/**
 * Интеграционные тесты для полного процесса открытия игры
 */
class OpenGameIntegrationTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
    }

    /**
     * Тест полного процесса открытия игры
     */
    public function testFullGameOpeningProcess(): void
    {
        // Создаем тестовую игру
        $game = $this->createTestGame([
            'name' => 'Интеграционная игра',
            'map_w' => 200,
            'map_h' => 200,
            'turn_type' => 'concurrently'
        ]);

        // Создаем игроков
        $players = [];
        for ($i = 1; $i <= 3; $i++) {
            $players[] = $this->createTestUser([
                'login' => "Игрок{$i}",
                'game' => $game['id'],
                'turn_order' => $i
            ]);
        }

        // Симулируем POST запрос на открытие игры
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $players[1]['id'] // открываем вторым игроком
        ]);

        // Включаем логику из index.php
        $this->simulateGameOpening();

        // Проверяем результат
        $this->assertEquals($game['id'], $_SESSION['game_id'], 'ID игры должен быть установлен в сессии');
        $this->assertEquals($players[1]['id'], $_SESSION['user_id'], 'ID пользователя должен быть установлен в сессии');

        // Проверяем, что игра и пользователь существуют в БД
        $this->assertTrue($this->recordExists('game', ['id' => $game['id']]));
        $this->assertTrue($this->recordExists('user', ['id' => $players[1]['id'], 'game' => $game['id']]));
    }

    /**
     * Тест открытия игры и проверки данных в сессии
     */
    public function testGameOpeningSessionPersistence(): void
    {
        $game = $this->createTestGame([
            'name' => 'Тест сессии',
            'map_w' => 150,
            'map_h' => 150,
            'turn_type' => 'byturn'
        ]);

        $user = $this->createTestUser([
            'login' => 'Тестовый пользователь',
            'game' => $game['id'],
            'turn_order' => 1,
            'money' => 100,
            'age' => 2
        ]);

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $user['id']
        ]);

        $this->simulateGameOpening();

        // Проверяем, что сессия содержит правильные данные
        $this->assertEquals($game['id'], $_SESSION['game_id']);
        $this->assertEquals($user['id'], $_SESSION['user_id']);

        // Проверяем, что можем получить объекты игры и пользователя
        $sessionGame = Game::get($_SESSION['game_id']);
        $sessionUser = User::get($_SESSION['user_id']);

        $this->assertNotNull($sessionGame, 'Должна быть получена игра из сессии');
        $this->assertNotNull($sessionUser, 'Должен быть получен пользователь из сессии');

        $this->assertEquals('Тест сессии', $sessionGame->name);
        $this->assertEquals('Тестовый пользователь', $sessionUser->login);
        $this->assertEquals($game['id'], $sessionUser->game);
    }

    /**
     * Тест открытия игры с различными конфигурациями
     */
    public function testGameOpeningWithDifferentConfigurations(): void
    {
        $configurations = [
            ['name' => 'Маленькая карта', 'map_w' => 50, 'map_h' => 50, 'turn_type' => 'byturn'],
            ['name' => 'Большая карта', 'map_w' => 500, 'map_h' => 500, 'turn_type' => 'concurrently'],
            ['name' => 'Средняя карта', 'map_w' => 200, 'map_h' => 300, 'turn_type' => 'onewindow']
        ];

        foreach ($configurations as $config) {
            $this->clearTestData();
            $this->clearSession();

            $game = $this->createTestGame($config);

            $user = $this->createTestUser([
                'login' => 'Игрок',
                'game' => $game['id']
            ]);

            $this->simulatePostRequest([
                'method' => 'login',
                'gid' => $game['id'],
                'uid' => $user['id']
            ]);

            $this->simulateGameOpening();

            $this->assertEquals($game['id'], $_SESSION['game_id'], "Сессия должна быть установлена для конфигурации: {$config['name']}");
            $this->assertEquals($user['id'], $_SESSION['user_id'], "Пользователь должен быть установлен для конфигурации: {$config['name']}");
        }
    }

    /**
     * Тест списка игр с различным количеством игроков
     */
    public function testGameListWithDifferentPlayerCounts(): void
    {
        // Создаем игры с разным количеством игроков
        $gameConfigs = [
            ['name' => 'Одиночная игра', 'players' => 1],
            ['name' => 'Дуэль', 'players' => 2],
            ['name' => 'Тройка', 'players' => 3],
            ['name' => 'Максимум игроков', 'players' => 16]
        ];

        foreach ($gameConfigs as $config) {
            $game = $this->createTestGame([
                'name' => $config['name'],
                'map_w' => 100,
                'map_h' => 100,
                'turn_type' => 'byturn'
            ]);

            // Создаем игроков
            for ($i = 1; $i <= $config['players']; $i++) {
                $this->createTestUser([
                    'login' => "Игрок{$i}",
                    'game' => $game['id'],
                    'turn_order' => $i
                ]);
            }
        }

        // Получаем список игр
        $gamelist = Game::game_list();

        // Проверяем, что все игры есть в списке
        $this->assertCount(4, $gamelist, 'Должен быть список из 4 игр');

        // Проверяем количество игроков для каждой игры
        $expectedCounts = [16, 3, 2, 1]; // отсортировано по id DESC
        foreach ($gamelist as $index => $gameInfo) {
            $this->assertEquals($expectedCounts[$index], $gameInfo['ucount'],
                "Игра '{$gameInfo['name']}' должна иметь {$expectedCounts[$index]} игроков");
        }
    }

    /**
     * Тест обработки ошибок при открытии игры
     */
    public function testErrorHandlingInGameOpening(): void
    {
        // Тест с несуществующей игрой
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => 999,
            'uid' => 1
        ]);

        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals('game error', $output);

        // Тест с несуществующим пользователем
        $this->clearTestData();
        $game = $this->createTestGame();

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => 999
        ]);

        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals('user error', $output);

        // Тест с пользователем из другой игры
        $this->clearTestData();
        $game1 = $this->createTestGame(['name' => 'Игра 1']);
        $game2 = $this->createTestGame(['name' => 'Игра 2']);

        $userFromGame2 = $this->createTestUser(['game' => $game2['id']]);

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game1['id'],
            'uid' => $userFromGame2['id']
        ]);

        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals('user error', $output);
    }

    /**
     * Тест производительности открытия игры
     */
    public function testPerformanceGameOpening(): void
    {
        $game = $this->createTestGame([
            'name' => 'Тест производительности',
            'map_w' => 500,
            'map_h' => 500,
            'turn_type' => 'concurrently'
        ]);

        // Создаем максимальное количество игроков
        $players = [];
        for ($i = 1; $i <= 16; $i++) {
            $players[] = $this->createTestUser([
                'login' => "Игрок{$i}",
                'game' => $game['id'],
                'turn_order' => $i
            ]);
        }

        $startTime = microtime(true);

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $players[0]['id']
        ]);

        $this->simulateGameOpening();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $executionTime, 'Открытие игры должно занимать менее 1 секунды');
        $this->assertEquals($game['id'], $_SESSION['game_id']);
        $this->assertEquals($players[0]['id'], $_SESSION['user_id']);
    }

    /**
     * Тест безопасности: попытка открыть игру с некорректными ID
     */
    public function testSecurityInvalidIds(): void
    {
        // Тест с отрицательными ID
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => -1,
            'uid' => -1
        ]);

        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals('game error', $output);

        // Тест с очень большими ID
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => PHP_INT_MAX,
            'uid' => PHP_INT_MAX
        ]);

        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals('game error', $output);

        // Тест с нечисловыми ID
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => 'not_a_number',
            'uid' => 'also_not_a_number'
        ]);

        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals('game error', $output);
    }

    /**
     * Тест множественного открытия игр
     */
    public function testMultipleGameOpenings(): void
    {
        // Создаем несколько игр
        $games = [];
        for ($i = 1; $i <= 3; $i++) {
            $game = $this->createTestGame([
                'name' => "Игра {$i}",
                'map_w' => 100,
                'map_h' => 100,
                'turn_type' => 'byturn'
            ]);

            $user = $this->createTestUser([
                'login' => "Игрок игры {$i}",
                'game' => $game['id']
            ]);

            $games[] = ['game' => $game, 'user' => $user];
        }

        // Открываем каждую игру по очереди
        foreach ($games as $gameData) {
            $this->clearSession();

            $this->simulatePostRequest([
                'method' => 'login',
                'gid' => $gameData['game']['id'],
                'uid' => $gameData['user']['id']
            ]);

            $this->simulateGameOpening();

            $this->assertEquals($gameData['game']['id'], $_SESSION['game_id']);
            $this->assertEquals($gameData['user']['id'], $_SESSION['user_id']);
        }
    }

    /**
     * Тест открытия игры с проверкой целостности данных
     */
    public function testGameOpeningDataIntegrity(): void
    {
        $game = $this->createTestGame([
            'name' => 'Тест целостности данных',
            'map_w' => 250,
            'map_h' => 300,
            'turn_type' => 'onewindow',
            'turn_num' => 5
        ]);

        $user = $this->createTestUser([
            'login' => 'Тестовый игрок',
            'game' => $game['id'],
            'turn_order' => 1,
            'money' => 200,
            'age' => 3,
            'turn_status' => 'active'
        ]);

        // Открываем игру
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $user['id']
        ]);

        $this->simulateGameOpening();

        // Проверяем, что сессия установлена
        $this->assertEquals($game['id'], $_SESSION['game_id']);
        $this->assertEquals($user['id'], $_SESSION['user_id']);

        // Проверяем, что можем получить полные объекты
        $loadedGame = Game::get($_SESSION['game_id']);
        $loadedUser = User::get($_SESSION['user_id']);

        // Проверяем данные игры
        $this->assertEquals('Тест целостности данных', $loadedGame->name);
        $this->assertEquals(250, $loadedGame->map_w);
        $this->assertEquals(300, $loadedGame->map_h);
        $this->assertEquals('onewindow', $loadedGame->turn_type);
        $this->assertEquals(5, $loadedGame->turn_num);

        // Проверяем данные пользователя
        $this->assertEquals('Тестовый игрок', $loadedUser->login);
        $this->assertEquals($game['id'], $loadedUser->game);
        $this->assertEquals(1, $loadedUser->turn_order);
        $this->assertEquals(200, $loadedUser->money);
        $this->assertEquals(3, $loadedUser->age);
        $this->assertEquals('active', $loadedUser->turn_status);
    }

    /**
     * Тест открытия игры с проверкой связей между объектами
     */
    public function testGameOpeningObjectRelationships(): void
    {
        $game = $this->createTestGame([
            'name' => 'Тест связей объектов',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        // Создаем нескольких игроков
        $users = [];
        for ($i = 1; $i <= 4; $i++) {
            $users[] = $this->createTestUser([
                'login' => "Игрок{$i}",
                'game' => $game['id'],
                'turn_order' => $i
            ]);
        }

        // Открываем игру первым игроком
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $users[0]['id']
        ]);

        $this->simulateGameOpening();

        // Получаем объекты из сессии
        $sessionGame = Game::get($_SESSION['game_id']);
        $sessionUser = User::get($_SESSION['user_id']);

        // Проверяем, что игра содержит всех пользователей
        $this->assertCount(4, $sessionGame->users, 'Игра должна содержать 4 пользователей');

        // Проверяем, что текущий пользователь является одним из пользователей игры
        $this->assertArrayHasKey($sessionUser->id, $sessionGame->users, 'Текущий пользователь должен быть в списке пользователей игры');

        // Проверяем, что пользователь принадлежит правильной игре
        $this->assertEquals($sessionGame->id, $sessionUser->game, 'Пользователь должен принадлежать открытой игре');
    }

    /**
     * Вспомогательный метод для симуляции логики открытия игры из index.php
     */
    private function simulateGameOpening(): void
    {
        // Определяем страницу как в index.php
        $page = isset($_REQUEST['method']) ? $_REQUEST['method'] : 'map';

        if ($page == 'login' && isset($_REQUEST['gid']) && isset($_REQUEST['uid'])) {
            $game = Game::get((int)$_REQUEST['gid']);
            if (!$game) {
                echo 'game error';
                return;
            }
            $user = User::get((int)$_REQUEST['uid']);
            if (!$user) {
                echo 'user error';
                return;
            }
            if ($user->game != $game->id) {
                echo 'user error';
                return;
            }
            $_SESSION['game_id'] = $game->id;
            $_SESSION['user_id'] = $user->id;
        } elseif ($page == 'login' && (!isset($_REQUEST['gid']) || !isset($_REQUEST['uid']))) {
            // Обработка случаев, когда параметры отсутствуют
            if (!isset($_REQUEST['gid'])) {
                echo 'game error';
            } elseif (!isset($_REQUEST['uid'])) {
                echo 'user error';
            }
        }
    }
}
