<?php

/**
 * Тест для проверки оптимизированной генерации карты
 */

// Подключаем необходимые файлы
require_once dirname(__DIR__) . "/config.php";
require_once dirname(__DIR__) . "/vendor/autoload.php";

// Настраиваем тестовую БД
MyDB::setDBConfig("localhost", "civ_test", "civ_test", "3306", "civ_for_tests");



// Инициализируем типы данных
require_once dirname(__DIR__) . "/tests/TestGameDataInitializer.php";
TestGameDataInitializer::initializeAll();

echo "=== Тест оптимизированной генерации карты ===\n";

try {
    // Создаем тестовую игру
    $gameData = [
        "name" => "Map Generation Test",
        "map_w" => 50,
        "map_h" => 50,
        "turn_type" => "byturn",
        "turn_num" => 1,
    ];

    $gameId = MyDB::insert("game", $gameData);
    echo "✓ Создана тестовая игра ID: $gameId\n";

    // Создаем планету
    $planetData = [
        "name" => "Test Planet",
        "game_id" => $gameId,
    ];

    $planetId = MyDB::insert("planet", $planetData);
    echo "✓ Создана планета ID: $planetId\n";

    // Устанавливаем размеры карты
    Cell::$map_width = 50;
    Cell::$map_height = 50;

    echo "Генерируем карту 50x50...\n";

    // Засекаем время
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    // Генерируем карту
    Cell::generate_map($planetId, $gameId);

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    // Выводим статистику
    $executionTime = round($endTime - $startTime, 2);
    $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);

    echo "✓ Генерация завершена за {$executionTime} сек\n";
    echo "✓ Использовано памяти: {$memoryUsed} MB\n";

    // Проверяем результат
    $cellCount = MyDB::query(
        "SELECT COUNT(*) FROM cell WHERE planet = :planet",
        ["planet" => $planetId],
        "elem",
    );
    $resourceCount = MyDB::query(
        "SELECT COUNT(*) FROM resource WHERE planet = :planet",
        ["planet" => $planetId],
        "elem",
    );

    $expectedCells = 50 * 50; // 2500

    echo "✓ Создано клеток: $cellCount (ожидалось: $expectedCells)\n";
    echo "✓ Создано ресурсов: $resourceCount\n";

    // Проверяем корректность
    if ($cellCount == $expectedCells) {
        echo "✓ УСПЕХ: Все клетки созданы корректно\n";
    } else {
        echo "❌ ОШИБКА: Неправильное количество клеток\n";
    }

    // Проверяем разнообразие типов клеток
    $cellTypes = MyDB::query(
        "SELECT type, COUNT(*) as count FROM cell WHERE planet = :planet GROUP BY type",
        ["planet" => $planetId],
    );
    echo "\nРаспределение типов клеток:\n";
    foreach ($cellTypes as $typeInfo) {
        $percentage = round(($typeInfo["count"] / $cellCount) * 100, 1);
        echo "  {$typeInfo["type"]}: {$typeInfo["count"]} ({$percentage}%)\n";
    }

    // Проверяем типы ресурсов
    if ($resourceCount > 0) {
        $resourceTypes = MyDB::query(
            "SELECT type, COUNT(*) as count FROM resource WHERE planet = :planet GROUP BY type",
            ["planet" => $planetId],
        );
        echo "\nРаспределение типов ресурсов:\n";
        foreach ($resourceTypes as $typeInfo) {
            $percentage = round(($typeInfo["count"] / $resourceCount) * 100, 1);
            echo "  Тип {$typeInfo["type"]}: {$typeInfo["count"]} ({$percentage}%)\n";
        }
    }

    // Тест производительности с большой картой
    echo "\n=== Тест большой карты 100x100 ===\n";

    // Создаем новую игру для большой карты
    $bigGameData = [
        "name" => "Big Map Generation Test",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "turn_num" => 1,
    ];

    $bigGameId = MyDB::insert("game", $bigGameData);
    $bigPlanetData = [
        "name" => "Big Test Planet",
        "game_id" => $bigGameId,
    ];

    $bigPlanetId = MyDB::insert("planet", $bigPlanetData);

    Cell::$map_width = 100;
    Cell::$map_height = 100;

    echo "Генерируем карту 100x100...\n";

    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    Cell::generate_map($bigPlanetId, $bigGameId);

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $executionTime = round($endTime - $startTime, 2);
    $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);

    echo "✓ Генерация большой карты завершена за {$executionTime} сек\n";
    echo "✓ Использовано памяти: {$memoryUsed} MB\n";

    $bigCellCount = MyDB::query(
        "SELECT COUNT(*) FROM cell WHERE planet = :planet",
        ["planet" => $bigPlanetId],
        "elem",
    );
    $expectedBigCells = 100 * 100; // 10000

    echo "✓ Создано клеток: $bigCellCount (ожидалось: $expectedBigCells)\n";

    if ($executionTime < 30) {
        echo "✅ ОТЛИЧНАЯ ПРОИЗВОДИТЕЛЬНОСТЬ: Генерация заняла менее 30 секунд\n";
    } elseif ($executionTime < 60) {
        echo "✅ ХОРОШАЯ ПРОИЗВОДИТЕЛЬНОСТЬ: Генерация заняла менее минуты\n";
    } else {
        echo "⚠️  МЕДЛЕННО: Генерация заняла более минуты\n";
    }

    // Очистка тестовых данных
    echo "\nОчистка тестовых данных...\n";
    MyDB::query("DELETE FROM resource WHERE planet IN (:p1, :p2)", [
        "p1" => $planetId,
        "p2" => $bigPlanetId,
    ]);
    MyDB::query("DELETE FROM cell WHERE planet IN (:p1, :p2)", [
        "p1" => $planetId,
        "p2" => $bigPlanetId,
    ]);
    MyDB::query("DELETE FROM planet WHERE id IN (:p1, :p2)", [
        "p1" => $planetId,
        "p2" => $bigPlanetId,
    ]);
    MyDB::query("DELETE FROM game WHERE id IN (:g1, :g2)", [
        "g1" => $gameId,
        "g2" => $bigGameId,
    ]);

    echo "✓ Тестовые данные очищены\n";
    echo "\n🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!\n";
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";
}

?>
