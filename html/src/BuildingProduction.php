<?php

namespace App;

/**
 * Стратегия для производства здания.
 */
class BuildingProduction implements ProductionInterface
{
    private $buildingType;

    public function __construct(BuildingType $buildingType)
    {
        $this->buildingType = $buildingType;
    }

    /**
     * Завершить производство здания.
     * @param City $city
     */
    public function complete(City $city)
    {
        CityProductionManager::createBuilding($city, $this->buildingType);
        $this->buildingType->city_effect($city);
    }
}
