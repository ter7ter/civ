<?php

namespace App;

/**
 * Стратегия для миссии строительства дороги.
 */
class BuildRoadMission implements MissionCompleteInterface
{
    /**
     * Завершить выполнение миссии строительства дороги.
     * @param Unit $unit
     * @param string|null $title
     * @return bool
     */
    public function complete(Unit $unit, string|false $title = false): bool
    {
        $cell = Cell::get($unit->x, $unit->y, $unit->planet);
        if (!$cell->road) {
            $cell->road = 'road';
            $cell->save();
        }
        return true;
    }
}
