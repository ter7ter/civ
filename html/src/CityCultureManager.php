<?php

namespace App;

/**
 * Класс для управления культурой города
 */
class CityCultureManager
{
    /**
     * Рассчитать культуру города
     * @param City $city
     */
    public static function calculateCulture(City $city)
    {
        foreach ($city->buildings as $building) {
            $city->culture += $building->type->culture;
        }
        if (
            isset(GameConfig::$CULTURE_LEVELS[$city->culture_level + 1]) &&
            $city->culture >=
                GameConfig::$CULTURE_LEVELS[$city->culture_level + 1]
        ) {
            // Набралось культуры на следующий уровень
            $city->culture_level++;
            $city->culture -= GameConfig::$CULTURE_LEVELS[$city->culture_level];
        }
    }

    /**
     * Культурное влияние города
     * @param City $city
     * @return array
     */
    public static function getCultureCells(City $city)
    {
        $cells = [];
        $cellsu = [$city->x . "x" . $city->y];
        $cells[0] = [];
        $culture_up = GameConfig::$CULTURE_LEVELS[$city->culture_level + 1];
        for ($dx = -1; $dx < 2; $dx++) {
            for ($dy = -1; $dy < 2; $dy++) {
                $x = $city->x;
                $y = $city->y;
                if ($dx == 0 && $dy == 0) {
                    continue;
                }
                Cell::calc_coord($x, $y, $dx, $dy);
                $cells[0][] = [
                    "x" => $x,
                    "y" => $y,
                    "culture" =>
                        $city->culture_level * 10 +
                        ceil(($city->culture * 10) / $culture_up),
                ];
                $cellsu[] = $x . "x" . $y;
            }
        }
        for ($level = 1; $level <= $city->culture_level; $level++) {
            $cells[$level] = [];
            foreach ($cells[$level - 1] as $cell) {
                foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as $diff) {
                    $x = $cell["x"];
                    $y = $cell["y"];
                    Cell::calc_coord($x, $y, $diff[0], $diff[1]);
                    if (!in_array($x . "x" . $y, $cellsu)) {
                        $cells[$level][] = [
                            "x" => $x,
                            "y" => $y,
                            "culture" =>
                                ($city->culture_level - $level) * 10 +
                                ceil(($city->culture * 10) / $culture_up),
                        ];
                        $cellsu[] = $x . "x" . $y;
                    }
                }
            }
        }
        $result = [];
        foreach ($cells as $items) {
            foreach ($items as $item) {
                array_push($result, $item);
            }
        }
        return $result;
    }
}
