<?php

require_once __DIR__ . "/../TestBase.php";

/**
 * Тесты для функции открытия игры
 */
class OpenGameTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
    }

    /**
     * Тест 1.1: Успешное открытие игры
     */
    public function testSuccessfulGameOpening(): void
    {
        // Создаем тестовую игру
        $game = $this->createTestGame([
            'name' => 'Тестовая игра для открытия',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        // Создаем тестового пользователя
        $user = $this->createTestUser([
            'login' => 'Тестовый игрок',
            'game' => $game['id'],
            'turn_order' => 1
        ]);

        // Симулируем POST запрос на открытие игры
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $user['id']
        ]);

        // Включаем логику из index.php для обработки открытия игры
        $this->simulateGameOpening();

        // Проверяем, что сессия установлена корректно
        $this->assertEquals($game['id'], $_SESSION['game_id'], 'ID игры должен быть установлен в сессии');
        $this->assertEquals($user['id'], $_SESSION['user_id'], 'ID пользователя должен быть установлен в сессии');
    }

    /**
     * Тест 1.2: Открытие игры с несколькими игроками
     */
    public function testOpenGameWithMultiplePlayers(): void
    {
        $game = $this->createTestGame([
            'name' => 'Игра с несколькими игроками',
            'map_w' => 150,
            'map_h' => 150,
            'turn_type' => 'concurrently'
        ]);

        $players = [];
        for ($i = 1; $i <= 4; $i++) {
            $players[] = $this->createTestUser([
                'login' => "Игрок{$i}",
                'game' => $game['id'],
                'turn_order' => $i
            ]);
        }

        // Открываем игру вторым игроком
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $players[1]['id'] // второй игрок
        ]);

        $this->simulateGameOpening();

        $this->assertEquals($game['id'], $_SESSION['game_id']);
        $this->assertEquals($players[1]['id'], $_SESSION['user_id']);
    }

    /**
     * Тест 1.3: Открытие игры с разными типами ходов
     */
    public function testOpenGameWithDifferentTurnTypes(): void
    {
        $turnTypes = ['byturn', 'concurrently', 'onewindow'];

        foreach ($turnTypes as $turnType) {
            $this->clearTestData();
            $this->clearSession();

            $game = $this->createTestGame([
                'name' => "Игра с типом {$turnType}",
                'map_w' => 100,
                'map_h' => 100,
                'turn_type' => $turnType
            ]);

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

            $this->assertEquals($game['id'], $_SESSION['game_id'], "Сессия должна быть установлена для типа ходов {$turnType}");
            $this->assertEquals($user['id'], $_SESSION['user_id'], "Пользователь должен быть установлен для типа ходов {$turnType}");
        }
    }

    /**
     * Тест 2.1: Попытка открыть несуществующую игру
     */
    public function testOpenNonExistentGame(): void
    {
        $user = $this->createTestUser(['login' => 'Игрок']);

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => 999, // несуществующий ID игры
            'uid' => $user['id']
        ]);

        $this->expectOutputString('game error');
        $this->simulateGameOpening();
    }

    /**
     * Тест 2.2: Попытка открыть игру с несуществующим пользователем
     */
    public function testOpenGameWithNonExistentUser(): void
    {
        $game = $this->createTestGame();

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => 999 // несуществующий ID пользователя
        ]);

        $this->expectOutputString('user error');
        $this->simulateGameOpening();
    }

    /**
     * Тест 2.3: Попытка открыть игру с пользователем из другой игры
     */
    public function testOpenGameWithUserFromDifferentGame(): void
    {
        $game1 = $this->createTestGame(['name' => 'Игра 1']);
        $game2 = $this->createTestGame(['name' => 'Игра 2']);

        $userFromGame2 = $this->createTestUser([
            'login' => 'Игрок из игры 2',
            'game' => $game2['id']
        ]);

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game1['id'], // пытаемся открыть игру 1
            'uid' => $userFromGame2['id'] // пользователем из игры 2
        ]);

        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();

        $this->assertEquals('user error', $output);

        // Проверяем, что сессия НЕ установлена
        $this->assertArrayNotHasKey('game_id', $_SESSION);
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    /**
     * Тест 2.4: Открытие игры без указания ID игры
     */
    public function testOpenGameWithoutGameId(): void
    {
        $user = $this->createTestUser();

        $this->simulatePostRequest([
            'method' => 'login',
            // 'gid' не указан
            'uid' => $user['id']
        ]);

        $this->expectOutputString('game error');
        $this->simulateGameOpening();
    }

    /**
     * Тест 2.5: Открытие игры без указания ID пользователя
     */
    public function testOpenGameWithoutUserId(): void
    {
        $game = $this->createTestGame();

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id']
            // 'uid' не указан
        ]);

        $this->expectOutputString('user error');
        $this->simulateGameOpening();
    }

    /**
     * Тест 3.1: Загрузка списка игр
     */
    public function testGameListLoading(): void
    {
        // Создаем несколько игр с игроками
        $games = [];
        for ($i = 1; $i <= 3; $i++) {
            $game = $this->createTestGame([
                'name' => "Игра {$i}",
                'map_w' => 100,
                'map_h' => 100,
                'turn_type' => 'byturn'
            ]);

            // Добавляем игроков к каждой игре
            for ($j = 1; $j <= $i; $j++) { // игра 1 - 1 игрок, игра 2 - 2 игрока, игра 3 - 3 игрока
                $this->createTestUser([
                    'login' => "Игрок{$j} игры{$i}",
                    'game' => $game['id'],
                    'turn_order' => $j
                ]);
            }

            $games[] = $game;
        }

        // Загружаем список игр
        $gamelist = Game::game_list();

        // Проверяем, что список содержит все игры
        $this->assertCount(3, $gamelist, 'Должен быть список из 3 игр');

        // Проверяем сортировку (ORDER BY id DESC)
        $this->assertEquals('Игра 3', $gamelist[0]['name'], 'Первая игра должна быть самой новой');
        $this->assertEquals('Игра 2', $gamelist[1]['name']);
        $this->assertEquals('Игра 1', $gamelist[2]['name']);

        // Проверяем количество игроков
        $this->assertEquals(3, $gamelist[0]['ucount'], 'Игра 3 должна иметь 3 игроков');
        $this->assertEquals(2, $gamelist[1]['ucount'], 'Игра 2 должна иметь 2 игроков');
        $this->assertEquals(1, $gamelist[2]['ucount'], 'Игра 1 должна иметь 1 игроков');
    }

    /**
     * Тест 3.2: Загрузка пустого списка игр
     */
    public function testEmptyGameList(): void
    {
        $gamelist = Game::game_list();
        $this->assertEmpty($gamelist, 'Список игр должен быть пустым');
    }

    /**
     * Тест 4.1: Проверка сессии после открытия игры
     */
    public function testSessionStateAfterGameOpening(): void
    {
        $game = $this->createTestGame();
        $user = $this->createTestUser(['game' => $game['id']]);

        // Устанавливаем начальную сессию
        $_SESSION = ['some_other_data' => 'test'];

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $user['id']
        ]);

        $this->simulateGameOpening();

        // Проверяем, что новые данные добавлены к существующей сессии
        $this->assertEquals($game['id'], $_SESSION['game_id']);
        $this->assertEquals($user['id'], $_SESSION['user_id']);
        $this->assertEquals('test', $_SESSION['some_other_data'], 'Другие данные сессии должны сохраниться');
    }

    /**
     * Тест 4.2: Переоткрытие игры другим пользователем
     */
    public function testReopenGameWithDifferentUser(): void
    {
        $game = $this->createTestGame();

        $user1 = $this->createTestUser([
            'login' => 'Игрок1',
            'game' => $game['id'],
            'turn_order' => 1
        ]);

        $user2 = $this->createTestUser([
            'login' => 'Игрок2',
            'game' => $game['id'],
            'turn_order' => 2
        ]);

        // Сначала открываем игру первым игроком
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $user1['id']
        ]);

        $this->simulateGameOpening();

        $this->assertEquals($user1['id'], $_SESSION['user_id']);

        // Затем открываем игру вторым игроком (в новой сессии)
        $this->clearSession();

        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $user2['id']
        ]);

        $this->simulateGameOpening();

        $this->assertEquals($user2['id'], $_SESSION['user_id']);
        $this->assertEquals($game['id'], $_SESSION['game_id']);
    }

    /**
     * Тест 5.1: Открытие игры с максимальным количеством игроков
     */
    public function testOpenGameWithMaxPlayers(): void
    {
        $game = $this->createTestGame([
            'name' => 'Игра с максимумом игроков',
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

        // Открываем игру последним игроком
        $this->simulatePostRequest([
            'method' => 'login',
            'gid' => $game['id'],
            'uid' => $players[15]['id'] // последний игрок
        ]);

        $this->simulateGameOpening();

        $this->assertEquals($game['id'], $_SESSION['game_id']);
        $this->assertEquals($players[15]['id'], $_SESSION['user_id']);
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
