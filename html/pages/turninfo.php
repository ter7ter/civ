<?php
try {
/** @var User $user */
/** @var Game $game */

// Data for the player turn info block
$data['user_login'] = $user->login;
$data['user_money'] = $user->money;
$data['user_age'] = $user->age;
$data['user_income'] = ($user->income < 0) ? $user->income : '+'.$user->income;
$data['turn_status'] = $user->turn_status;
$game = Game::get($user->game);
$data['players'] = [];
foreach ($game->users as $player) {
    $data['players'][] = [
        'id' => $player->id,
        'login' => $player->login,
        'turn_order' => $player->turn_order,
        'color' => $player->color,
        'turn_status' => $player->turn_status
    ];
}
$data['turn_num'] = $game->turn_num;

} catch (Throwable $e) {
    error_log("Error in pages/turninfo.php: " . $e->getMessage() . " on line " . $e->getLine());
    // Optionally, return an error message to the client
    // http_response_code(500);
    // echo json_encode(['error' => 'Server error']);
}
?>