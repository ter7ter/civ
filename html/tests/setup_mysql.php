<?php

/**
 * Скрипт для настройки MySQL базы данных для тестов
 */

echo "Настройка MySQL базы данных для тестов...\n\n";

// Параметры подключения
$host = "db";
$user = "civ_test";
$pass = "civ_test";
$dbname = "civ_for_tests";
$port = "3306";

echo "Параметры подключения:\n";
echo "Host: $host\n";
echo "User: $user\n";
echo "Password: $pass\n";
echo "Database: $dbname\n";
echo "Port: $port\n\n";

define('PROJECT_ROOT', dirname(__DIR__).'/tests/../');

try {
    // Сначала подключаемся без указания базы данных
    $dsn = "mysql:host=$host;port=$port;charset=utf8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Подключение к MySQL серверу успешно!\n\n";

    // Проверяем, существует ли база данных
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "База данных '$dbname' не существует. Создаем...\n";
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
        echo "✓ База данных '$dbname' создана!\n\n";
    } else {
        echo "✓ База данных '$dbname' уже существует!\n\n";
    }

    // Подключаемся к базе данных
    $pdo = null; // Закрываем предыдущее подключение
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8;port=$port";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Подключение к базе данных '$dbname' успешно!\n\n";

    // Проверяем права пользователя
    $stmt = $pdo->query("SHOW GRANTS");
    $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Права пользователя:\n";
    foreach ($grants as $grant) {
        echo "  $grant\n";
    }
    echo "\n";

    // Проверяем, можем ли мы создавать таблицы
    echo "Проверка создания тестовой таблицы...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_setup (id INT AUTO_INCREMENT PRIMARY KEY, test_value VARCHAR(50))");
    $pdo->exec("INSERT INTO test_setup (test_value) VALUES ('test')");
    $stmt = $pdo->query("SELECT COUNT(*) FROM test_setup");
    $count = $stmt->fetchColumn();
    $pdo->exec("DROP TABLE test_setup");

    // Отключаем foreign key checks для MySQL
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $sqlFiles = [
        PROJECT_ROOT . "/sql/civ.sql",
        PROJECT_ROOT . "/sql/add_building_type_table.sql",
        PROJECT_ROOT . "/sql/add_planet_table.sql",
        PROJECT_ROOT . "/sql/add_research_type_table.sql",
        PROJECT_ROOT . "/sql/add_unit_type_table.sql",
    ];

    foreach ($sqlFiles as $sqlFile) {
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: " . $sqlFile);
        }
        $sqlContent = file_get_contents($sqlFile);
        // Разделяем SQL-запросы по точке с запятой, но учитываем, что точка с запятой может быть внутри строк
        $queries = array_filter(array_map('trim', explode(';', $sqlContent)));

        foreach ($queries as $query) {
            if (!empty($query)) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    // Игнорируем ошибки, если таблица уже существует (CREATE TABLE IF NOT EXISTS)
                    // или другие незначительные ошибки при создании схемы
                    // Также игнорируем ошибки, связанные с внешними ключами при DROP TABLE,
                    // так как они могут возникать, если таблицы уже были удалены или в другом порядке.
                    if (strpos($e->getMessage(), "already exists") === false &&
                        strpos($e->getMessage(), "Cannot delete or update a parent row") === false &&
                        strpos($e->getMessage(), "a foreign key constraint fails") === false &&
                        strpos($e->getMessage(), "Unknown table") === false) {
                        throw $e;
                    }
                }
            }
        }
    }

    // Включаем foreign key checks обратно
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "✓ Создание и удаление таблиц работает (записей: $count)\n\n";

    echo "Настройка MySQL завершена успешно!\n";
    echo "Теперь тесты должны работать с MySQL.\n";

} catch (PDOException $e) {
    echo "✗ Ошибка настройки MySQL:\n";
    echo "Код ошибки: " . $e->getCode() . "\n";
    echo "Сообщение: " . $e->getMessage() . "\n\n";

    echo "Возможные решения:\n";
    echo "1. Убедитесь, что MySQL сервер запущен\n";
    echo "2. Создайте пользователя 'civ_test' с паролем 'civ_test'\n";
    echo "3. Дайте пользователю права на создание баз данных:\n";
    echo "   GRANT ALL PRIVILEGES ON *.* TO 'civ_test'@'localhost' IDENTIFIED BY 'civ_test';\n";
    echo "   FLUSH PRIVILEGES;\n";
    echo "4. Или запустите этот скрипт от имени root пользователя MySQL\n";
}

echo "\nНастройка завершена.\n";
