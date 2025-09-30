<?php

namespace App;

/**
 * Стратегия для производства юнита.
 */
class UnitProduction implements ProductionInterface
{
    private $unitType;

    public function __construct(UnitType $unitType)
    {
        $this->unitType = $unitType;
    }

    /**
     * Завершить производство юнита.
     * @param City $city
     */
    public function complete(City $city)
    {
        $city->population -= $this->unitType->population_cost;
        CityProductionManager::createUnit($city, $this->unitType);
        if ($this->unitType->population_cost > 0) {
            CityPopulationManager::locatePeople($city);
            CityPopulationManager::calculatePeople($city);
        }
    }
}
