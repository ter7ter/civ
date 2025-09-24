<?php
/**
 * Скрипт для создания новой игры.
 * Обрабатывает форму создания игры, валидирует данные, создает игру, пользователей и карту.
 */

// Helper functions for testability
if (!function_exists("send_header")) {
    function send_header($location)


    {
        Header($location);
    }
}
if (!function_exists("terminate_script")) {
    function terminate_script()
    {
        exit();
    }
}

// Обработка создания новой игры
if (
    isset($_REQUEST["name"]) &&
    isset($_REQUEST["users"]) &&
    is_array($_REQUEST["users"])
) {
    $name = trim(htmlspecialchars($_REQUEST["name"]));
    $map_w = (int) $_REQUEST["map_w"];
    $map_h = (int) $_REQUEST["map_h"];
    $turn_type = $_REQUEST["turn_type"];

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
    }

    if (count($users) < 2) {
        $errors[] = "Для игры необходимо минимум 2 игрока";
    }

    if (count($users) > 16) {
        $errors[] = "Максимальное количество игроков: 16";
    }

    // Если есть ошибки, сохраняем их для отображения
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
        $data = [
            "name" => $name,
            "map_w" => $map_w,
            "map_h" => $map_h,
            "turn_type" => $turn_type,
            "users" => $user_logins,
        ];
    } else {
        // Создаем игру
        try {
            $game_data = [
                "name" => $name,
                "map_w" => $map_w,
                "map_h" => $map_h,
                "turn_type" => $turn_type,
                "turn_num" => 1,
            ];

            $game = new Game($game_data);
            $game->save();

            // Создаем пользователей
            foreach ($users as $user_data) {
                $user_create_data = [
                    "login" => $user_data["login"],
                    "color" => $user_data["color"],
                    "game" => $game->id,
                    "turn_order" => $user_data["order"],
                    "turn_status" => "wait",
                    "money" => 50, // начальные деньги
                    "age" => 1,
                ];

                $u = new User($user_create_data);
                $u->save();
            }

            // Генерируем карту и начальные условия
            $game->create_new_game();

            // Устанавливаем начальные статусы ходов
            $game->calculate();
            $game->turn_num = 1; // calculate() увеличивает номер хода, а нам нужен 1-й
            $game->save();

            // Перенаправляем на страницу выбора игры
            if (isset($_REQUEST["json"])) {
                $data = ["success" => true, "game_id" => $game->id];
            } else {
                MyDB::end_transaction(); // Commit transaction before redirect
                send_header("Location: index.php?method=selectgame");
                terminate_script();
            }
        } catch (TestExitException $e) {
            // This exception is used by the test framework to stop execution, so re-throw it.
            throw $e;
        } catch (Exception $e) {
            $error = "Ошибка при создании игры: " . $e->getMessage();
        }
    }
} else {
    // Начальные значения для формы
    $data = [
        "name" => "",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "users" => ["", ""],
    ];
}
?>
