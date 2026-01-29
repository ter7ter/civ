<?php

namespace App;

use Exception;
use Generator;

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
    public static function startMission(Unit $unit, $mtype, &$result_message = null, string $title = "")
    {
        if ($unit->mission) {
            $result_message = "mission_empty";
            $unit->mission = false;
        }
        if ($unit->points == 0) {
            $unit->save();
            $result_message = "unit points empty";
            return false;
        }
        $cell = Cell::get($unit->x, $unit->y, $unit->planet);
        if (!$mtype->check_cell($unit->x, $unit->y, $unit->planet)) {
            $result_message = "incorrect cell";
            return false;
        }
        if (!in_array($cell->type->id, $mtype->cell_types)) {
            $result_message = "incorrect cell type";
            return false;
        }
        $need_points = $cell->get_mission_need_points($mtype);
        if ($unit->points >= $need_points) {
            //Можем сделать сразу
            if (!$mtype->complete($unit, $title)) {
                $result_message = "mission failed";
                return false;
            }
            if (is_null($unit)) {
                return "unit_lost";
            }
            if ($mtype->unit_lost) {
                $unit->remove();
                return "unit_lost";
            }
            $unit->points -= $need_points;
            $unit->save();
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
     * Получить юнитов выполняющих миссию
     *
     * @param Cell $cell
     * @param MissionType $mission
     * @return Generator
     * @throws Exception
     */
    public static function getMissionUnits(Cell $cell, MissionType $mission): Generator
    {
        $unitsData = [];
        $unitsData = MyDB::query(
            "SELECT id FROM unit WHERE x = :x AND y = :y AND planet = :planet AND mission = :mission",
            ["x" => $cell->x, "y" => $cell->y, "planet" => $cell->planet, "mission" => $mission->id],
        );
        foreach ($unitsData as $unitData) {
            yield Unit::get($unitData["id"]);
        }
    }

    /**
     *  Сколько ходов осталось до выполнения миссии
     * @param int $x
     * @param int $y
     * @param int $planet
     * @param MissionType $mission
     * @return int
     * @throws Exception
     */
    public static function getNeedTurns(int $x, int $y, int $planet, MissionType $mission): int
    {
        $cell = Cell::get($x, $y, $planet);
        $need_points = $cell->get_mission_need_points($mission);
        if ($need_points == 0) {
            return 0;
        }
        $pointPerTurn = 0;
        foreach (self::getMissionUnits($cell, $mission) as $unit) {
            $pointPerTurn = $unit->type->points;
        }
        return ceil($need_points / $pointPerTurn);
    }

    /**
     * Обработка миссий в calculate
     * @param Unit $unit
     * @throws Exception
     */
    public static function processMissions(Unit $unit): void
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

    /**
     * Сколько ходов нужно юниту для выполнения миссии, с учётом уже работающих над этой миссией юнитов
     * @param Unit $unit
     * @param MissionType $mission
     * @return int
     * @throws Exception
     */
    public static function unitGetNeedTurns(Unit $unit, MissionType $mission): int
    {
        $cell = Cell::get($unit->x, $unit->y, $unit->planet);
        $need_points = $cell->get_mission_need_points($mission);
        if ($need_points == 0) {
            return 0;
        }
        $pointPerTurn = $unit->type->points;
        foreach (self::getMissionUnits($cell, $mission) as $unit) {
            $pointPerTurn = $unit->type->points;
        }
        return ceil($need_points / $pointPerTurn);
    }
}
