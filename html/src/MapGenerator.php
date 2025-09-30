<?php

namespace App;

/**
 * Класс для генерации новой игры, включая карту и размещение игроков.
 */
class MapGenerator
{
    /**
     * Создать новую игру с генерацией карты и размещением игроков
     * @param Game $game Игра для создания
     * @throws Exception
     */
    public static function generateNewGame(Game $game)
    {
        // Убеждаемся, что типы юнитов инициализированы перед созданием юнитов
        if (empty(UnitType::getAll())) {
            if (class_exists("TestGameDataInitializer")) {
                TestGameDataInitializer::initializeUnitTypes();
            }
        }

        $planet = new Planet(['name' => 'Planet 1', 'game_id' => $game->id]);
        $planet->save();
        $planetId = $planet->id;

        Cell::generate_map($planetId, $game->id);
        $users = MyDB::query(
            "SELECT id FROM user WHERE game = :gameid ORDER BY turn_order",
            ["gameid" => $game->id],
        );
        foreach ($users as $user) {
            $userObj = User::get($user["id"]);
            if ($userObj !== null) {
                $game->users[$user["id"]] = $userObj;
            }
        }
        $positions = [];
        $i = 0;
        while (count($positions) < count($game->users)) {
            $i++;
            $pos_x = mt_rand(0, $game->map_w - 1);
            $pos_y = mt_rand(0, $game->map_h - 1);
            $cell = Cell::get($pos_x, $pos_y, $planetId);
            if ($i > 1000) {
                throw new \Exception("Too many iterations");
            }
            if (
                !in_array($cell->type->id, [
                    "plains",
                    "plains2",
                    "forest",
                    "hills",
                ])
            ) {
                //Эта клетка не подходит для заселения
                continue;
            }
            $around_ok = 0;
            $cells = Cell::get_cells_around($pos_x, $pos_y, 3, 3, $planetId);
            foreach ($cells as $row) {
                foreach ($row as $item) {
                    if (
                        in_array($cell->type->id, [
                            "plains",
                            "plains2",
                            "forest",
                            "hills",
                        ])
                    ) {
                        $around_ok++;
                    }
                }
            }
            if ($around_ok < 3) {
                //Мало подходящих соседних клеток
                continue;
            }
            //Проверяем наличие соседей поблизости
            $users_around = false;
            foreach ($positions as $pos) {
                if (Cell::calc_distance($pos[0], $pos[1], $pos_x, $pos_y) < 8) {
                    $users_around = true;
                }
            }
            if ($users_around) {
                continue;
            }
            $positions[] = [$pos_x, $pos_y];
        }
        foreach ($game->users as $user) {
            $position = array_shift($positions);
            $citizen = new Unit([
                "x" => $position[0],
                "y" => $position[1],
                "planet" => $planetId,
                "health" => 3,
                "points" => 2,
                "user_id" => $user->id,
                "type" => GameConfig::$START_UNIT_SETTLER_TYPE,
            ]);
            $citizen->save();
        }
    }
}
