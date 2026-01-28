<?php

use App\Game;
use App\User;
use App\Cell;
use App\City;
use App\MyDB;

/** @var User $user Текущий пользователь */
if (!isset($_SESSION["game_id"])) {
    $error = "Game not found";
} else {
    $game = Game::get($_SESSION["game_id"]);
    if (!$game) {
        $error = "Game not found";
    } else {
        // Получаем первую планету игры
        $firstPlanet = $game->get_first_planet();
        if (!$firstPlanet) {
            $map_data = [];
        } else {
            // Получаем всех пользователей игры для получения их цветов
            $user_colors = [];
            foreach ($game->users as $usr) {
                $user_colors[$usr->id] = $usr->color;
            }

            // Загружаем все клетки для первой планеты
            $allCells = Cell::getAllByPlanet($firstPlanet->id);

            // Предварительно загружаем все города для этой планеты
            $cities = MyDB::query(
                "SELECT id, x, y, title, population FROM city WHERE planet = :planet_id",
                ["planet_id" => $firstPlanet->id],
            );

            // Создаем индексированный массив городов для быстрого доступа
            $cities_by_coords = [];
            foreach ($cities as $city_data) {
                $key = $city_data["x"] . "_" . $city_data["y"];
                $cities_by_coords[$key] = [
                    "id" => $city_data["id"],
                    "title" => $city_data["title"],
                    "population" => $city_data["population"],
                ];
            }

            // Формируем массив данных для карты
            $map_data = [];
            foreach ($allCells as $cell_data) {
                $owner_color = null;
                if (
                    $cell_data["owner"] &&
                    isset($user_colors[$cell_data["owner"]])
                ) {
                    $owner_color = $user_colors[$cell_data["owner"]];
                }

                // Проверяем, есть ли город на этой клетке, используя предварительно загруженные данные
                $key = $cell_data["x"] . "_" . $cell_data["y"];
                $city_data = isset($cities_by_coords[$key])
                    ? $cities_by_coords[$key]
                    : null;

                $map_data[$cell_data["x"]][$cell_data["y"]] = [
                    "x" => $cell_data["x"],
                    "y" => $cell_data["y"],
                    "type" => $cell_data["type_id"],
                    "owner" => $owner_color ? ["color" => $owner_color] : null,
                    "city" => $city_data,
                ];
            }
        }

        $data = $map_data;
    }
}

?>
