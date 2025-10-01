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
    public static function getPossibleBuildings(City $city): array
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
    public static function calculateProduction(City $city): bool
    {
        if (!$city->production) {
            return false;
        }
        $productionItem = match ($city->production_type) {
            "unit" => UnitType::get($city->production),
            "build" => BuildingType::get($city->production),
            default => throw new \Exception(
                "Missing production type {$city->production_type}",
            ),
        };
        if ($city->production_complete < $productionItem->cost) {
            $city->production_complete += $city->pwork;
        }
        if ($city->production_complete >= $productionItem->cost) {
            if (
                $city->production_type == "unit" &&
                $city->population <= $productionItem->population_cost
            ) {
                $city->production_complete = $productionItem->cost;
                return true;
            }
            // Закончили производство
            $productionImpl = self::createProductionStrategy($city->production_type, $productionItem);
            $productionImpl->complete($city);

            $eventType = $city->production_type == "unit" ? "city_unit" : "city_building";
            $event = new Event([
                "type" => $eventType,
                "user_id" => $city->user->id,
                "object" => $productionItem->id,
                "source" => $city->id,
            ]);
            $event->save();
            $city->production_complete = 0;
            self::selectNextProduction($city);
        }
    }

    /**
     * Создать стратегию для производства
     * @param string $productionType
     * @param mixed $productionItem
     * @return ProductionInterface
     * @throws \Exception
     */
    private static function createProductionStrategy(string $productionType, $productionItem): ProductionInterface
    {
        switch ($productionType) {
            case "unit":
                return new UnitProduction($productionItem);
            case "buil":
                return new BuildingProduction($productionItem);
            default:
                throw new \Exception("Unknown production type: {$productionType}");
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
