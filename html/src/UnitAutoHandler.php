<?php

namespace App;

/**
 * Класс для авто операции юнита
 */
class UnitAutoHandler
{
    /**
     * Автоматизация действий рабочих. Ещё не протестировано
     * @param Unit $unit
     */
    public static function processAuto(Unit $unit)
    {
        $auto_ok = false;
        $number = 0;
        if ($unit->auto == "work") {
            //Проверяем есть ли задачи
            if (
                MyDB::query(
                    "SELECT count(unit_id) FROM mission_order WHERE unit_id = :uid",
                    ["uid" => $unit->id],
                    "el",
                ) == 0
            ) {
                //Выполняемых задач нет, нужно выдать следующую
                $cities = $unit->user->get_cities();
                $paths = [];
                foreach ($cities as $city) {
                    //Проверяем расстояния до своих городов
                    $path = UnitMovement::calculatePath(
                        Cell::get($unit->x, $unit->y, $unit->planet),
                        Cell::get($city->x, $city->y, $city->planet),
                        200
                    );
                    if ($path) {
                        $paths[$city->id] = $path;
                    }
                }
                usort($paths, function ($a, $b) {
                    if ($a["dist"] > $b["dist"]) {
                        return 1;
                    }
                    if ($a["dist"] < $b["dist"]) {
                        return -1;
                    }
                    return 0;
                });
                while (count($paths) > 0 && !$auto_ok) {
                    //Пробуем все города от ближайшего
                    $path = array_shift($paths);
                    $paths_to = [];
                    foreach ($cities as $city) {
                        if ($city === array_shift($cities)) { # fixed to check if current city
                            continue;
                        }
                        $path_to = UnitMovement::calculatePath(
                            Cell::get($city->x, $city->y, $city->planet),
                            Unit::get($unit->x, $unit->y, $unit->planet), # fixed to target
                            200
                        );
                        if ($path_to) {
                            $paths_to[$city->id] = $path_to;
                        }
                        usort($paths_to, function ($a, $b) {
                            if ($a["dist"] > $b["dist"]) {
                                return 1;
                            }
                            if ($a["dist"] < $b["dist"]) {
                                return -1;
                            }
                            return 0;
                        });
                        while (count($paths_to) > 0) {
                            // Перебираем дороги ко всем городам от него
                            $path_to = array_shift($paths_to);
                            foreach ($path_to["path"] as $cell) {
                                if (
                                    !$cell->city &&
                                    !$cell->road &&
                                    ($cell->owner == $unit->user ||
                                        !$cell->owner)
                                ) {
                                    $need_road = true; //Строим тут дорогу
                                    $move_path = UnitMovement::calculatePath(
                                        Cell::get($unit->x, $unit->y, $unit->planet),
                                        $cell,
                                        200
                                    );
                                    array_shift($move_path["path"]);
                                    foreach ($move_path["path"] as $move) {
                                        $number++;
                                        UnitOrderHandler::addOrder($unit, "move", $move->x, $move->y);
                                        $number++;
                                    }
                                    $number++;
                                    UnitOrderHandler::addOrder($unit, "build_road", "NULL", "NULL");
                                    $unit->x = $cell->x;
                                    $unit->y = $cell->y;
                                }
                            }
                            if (isset($need_road) && $need_road) {
                                $auto_ok = true;
                                break;
                            }
                        }
                    }
                }
                if (!$auto_ok) {
                    //Не нашли где строить дороги между городами
                    $max_path = GameConfig::$WORK_DIST1 + 1;
                    $path = false;
                    $peoples = MyDB::query(
                        "SELECT `p`.`x` as `x`, `p`.`y` as `y` FROM `people_cells` as `p`
                                        INNER JOIN `cell` ON cell.x = p.x AND cell.y = p.y AND cell.planet = p.planet
                                        WHERE cell.road = 'none' AND cell.owner = :userid",
                        ["userid" => $unit->user->id],
                    );
                    foreach ($peoples as $cell) {
                        $npath = UnitMovement::calculatePath(
                            Cell::get($unit->x, $unit->y, $unit->planet),
                            Cell::get($cell["x"], $cell["y"], $unit->planet),
                            200
                        );
                        if ($npath && $npath["dist"] < $max_path) {
                            $max_path = $npath["dist"];
                            $path = $npath;
                        }
                    }
                    if ($path) {
                        $auto_ok = true;
                        foreach ($path["path"] as $cell) {
                            $number++;
                            UnitOrderHandler::addOrder($unit, "move", $cell->x, $cell->y, $number);
                            $number++;
                        }
                        $number++;
                        UnitOrderHandler::addOrder($unit, "build_road", "NULL", "NULL", $number);
                    }
                }
                if (!$auto_ok) {
                    //Не нашли где строить дороги около городов
                    $max_path = GameConfig::$WORK_DIST1 + 1;
                    $path = false;
                    $mine = MissionType::get("mine");
                    $irrigation = MissionType::get("irrigation");
                    $cell_types = array_merge(
                        $mine->cell_types,
                        $irrigation->cell_types,
                    );
                    $cell_types = array_unique($cell_types);
                    //TODO: выбор типа улучшения с учётом технологий и приоритетов
                    $peoples = MyDB::query(
                        "SELECT `p`.`x` as `x`, `p`.`y` as `y` FROM `people_cells` as `p`
                                        INNER JOIN `cell` ON cell.x = p.x AND cell.y = p.y AND cell.planet = p.planet
                                        WHERE cell.improvement = 'none' AND cell.owner = :userid
                                        AND `cell`.`type` IN ('" .
                            join($cell_types, "', '") .
                            "')",
                        ["userid" => $unit->user->id],
                    );
                    foreach ($peoples as $cell) {
                        $npath = UnitMovement::calculatePath(
                            Cell::get($unit->x, $unit->y, $unit->planet),
                            Cell::get($cell["x"], $cell["y"], $unit->planet),
                            200
                        );
                        if ($npath && $npath["dist"] < $max_path) {
                            $max_path = $npath["dist"];
                            $path = $npath;
                        }
                    }
                    if ($path) {
                        $auto_ok = true;
                        foreach ($path["path"] as $cell) {
                            $number++;
                            UnitOrderHandler::addOrder($unit, "move", $cell->x, $cell->y, $number);
                            $number++;
                        }
                        $number++;
                        $cell = array_pop($path["path"]);
                        if (
                            in_array(
                                $irrigation->cell_types,
                                $cell->type->id,
                            )
                        ) {
                            UnitOrderHandler::addOrder($unit, "irrigation", "NULL", "NULL", $number);
                        }
                        if (in_array($mine->cell_types, $cell->type->id)) {
                            UnitOrderHandler::addOrder($unit, "mine", "NULL", "NULL", $number);
                        }
                    }
                }
            }
        }
    }
}
