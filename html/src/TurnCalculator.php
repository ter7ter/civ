<?php

namespace App;

/**
 * Класс для расчета данных хода игр
 */
class TurnCalculator
{
    /**
     * Рассчитать новый ход для всех пользователей в игре
     * @param Game $game
     */
    public static function calculateTurn(Game $game)
    {
        $first = true;
        foreach ($game->users as $user) {
            $user->calculate_research(); //Начало нового
            $user->calculate_resource();
            $user->calculate_cities();
            $user->calculate_income();
            if (
                $game->turn_type == "byturn" ||
                $game->turn_type == "onewindow"
            ) {
                if ($first) {
                    $user->turn_status = "play";
                    $first = false;
                } else {
                    $user->turn_status = "wait";
                }
            } else {
                $user->turn_status = "play";
            }
            $user->save();
        }
        $game->turn_num++;
        $game->save();
    }

    /**
     * Получить активного игрока в игре
     * @param Game $game
     * @return int|null
     */
    public static function getActivePlayer(Game $game)
    {
        return MyDB::query(
            "SELECT id FROM user WHERE game = :gid AND turn_status = 'play' LIMIT 1",
            ["gid" => $game->id],
            "elem",
        );
    }
}
