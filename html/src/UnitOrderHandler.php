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
     * Обрабатывает приказы в calculate
     * @param Unit $unit
     */
    public static function processOrders(Unit $unit)
    {
        $order = MyDB::query(
            "SELECT * FROM mission_order WHERE unit_id = :uid
                    ORDER BY `number` ASC LIMIT 1",
            ["uid" => $unit->id],
            "row",
        );
        while ($order && $unit->points > 0) {
            if ($order["type"] == "move") {
                $cell = Cell::get($order["target_x"], $order["target_y"], $unit->planet);
                if (!$cell) {
                    //Несуществующая клетка в задаче, отменяем
                    MyDB::query(
                        "DELETE FROM mission_order WHERE `unit_id` = :uid",
                        ["uid" => $unit->id],
                    );
                    break;
                }
                if (UnitMovement::moveTo($unit, $cell)) {
                    MyDB::query(
                        "DELETE FROM mission_order WHERE `unit_id` = :uid AND `number` = :number",
                        ["uid" => $unit->id, "number" => $order["number"]],
                    );
                } else {
                    //Если не можем туда идти отменяем все дальнейшие задачи
                    MyDB::query(
                        "DELETE FROM mission_order WHERE `unit_id` = :uid",
                        ["uid" => $unit->id],
                    );
                    break;
                }
            } else {
                if (UnitMissionHandler::startMission(Unit::get($unit->id), MissionType::get($order["type"]))) {
                    //Смогли запустить миссию
                    MyDB::query(
                        "DELETE FROM mission_order WHERE `unit_id` = :uid AND `number` = :number",
                        ["uid" => $unit->id, "number" => $order["number"]],
                    );
                } else {
                    //Если не можем то отменяем все дальнейшие задачи
                    MyDB::query(
                        "DELETE FROM mission_order WHERE `unit_id` = :uid",
                        ["uid" => $unit->id],
                    );
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
