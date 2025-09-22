<?php

require_once __DIR__ . '/../TestBase.php';
require_once __DIR__ . '/../DatabaseMocks.php';

/**
 * Интеграционные тесты для полного процесса создания игры
 */
class CreateGameIntegrationTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
    }

    /**
     * Тест полного процесса создания игры с реальной базой данных
     */
    public function testFullGameCreationProcess(): void
    {
        // Подготовка данных
        $gameData = [
            'name' => 'Интеграционный тест игры',
            'map_w' => 150,
            'map_h' => 150,
            'turn_type' => 'byturn',
            'users' => ['Алиса', 'Боб', 'Чарли']
        ];

        $this->simulatePostRequest($gameData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/creategame.php');
        $error = $vars['error'] ?? false;
        $data = $vars['data'] ?? [];

        // Проверяем результат
        $this->assertFalse($error, 'Создание игры должно пройти без ошибок: ' . (is_string($error) ? $error : ''));

        // Проверяем, что игра создана в "базе данных"
        $this->assertTrue($this->recordExists('game', ['name' => 'Интеграционный тест игры']));

        // Проверяем, что созданы все пользователи
        $this->assertTrue($this->recordExists('user', ['login' => 'Алиса']));
        $this->assertTrue($this->recordExists('user', ['login' => 'Боб']));
        $this->assertTrue($this->recordExists('user', ['login' => 'Чарли']));
    }

    /**
     * Тест создания игры и проверки корректности данных в БД
     */
    public function testGameDataPersistence(): void
    {
        $gameData = [
            'name' => 'Тест сохранения данных',
            'map_w' => 200,
            'map_h' => 300,
            'turn_type' => 'concurrently',
            'users' => ['Игрок1', 'Игрок2']
        ];

        $this->simulatePostRequest($gameData);

        mockIncludeFile(__DIR__ . '/../../pages/creategame.php');

        // Проверяем данные игры
        $gameRecord = $this->getLastRecord('game');
        $this->assertNotNull($gameRecord, 'Запись игры должна быть создана');
        $this->assertEquals('Тест сохранения данных', $gameRecord['name']);
        $this->assertEquals(200, $gameRecord['map_w']);
        $this->assertEquals(300, $gameRecord['map_h']);
        $this->assertEquals('concurrently', $gameRecord['turn_type']);

        // Проверяем данные пользователей
        $userCount = $this->getTableCount('user');
        $this->assertEquals(2, $userCount, 'Должно быть создано 2 пользователя');
    }

    /**
     * Тест создания игры с различными типами ходов
     */
    public function testDifferentTurnTypesIntegration(): void
    {
        $turnTypes = [
            'concurrently' => 'Одновременная игра',
            'byturn' => 'По очереди',
            'onewindow' => 'Один компьютер'
        ];

        foreach ($turnTypes as $turnType => $gameName) {
            $this->clearTestData();

            $gameData = [
                'name' => $gameName,
                'map_w' => 100,
                'map_h' => 100,
                'turn_type' => $turnType,
                'users' => ['Игрок1', 'Игрок2']
            ];

            $this->simulatePostRequest($gameData);

            $vars = mockIncludeFile(__DIR__ . '/../../pages/creategame.php');
            $error = $vars['error'] ?? false;

            $this->assertFalse($error, "Создание игры с типом {$turnType} должно пройти успешно: " . (is_string($error) ? $error : ''));

            $gameRecord = $this->getLastRecord('game');
            $this->assertEquals($turnType, $gameRecord['turn_type'], "Тип ходов должен быть {$turnType}");
        }
    }

    /**
     * Тест генерации уникальных цветов для игроков
     */
    public function testUniquePlayerColors(): void
    {
        $players = ['Красный', 'Синий', 'Зеленый', 'Желтый', 'Пурпурный', 'Циан'];

        $gameData = [
            'name' => 'Тест цветов',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn',
            'users' => $players
        ];

        $this->simulatePostRequest($gameData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/creategame.php');
        $error = $vars['error'] ?? false;

        $this->assertFalse($error, 'Создание игры должно пройти успешно: ' . (is_string($error) ? $error : ''));

        // Проверяем, что все цвета уникальны
        $colors = MyDBTestWrapper::query("SELECT color FROM user");
        $colors = array_column($colors, 'color');

        $this->assertEquals(count($players), count($colors), 'Должно быть создано столько цветов, сколько игроков');
        $this->assertEquals(count($colors), count(array_unique($colors)), 'Все цвета должны быть уникальными');

        // Проверяем формат цветов (должны быть hex)
        foreach ($colors as $color) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color, 'Цвет должен быть в формате hex');
        }
    }

    /**
     * Тест порядка ходов игроков
     */
    public function testPlayerTurnOrder(): void
    {
        $players = ['Первый', 'Второй', 'Третий'];

        $gameData = [
            'name' => 'Тест порядка ходов',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn',
            'users' => $players
        ];

        $this->simulatePostRequest($gameData);

        mockIncludeFile(__DIR__ . '/../../pages/creategame.php');

        // Проверяем порядок ходов
        $userOrder = MyDBTestWrapper::query("SELECT login, turn_order FROM user ORDER BY turn_order");

        $this->assertEquals(3, count($userOrder), 'Должно быть 3 игрока');

        for ($i = 0; $i < count($players); $i++) {
            $this->assertEquals($players[$i], $userOrder[$i]['login'], 'Порядок игроков должен соответствовать порядку в массиве');
            $this->assertEquals($i + 1, $userOrder[$i]['turn_order'], 'Порядок ходов должен начинаться с 1');
        }
    }

    /**
     * Тест начальных параметров игроков
     */
    public function testInitialPlayerParameters(): void
    {
        $gameData = [
            'name' => 'Тест начальных параметров',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn',
            'users' => ['Тестер1', 'Тестер2']
        ];

        $this->simulatePostRequest($gameData);

        mockIncludeFile(__DIR__ . '/../../pages/creategame.php');

        // Проверяем начальные параметры всех игроков
        $users = MyDBTestWrapper::query("SELECT * FROM user");

        foreach ($users as $user) {
            $this->assertEquals('wait', $user['turn_status'], 'Начальный статус должен быть wait');
            $this->assertEquals(50, $user['money'], 'Начальные деньги должны быть 50');
            $this->assertEquals(1, $user['age'], 'Начальная эра должна быть 1');
            $this->assertEquals(0, $user['income'], 'Начальный доход должен быть 0');
        }
    }

    /**
     * Тест обработки ошибок валидации в интеграционном режиме
     */
    public function testValidationErrorsIntegration(): void
    {
        $invalidData = [
            'name' => '', // пустое название
            'map_w' => 1000, // слишком большая ширина
            'map_h' => 10, // слишком маленькая высота
            'turn_type' => 'invalid',
            'users' => ['Игрок1'] // недостаточно игроков
        ];

        $this->simulatePostRequest($invalidData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/creategame.php');
        $error = $vars['error'] ?? false;

        // Должно быть несколько ошибок
        $this->assertTrue($error !== false, 'Должны быть ошибки валидации');
        $this->assertStringContainsString('Название игры не может быть пустым', $error);

        $this->assertStringContainsString('должна быть от 50 до 500', $error);
        $this->assertStringContainsString('минимум 2 игрока', $error);

        // Проверяем, что данные не были сохранены в БД
        $this->assertEquals(0, $this->getTableCount('game'), 'Игра не должна быть создана при ошибках валидации');
        $this->assertEquals(0, $this->getTableCount('user'), 'Пользователи не должны быть созданы при ошибках валидации');
    }

    /**
     * Тест производительности создания игры с максимальным количеством игроков
     */
    public function testPerformanceWithMaxPlayers(): void
    {
        $players = [];
        for ($i = 1; $i <= 16; $i++) {
            $players[] = "Игрок{$i}";
        }

        $gameData = [
            'name' => 'Тест производительности',
            'map_w' => 500,
            'map_h' => 500,
            'turn_type' => 'concurrently',
            'users' => $players
        ];

        $startTime = microtime(true);

        $this->simulatePostRequest($gameData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/creategame.php');
        $error = $vars['error'] ?? false;

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertFalse($error, 'Создание игры должно пройти успешно: ' . (is_string($error) ? $error : ''));
        $this->assertLessThan(5.0, $executionTime, 'Создание игры должно занимать менее 5 секунд');

        // Проверяем, что все данные созданы
        $this->assertEquals(1, $this->getTableCount('game'), 'Должна быть создана 1 игра');
        $this->assertEquals(16, $this->getTableCount('user'), 'Должно быть создано 16 игроков');
    }

    /**
     * Тест безопасности: SQL инъекции
     */
    public function testSQLInjectionProtection(): void
    {
        $maliciousData = [
            'name' => "'; DROP TABLE game; --",
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn',
            'users' => ["'; DROP TABLE user; --", 'Игрок2']
        ];

        $this->simulatePostRequest($maliciousData);

        $vars = mockIncludeFile(__DIR__ . '/../../pages/creategame.php');
        $error = $vars['error'] ?? false;

        // Проверяем, что таблицы не были удалены
        $tablesQuery = MyDBTestWrapper::query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = array_column($tablesQuery, 'name');

        $this->assertContains('game', $tables, 'Таблица game должна существовать');
        $this->assertContains('user', $tables, 'Таблица user должна существовать');

        // Если игра была создана, проверяем что вредоносные данные экранированы
        if (!$error) {
            $gameRecord = $this->getLastRecord('game');
            if ($gameRecord) {
                $this->assertEquals(htmlspecialchars($maliciousData['name']), $gameRecord['name']);
            }
        }
    }

    /**
     * Тест создания нескольких игр подряд
     */
    public function testMultipleGameCreation(): void
    {
        $games = [
            ['name' => 'Игра 1', 'users' => ['А1', 'Б1']],
            ['name' => 'Игра 2', 'users' => ['А2', 'Б2', 'В2']],
            ['name' => 'Игра 3', 'users' => ['А3', 'Б3', 'В3', 'Г3']]
        ];

        foreach ($games as $index => $gameInfo) {
            $gameData = [
                'name' => $gameInfo['name'],
                'map_w' => 100,
                'map_h' => 100,
                'turn_type' => 'byturn',
                'users' => $gameInfo['users']
            ];

            $this->simulatePostRequest($gameData);

            $vars = mockIncludeFile(__DIR__ . '/../../pages/creategame.php');
            $error = $vars['error'] ?? false;

            $this->assertFalse($error, "Создание игры " . ($index + 1) . " должно пройти успешно: " . (is_string($error) ? $error : ''));
        }

        // Проверяем общее количество созданных записей
        $this->assertEquals(3, $this->getTableCount('game'), 'Должно быть создано 3 игры');
        $this->assertEquals(9, $this->getTableCount('user'), 'Должно быть создано 9 игроков всего');
    }

    // Моки теперь реализованы в DatabaseMocks.php и подключаются автоматически
}