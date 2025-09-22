<?php

require_once __DIR__ . '/../TestBase.php';

/**
 * Тесты для проверки конфигурации базы данных в тестах
 */
class DatabaseConfigTest extends TestBase
{
    /**
     * Тест проверки тестовых констант БД
     */
    public function testTestDatabaseConstants(): void
    {
        // Проверяем, что тестовые константы определены
        $this->assertTrue(defined('TEST_DB_HOST'), 'TEST_DB_HOST должна быть определена');
        $this->assertTrue(defined('TEST_DB_USER'), 'TEST_DB_USER должна быть определена');
        $this->assertTrue(defined('TEST_DB_PASS'), 'TEST_DB_PASS должна быть определена');
        $this->assertTrue(defined('TEST_DB_NAME'), 'TEST_DB_NAME должна быть определена');
        $this->assertTrue(defined('TEST_DB_PORT'), 'TEST_DB_PORT должна быть определена');

        // Проверяем значения по умолчанию
        $this->assertEquals('localhost', TEST_DB_HOST, 'TEST_DB_HOST должна иметь значение по умолчанию');
        $this->assertEquals('test_user', TEST_DB_USER, 'TEST_DB_USER должна иметь значение по умолчанию');
        $this->assertEquals('test_pass', TEST_DB_PASS, 'TEST_DB_PASS должна иметь значение по умолчанию');
        $this->assertEquals('test_db', TEST_DB_NAME, 'TEST_DB_NAME должна иметь значение по умолчанию');
        $this->assertEquals(3306, TEST_DB_PORT, 'TEST_DB_PORT должна иметь значение по умолчанию');
    }

    /**
     * Тест разделения констант основного проекта и тестов
     */
    public function testDatabaseConstantsSeparation(): void
    {
        // Если основные константы определены, они должны отличаться от тестовых
        if (defined('DB_HOST') && defined('TEST_DB_HOST')) {
            $this->assertNotEquals(DB_HOST, TEST_DB_HOST,
                'Основные и тестовые константы БД должны различаться');
        }

        if (defined('DB_USER') && defined('TEST_DB_USER')) {
            $this->assertNotEquals(DB_USER, TEST_DB_USER,
                'Основные и тестовые пользователи БД должны различаться');
        }

        if (defined('DB_NAME') && defined('TEST_DB_NAME')) {
            $this->assertNotEquals(DB_NAME, TEST_DB_NAME,
                'Основные и тестовые имена БД должны различаться');
        }
    }

    /**
     * Тест инициализации TestMyDB
     */
    public function testTestMyDBInitialization(): void
    {
        // Проверяем, что мок-класс доступен под именем TestMyDB
        $this->assertTrue(class_exists('MyDBTestWrapper'), 'Класс MyDBTestWrapper должен существовать');

        // Проверяем, что TestMyDB (который является псевдонимом для MyDBTestWrapper) использует тестовые константы
        $this->assertEquals(TEST_DB_HOST, MyDBTestWrapper::$dbhost, 'MyDBTestWrapper должен использовать TEST_DB_HOST');
        $this->assertEquals(TEST_DB_USER, MyDBTestWrapper::$dbuser, 'MyDBTestWrapper должен использовать TEST_DB_USER');
        $this->assertEquals(TEST_DB_PASS, MyDBTestWrapper::$dbpass, 'MyDBTestWrapper должен использовать TEST_DB_PASS');
        $this->assertEquals(TEST_DB_NAME, MyDBTestWrapper::$dbname, 'MyDBTestWrapper должен использовать TEST_DB_NAME');
        $this->assertEquals(TEST_DB_PORT, MyDBTestWrapper::$dbport, 'MyDBTestWrapper должен использовать TEST_DB_PORT');
    }

    /**
     * Тест подключения к тестовой БД
     */
    public function testTestDatabaseConnection(): void
    {
        $pdo = MyDBTestWrapper::get();
        $this->assertInstanceOf(PDO::class, $pdo, 'TestMyDB должен возвращать PDO соединение');

        // Проверяем, что это SQLite в памяти
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->assertEquals('sqlite', $driver, 'Тестовая БД должна использовать SQLite');
    }

    /**
     * Тест создания тестовых таблиц
     */
    public function testTestTablesCreation(): void
    {
        $pdo = MyDBTestWrapper::get();

        // Проверяем основные таблицы
        $expectedTables = ['game', 'user', 'cell', 'unit', 'city'];

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $actualTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($expectedTables as $table) {
            $this->assertContains($table, $actualTables, "Таблица {$table} должна существовать");
        }
    }

    /**
     * Тест работы с тестовыми данными
     */
    public function testTestDataOperations(): void
    {
        // Создаем тестовую игру
        $gameData = [
            'name' => 'Test Database Game',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn',
            'turn_num' => 1
        ];

        $gameId = MyDBTestWrapper::insert('game', $gameData);
        $this->assertGreaterThan(0, $gameId, 'ID игры должен быть больше 0');

        // Проверяем, что игра создана
        $game = MyDBTestWrapper::query("SELECT * FROM game WHERE id = :id", ['id' => $gameId], 'row');
        $this->assertNotNull($game, 'Игра должна быть найдена в БД');
        $this->assertEquals('Test Database Game', $game['name'], 'Название игры должно совпадать');

        // Обновляем игру
        $updateData = ['name' => 'Updated Test Game'];
        $result = MyDBTestWrapper::update('game', $updateData, $gameId);
        $this->assertTrue($result, 'Обновление должно быть успешным');

        // Проверяем обновление
        $updatedGame = MyDBTestWrapper::query("SELECT * FROM game WHERE id = :id", ['id' => $gameId], 'row');
        $this->assertEquals('Updated Test Game', $updatedGame['name'], 'Название должно быть обновлено');
    }

