<?php

namespace App;

/**
 * Класс для управления движением юнита
 */
class UnitMovement
{
    /**
     * Может ли юнит переместится на данную клетку
     * @param Unit $unit
     * @param Cell $cell
     * @return bool
     */
    public static function canMove(Unit $unit, Cell $cell)
    {
        if ($cell->planet != $unit->planet) {
            return false;
        }
        if ($cell->city && $cell->city->user->id == $unit->user->id) {
            return true;
        }
        if ($unit->points == 0) {
            return false;
        }
        if ($unit->mission) {
            return false;
        }
        if (!isset($unit->type->getCanMove()[$cell->type->id])) {
            return false;
        }
        if (abs($unit->x - $cell->x) > 1 && abs($unit->x - $cell->x) != 99) {
            return false;
        }
        if (abs($unit->y - $cell->y) > 1 && abs($unit->y - $cell->y) != 99) {
            return false;
        }
        return true;
    }

    /**
     * Осуществляет перемещение с текущей клетки на заданную
     * @param Unit $unit
     * @param Cell $cell
     * @return bool
     */
    public static function moveTo(Unit $unit, Cell $cell)
    {
        if (!self::canMove($unit, $cell)) {
            return false;
        }
        $cell_from = Cell::get($unit->x, $unit->y, $unit->planet);
        if (
            ($cell->road || $cell->city) &&
            ($cell_from->road || $cell_from->city)
        ) {
            $unit->points -= GameConfig::$ROAD_MOVE_POINTS;
        } else {
            if ($cell->city && $cell->city->user->id == $unit->user->id) {
                $unit->points -= $unit->type->getCanMove()["city"];
            } else {
                $unit->points -= $unit->type->getCanMove()[$cell->type->id];
            }
        }
        $unit->x = $cell->x;
        $unit->y = $cell->y;
        if ($unit->points < 0) {
            $unit->points = 0;
        }
        $unit->save();

        // Обновляем массивы units в клетках после перемещения
        $cell_from = Cell::get($unit->x, $unit->y, $unit->planet);
        $cell_from->get_units();
        $cell->get_units();
        $cell_from->units = array_filter($cell_from->units, function ($u) use ($unit) {
            return $u->id != $unit->id;
        });
        $cell->units[] = $unit;

        return true;
    }

    /**
     * Осуществляет перемещение по заданному пути
     * @param Unit $unit
     * @param array $path
     */
    public static function movePath(Unit $unit, $path)
    {
        MyDB::query("DELETE FROM mission_order WHERE unit_id = :uid", [
            "uid" => $unit->id,
        ]);
        $number = 1;
        foreach ($path as $cell) {
            $unit->add_order("move", $cell["x"], $cell["y"], $number);
            $number++;
        }
    }

    /**
     * Расчитывает путь между двумя клетками
     * @param Unit $unit
     * @param Cell $cell1
     * @param Cell $cell2
     * @param int $max_path
     * @return array|bool
     */
    public static function calculatePath(Unit $unit, $cell1, $cell2, $max_path = 200)
    {
        if ($cell1->planet != $cell2->planet) {
            return false;
        }
        $cells_next = [["cell" => $cell1, "prev" => false, "dist" => 0]];
        $cells_path = [];
        $dist = $max_path + 1;
        while (count($cells_next) > 0) {
            $next = array_shift($cells_next);
            if (
                isset($cells_path[$next["cell"]->x . "_" . $next["cell"]->y]) &&
                $cells_path[$next["cell"]->x . "_" . $next["cell"]->y]["dist"] <= $next["dist"]
            ) {
                continue; //Сюда уже нашли путь не хуже
            }
            if ($next["dist"] >= $dist) {
                continue;
            } //Слишком длинный путь
            if ($next["cell"] == $cell2) {
                $dist = $next["dist"]; //Нашли возможный путь
            }
            $cells_path[$next["cell"]->x . "_" . $next["cell"]->y] = $next;
            $cells_around = Cell::get_cells_around(
                $next["cell"]->x,
                $next["cell"]->y,
                3,
                3,
                $next["cell"]->planet,
            );
            foreach ($cells_around as $row) {
                foreach ($row as $map_near) {
                    if ($map_near == $next["cell"]) {
                        continue;
                    }
                    if (
                        !isset($unit->type->getCanMove()[$map_near->type->id]) &&
                        !$map_near->city
                    ) {
                        continue;
                    } //Сюда пути нет
                    if (
                        ($next["cell"]->road || $next["cell"]->city) &&
                        ($map_near->road || $map_near->city)
                    ) {
                        $dist_near = GameConfig::$ROAD_MOVE_POINTS; //Есть дорога
                    } else {
                        if ($map_near->city) {
                            $dist_near = $unit->type->getCanMove()["city"];
                        } else {
                            $dist_near = $unit->type->getCanMove()[$map_near->type->id];
                        }
                    }
                    $cells_next[] = [
                        "cell" => $map_near,
                        "prev" => $next["cell"],
                        "dist" => $next["dist"] + $dist_near,
                    ];
                }
            }
        }
        if ($dist <= $max_path) {
            //Нашли путь
            $x = $cell2->x;
            $y = $cell2->y;
            $path = [$cell2];
            $i = 0;
            while (!($x == $cell1->x && $y == $cell1->y)) {
                $i++;
                if ($i > $max_path) {
                    return false;
                }
                if ($cells_path[$x . "_" . $y]["prev"]) {
                    $path[] = $cells_path[$x . "_" . $y]["prev"];
                    $x = $path[count($path) - 1]->x;
                    $y = $path[count($path) - 1]->y;
                } else {
                    break;
                }
            }
            $path = array_reverse($path);
            return ["dist" => $dist, "path" => $path];
        }
        return false;
    }
}
