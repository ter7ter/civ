<?php

/**
 * Отладочный скрипт для проверки подключения к MySQL из тестов
 */

echo "Проверка подключения к MySQL...\n\n";

// Параметры подключения как в тестах
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

try {
    // Попытка подключения
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8;port=$port";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Подключение к MySQL успешно!\n\n";

    // Проверка существования базы данных
    $stmt = $pdo->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();
    echo "Текущая база данных: $currentDb\n\n";

    // Проверка существования таблиц
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Существующие таблицы:\n";
    if (empty($tables)) {
        echo "  (таблиц нет)\n";
    } else {
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }
    echo "\n";

    // Попытка создать тестовую таблицу
    echo "Создание тестовой таблицы...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_connection (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_value VARCHAR(50) NOT NULL
    )");

    // Вставка тестовых данных
    $stmt = $pdo->prepare("INSERT INTO test_connection (test_value) VALUES (?)");
    $stmt->execute(["Test value"]);

    // Чтение данных
    $stmt = $pdo->query("SELECT * FROM test_connection");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "✓ Тестовая таблица создана и данные вставлены\n";
    echo "Прочитано записей: " . count($result) . "\n";

    // Очистка
    $pdo->exec("DROP TABLE test_connection");

    echo "✓ Очистка выполнена\n\n";

    echo "Все проверки пройдены успешно!\n";

} catch (PDOException $e) {
    echo "✗ Ошибка подключения к MySQL:\n";
    echo "Код ошибки: " . $e->getCode() . "\n";
    echo "Сообщение: " . $e->getMessage() . "\n\n";

    // Дополнительная диагностика
    echo "Возможные причины:\n";
    echo "1. MySQL сервер не запущен\n";
    echo "2. Неверные параметры подключения\n";
    echo "3. Пользователь не имеет прав доступа\n";
    echo "4. База данных не существует\n";
    echo "5. Firewall блокирует подключение\n";
}

echo "\nПроверка завершена.\n";
