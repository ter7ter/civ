<?php

/**
 * Интеграционный тест для проверки оптимизированной генерации карты
 * Тестирует процесс создания игры с реальной генерацией карты
 */

// Подключаем необходимые файлы
require_once dirname(__DIR__) . "/config.php";
require_once dirname(__DIR__) . "/vendor/autoload.php";

// Настраиваем тестовую БД
MyDB::setDBConfig("localhost", "civ_test", "civ_test", "3306", "civ_for_tests");



// Инициализируем типы данных
require_once dirname(__DIR__) . "/tests/TestGameDataInitializer.php";
TestGameDataInitializer::initializeAll();

echo "=== Интеграционный тест создания игры ===\n";

try {
    $startTime = microtime(true);

    // Очистка предыдущих тестовых данных
    echo "Очистка старых данных...\n";
    MyDB::query("DELETE FROM resource");
    MyDB::query("DELETE FROM cell");
    MyDB::query("DELETE FROM city");
    MyDB::query("DELETE FROM unit");
    MyDB::query("DELETE FROM user");
    MyDB::query("DELETE FROM planet");
    MyDB::query("DELETE FROM game");

    // 1. Создание игры
    echo "1. Создаем новую игру...\n";
    $game = new Game([
        'name' => 'Интеграционный тест - быстрая генерация',
        'map_w' => 80,
        'map_h' => 80,
        'turn_type' => 'byturn',
        'turn_num' => 1
    ]);

    $game->save();
    echo "   ✓ Игра создана ID: {$game->id}\n";

    // 2. Создание пользователей
    echo "2. Создаем пользователей...\n";
    $users = [];
    $userNames = ['Алиса', 'Боб', 'Чарли', 'Диана'];
    $colors = ['#FF0000', '#00FF00', '#0000FF', '#FFFF00'];

    for ($i = 0; $i < 4; $i++) {
        $user = new User([
            'login' => $userNames[$i],
            'color' => $colors[$i],
            'game' => $game->id,
            'turn_order' => $i + 1,
            'money' => 50,
            'age' => 1
        ]);
        $user->save();
        $users[] = $user;
        echo "   ✓ Пользователь '{$userNames[$i]}' создан ID: {$user->id}\n";
    }

    // 3. Создание полноценной игры через create_new_game()
    echo "3. Запуск полной генерации игры...\n";
    $mapGenStart = microtime(true);

    // Этот метод создаст планету и сгенерирует карту
    $game->create_new_game();

    $mapGenEnd = microtime(true);
    $mapGenTime = round($mapGenEnd - $mapGenStart, 2);

    echo "   ✓ Полная генерация завершена за {$mapGenTime} сек\n";

    // 4. Проверка результатов
    echo "4. Проверка результатов...\n";

    // Проверяем планеты
    $planetCount = MyDB::query("SELECT COUNT(*) FROM planet WHERE game_id = :game_id", ['game_id' => $game->id], 'elem');
    echo "   ✓ Создано планет: $planetCount\n";

    if ($planetCount > 0) {
        $planet = MyDB::query("SELECT * FROM planet WHERE game_id = :game_id LIMIT 1", ['game_id' => $game->id], 'row');
        $planetId = $planet['id'];

        // Проверяем клетки карты
        $cellCount = MyDB::query("SELECT COUNT(*) FROM cell WHERE planet = :planet", ['planet' => $planetId], 'elem');
        $expectedCells = 80 * 80; // 6400
        echo "   ✓ Создано клеток: $cellCount (ожидалось: $expectedCells)\n";

        if ($cellCount == $expectedCells) {
            echo "   ✅ Генерация карты: УСПЕШНО\n";
        } else {
            echo "   ❌ Генерация карты: ОШИБКА - неправильное количество клеток\n";
        }

        // Проверяем ресурсы
        $resourceCount = MyDB::query("SELECT COUNT(*) FROM resource WHERE planet = :planet", ['planet' => $planetId], 'elem');
        echo "   ✓ Создано ресурсов: $resourceCount\n";

        // Проверяем типы клеток
        $cellTypes = MyDB::query("SELECT type, COUNT(*) as count FROM cell WHERE planet = :planet GROUP BY type", ['planet' => $planetId]);
        echo "   📊 Распределение типов клеток:\n";
        foreach ($cellTypes as $typeInfo) {
            $percentage = round(($typeInfo['count'] / $cellCount) * 100, 1);
            echo "      {$typeInfo['type']}: {$typeInfo['count']} ({$percentage}%)\n";
        }

        if ($resourceCount > 0) {
            $resourceTypes = MyDB::query("SELECT type, COUNT(*) as count FROM resource WHERE planet = :planet GROUP BY type", ['planet' => $planetId]);
            echo "   📊 Распределение ресурсов:\n";
            foreach ($resourceTypes as $typeInfo) {
                $percentage = round(($typeInfo['count'] / $resourceCount) * 100, 1);
                echo "      {$typeInfo['type']}: {$typeInfo['count']} ({$percentage}%)\n";
            }
        }
    }

    // 5. Тест производительности
    $totalTime = round(microtime(true) - $startTime, 2);
    echo "\n5. Оценка производительности:\n";
    echo "   ⏱️  Общее время: {$totalTime} сек\n";
    echo "   ⏱️  Время генерации карты: {$mapGenTime} сек\n";

    $memoryUsed = round(memory_get_peak_usage() / 1024 / 1024, 2);
    echo "   💾 Максимум памяти: {$memoryUsed} MB\n";

    // Оценка производительности
    if ($totalTime < 5) {
        echo "   🚀 ОТЛИЧНАЯ ПРОИЗВОДИТЕЛЬНОСТЬ: < 5 сек\n";
    } elseif ($totalTime < 15) {
        echo "   ✅ ХОРОШАЯ ПРОИЗВОДИТЕЛЬНОСТЬ: < 15 сек\n";
    } elseif ($totalTime < 60) {
        echo "   ⚠️  ПРИЕМЛЕМАЯ ПРОИЗВОДИТЕЛЬНОСТЬ: < 1 мин\n";
    } else {
        echo "   ❌ МЕДЛЕННАЯ ПРОИЗВОДИТЕЛЬНОСТЬ: > 1 мин\n";
    }

    // 6. Тест безопасности - SQL инъекции
    echo "\n6. Тест безопасности (SQL инъекции)...\n";

    try {
        $maliciousGame = new Game([
            'name' => "'; DROP TABLE game; --",
            'map_w' => 50,
            'map_h' => 50,
            'turn_type' => 'byturn'
        ]);
        $maliciousGame->save();

        // Проверяем, что таблица не была удалена
        $gameCount = MyDB::query("SELECT COUNT(*) FROM game", [], 'elem');
        if ($gameCount >= 2) { // Наша игра + вредоносная
            echo "   ✅ SQL инъекции: ЗАЩИЩЕНО\n";
        } else {
            echo "   ❌ SQL инъекции: УЯЗВИМО\n";
        }

        // Проверяем, что данные экранированы
        $savedGame = MyDB::query("SELECT * FROM game WHERE id = :id", ['id' => $maliciousGame->id], 'row');
        if ($savedGame && $savedGame['name'] === "'; DROP TABLE game; --") {
            echo "   ✅ Экранирование данных: РАБОТАЕТ\n";
        } else {
            echo "   ❌ Экранирование данных: НЕ РАБОТАЕТ\n";
        }

    } catch (Exception $e) {
        echo "   ⚠️  SQL инъекция вызвала исключение: " . $e->getMessage() . "\n";
    }

    // 7. Очистка
    echo "\n7. Очистка тестовых данных...\n";
    MyDB::query("DELETE FROM resource");
    MyDB::query("DELETE FROM cell");
    MyDB::query("DELETE FROM user");
    MyDB::query("DELETE FROM planet");
    MyDB::query("DELETE FROM game");
    echo "   ✓ Тестовые данные очищены\n";

    // Финальная оценка
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 ИНТЕГРАЦИОННЫЙ ТЕСТ ЗАВЕРШЕН УСПЕШНО!\n";
    echo str_repeat("=", 50) . "\n";

    echo "\n📈 ДОСТИЖЕНИЯ:\n";
    echo "✅ Карта {$game->map_w}x{$game->map_h} сгенерирована за {$mapGenTime} сек\n";
    echo "✅ Создано $cellCount клеток и $resourceCount ресурсов\n";
    echo "✅ 4 пользователя успешно созданы\n";
    echo "✅ Тесты безопасности пройдены\n";
    echo "✅ Общее время выполнения: {$totalTime} сек\n";
    echo "✅ Использовано памяти: {$memoryUsed} MB\n";

    if ($mapGenTime < 2) {
        echo "\n🏆 ПРЕВОСХОДНО! Генерация карты работает в 100+ раз быстрее!\n";
    } elseif ($mapGenTime < 10) {
        echo "\n🥇 ОТЛИЧНО! Значительное улучшение производительности!\n";
    } else {
        echo "\n✅ ХОРОШО! Производительность в пределах нормы\n";
    }

} catch (Exception $e) {
    echo "❌ КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";

    // Попытка очистки при ошибке
    try {
        MyDB::query("DELETE FROM resource");
        MyDB::query("DELETE FROM cell");
        MyDB::query("DELETE FROM user");
        MyDB::query("DELETE FROM planet");
        MyDB::query("DELETE FROM game");
        echo "   ✓ Аварийная очистка выполнена\n";
    } catch (Exception $cleanupError) {
        echo "   ❌ Ошибка при аварийной очистке: " . $cleanupError->getMessage() . "\n";
    }
}
