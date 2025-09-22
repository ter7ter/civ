<?php
/** @var User $user */
if ($user->turn_status == 'play') {
    $user->calculate_units(); //Конец хода
    $game = Game::get($user->game);
    $user->turn_status = 'end';
    $user->save();
    $game->all_system_message('Игрок '.$user->login. ' закончил свой ход');
    if ($game->turn_type == 'byturn' || $game->turn_type == 'onewindow') {
        // Ищем следующего игрока, который еще не ходил (статус 'wait')
        $next_user = MyDB::query("SELECT id FROM user WHERE game = ?gid AND turn_status = 'wait' ORDER BY turn_order LIMIT 1",
            ['gid' => $game->id], 'elem');

        //Если следующий игрок, который должен ходить, найден
        if ($next_user) {
            $next_user = User::get($next_user);
            $next_user->turn_status = 'play';
            $next_user->new_system_message('Вы начинаете свой ход');
            $next_user->save();
        }
    }
    if (MyDB::query("SELECT count(id) FROM user WHERE game = ?gid AND turn_status != 'end'",
        ['gid' => $game->id], 'elem') == 0) {
        //Все сходили
        $game->calculate();
        $game->all_system_message('Начало нового хода');
    }
    if ($game->turn_type == 'onewindow') {
        $_SESSION['user_id'] = MyDB::query("SELECT id FROM user WHERE game = ?gid AND turn_status = 'play' LIMIT 1",
            ['gid' => $game->id], 'elem');
        $data['reload'] = '1';
    } else {
        $data['reload'] = '0';
    }
}