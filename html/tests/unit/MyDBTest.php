<?php

namespace App\Tests;

require_once __DIR__ . "/../bootstrap.php";

use App\MyDB;
use PDO;
use PDOException;

/**
 * Тесты для класса MyDB
 */
class MyDBTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        MyDB::disableLogging();
        MyDB::clearQueryLog();
    }
    /**
     * Тест установки конфигурации БД
     */
    public function testSetDBConfig(): void
    {
        $this->assertEquals('db', MyDB::$dbhost);
        $this->assertEquals('civ_test', MyDB::$dbuser);
        $this->assertEquals('civ_test', MyDB::$dbpass);
        $this->assertEquals('3306', MyDB::$dbport);
    }

    /**
     * Тест получения соединения с БД
     */
    public function testGet(): void
    {
        $pdo = MyDB::get();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame($pdo, MyDB::get()); // Должен возвращать тот же объект
    }

    /**
     * Тест простого SELECT запроса
     */
    public function testQuerySelect(): void
    {
        // Создаем уникальную тестовую таблицу
        $tableName = 'test_table_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY, name VARCHAR(50))");

        // Вставляем тестовые данные
        MyDB::insert($tableName, ['id' => 1, 'name' => 'Test Name']);

        // Выполняем SELECT запрос
        $result = MyDB::query("SELECT * FROM `$tableName` WHERE id = :id", ['id' => 1]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('Test Name', $result[0]['name']);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }

    /**
     * Тест SELECT запроса с различными типами вывода
     */
    public function testQuerySelectOutputTypes(): void
    {
        // Создаем уникальную тестовую таблицу
        $tableName = 'test_output_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY, value INT)");
        MyDB::insert($tableName, ['id' => 1, 'value' => 42]);

        // Тест row
        $row = MyDB::query("SELECT * FROM `$tableName` WHERE id = 1", [], 'row');
        $this->assertIsArray($row);
        $this->assertEquals(42, $row['value']);

        // Тест elem
        $elem = MyDB::query("SELECT value FROM `$tableName` WHERE id = 1", [], 'elem');
        $this->assertEquals(42, $elem);

        // Тест column
        $column = MyDB::query("SELECT value FROM `$tableName`", [], 'column');
        $this->assertIsArray($column);
        $this->assertContains(42, $column);

        // Тест num_rows
        $count = MyDB::query("SELECT * FROM `$tableName`", [], 'num_rows');
        $this->assertGreaterThanOrEqual(1, $count);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }

    /**
     * Тест INSERT запроса
     */
    public function testInsert(): void
    {
        // Создаем тестовую таблицу
        MyDB::query("CREATE TABLE IF NOT EXISTS test_insert (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(100))");

        $data = ['title' => 'Test Title'];
        $id = MyDB::insert('test_insert', $data);

        $this->assertIsString($id);
        $this->assertGreaterThan(0, (int)$id);

        // Проверяем вставку
        $result = MyDB::query("SELECT * FROM test_insert WHERE id = :id", ['id' => $id], 'row');
        $this->assertEquals('Test Title', $result['title']);
    }

    /**
     * Тест множественной вставки
     */
    public function testInsertMultiple(): void
    {
        // Создаем тестовую таблицу
        MyDB::query("CREATE TABLE IF NOT EXISTS test_multi_insert (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50), age INT)");

        $data = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30],
        ];

        $id = MyDB::insert('test_multi_insert', $data);

        $this->assertIsString($id);

        // Проверяем вставку
        $result = MyDB::query("SELECT COUNT(*) FROM test_multi_insert", [], 'elem');
        $this->assertGreaterThanOrEqual(2, $result);
    }

    /**
     * Тест UPDATE запроса
     */
    public function testUpdate(): void
    {
        // Создаем уникальную тестовую таблицу
        $tableName = 'test_update_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY, status VARCHAR(20))");
        MyDB::insert($tableName, ['id' => 1, 'status' => 'active']);

        // Обновляем запись
        $result = MyDB::update($tableName, ['status' => 'inactive'], 1);

        $this->assertGreaterThan(0, $result);

        // Проверяем обновление
        $updated = MyDB::query("SELECT status FROM `$tableName` WHERE id = 1", [], 'elem');
        $this->assertEquals('inactive', $updated);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }

    /**
     * Тест UPDATE с условием WHERE
     */
    public function testUpdateWithWhere(): void
    {
        // Создаем уникальную тестовую таблицу
        $tableName = 'test_where_update_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY, category VARCHAR(20), active TINYINT)");
        MyDB::insert($tableName, ['id' => 1, 'category' => 'A', 'active' => 1]);
        MyDB::insert($tableName, ['id' => 2, 'category' => 'B', 'active' => 1]);

        // Обновляем записи с условием
        $result = MyDB::update($tableName, ['active' => 0], "category = 'A'");

        $this->assertGreaterThan(0, $result);

        // Проверяем обновление
        $updated = MyDB::query("SELECT active FROM `$tableName` WHERE category = 'A'", [], 'elem');
        $this->assertEquals(0, $updated);

        // Проверяем, что другая запись не изменилась
        $notUpdated = MyDB::query("SELECT active FROM `$tableName` WHERE category = 'B'", [], 'elem');
        $this->assertEquals(1, $notUpdated);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }

    /**
     * Тест обработки NULL значений
     */
    public function testNullHandling(): void
    {
        // Создаем уникальную тестовую таблицу
        $tableName = 'test_null_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY, value VARCHAR(50))");

        // Вставляем с NULL
        $id = MyDB::insert($tableName, ['id' => 1, 'value' => 'NULL']);

        // Проверяем, что NULL правильно обработан
        $result = MyDB::query("SELECT value FROM `$tableName` WHERE id = 1", [], 'elem');
        $this->assertNull($result);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }

    /**
     * Тест DDL запросов (CREATE, DROP)
     */
    public function testDDLQueries(): void
    {
        // Создаем уникальную таблицу
        $tableName = 'test_ddl_' . uniqid();

        // Создаем таблицу
        $result = MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY)");
        $this->assertTrue($result);

        // Проверяем, что таблица создана
        $exists = MyDB::query("SHOW TABLES LIKE '$tableName'", [], 'num_rows');
        $this->assertGreaterThan(0, $exists);

        // Удаляем таблицу
        $result = MyDB::query("DROP TABLE `$tableName`");
        $this->assertTrue($result);
    }

    /**
     * Тест обработки ошибок
     */
    public function testErrorHandling(): void
    {
        // Попытка выполнить некорректный запрос
        $this->expectException(PDOException::class);
        MyDB::query("SELECT * FROM nonexistent_table");
    }

    /**
     * Тест транзакций
     */
    public function testTransactions(): void
    {
        // Создаем уникальную тестовую таблицу
        $tableName = 'test_transaction_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY, balance INT)");
        MyDB::insert($tableName, ['id' => 1, 'balance' => 100]);

        // Начинаем транзакцию
        MyDB::start_transaction();

        // Обновляем баланс
        MyDB::update($tableName, ['balance' => 150], 1);

        // Проверяем, что изменения видны в транзакции
        $balance = MyDB::query("SELECT balance FROM `$tableName` WHERE id = 1", [], 'elem');
        $this->assertEquals(150, $balance);

        // Завершаем транзакцию
        MyDB::end_transaction();

        // Проверяем, что изменения сохранены
        $finalBalance = MyDB::query("SELECT balance FROM `$tableName` WHERE id = 1", [], 'elem');
        $this->assertEquals(150, $finalBalance);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }

    /**
     * Тест включения логирования
     */
    public function testEnableLogging(): void
    {
        // По умолчанию логирование отключено (лог пустой)
        $log = MyDB::getQueryLog();
        $this->assertEmpty($log);

        // Выполняем запрос без логирования
        MyDB::query("SELECT 1");

        // Лог все еще пустой
        $log = MyDB::getQueryLog();
        $this->assertEmpty($log);

        // Включаем логирование
        MyDB::enableLogging();

        // Теперь запрос логируется
        MyDB::query("SELECT 1");

        $log = MyDB::getQueryLog();
        $this->assertCount(1, $log);
    }

    /**
     * Тест получения лога запросов
     */
    public function testGetQueryLog(): void
    {
        $log = MyDB::getQueryLog();

        $this->assertIsArray($log);
        $this->assertEmpty($log);
    }

    /**
     * Тест логирования в query
     */
    public function testQueryLogging(): void
    {
        // Включаем логирование
        MyDB::enableLogging();

        // Создаем уникальную тестовую таблицу
        $tableName = 'test_query_log_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY)");

        // Проверяем лог
        $log = MyDB::getQueryLog();
        $this->assertCount(1, $log);
        $this->assertEquals("CREATE TABLE `$tableName` (id INT PRIMARY KEY)", $log[0]['query']);
        $this->assertIsArray($log[0]['params']);
        $this->assertArrayHasKey('timestamp', $log[0]);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }

    /**
     * Тест логирования в insert
     */
    public function testInsertLogging(): void
    {
        // Включаем логирование
        MyDB::enableLogging();

        // Создаем уникальную тестовую таблицу
        $tableName = 'test_insert_log_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50))");

        // Вставляем данные
        MyDB::insert($tableName, ['name' => 'Test']);

        // Проверяем лог (должен быть лог для CREATE и INSERT)
        $log = MyDB::getQueryLog();
        $this->assertGreaterThanOrEqual(2, count($log));

        // Находим лог INSERT
        $insertLog = null;
        foreach ($log as $entry) {
            if (strpos($entry['query'], 'INSERT INTO') === 0) {
                $insertLog = $entry;
                break;
            }
        }

        $this->assertNotNull($insertLog);
        $this->assertStringContainsString('INSERT INTO', $insertLog['query']);
        $this->assertIsArray($insertLog['params']);
        $this->assertArrayHasKey('timestamp', $insertLog);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }

    /**
     * Тест логирования в update
     */
    public function testUpdateLogging(): void
    {
        // Включаем логирование
        MyDB::enableLogging();

        // Создаем уникальную тестовую таблицу
        $tableName = 'test_update_log_' . uniqid();
        MyDB::query("CREATE TABLE `$tableName` (id INT PRIMARY KEY, value INT)");
        MyDB::insert($tableName, ['id' => 1, 'value' => 10]);

        // Обновляем данные
        MyDB::update($tableName, ['value' => 20], 1);

        // Проверяем лог
        $log = MyDB::getQueryLog();
        $this->assertGreaterThanOrEqual(3, count($log)); // CREATE, INSERT, UPDATE

        // Находим лог UPDATE
        $updateLog = null;
        foreach ($log as $entry) {
            if (strpos($entry['query'], 'UPDATE') === 0) {
                $updateLog = $entry;
                break;
            }
        }

        $this->assertNotNull($updateLog);
        $this->assertStringContainsString('UPDATE', $updateLog['query']);
        $this->assertIsArray($updateLog['params']);
        $this->assertArrayHasKey('timestamp', $updateLog);

        // Очищаем таблицу
        MyDB::query("DROP TABLE `$tableName`");
    }
}
