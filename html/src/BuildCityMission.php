<?php

namespace App;

/**
 * Стратегия для миссии основания города.
 */
class BuildCityMission implements MissionCompleteInterface
{
    /**
     * Завершить выполнение миссии основания города.
     * @param Unit $unit
     * @param string|null $title
     * @return bool
     */
    public function complete(Unit $unit, string|false $title = false): bool
    {
        if (!$title) {
            return false;
        }
        City::new_city($unit->user, $unit->x, $unit->y, $title, $unit->planet);
        return true;
    }
}
