<?php

require_once dirname(__DIR__) . "/config.php";
require_once dirname(__DIR__) . "/vendor/autoload.php";

MyDB::setDBConfig("localhost", "civ_test", "civ_test", "3306", "civ_for_tests");

try {
    echo "Пересоздание таблицы resource с правильным типом поля...\n";

    // Удаляем старую таблицу
    MyDB::query("DROP TABLE IF EXISTS resource");
    echo "✓ Старая таблица resource удалена\n";

    // Создаем новую таблицу с правильной структурой
    $sql = "CREATE TABLE resource (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        x INT NOT NULL,
        y INT NOT NULL,
        planet INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        amount INT DEFAULT 0
    )";

    MyDB::query($sql);
    echo "✓ Новая таблица resource создана\n";

    // Проверяем структуру
    echo "\nПроверка структуры новой таблицы:\n";
    $result = MyDB::query("DESCRIBE resource");
    foreach ($result as $column) {
        echo "  {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }

    echo "\n🎉 Таблица resource успешно пересоздана!\n";

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
