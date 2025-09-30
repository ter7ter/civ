<?php

namespace App;

/**
 * Класс для управления миссиями юнита
 */
class UnitMissionHandler
{
    /**
     * Какие миссии этот юнит может выполнить в данной точке
     * @param Unit $unit
     * @param int $x
     * @param int $y
     * @return array
     */
    public static function getMissionTypes(Unit $unit, $x = null, $y = null)
    {
        if (is_null($x)) {
            $x = $unit->x;
        }
        if (is_null($y)) {
            $y = $unit->y;
        }
        $result = [];
        foreach ($unit->type->missions as $mission_id) {
            $mtype = MissionType::get($mission_id);
            if ($mtype->check_cell($x, $y, $unit->planet)) {
                $result[$mtype->id] = $mtype;
            }
        }
        return $result;
    }

    /**
     * Выполнение юнитом задания
     * @param Unit $unit
     * @param MissionType $mtype
     * @param string $title
     * @return bool|string
     */
    public static function startMission(Unit $unit, $mtype, $title = "")
    {
        if ($unit->mission) {
            $unit->mission = false;
        }
        if ($unit->points == 0) {
            $unit->save();
            return false;
        }
        $cell = Cell::get($unit->x, $unit->y, $unit->planet);
        if (!$mtype->check_cell($unit->x, $unit->y)) {
            return false;
        }
        if (!in_array($cell->type->id, $mtype->cell_types)) {
            return false;
        }
        $need_points = $cell->get_mission_need_points($mtype);
        if ($unit->points >= $need_points) {
            //Можем сделать сразу
            if (!$mtype->complete($unit, $title)) {
                return false;
            }
            if ($mtype->unit_lost) {
                $unit->remove();
                return "unit_lost";
            }
            $unit->points -= $need_points;
            return true;
        } else {
            $unit->mission = $mtype;
            $unit->mission_points = (int) $unit->points;
            $unit->points = 0;
            $unit->save();
            return true;
        }
    }

    /**
     * Обработка миссий в calculate
     * @param Unit $unit
     */
    public static function processMissions(Unit $unit)
    {
        if ($unit->points == 0) {
            return;
        }
        if ($unit->mission) {
            $cell = Cell::get($unit->x, $unit->y, $unit->planet);
            $need_points = $cell->get_mission_need_points($unit->mission);
            if ($need_points <= $unit->points) {
                //Можем закончить
                $units = MyDB::query(
                    "SELECT id FROM unit WHERE x = :x AND y = :y AND planet = :planet AND mission = :mission AND id != :uid",
                    [
                        "x" => $unit->x,
                        "y" => $unit->y,
                        "planet" => $unit->planet,
                        "mission" => $unit->mission->id,
                        "uid" => $unit->id,
                    ],
                );
                $unit->points -= $need_points;
                $unit->mission->complete($unit);
                $unit->mission = false;
                $unit->mission_points = 0;
                foreach ($units as $row) {
                    $u = Unit::get($row["id"]);
                    $u->mission = false;
                    $u->mission_points = 0;
                    $u->save();
                }
            } else {
                $unit->mission_points += $unit->points;
                $unit->points = 0;
            }
        }
    }
}
