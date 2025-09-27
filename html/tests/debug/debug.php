<?php
require_once "mocks/MockLoader.php";

initializeTestEnvironment();

$game1 = ["id" => 1, "name" => "Игра 1"];
$game2 = ["id" => 2, "name" => "Игра 2"];

DatabaseTestAdapter::insert("game", $game1);
DatabaseTestAdapter::insert("game", $game2);

$userFromGame2 = [
    "id" => 1,
    "login" => "Игрок из игры 2",
    "color" => "#ff0000",
    "game" => 2,
    "turn_order" => 1,
    "turn_status" => "wait",
    "money" => 50,
    "age" => 1,
];
DatabaseTestAdapter::insert("user", $userFromGame2);

echo "User data: ";
$user = User::get(1);
echo "User game: " . ($user ? $user->game : "null") . PHP_EOL;
echo "Game1 id: " . $game1["id"] . PHP_EOL;
echo "Comparison: " .
    ($user && $user->game != $game1["id"] ? "true" : "false") .
    PHP_EOL;
