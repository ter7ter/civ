<?php

/**
 * Отладочный скрипт для проверки сохранения планеты
 */

require_once __DIR__ . "/bootstrap.php";

// Создаем тестовые таблицы
echo "Создание простой тестовой таблицы...\n";
$pdo = MyDB::get();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS simple_test (id INT PRIMARY KEY, name VARCHAR(50))");
    echo "✓ Простая таблица создана\n";
} catch (Exception $e) {
    echo "✗ Ошибка при создании простой таблицы: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS auto_test (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50))");
    echo "✓ Таблица с AUTO_INCREMENT создана\n";
} catch (Exception $e) {
    echo "✗ Ошибка при создании таблицы с AUTO_INCREMENT: " . $e->getMessage() . "\n";
}

echo "Создание полных тестовых таблиц...\n";
DatabaseTestAdapter::createTestTables();
echo "✓ Таблицы созданы\n\n";

echo "Проверка сохранения планеты...\n\n";

try {
    // Создаем тестовую игру
    echo "Создание тестовой игры...\n";
    $gameData = [
        "name" => "Test Game for Planet",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "turn_num" => 1,
    ];

    $gameId = MyDB::insert("game", $gameData);
    echo "✓ Игра создана с ID: $gameId\n";

    // Проверяем, что игра создана - используем простой SELECT без параметров
    $allGames = MyDB::query("SELECT * FROM game", [], 'assoc');
    echo "Все игры в БД: " . json_encode($allGames) . "\n";

    // Проверим через прямой PDO
    $pdo = MyDB::get();
    $stmt = $pdo->query("SELECT id, name FROM game");
    $directResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Прямой PDO запрос: " . json_encode($directResult) . "\n";

    // Проверяем, что игра создана
    $gameCheck = MyDB::query("SELECT * FROM game WHERE id = :id", ['id' => $gameId], 'row');
    echo "✓ Игра найдена в БД: " . json_encode($gameCheck) . "\n";

    // Проверяем существующие таблицы
    $tables = MyDB::query("SHOW TABLES", [], "column");
    echo "Существующие таблицы: " . implode(", ", $tables) . "\n";

    // Создаем планету
    echo "Создание планеты...\n";
    $planetData = [
        'name' => 'Test Planet',
        'game_id' => $gameId
    ];

    $planet = new Planet($planetData);
    echo "✓ Объект Planet создан\n";

    $planet->save();
    echo "✓ Планета сохранена с ID: {$planet->id}\n";

    // Проверяем сохранение в БД
    $savedData = MyDB::query("SELECT * FROM planet WHERE id = :id", ['id' => $planet->id], 'row');
    echo "✓ Планета найдена в БД: " . json_encode($savedData) . "\n";

    // Проверяем Planet::get()
    $loadedPlanet = Planet::get($planet->id);
    echo "✓ Planet::get() работает: " . ($loadedPlanet ? "да" : "нет") . "\n";
    if ($loadedPlanet) {
        echo "  Имя: {$loadedPlanet->name}, game_id: {$loadedPlanet->game_id}\n";
    }

    echo "\nВсе проверки пройдены успешно!\n";

} catch (Exception $e) {
    echo "✗ Ошибка:\n";
    echo "Сообщение: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
    echo "Трейс:\n" . $e->getTraceAsString() . "\n";
}

echo "\nПроверка завершена.\n";
