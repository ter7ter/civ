<?php

use App\BuildingType;
use App\UnitType;
use App\Сity;

/**
 * Скрипт для обработки страницы города.
 * Получает данные о городе, позволяет изменять размещение жителей и производство.
 */

/** @var User $user Текущий пользователь */
if (!isset($_REQUEST['cid']) || !$cid = (int)$_REQUEST['cid']) {
    $error = "City not found";
} elseif (!$city = City::get($cid)) {
    $error = "City not found";
} elseif ($city->user->id != $user->id) {
    $error = "City not you";
} else {
    if (isset($_REQUEST['change_people']) && $user->turn_status == 'play') {
        $cells = [];
        if (isset($_REQUEST['peoplex'])) {
            foreach ($_REQUEST['peoplex'] as $index => $x) {
                //Проверяем на повторы
                $y = $_REQUEST['peopley'][$index];
                $dubl = false;
                foreach ($cells as $cell) {
                    if ($cell['x'] == $x && $cell['y'] == $y) {
                        $dubl = true;
                    }
                }
                if (!$dubl) {
                    $cells[] = ['x' => $x, 'y' => $y];
                }
            }
        }
        $city->set_people($cells);
        $people_free = $city->population - count($cells);
        $city->people_artist = $people_free;
        $city->calculate_people();
        $city->calculate_buildings();
        $city->save();
    }
    $units_possible = $city->get_possible_units();
    $buildings_possible = $city->get_possible_buildings();
    if (isset($_REQUEST['production']) && $user->turn_status == 'play') {
        $production_id = (int)$_REQUEST['production'];
        if ($production_id == false) {
            $city->production = false;
            $city->production_complete = 0;
        } else {
            switch ($_REQUEST['production_type']) {
                case 'unit':
                    if (isset($units_possible[$production_id])) {
                        $city->production = $production_id;
                        $city->production_type = 'unit';
                    }
                    break;
                case 'building':
                    if (isset($buildings_possible[$production_id])) {
                        $city->production = $production_id;
                        $city->production_type = 'building';
                    }
                    break;
            }
        }
        $city->save();
    }
    foreach (['x', 'y', 'eat', 'eat_up', 'title', 'population', 'pwork', 'peat', 'pmoney', 'presearch',
                 'people_dis', 'people_norm', 'people_happy', 'people_artist',
                 'culture', 'culture_level'] as $field) {
        $data[$field] = $city->$field;
    }
    $data['culture_up'] = GameConfig::$CULTURE_LEVELS[$city->culture_level + 1];
    $data['possible_units'] = [];
    $data['possible_buildings'] = [];
    foreach ($units_possible as $unit) {
        $data['possible_units'][] = ['id' => $unit->id,
                                     'title' => $unit->getTitle(),
                                     'cost' => $unit->cost];
    }
    foreach ($buildings_possible as $building) {
        $data['possible_buildings'][] = ['id' => $building->id,
                                        'title' => $building->getTitle(),
                                        'cost' => $building->cost];
    }
    if ($city->production) {
        switch ($city->production_type) {
            case 'unit':
                $production = UnitType::get($city->production);
                break;
            case 'building':
                $production = BuildingType::get($city->production);
                break;
        }
        $data['production'] = ['id' => $city->production,
                               'type' => $city->production_type,
                               'cost' => $production->cost,
                               'title' => $production->getTitle(),
                               'complete' => $city->production_complete];
    } else {
        $data['production'] = false;
    }
    $data['people_cells'] = [];
    foreach ($city->people_cells as $cell) {
        $data['people_cells'][] = [	'x' => $cell->x,
                                    'y' => $cell->y,
                                    'work' => $cell->get_work(),
                                    'eat' => $cell->get_eat(),
                                    'money' => $cell->get_money()];
    }
    $data['buildings'] = [];
    foreach ($city->buildings as $building) {
        $data['buildings'][] = [
            'type' => $building->type,
            'title' => $building->getTitle()
        ];
    }
    $data['resources'] = [];
    foreach ($city->resources as $resource) {
        $data['resources'][] = [
            'type' => $resource['type']->id,
            'title' => $resource['type']->getTitle(),
            'count' => $resource['count']
        ];
    }
}