    /**
     * Тест очистки тестовых данных
     */
    public function testTestDataCleanup(): void
    {
        // Добавляем тестовые данные
        $gameId = MyDBTestWrapper::insert('game', [
            'name' => 'Cleanup Test Game',
            'map_w' => 50,
            'map_h' => 50,
            'turn_type' => 'byturn'
        ]);

        $userId = MyDBTestWrapper::insert('user', [
            'login' => 'TestUser',
            'color' => '#ff0000',
            'game' => $gameId,
            'money' => 100
        ]);

        // Проверяем, что данные есть
        $gameCount = MyDBTestWrapper::query("SELECT COUNT(*) FROM game", [], 'elem');
        $userCount = MyDBTestWrapper::query("SELECT COUNT(*) FROM user", [], 'elem');

        $this->assertGreaterThan(0, $gameCount, 'Должна быть минимум 1 игра');
        $this->assertGreaterThan(0, $userCount, 'Должен быть минимум 1 пользователь');

        // Очищаем данные
        MyDBTestWrapper::resetTestDatabase();

        // Проверяем, что данные очищены
        $gameCountAfter = MyDBTestWrapper::query("SELECT COUNT(*) FROM game", [], 'elem');
        $userCountAfter = MyDBTestWrapper::query("SELECT COUNT(*) FROM user", [], 'elem');

        $this->assertEquals(0, $gameCountAfter, 'Игры должны быть удалены');
        $this->assertEquals(0, $userCountAfter, 'Пользователи должны быть удалены');
    }

    /**
     * Тест работы транзакций
     */
    public function testTransactionHandling(): void
    {
        // Начинаем транзакцию
        MyDBTestWrapper::start_transaction();

        // Добавляем данные
        $gameId = MyDBTestWrapper::insert('game', [
            'name' => 'Transaction Test',
            'map_w' => 100,
            'map_h' => 100
        ]);

        $this->assertGreaterThan(0, $gameId, 'Игра должна быть создана');

        // Завершаем транзакцию
        MyDBTestWrapper::end_transaction();

        // Проверяем, что данные сохранены
        $game = MyDBTestWrapper::query("SELECT * FROM game WHERE id = :id", ['id' => $gameId], 'row');
        $this->assertNotNull($game, 'Игра должна быть сохранена после коммита');
    }

    /**
     * Тест логирования запросов
     */
    public function testQueryLogging(): void
    {
        // Очищаем лог запросов
        MyDBTestWrapper::clearQueries();

        // Выполняем несколько запросов
        MyDBTestWrapper::insert('game', ['name' => 'Log Test', 'map_w' => 100, 'map_h' => 100]);
        MyDBTestWrapper::query("SELECT * FROM game", [], 'all');

        // Проверяем лог
        $queries = MyDBTestWrapper::getQueries();
        $this->assertGreaterThan(0, count($queries), 'Должны быть залогированы запросы');

        // Проверяем структуру лога
        $firstQuery = $queries[0];
        $this->assertArrayHasKey('sql', $firstQuery, 'Лог должен содержать SQL');
        $this->assertArrayHasKey('params', $firstQuery, 'Лог должен содержать параметры');
        $this->assertArrayHasKey('mode', $firstQuery, 'Лог должен содержать режим');
    }

    /**
     * Тест работы моков классов Game и User
     */
    public function testGameAndUserMocks(): void
    {
        // Проверяем, что моки доступны
        $this->assertTrue(class_exists('Game'), 'Класс Game должен быть доступен');
        $this->assertTrue(class_exists('User'), 'Класс User должен быть доступен');

        // Создаем игру через мок
        $game = new Game([
            'name' => 'Mock Test Game',
            'map_w' => 200,
            'map_h' => 200,
            'turn_type' => 'concurrently'
        ]);

        $game->save();
        $this->assertGreaterThan(0, $game->id, 'ID игры должен быть установлен после сохранения');

        // Создаем пользователя через мок
        $user = new User([
            'login' => 'MockUser',
            'color' => '#00ff00',
            'game' => $game->id,
            'money' => 150
        ]);

        $user->save();
        $this->assertGreaterThan(0, $user->id, 'ID пользователя должен быть установлен после сохранения');

        // Проверяем связь через static методы
        $retrievedGame = Game::get($game->id);
        $this->assertInstanceOf(Game::class, $retrievedGame, 'Game::get должен возвращать объект Game');
        $this->assertEquals($game->name, $retrievedGame->name, 'Данные игры должны совпадать');

        $retrievedUser = User::get($user->id);
        $this->assertInstanceOf(User::class, $retrievedUser, 'User::get должен возвращать объект User');
        $this->assertEquals($user->login, $retrievedUser->login, 'Данные пользователя должны совпадать');
    }
}
