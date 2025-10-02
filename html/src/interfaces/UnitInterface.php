<?php

namespace App\Interfaces;

/**
 * Интерфейс для юнитов: движения, миссии
 */
interface UnitInterface
{
    public function can_move($cell): bool;
    public function move_to($cell): bool;
    public function get_mission_types($x = null, $y = null): array;
    public function start_mission($mission, $title = ""): bool|string;
}
