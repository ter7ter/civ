<?php

/**
 * Отладочный скрипт для проверки работы SQLite с auto increment
 */

// Устанавливаем временную зону
date_default_timezone_set("Europe/Moscow");

// Включаем bootstrap
require_once __DIR__ . "/bootstrap.php";

echo "🔧 ОТЛАДКА SQLITE AUTO INCREMENT\n";
echo str_repeat("=", 50) . "\n";

// Проверяем подключение к БД
try {
    $db = MyDB::get();
    echo "✅ Подключение к БД установлено\n";
    echo "   Тип БД: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "   Версия SQLite: " . $db->query('SELECT sqlite_version()')->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "❌ Ошибка подключения к БД: " . $e->getMessage() . "\n";
    exit(1);
}

// Очищаем таблицы для чистого теста
echo "\n🧹 Очистка тестовых таблиц...\n";
try {
    MyDB::query("DELETE FROM game");
    MyDB::query("DELETE FROM user");
    MyDB::query("DELETE FROM sqlite_sequence WHERE name IN ('game', 'user')");
    echo "✅ Таблицы очищены\n";
} catch (Exception $e) {
    echo "⚠️ Ошибка очистки: " . $e->getMessage() . "\n";
}

// Тестируем последовательную вставку записей
echo "\n🧪 Тест последовательной вставки в game:\n";

for ($i = 1; $i <= 3; $i++) {
    try {
        $gameData = [
            'name' => "Игра $i",
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn',
            'turn_num' => 1
        ];

        echo "   Вставляем игру $i...\n";
        $insertedId = MyDB::insert('game', $gameData);

        if ($insertedId) {
            echo "   ✅ Игра $i создана с ID: $insertedId\n";

            // Проверяем PDO lastInsertId
            $pdoLastId = $db->lastInsertId();
            echo "   📊 PDO lastInsertId(): $pdoLastId\n";

            // Проверяем запись в БД
            $saved = MyDB::query("SELECT * FROM game WHERE id = :id", ['id' => $insertedId], 'row');
            if ($saved) {
                echo "   📄 Сохранено: ID={$saved['id']}, Name='{$saved['name']}'\n";
            } else {
                echo "   ❌ Запись не найдена в БД!\n";
            }
        } else {
            echo "   ❌ insert() вернул: " . var_export($insertedId, true) . "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Проверяем текущий счетчик auto increment
echo "🔢 Проверка счетчика auto increment:\n";
try {
    $sequence = MyDB::query("SELECT seq FROM sqlite_sequence WHERE name = 'game'", [], 'row');
    if ($sequence) {
        echo "   Текущий seq для game: {$sequence['seq']}\n";
    } else {
        echo "   Нет записи в sqlite_sequence для game\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка получения sequence: " . $e->getMessage() . "\n";
}

// Тест создания игры через класс Game
echo "\n🎮 Тест создания через класс Game:\n";
try {
    $gameObj = new Game([
        'name' => 'Тестовая игра через класс',
        'map_w' => 150,
        'map_h' => 150,
        'turn_type' => 'byturn',
        'turn_num' => 1
    ]);

    echo "   Объект создан, id до save(): " . var_export($gameObj->id, true) . "\n";

    $gameObj->save();

    echo "   После save() id = " . var_export($gameObj->id, true) . "\n";

    if ($gameObj->id) {
        echo "   ✅ Успешно сохранено с ID: {$gameObj->id}\n";
    } else {
        echo "   ❌ Ошибка: id не установлен\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка при создании через класс: " . $e->getMessage() . "\n";
}

// Тест создания пользователей
echo "\n👤 Тест создания пользователей:\n";
if (isset($gameObj) && $gameObj->id) {
    for ($i = 1; $i <= 2; $i++) {
        try {
            $userData = [
                'login' => "Игрок$i",
                'color' => $i == 1 ? '#ff0000' : '#00ff00',
                'game' => $gameObj->id,
                'turn_order' => $i,
                'turn_status' => 'wait',
                'money' => 50,
                'age' => 1
            ];

            echo "   Создаем пользователя $i...\n";
            $user = new User($userData);
            echo "   Объект User создан, id до save(): " . var_export($user->id, true) . "\n";

            $user->save();
            echo "   После save() id = " . var_export($user->id, true) . "\n";

            if ($user->id) {
                echo "   ✅ Пользователь $i создан с ID: {$user->id}\n";
            } else {
                echo "   ❌ Ошибка: id пользователя не установлен\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Ошибка создания пользователя $i: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "   ⚠️ Пропускаем создание пользователей - нет игры\n";
}

// Финальная проверка всех записей
echo "\n📊 Финальная проверка:\n";
try {
    $games = MyDB::query("SELECT * FROM game ORDER BY id");
    echo "   Игр в БД: " . count($games) . "\n";
    foreach ($games as $game) {
        echo "     - ID: {$game['id']}, Name: '{$game['name']}'\n";
    }

    $users = MyDB::query("SELECT * FROM user ORDER BY id");
    echo "   Пользователей в БД: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "     - ID: {$user['id']}, Login: '{$user['login']}', Game: {$user['game']}\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка финальной проверки: " . $e->getMessage() . "\n";
}

// Тест на edge cases
echo "\n⚠️ Тест граничных случаев:\n";

// Пустые данные
try {
    echo "   Тест вставки пустых данных...\n";
    $emptyId = MyDB::insert('game', []);
    echo "   Результат: " . var_export($emptyId, true) . "\n";
} catch (Exception $e) {
    echo "   ❌ Ожидаемая ошибка: " . $e->getMessage() . "\n";
}

// NULL значения
try {
    echo "   Тест вставки NULL значений...\n";
    $nullId = MyDB::insert('game', [
        'name' => 'NULL Test',
        'map_w' => null,
        'map_h' => 100,
        'turn_type' => 'byturn',
        'turn_num' => 1
    ]);
    echo "   Результат: " . var_export($nullId, true) . "\n";
} catch (Exception $e) {
    echo "   ❌ Ошибка с NULL: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Отладка SQLite завершена\n";
