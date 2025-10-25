<?php

namespace App;

/**
 * Класс для управления населением города
 */
class CityPopulationManager
{
    /**
     * Разместить жителей по клеткам
     * @param City $city
     */
    public static function locatePeople(City $city)
    {
        $city->people_cells = [];
        $cells = $city->get_city_cells();
        $people_count = $city->population;
        while ($people_count > 0 && count($cells) > 0) {
            $best = $cells[0];
            $best_key = 0;
            foreach ($cells as $key => $cell) {
                if ($cell->get_eat($city) > $best->get_eat($city)) {
                    $best = $cell;
                    $best_key = $key;
                } elseif (
                    $cell->get_eat($city) == $best->get_eat($city) &&
                    $cell->get_work($city) > $best->get_work($city)
                ) {
                    $best = $cell;
                    $best_key = $key;
                } elseif (
                    $cell->get_eat($city) == $best->get_eat($city) &&
                    $cell->get_work($city) == $best->get_work($city) &&
                    $cell->get_money($city) > $best->get_money($city)
                ) {
                    $best = $cell;
                    $best_key = $key;
                }
            }
            $city->people_cells[] = $best;
            array_splice($cells, $best_key, 1);
            $people_count--;
        }
    }

    /**
     * Установить размещение жителей
     * @param City $city
     * @param array $people_cells
     */
    public static function setPeople(City $city, $people_cells)
    {
        $city->people_cells = [];
        $city_cells = $city->get_city_cells();
        $people_count = $city->population;
        foreach ($people_cells as $cellp) {
            if ($people_count == 0) {
                break;
            }
            foreach ($city_cells as $cellc) {
                if ($cellp["x"] == $cellc->x && $cellp["y"] == $cellc->y) {
                    $city->people_cells[] = $cellc;
                    $people_count--;
                }
            }
        }
    }

    /**
     * Рассчитать параметры жителей
     * @param City $city
     */
    public static function calculatePeople(City $city)
    {
        $city->pwork = 1;
        $city->peat = 2;
        $money = 1;
        foreach ($city->people_cells as $cell) {
            $city->pwork += $cell->get_work();
            $city->peat += $cell->get_eat();
            $money += $cell->get_money();
        }
        $city->presearch = round(($money * $city->user->research_percent) / 10);
        $city->pmoney = $money - $city->presearch;
        $city->people_dis = 0;
        $city->people_happy = 0;
        $city->people_norm = count($city->people_cells);
        if ($city->people_norm >= POPULATION_PEOPLE_DIS) {
            $city->people_norm = POPULATION_PEOPLE_DIS - 1;
            $city->people_dis = $city->population - $city->people_norm;
        }
        $add_happy = $city->people_artist;
        $city->people_norm -= $add_happy;
        if ($city->people_norm < 0) {
            $add_happy += $city->people_norm;
            $city->people_norm = 0;
        }
        $city->people_happy += $add_happy;
    }

    /**
     * Проверить настроение жителей
     * @param City $city
     */
    public static function checkMood(City $city)
    {
        if ($city->people_dis > $city->people_happy) {
            $city->pwork = 0;
            $city->pmoney = 0;
        }
    }

    /**
     * Добавить жителя
     * @param City $city
     */
    public static function addPeople(City $city)
    {
        $city->population++;
        $city->save();
    }

    /**
     * Удалить жителя
     * @param City $city
     */
    public static function removePeople(City $city)
    {
        if ($city->population > 1) {
            $city->population--;
            $city->save();
        }
    }
}
