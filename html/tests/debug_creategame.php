<?php

/**
 * Детальный отладочный скрипт для страницы создания игры
 */

// Устанавливаем временную зону
date_default_timezone_set("Europe/Moscow");

// Включаем bootstrap
require_once __DIR__ . "/bootstrap.php";

echo "🔧 ОТЛАДКА СТРАНИЦЫ СОЗДАНИЯ ИГРЫ\n";
echo str_repeat("=", 50) . "\n";

// Подготавливаем тестовые данные
$testData = [
    "name" => "Тестовая игра XSS",
    "map_w" => 100,
    "map_h" => 100,
    "turn_type" => "byturn",
    "users" => ['<img src="x" onerror="alert(1)">', "Игрок2"]
];

// Имитируем REQUEST данные
$_REQUEST = $testData;
$_POST = $testData;

echo "📝 Тестовые данные:\n";
print_r($testData);
echo "\n";

echo "🚀 Выполняем код создания игры...\n\n";

// Начинаем перехват вывода
ob_start();

try {
    // Здесь будем пошагово выполнять код из creategame.php

    if (
        isset($_REQUEST["name"]) &&
        isset($_REQUEST["users"]) &&
        is_array($_REQUEST["users"])
    ) {
        echo "✅ Данные запроса корректны\n";

        $name = trim(htmlspecialchars($_REQUEST["name"]));
        $map_w = (int) $_REQUEST["map_w"];
        $map_h = (int) $_REQUEST["map_h"];
        $turn_type = $_REQUEST["turn_type"];

        echo "   name: '$name'\n";
        echo "   map_w: $map_w\n";
        echo "   map_h: $map_h\n";
        echo "   turn_type: '$turn_type'\n";

        // Валидация входных данных
        $errors = [];

        if (empty($name)) {
            $errors[] = "Название игры не может быть пустым";
        }

        if ($map_w < 50 || $map_w > 500) {
            $errors[] = "Ширина карты должна быть от 50 до 500";
        }

        if ($map_h < 50 || $map_h > 500) {
            $errors[] = "Высота карты должна быть от 50 до 500";
        }

        if (!in_array($turn_type, ["concurrently", "byturn", "onewindow"])) {
            $turn_type = "byturn"; // значение по умолчанию
        }

        if (!empty($errors)) {
            echo "❌ Ошибки валидации: " . implode(", ", $errors) . "\n";
            exit(1);
        }

        echo "✅ Валидация пройдена\n";

        // Обработка списка пользователей
        $users = [];
        $user_logins = [];
        $num = 0;

        foreach ($_REQUEST["users"] as $user_login) {
            $user_login = trim(htmlspecialchars($user_login));
            if (empty($user_login)) {
                continue; // пропускаем пустые поля
            }

            if (in_array($user_login, $user_logins)) {
                $errors[] = "Игрок '$user_login' указан несколько раз";
                continue;
            }

            $num++;
            $user_logins[] = $user_login;

            // Генерация цвета для игрока
            $color = "#";
            $sym = "ff";
            $color_num = $num;

            if ($num > 8) {
                $sym = "88";
                $color_num = $num - 8;
            }

            if (($color_num & 4) > 0) {
                $color .= $sym;
            } else {
                $color .= "00";
            }
            if (($color_num & 2) > 0) {
                $color .= $sym;
            } else {
                $color .= "00";
            }
            if (($color_num & 1) > 0) {
                $color .= $sym;
            } else {
                $color .= "00";
            }

            $users[] = [
                "login" => $user_login,
                "color" => $color,
                "order" => $num,
            ];

            echo "   Пользователь $num: '$user_login' ($color)\n";
        }

        if (count($users) < 2) {
            $errors[] = "Для игры необходимо минимум 2 игрока";
        }

        if (count($users) > 16) {
            $errors[] = "Максимальное количество игроков: 16";
        }

        if (!empty($errors)) {
            echo "❌ Ошибки пользователей: " . implode(", ", $errors) . "\n";
            exit(1);
        }

        echo "✅ Пользователи обработаны (" . count($users) . " игроков)\n\n";

        // Создаем игру
        echo "🎮 Создание игры...\n";
        try {
            $game_data = [
                "name" => $name,
                "map_w" => $map_w,
                "map_h" => $map_h,
                "turn_type" => $turn_type,
                "turn_num" => 1,
            ];

            echo "   Создаем объект Game с данными:\n";
            print_r($game_data);

            $game = new Game($game_data);

            if ($game) {
                echo "✅ Объект Game создан успешно\n";
                echo "   game->id до save(): " . var_export($game->id, true) . "\n";
            } else {
                echo "❌ Не удалось создать объект Game\n";
                exit(1);
            }

            echo "   Сохраняем игру...\n";
            $game->save();

            echo "   game->id после save(): " . var_export($game->id, true) . "\n";

            if ($game->id === null || $game->id === false) {
                echo "❌ ПРОБЛЕМА: game->id = " . var_export($game->id, true) . "\n";

                // Проверим, что происходит в БД
                $lastId = MyDB::get()->lastInsertId();
                echo "   PDO lastInsertId(): " . var_export($lastId, true) . "\n";

                // Проверим записи в таблице game
                $gameCount = MyDB::query("SELECT COUNT(*) FROM game", [], "el");
                echo "   Записей в таблице game: $gameCount\n";

                $lastGame = MyDB::query("SELECT * FROM game ORDER BY id DESC LIMIT 1", [], "row");
                if ($lastGame) {
                    echo "   Последняя запись в game:\n";
                    print_r($lastGame);
                } else {
                    echo "   Нет записей в таблице game\n";
                }

                exit(1);
            }

            echo "✅ Игра сохранена с ID: {$game->id}\n\n";

            // Создаем пользователей
            echo "👥 Создание пользователей...\n";
            foreach ($users as $user_data) {
                echo "   Создаем пользователя: {$user_data['login']}\n";

                $user_create_data = [
                    "login" => $user_data["login"],
                    "color" => $user_data["color"],
                    "game" => $game->id,
                    "turn_order" => $user_data["order"],
                    "turn_status" => "wait",
                    "money" => 50, // начальные деньги
                    "age" => 1,
                ];

                echo "   Данные пользователя:\n";
                print_r($user_create_data);

                $u = new User($user_create_data);
                echo "   Объект User создан\n";

                $u->save();
                echo "   Пользователь сохранен с ID: " . var_export($u->id, true) . "\n";

                if ($u->id === null || $u->id === false) {
                    echo "❌ ПРОБЛЕМА с созданием пользователя\n";
                    exit(1);
                }
            }

            echo "✅ Все пользователи созданы\n\n";

            // Генерируем карту и начальные условия
            echo "🗺️ Создание карты и начальных условий...\n";
            try {
                $game->create_new_game();
                echo "✅ Карта и начальные условия созданы\n";
            } catch (Exception $e) {
                echo "❌ Ошибка при создании карты: " . $e->getMessage() . "\n";
                echo "   Trace: " . $e->getTraceAsString() . "\n";
                // Не прерываем выполнение, так как это может быть не критично для теста
            }

            // Устанавливаем начальные статусы ходов
            echo "♻️ Расчет начальных статусов...\n";
            try {
                $game->calculate();
                $game->turn_num = 1; // calculate() увеличивает номер хода, а нам нужен 1-й
                $game->save();
                echo "✅ Начальные статусы рассчитаны\n";
            } catch (Exception $e) {
                echo "❌ Ошибка при расчете: " . $e->getMessage() . "\n";
                echo "   Trace: " . $e->getTraceAsString() . "\n";
            }

            echo "🏁 Игра создана успешно! ID: {$game->id}\n";

        } catch (Exception $e) {
            echo "❌ Ошибка при создании игры: " . $e->getMessage() . "\n";
            echo "   Trace: " . $e->getTraceAsString() . "\n";
        }

    } else {
        echo "❌ Нет необходимых данных в запросе\n";
    }

} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
} finally {
    $output = ob_get_clean();
    echo $output;
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Отладка создания игры завершена\n";
