<?php

namespace App;

/**
 * Стратегия для миссий постройки улучшений (mine, irrigation).
 */
class BuildMineAndIrrigationMission implements MissionCompleteInterface
{
    private $improvementType;

    public function __construct(string $improvementType)
    {
        $this->improvementType = $improvementType;
    }

    /**
     * Завершить выполнение миссии постройки улучшения.
     * @param Unit $unit
     * @param string|null $title
     * @return bool
     */
    public function complete(Unit $unit, string|false $title = false): bool
    {
        $cell = Cell::get($unit->x, $unit->y, $unit->planet);
        $cell->improvement = $this->improvementType;
        $cell->save();
        return true;
    }
}
