<?php

require_once __DIR__ . '/../TestBase.php';
require_once __DIR__ . '/../DatabaseMocks.php';

/**
 * Интеграционные тесты для полного процесса редактирования игры
 */
class EditGameIntegrationTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
    }

    /**
     * Тест полного процесса редактирования игры с реальной базой данных
     */
    public function testFullGameEditProcess(): void
    {
        // Создаем исходную игру
        $originalGame = $this->createTestGame([
            'name' => 'Исходная игра',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        // Создаем игроков
        $this->createTestUser(['login' => 'Алиса', 'game' => $originalGame['id'], 'turn_order' => 1]);
        $this->createTestUser(['login' => 'Боб', 'game' => $originalGame['id'], 'turn_order' => 2]);
        $this->createTestUser(['login' => 'Чарли', 'game' => $originalGame['id'], 'turn_order' => 3]);

        // Подготовка данных для редактирования
        $editData = [
            'game_id' => $originalGame['id'],
            'name' => 'Отредактированная игра',
            'map_w' => 200,
            'map_h' => 150,
            'turn_type' => 'concurrently'
        ];

        $this->simulatePostRequest($editData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/editgame.php');
        $error = $vars['error'] ?? false;

        // Проверяем результат
        $this->assertFalse($error, 'Редактирование игры должно пройти без ошибок: ' . (is_string($error) ? $error : ''));

        // Проверяем, что игра обновлена в "базе данных"
        $this->assertTrue($this->recordExists('game', ['name' => 'Отредактированная игра']));
        $this->assertFalse($this->recordExists('game', ['name' => 'Исходная игра']));

        // Проверяем, что пользователи остались без изменений
        $this->assertTrue($this->recordExists('user', ['login' => 'Алиса']));
        $this->assertTrue($this->recordExists('user', ['login' => 'Боб']));
        $this->assertTrue($this->recordExists('user', ['login' => 'Чарли']));
    }

    /**
     * Тест редактирования игры и проверки корректности обновленных данных в БД
     */
    public function testEditedGameDataPersistence(): void
    {
        $originalGame = $this->createTestGame([
            'name' => 'Тест сохранения изменений',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        $this->createTestUser(['login' => 'Игрок1', 'game' => $originalGame['id']]);
        $this->createTestUser(['login' => 'Игрок2', 'game' => $originalGame['id']]);

        $editData = [
            'game_id' => $originalGame['id'],
            'name' => 'Обновленное название',
            'map_w' => 300,
            'map_h' => 250,
            'turn_type' => 'onewindow'
        ];

        $this->simulatePostRequest($editData);

        mockIncludeFile(__DIR__ . '/../../pages/editgame.php');

        // Проверяем обновленные данные игры
        $gameRecord = $this->getLastRecord('game');
        $this->assertNotNull($gameRecord, 'Запись игры должна существовать');
        $this->assertEquals('Обновленное название', $gameRecord['name']);
        $this->assertEquals(300, $gameRecord['map_w']);
        $this->assertEquals(250, $gameRecord['map_h']);
        $this->assertEquals('onewindow', $gameRecord['turn_type']);

        // Проверяем, что количество пользователей не изменилось
        $userCount = $this->getTableCount('user');
        $this->assertEquals(2, $userCount, 'Количество пользователей должно остаться прежним');
    }

    /**
     * Тест редактирования игры с различными типами ходов
     */
    public function testEditDifferentTurnTypesIntegration(): void
    {
        $game = $this->createTestGame([
            'name' => 'Тест типов ходов',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        $this->createTestUser(['login' => 'Игрок1', 'game' => $game['id']]);
        $this->createTestUser(['login' => 'Игрок2', 'game' => $game['id']]);

        $turnTypes = [
            'concurrently' => 'Одновременная игра',
            'byturn' => 'По очереди',
            'onewindow' => 'Один компьютер'
        ];

        foreach ($turnTypes as $turnType => $gameName) {
            $editData = [
                'game_id' => $game['id'],
                'name' => $gameName,
                'map_w' => 100,
                'map_h' => 100,
                'turn_type' => $turnType
            ];

            $this->simulatePostRequest($editData);

            $vars = mockIncludeFile(__DIR__ . '/../../pages/editgame.php');
            $error = $vars['error'] ?? false;

            $this->assertFalse($error, "Редактирование игры с типом {$turnType} должно пройти успешно: " . (is_string($error) ? $error : ''));

            $gameRecord = $this->getLastRecord('game');
            $this->assertEquals($turnType, $gameRecord['turn_type'], "Тип ходов должен быть {$turnType}");
            $this->assertEquals($gameName, $gameRecord['name'], "Название должно быть {$gameName}");
        }
    }

    /**
     * Тест загрузки данных игры для редактирования
     */
    public function testLoadGameDataForEditingIntegration(): void
    {
        $game = $this->createTestGame([
            'name' => 'Игра для загрузки',
            'map_w' => 180,
            'map_h' => 220,
            'turn_type' => 'concurrently'
        ]);

        $players = ['Первый', 'Второй', 'Третий', 'Четвертый'];
        foreach ($players as $index => $playerName) {
            $this->createTestUser([
                'login' => $playerName,
                'game' => $game['id'],
                'turn_order' => $index + 1
            ]);
        }

        // Симулируем GET запрос для загрузки данных
        $_REQUEST = ['game_id' => $game['id']];
        $_GET = ['game_id' => $game['id']];

        $vars = mockIncludeFile(__DIR__ . '/../../pages/editgame.php');
        $data = $vars['data'] ?? [];

        // Проверяем корректность загруженных данных
        $this->assertEquals($game['id'], $data['game_id']);
        $this->assertEquals('Игра для загрузки', $data['name']);
        $this->assertEquals(180, $data['map_w']);
        $this->assertEquals(220, $data['map_h']);
        $this->assertEquals('concurrently', $data['turn_type']);
        $this->assertEquals($players, $data['users']);
    }

    /**
     * Тест сохранения порядка игроков при редактировании
     */
    public function testPlayerOrderPreservationOnEdit(): void
    {
        $game = $this->createTestGame([
            'name' => 'Тест порядка игроков',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        $players = ['Альфа', 'Бета', 'Гамма', 'Дельта'];
        foreach ($players as $index => $playerName) {
            $this->createTestUser([
                'login' => $playerName,
                'game' => $game['id'],
                'turn_order' => $index + 1
            ]);
        }

        // Редактируем игру
        $editData = [
            'game_id' => $game['id'],
            'name' => 'Отредактированная игра',
            'map_w' => 150,
            'map_h' => 150,
            'turn_type' => 'concurrently'
        ];

        $this->simulatePostRequest($editData);
        mockIncludeFile(__DIR__ . '/../../pages/editgame.php');

        // Проверяем, что порядок игроков сохранился
        $userOrder = MyDBTestWrapper::query("SELECT login, turn_order FROM user WHERE game = ? ORDER BY turn_order", [$game['id']]);

        $this->assertEquals(4, count($userOrder), 'Должно быть 4 игрока');

        for ($i = 0; $i < count($players); $i++) {
            $this->assertEquals($players[$i], $userOrder[$i]['login'], 'Порядок игроков должен сохраниться');
            $this->assertEquals($i + 1, $userOrder[$i]['turn_order'], 'Порядок ходов должен сохраниться');
        }
    }

    /**
     * Тест сохранения параметров игроков при редактировании игры
     */
    public function testPlayerParametersPreservationOnEdit(): void
    {
        $game = $this->createTestGame([
            'name' => 'Тест сохранения параметров',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        // Создаем игроков с различными параметрами
        $player1 = $this->createTestUser([
            'login' => 'Богач',
            'game' => $game['id'],
            'money' => 100,
            'age' => 2,
            'turn_status' => 'active',
            'turn_order' => 1
        ]);

        $player2 = $this->createTestUser([
            'login' => 'Бедняк',
            'game' => $game['id'],
            'money' => 25,
            'age' => 1,
            'turn_status' => 'wait',
            'turn_order' => 2
        ]);

        // Редактируем игру
        $editData = [
            'game_id' => $game['id'],
            'name' => 'Отредактированная игра',
            'map_w' => 200,
            'map_h' => 200,
            'turn_type' => 'concurrently'
        ];

        $this->simulatePostRequest($editData);
        mockIncludeFile(__DIR__ . '/../../pages/editgame.php');

        // Проверяем, что параметры игроков не изменились
        $users = MyDBTestWrapper::query("SELECT * FROM user WHERE game = ? ORDER BY turn_order", [$game['id']]);

        $this->assertEquals(2, count($users), 'Должно быть 2 игрока');

        // Проверяем первого игрока
        $this->assertEquals('Богач', $users[0]['login']);
        $this->assertEquals(100, $users[0]['money']);
        $this->assertEquals(2, $users[0]['age']);
        $this->assertEquals('active', $users[0]['turn_status']);

        // Проверяем второго игрока
        $this->assertEquals('Бедняк', $users[1]['login']);
        $this->assertEquals(25, $users[1]['money']);
        $this->assertEquals(1, $users[1]['age']);
        $this->assertEquals('wait', $users[1]['turn_status']);
    }

    /**
     * Тест обработки ошибок валидации в интеграционном режиме
     */
    public function testValidationErrorsIntegrationOnEdit(): void
    {
        $game = $this->createTestGame([
            'name' => 'Исходная игра',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        $this->createTestUser(['login' => 'Игрок1', 'game' => $game['id']]);
        $this->createTestUser(['login' => 'Игрок2', 'game' => $game['id']]);

        $invalidData = [
            'game_id' => $game['id'],
            'name' => '', // пустое название
            'map_w' => 1000, // слишком большая ширина
            'map_h' => 10, // слишком маленькая высота
            'turn_type' => 'invalid'
        ];

        $this->simulatePostRequest($invalidData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/editgame.php');
        $error = $vars['error'] ?? false;

        // Должно быть несколько ошибок
        $this->assertTrue($error !== false, 'Должны быть ошибки валидации');
        $this->assertStringContainsString('Название игры не может быть пустым', $error);
        $this->assertStringContainsString('должна быть от 50 до 500', $error);

        // Проверяем, что данные игры не были изменены в БД
        $gameRecord = $this->getLastRecord('game');
        $this->assertEquals('Исходная игра', $gameRecord['name'], 'Название игры не должно измениться при ошибках валидации');
        $this->assertEquals(100, $gameRecord['map_w'], 'Ширина карты не должна измениться при ошибках валидации');
        $this->assertEquals(100, $gameRecord['map_h'], 'Высота карты не должна измениться при ошибках валидации');
        $this->assertEquals('byturn', $gameRecord['turn_type'], 'Тип ходов не должен измениться при ошибках валидации');
    }

    /**
     * Тест производительности редактирования игры с максимальным количеством игроков
     */
    public function testPerformanceEditWithMaxPlayers(): void
    {
        $game = $this->createTestGame([
            'name' => 'Тест производительности редактирования',
            'map_w' => 200,
            'map_h' => 200,
            'turn_type' => 'byturn'
        ]);

        // Создаем максимальное количество игроков
        for ($i = 1; $i <= 16; $i++) {
            $this->createTestUser([
                'login' => "Игрок{$i}",
                'game' => $game['id'],
                'turn_order' => $i
            ]);
        }

        $editData = [
            'game_id' => $game['id'],
            'name' => 'Отредактированная игра с 16 игроками',
            'map_w' => 500,
            'map_h' => 500,
            'turn_type' => 'concurrently'
        ];

        $startTime = microtime(true);

        $this->simulatePostRequest($editData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/editgame.php');
        $error = $vars['error'] ?? false;

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertFalse($error, 'Редактирование игры должно пройти успешно: ' . (is_string($error) ? $error : ''));
        $this->assertLessThan(3.0, $executionTime, 'Редактирование игры должно занимать менее 3 секунд');

        // Проверяем, что все данные обновились корректно
        $gameRecord = $this->getLastRecord('game');
        $this->assertEquals('Отредактированная игра с 16 игроками', $gameRecord['name']);
        $this->assertEquals(500, $gameRecord['map_w']);
        $this->assertEquals(500, $gameRecord['map_h']);
        $this->assertEquals('concurrently', $gameRecord['turn_type']);

        // Проверяем, что все игроки остались
        $this->assertEquals(16, $this->getTableCount('user'), 'Должно остаться 16 игроков');
    }

    /**
     * Тест безопасности: SQL инъекции при редактировании
     */
    public function testSQLInjectionProtectionOnEdit(): void
    {
        $game = $this->createTestGame([
            'name' => 'Безопасная игра',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        $this->createTestUser(['login' => 'Игрок1', 'game' => $game['id']]);
        $this->createTestUser(['login' => 'Игрок2', 'game' => $game['id']]);

        $maliciousData = [
            'game_id' => $game['id'],
            'name' => "'; DROP TABLE game; --",
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ];

        $this->simulatePostRequest($maliciousData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/editgame.php');
        $error = $vars['error'] ?? false;

        // Проверяем, что таблицы не были удалены
        $tablesQuery = MyDBTestWrapper::query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = array_column($tablesQuery, 'name');

        $this->assertContains('game', $tables, 'Таблица game должна существовать');
        $this->assertContains('user', $tables, 'Таблица user должна существовать');

        // Если игра была отредактирована, проверяем что вредоносные данные экранированы
        if (!$error) {
            $gameRecord = $this->getLastRecord('game');
            if ($gameRecord) {
                $this->assertEquals(htmlspecialchars($maliciousData['name']), $gameRecord['name']);
            }
        }
    }

    /**
     * Тест редактирования нескольких игр подряд
     */
    public function testMultipleGameEdits(): void
    {
        $games = [];
        
        // Создаем несколько игр
        for ($i = 1; $i <= 3; $i++) {
            $game = $this->createTestGame([
                'name' => "Игра {$i}",
                'map_w' => 100,
                'map_h' => 100,
                'turn_type' => 'byturn'
            ]);
            
            $this->createTestUser(['login' => "А{$i}", 'game' => $game['id']]);
            $this->createTestUser(['login' => "Б{$i}", 'game' => $game['id']]);
            
            $games[] = $game;
        }

        // Редактируем каждую игру
        foreach ($games as $index => $game) {
            $editData = [
                'game_id' => $game['id'],
                'name' => "Отредактированная игра " . ($index + 1),
                'map_w' => 150 + ($index * 50),
                'map_h' => 150 + ($index * 50),
                'turn_type' => 'concurrently'
            ];

            $this->simulatePostRequest($editData);

            $vars = mockIncludeFile(__DIR__ . '/../../pages/editgame.php');
            $error = $vars['error'] ?? false;

            $this->assertFalse($error, "Редактирование игры " . ($index + 1) . " должно пройти успешно: " . (is_string($error) ? $error : ''));
        }

        // Проверяем, что все игры были отредактированы корректно
        $allGames = MyDBTestWrapper::query("SELECT * FROM game ORDER BY id");
        $this->assertEquals(3, count($allGames), 'Должно быть 3 игры');

        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals("Отредактированная игра " . ($i + 1), $allGames[$i]['name']);
            $this->assertEquals(150 + ($i * 50), $allGames[$i]['map_w']);
            $this->assertEquals(150 + ($i * 50), $allGames[$i]['map_h']);
            $this->assertEquals('concurrently', $allGames[$i]['turn_type']);
        }

        // Проверяем общее количество игроков
        $this->assertEquals(6, $this->getTableCount('user'), 'Должно быть 6 игроков всего');
    }

    /**
     * Тест редактирования игры с сохранением связанных данных
     */
    public function testEditGameWithRelatedDataPreservation(): void
    {
        $game = $this->createTestGame([
            'name' => 'Игра со связанными данными',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn',
            'turn_num' => 5 // игра уже идет несколько ходов
        ]);

        // Создаем игроков с различными статусами
        $this->createTestUser([
            'login' => 'Активный игрок',
            'game' => $game['id'],
            'turn_status' => 'active',
            'money' => 150,
            'age' => 3,
            'turn_order' => 1
        ]);

        $this->createTestUser([
            'login' => 'Ожидающий игрок',
            'game' => $game['id'],
            'turn_status' => 'wait',
            'money' => 75,
            'age' => 2,
            'turn_order' => 2
        ]);

        // Редактируем только базовые параметры игры
        $editData = [
            'game_id' => $game['id'],
            'name' => 'Переименованная игра',
            'map_w' => 200,
            'map_h' => 200,
            'turn_type' => 'concurrently'
        ];

        $this->simulatePostRequest($editData);
        $vars = mockIncludeFile(__DIR__ . '/../../pages/editgame.php');
        $error = $vars['error'] ?? false;

        $this->assertFalse($error, 'Редактирование должно пройти успешно: ' . (is_string($error) ? $error : ''));

        // Проверяем, что игра обновилась
        $updatedGame = $this->getLastRecord('game');
        $this->assertEquals('Переименованная игра', $updatedGame['name']);
        $this->assertEquals(200, $updatedGame['map_w']);
        $this->assertEquals(200, $updatedGame['map_h']);
        $this->assertEquals('concurrently', $updatedGame['turn_type']);
        $this->assertEquals(5, $updatedGame['turn_num'], 'Номер хода должен сохраниться');

        // Проверяем, что данные игроков не изменились
        $users = MyDBTestWrapper::query("SELECT * FROM user WHERE game = ? ORDER BY turn_order", [$game['id']]);
        
        $this->assertEquals('Активный игрок', $users[0]['login']);
        $this->assertEquals('active', $users[0]['turn_status']);
        $this->assertEquals(150, $users[0]['money']);
        $this->assertEquals(3, $users[0]['age']);

        $this->assertEquals('Ожидающий игрок', $users[1]['login']);
        $this->assertEquals('wait', $users[1]['turn_status']);
        $this->assertEquals(75, $users[1]['money']);
        $this->assertEquals(2, $users[1]['age']);
    }
}
