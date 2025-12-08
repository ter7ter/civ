<?php

namespace App\Interfaces;

/**
 * Интерфейс для юнитов: движения, миссии
 */
interface UnitInterface
{
    public function canMove($cell): bool;
    public function moveTo($cell): bool;
    public function getMissionTypes($x = null, $y = null): array;
    public function startMission($mission, $title = ""): bool|string;
}
