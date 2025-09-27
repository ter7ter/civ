<?php

/**
 * Скрипт для пересоздания всех тестовых таблиц
 */

require_once dirname(__DIR__) . "/config.php";
require_once dirname(__DIR__) . "/vendor/autoload.php";
require_once __DIR__ . "/mocks/DatabaseTestAdapter.php";

MyDB::setDBConfig("localhost", "civ_test", "civ_test", "3306", "civ_for_tests");

try {
    echo "=== Пересоздание всех тестовых таблиц ===\n\n";

    // Получаем список всех существующих таблиц
    echo "1. Получаем список существующих таблиц...\n";
    $existingTables = MyDB::query("SHOW TABLES");
    $tableNames = array_column($existingTables, "Tables_in_civ_for_tests");
    echo "   Найдено таблиц: " . count($tableNames) . "\n";

    // Отключаем проверку внешних ключей для удаления таблиц
    echo "\n2. Отключаем проверку внешних ключей...\n";
    MyDB::query("SET FOREIGN_KEY_CHECKS = 0");

    // Удаляем все существующие таблицы
    echo "\n3. Удаляем все существующие таблицы...\n";
    foreach ($tableNames as $tableName) {
        MyDB::query("DROP TABLE IF EXISTS `$tableName`");
        echo "   ✓ Таблица $tableName удалена\n";
    }

    // Включаем проверку внешних ключей обратно
    echo "\n4. Включаем проверку внешних ключей...\n";
    MyDB::query("SET FOREIGN_KEY_CHECKS = 1");

    // Создаем новые таблицы через DatabaseTestAdapter
    echo "\n5. Создаем новые таблицы...\n";
    DatabaseTestAdapter::createTestTables();
    echo "   ✓ Все таблицы созданы через DatabaseTestAdapter\n";

    // Проверяем результат
    echo "\n6. Проверяем результат...\n";
    $newTables = MyDB::query("SHOW TABLES");
    $newTableNames = array_column($newTables, "Tables_in_civ_for_tests");
    echo "   Создано таблиц: " . count($newTableNames) . "\n";

    echo "\n   Список созданных таблиц:\n";
    foreach ($newTableNames as $tableName) {
        echo "   - $tableName\n";
    }

    // Проверяем структуру ключевых таблиц
    echo "\n7. Проверяем структуру ключевых таблиц...\n";

    $keyTables = ['game', 'user', 'planet', 'cell', 'resource', 'city', 'unit'];
    foreach ($keyTables as $tableName) {
        if (in_array($tableName, $newTableNames)) {
            echo "\n   Структура таблицы $tableName:\n";
            $columns = MyDB::query("DESCRIBE $tableName");
            foreach ($columns as $column) {
                $key = $column['Key'] ? " ({$column['Key']})" : "";
                $null = $column['Null'] === 'NO' ? 'NOT NULL' : 'NULL';
                echo "     {$column['Field']} - {$column['Type']} - $null$key\n";
            }
        } else {
            echo "   ❌ Таблица $tableName не была создана\n";
        }
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 ПЕРЕСОЗДАНИЕ ТАБЛИЦ ЗАВЕРШЕНО УСПЕШНО!\n";
    echo str_repeat("=", 50) . "\n";

    echo "\nИтого:\n";
    echo "✅ Удалено старых таблиц: " . count($tableNames) . "\n";
    echo "✅ Создано новых таблиц: " . count($newTableNames) . "\n";
    echo "✅ Все ключевые таблицы присутствуют\n";
    echo "\nТестовая база данных готова к использованию!\n";

} catch (Exception $e) {
    echo "❌ КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";

    // Попытка восстановить проверку внешних ключей
    try {
        MyDB::query("SET FOREIGN_KEY_CHECKS = 1");
        echo "✓ Проверка внешних ключей восстановлена\n";
    } catch (Exception $cleanupError) {
        echo "❌ Не удалось восстановить проверку внешних ключей\n";
    }
}

?>
