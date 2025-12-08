<?php

namespace App;

/**
 * Класс для обработки приказов юнита
 */
class UnitOrderHandler
{
    /**
     * Добавляет приказ юниту
     * @param Unit $unit
     * @param string $mission
     * @param mixed $target_x
     * @param mixed $target_y
     * @param int|false $number
     * @return int
     */
    public static function addOrder($unit, $mission, $target_x = "NULL", $target_y = "NULL", $number = false)
    {
        if (!$number) {
            $number = MyDB::query(
                "SELECT max(number) FROM mission_order WHERE unit_id = :uid",
                ["uid" => $unit->id],
                "el",
            );
            if (!$number) {
                $number = 0;
            }
            $number++;
        }
        MyDB::insert("mission_order", [
            "unit_id" => $unit->id,
            "number" => $number,
            "type" => $mission,
            "target_x" => $target_x,
            "target_y" => $target_y,
        ]);
        return $number;
    }

    /**
     * Убрает приказ из базы
     *
     * @param $unit
     * @param $number
     * @return void
     */
    protected static function deleteOrder($unit, $number)
    {
        MyDB::query(
            "DELETE FROM mission_order WHERE `unit_id` = :uid AND `number` = :number",
            ["uid" => $unit->id, "number" => $number],
        );
    }

    public static function cancelOrders($unit)
    {
        MyDB::query(
            "DELETE FROM mission_order WHERE `unit_id` = :uid",
            ["uid" => $unit->id],
        );
    }

    /**
     * Обрабатывает приказы в calculate
     * @param Unit $unit
     * @throws \Exception
     */
    public static function processOrders(Unit $unit): void
    {
        if ($unit->mission) {//Если уже выполняется миссия, то не обрабатываем приказы (например, строим дорогу
            return;
        }
        $order = MyDB::query(
            "SELECT * FROM mission_order WHERE unit_id = :uid
                    ORDER BY `number` ASC LIMIT 1",
            ["uid" => $unit->id],
            "row",
        );
        while ($order && $unit->points > 0) {
            if ($order["type"] == "move" || $order["type"] == "road") {
                $cell = Cell::get($order["target_x"], $order["target_y"], $unit->planet);
                if (!$cell) {
                    //Несуществующая клетка в задаче, отменяем
                    self::cancelOrders($unit);
                    break;
                }
            }
            if ($order["type"] == "move") {
                if (UnitMovement::moveTo($unit, $cell)) {
                    self::deleteOrder($unit, $order["number"]);
                } else {
                    //Если не можем туда идти отменяем все дальнейшие задачи
                    self::cancelOrders($unit);
                    break;
                }
            } elseif ($order["type"] == "road") {
                if ($unit->planet !== $cell->planet || $unit->x !== $cell->x || $unit->y !== $cell->y) {
                    UnitMovement::moveTo($unit, $cell);
                } else {
                    log_msg("build road to {$cell->x}, {$cell->y}");
                    UnitMissionHandler::startMission($unit, MissionType::get("build_road"));
                    self::deleteOrder($unit, $order["number"]);
                }
            } else {
                if (UnitMissionHandler::startMission(Unit::get($unit->id), MissionType::get($order["type"]))) {
                    //Смогли запустить миссию
                    self::deleteOrder($unit, $order["number"]);
                } else {
                    //Если не можем, то отменяем все дальнейшие задачи
                    self::cancelOrders($unit);
                }
            }
            $order = MyDB::query(
                "SELECT * FROM mission_order WHERE unit_id = :uid
                    ORDER BY `number` ASC LIMIT 1",
                ["uid" => $unit->id],
                "row",
            );
        }
    }
}
