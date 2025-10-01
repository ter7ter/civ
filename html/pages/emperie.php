<?php

use App\BuildingType;
use App\UnitType;
use App\Сity;
use App\User;

/** @var User $user */
if ($user->turn_status == 'play' && isset($_REQUEST['research_percent']) && (int)$_REQUEST['research_percent'] <= 10 && (int)$_REQUEST['research_percent'] >= 0) {
    $user->research_percent = (int)$_REQUEST['research_percent'];
    $cities = $user->get_cities();
    foreach ($cities as $city) {
        $city->calculate_people();
        $city->calculate_buildings();
    }
    $user->calculate_income();
    $user->save();
}
$cities = $user->get_cities();
$data['cities'] = [];
$data['all_amount'] = 0;
$data['income'] = $user->income;
$data['research_amount'] = $user->research_amount;
$data['research_percent'] = $user->research_percent;
$data['turn_status'] = $user->turn_status;
foreach ($cities as $city) {
    $production = 'нет';
    if ($city->production) {
        if ($city->production_type == 'unit') {
            $production = UnitType::get($city->production);
        } elseif ($city->production_type == 'building') {
            $production = BuildingType::get($city->production);
        } else {
            throw new Exception("Unknow production type ".$city->production_type);
        }
        $production = $production->getTitle().' '.ceil(($production->cost - $city->production_complete) / $city->pwork).' ходов ';
    }
    $data['cities'][] = ['id' => $city->id,
                         'title' => $city->getTitle(),
                         'population' => $city->population,
                         'production' => $production,
                         'pwork' => $city->pwork,
                         'peat' => $city->peat,
                         'pmoney' => $city->pmoney];
    $data['all_amount'] += $city->pmoney;
    $data['all_amount'] += $city->presearch;
}
