<?php
// Логика страницы редактирования игры

// Если это POST-запрос, сохраняем изменения
if (isset($_REQUEST["name"])) {
    $game_id = (int)$_REQUEST['game_id'];
    if (!$game_id) {
        throw new Exception('Ошибка: ID игры не указан.');
    }
    $game = Game::get($game_id);
    if (!$game) {
        throw new Exception('Ошибка: Игра не найдена.');
    }
    $name = trim(htmlspecialchars($_REQUEST["name"]));
    $map_w = (int) $_REQUEST["map_w"];
    $map_h = (int) $_REQUEST["map_h"];
    $turn_type = $_REQUEST["turn_type"];
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
        $turn_type = "byturn";
    }

    // TODO: Добавить логику обновления игроков
    // Пока что обновляем только саму игру

    if (!empty($errors)) {
        $error = implode("<br>", $errors);
        // Загружаем данные обратно в форму в случае ошибки
        $data = [
            "name" => $name,
            "map_w" => $map_w,
            "map_h" => $map_h,
            "turn_type" => $turn_type,
            "users" => [], // Нужно будет загрузить заново
        ];
        $users_data = MyDB::query("SELECT * FROM user WHERE game = ?id ORDER BY turn_order", ['id' => $game_id]);
        foreach ($users_data as $user) {
            $data['users'][] = $user['login'];
        }

    } else {
        // Обновляем данные игры
        $game->name = $name;
        $game->map_w = $map_w;
        $game->map_h = $map_h;
        $game->turn_type = $turn_type;
        $game->save();

        // Завершаем транзакцию и перенаправляем
        MyDB::end_transaction();
        header("Location: index.php?method=editgame&game_id=" . $game->id . "&saved=1");
        exit();
    }

} else { // Если это GET-запрос, загружаем данные для формы
    $game_id = (int)$_REQUEST['game_id'];
    if (!$game_id) {
        throw new Exception('Ошибка: ID игры не указан.');
    }
    $game = Game::get($game_id);
    if (!$game) {
        throw new Exception('Ошибка: Игра не найдена.');
    }

    // Загружаем пользователей
    $users_data = MyDB::query("SELECT * FROM user WHERE game = ?id ORDER BY turn_order", ['id' => $game->id]);
    $user_logins = [];
    foreach ($users_data as $user) {
        $user_logins[] = $user['login'];
    }

    // Заполняем массив $data для шаблона
    $data = [
        "game_id" => $game->id, 
        "name" => $game->name,
        "map_w" => $game->map_w,
        "map_h" => $game->map_h,
        "turn_type" => $game->turn_type,
        "users" => $user_logins,
    ];
}
?>