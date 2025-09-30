<?php

namespace App;

/**
 * Класс для управления производством города
 */
class CityProductionManager
{
    /**
     * Получить возможные юниты для постройки
     * @param City $city
     * @return array
     */
    public static function getPossibleUnits(City $city)
    {
        $units = UnitType::getAll();
        $result = [];
        $have_research = $city->user->get_research();
        foreach ($units as $unit) {
            if ($unit->type == "water" && $city->is_coastal == false) {
                continue;
            }
            if ($unit->population_cost > $city->population) {
                continue;
            }
            $can_build = true;
            foreach ($unit->req_research as $research) {
                if (!isset($have_research[$research->id])) {
                    $can_build = false;
                }
            }
            foreach ($unit->req_resources as $res) {
                if (!isset($city->resources[$res->id])) {
                    $can_build = false;
                }
            }
            if (!$can_build) {
                continue;
            }
            $result[$unit->id] = $unit;
        }
        return $result;
    }

    /**
     * Получить возможные здания для постройки
     * @param City $city
     * @return array
     */
    public static function getPossibleBuildings(City $city)
    {
        $buildings = BuildingType::getAll();
        $result = [];
        $have_research = $city->user->get_research();
        foreach ($buildings as $building) {
            if (isset($city->buildings[$building->id])) {
                continue;
            }
            $can_build = true;
            foreach ($building->req_research as $research) {
                if (!isset($have_research[$research->id])) {
                    $can_build = false;
                }
            }
            foreach ($building->req_resources as $res) {
                if (!isset($city->resources[$res->id])) {
                    $can_build = false;
                }
            }
            if (!$can_build) {
                continue;
            }
            $result[$building->id] = $building;
        }
        return $result;
    }

    /**
     * Рассчитать производство
     * @param City $city
     * @return bool
     */
    public static function calculateProduction(City $city)
    {
        if (!$city->production) {
            return false;
        }
        switch ($city->production_type) {
            case "unit":
                $production = UnitType::get($city->production);
                break;
            case "buil":
                $production = BuildingType::get($city->production);
                break;
            default:
                throw new \Exception(
                    "Missing production type {$city->production_type}",
                );
        }
        if ($city->production_complete < $production->cost) {
            $city->production_complete += $city->pwork;
        }
        if ($city->production_complete >= $production->cost) {
            if (
                $city->production_type == "unit" &&
                $city->population <= $production->population_cost
            ) {
                $city->production_complete = $production->cost;
                return true;
            }
            // Закончили производство
            switch ($city->production_type) {
                case "unit":
                    $city->population -= $production->population_cost;
                    self::createUnit($city, $production);
                    if ($production->population_cost > 0) {
                        CityPopulationManager::locatePeople($city);
                        CityPopulationManager::calculatePeople($city);
                    }
                    $event = new Event([
                        "type" => "city_unit",
                        "user_id" => $city->user->id,
                        "object" => $production->id,
                        "source" => $city->id,
                    ]);
                    break;
                case "buil":
                    self::createBuilding($city, $production);
                    $production->city_effect($city);
                    $event = new Event([
                        "type" => "city_building",
                        "user_id" => $city->user->id,
                        "object" => $production->id,
                        "source" => $city->id,
                    ]);
                    break;
                default:
                    $city->production = false;
            }
            $event->save();
            $city->production_complete = 0;
            self::selectNextProduction($city);
        }
    }

    /**
     * Выбрать следующее производство
     * @param City $city
     */
    public static function selectNextProduction(City $city)
    {
        if ($city->production_type == "buil") {
            $city->production_type = "unit";
            $units = self::getPossibleUnits($city);
            $unit = array_shift($units);
            $city->production = $unit->id;
        }
    }

    /**
     * Создать юнит
     * @param City $city
     * @param UnitType $type
     * @return Unit
     */
    public static function createUnit(City $city, $type): Unit
    {
        $unit = new Unit([
            "x" => $city->x,
            "y" => $city->y,
            "planet" => $city->planet,
            "health" => 3,
            "points" => $type->points,
            "user_id" => $city->user->id,
            "type" => $type->id,
        ]);
        $unit->save();
        return $unit;
    }

    /**
     * Создать здание
     * @param City $city
     * @param BuildingType $type
     * @return Building
     */
    public static function createBuilding(City $city, $type): Building
    {
        $building = new Building(["type" => $type->id, "city_id" => $city->id]);
        $building->save();
        $city->buildings[] = $building;
        return $building;
    }
}
