<?php

namespace App;

/**
 * Класс для управления зданиями города
 */
class CityBuildingManager
{
    /**
     * Применить эффекты построек
     * @param City $city
     */
    public static function calculateBuildings(City $city)
    {
        foreach ($city->buildings as $building) {
            $building->type->city_effect($city);
            if ($building->type->upkeep > 0 && $city->pwork > 0) {
                $city->pmoney -= $building->type->upkeep;
            }
        }
    }
}
