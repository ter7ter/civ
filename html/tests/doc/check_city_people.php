<?php

require_once dirname(__DIR__) . "/config.php";
require_once dirname(__DIR__) . "/classes/MyDB.class.php";

try {
    echo "Проверка структуры таблицы city_people:\n";
    $result = MyDB::query("DESCRIBE city_people");
    foreach ($result as $column) {
        echo "  {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }

    echo "\nПроверка данных в таблице city_people:\n";
    $count = MyDB::query("SELECT COUNT(*) FROM city_people", [], "elem");
    echo "  Количество записей: $count\n";

} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
