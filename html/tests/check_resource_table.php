<?php

require_once dirname(__DIR__) . "/config.php";
require_once dirname(__DIR__) . "/classes/MyDB.class.php";

MyDB::setDBConfig("localhost", "civ_test", "civ_test", "3306", "civ_for_tests");

try {
    echo "Проверка структуры таблицы resource:\n";
    $result = MyDB::query("DESCRIBE resource");
    foreach ($result as $column) {
        echo "  {$column["Field"]} - {$column["Type"]} - {$column["Null"]} - {$column["Key"]}\n";
    }

    echo "\nПроверяем данные в таблице resource:\n";
    $count = MyDB::query("SELECT COUNT(*) FROM resource", [], "elem");
    echo "  Количество записей: $count\n";

    if ($count > 0) {
        $sample = MyDB::query("SELECT * FROM resource LIMIT 3");
        echo "  Примеры записей:\n";
        foreach ($sample as $row) {
            print_r($row);
        }
    }

    echo "\nПроверка структуры таблицы cell:\n";
    $result = MyDB::query("DESCRIBE cell");
    foreach ($result as $column) {
        echo "  {$column["Field"]} - {$column["Type"]} - {$column["Null"]} - {$column["Key"]}\n";
    }

    echo "\nПроверка структуры таблицы city:\n";
    $result = MyDB::query("DESCRIBE city");
    foreach ($result as $column) {
        echo "  {$column["Field"]} - {$column["Type"]} - {$column["Null"]} - {$column["Key"]}\n";
    }
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
