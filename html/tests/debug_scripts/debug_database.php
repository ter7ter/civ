<?php

/**
 * Отладочный скрипт для проверки работы БД в тестах
 */

// Устанавливаем временную зону
date_default_timezone_set("Europe/Moscow");

// Включаем bootstrap
require_once __DIR__ . "/bootstrap.php";

echo "🔧 ОТЛАДКА БАЗЫ ДАННЫХ\n";
echo str_repeat("=", 50) . "\n";

// Проверяем подключение к БД
try {
    $db = MyDB::get();
    echo "✅ Подключение к БД установлено\n";
    echo "   Тип БД: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
} catch (Exception $e) {
    echo "❌ Ошибка подключения к БД: " . $e->getMessage() . "\n";
    exit(1);
}

// Проверяем настройки БД
echo "\n📊 Настройки БД:\n";
echo "   dbhost: " . (MyDB::$dbhost ?? 'не установлено') . "\n";
echo "   dbuser: " . (MyDB::$dbuser ?? 'не установлено') . "\n";
echo "   dbname: " . (MyDB::$dbname ?? 'не установлено') . "\n";

// Проверяем создание таблиц
echo "\n🏗️ Проверка таблиц:\n";
try {
    DatabaseTestAdapter::createTestTables();
    echo "✅ Таблицы созданы успешно\n";
} catch (Exception $e) {
    echo "❌ Ошибка создания таблиц: " . $e->getMessage() . "\n";
    exit(1);
}

// Проверяем список таблиц
try {
    $tables = MyDB::query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    echo "   Найдено таблиц: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "   - " . $table['name'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка получения списка таблиц: " . $e->getMessage() . "\n";
}

// Тестируем вставку данных в таблицу game
echo "\n🧪 Тест вставки данных:\n";
try {
    $gameData = [
        'name' => 'Тестовая игра',
        'map_w' => 100,
        'map_h' => 100,
        'turn_type' => 'byturn',
        'turn_num' => 1
    ];

    echo "   Вставляем игру...\n";
    $gameId = MyDB::insert('game', $gameData);

    if ($gameId) {
        echo "✅ Игра создана с ID: $gameId\n";

        // Проверяем, что данные действительно вставились
        $savedGame = MyDB::query("SELECT * FROM game WHERE id = :id", ['id' => $gameId], 'row');
        if ($savedGame) {
            echo "✅ Игра найдена в БД: " . $savedGame['name'] . "\n";
        } else {
            echo "❌ Игра не найдена в БД\n";
        }
    } else {
        echo "❌ Ошибка: insert() вернул: " . var_export($gameId, true) . "\n";
        echo "   lastInsertId(): " . $db->lastInsertId() . "\n";
    }

} catch (Exception $e) {
    echo "❌ Ошибка при вставке: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

// Тестируем создание через класс Game
echo "\n🏗️ Тест создания через класс Game:\n";
try {
    $game = new Game([
        'name' => 'Тестовая игра 2',
        'map_w' => 150,
        'map_h' => 150,
        'turn_type' => 'byturn',
        'turn_num' => 1
    ]);

    echo "   Сохраняем игру...\n";
    $game->save();

    if ($game->id) {
        echo "✅ Игра создана через класс Game с ID: " . $game->id . "\n";
    } else {
        echo "❌ Ошибка: game->id = " . var_export($game->id, true) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Ошибка при создании через класс Game: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

// Тестируем создание пользователя
echo "\n👤 Тест создания пользователя:\n";
try {
    if (isset($game) && $game->id) {
        $user = new User([
            'login' => 'Тестовый игрок',
            'color' => '#ff0000',
            'game' => $game->id,
            'turn_order' => 1,
            'turn_status' => 'wait',
            'money' => 50,
            'age' => 1
        ]);

        echo "   Сохраняем пользователя...\n";
        $user->save();

        if ($user->id) {
            echo "✅ Пользователь создан с ID: " . $user->id . "\n";
        } else {
            echo "❌ Ошибка: user->id = " . var_export($user->id, true) . "\n";
        }
    } else {
        echo "⚠️ Пропускаем создание пользователя - нет игры\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка при создании пользователя: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Отладка завершена\n";
