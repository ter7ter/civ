<?php

use App\Event;
use App\Сity;
use App\User;

/** @var User $user */
if ($user->turn_status == 'play') {
    if (isset($_REQUEST['del'])) {
        $event = Event::get((int)$_REQUEST['del']);
        if ($event) {
            $event->remove();
        }
    }
    $event = $user->getNextEvent();
    if ($event) {
        $data['id'] = $event->id;
        $data['type'] = $event->type;
        if ($event->type == 'research') {
            $data['research_title'] = $event->object->getTitle();
            $data['aresearch'] = [];
            $aresearch = $user->getAvailableResearch();
            foreach ($aresearch as $research) {
                $data['aresearch'][] = ['id' => $research->id,
                                        'title' => $research->getTitle(),
                                        'turns' => $user->getResearchNeedTurns($research)];
            }
        } elseif ($event->type == 'city_building' || $event->type == 'city_unit') {
            $city = $event->soruce;
            $data['city_id'] = $city->id;
            $data['city_title'] = $city->getTitle();
            $data['build_title'] = $event->object->getTitle();
            $data['possible_units'] = [];
            $data['possible_buildings'] = [];
            $units_possible = $city->get_possible_units();
            $buildings_possible = $city->get_possible_buildings();

            foreach ($units_possible as $unit) {
                $data['possible_units'][] = ['id' => $unit->id,
                    'title' => $unit->getTitle(),
                    'turns' => ceil($unit->cost / $city->pwork) ];
            }
            foreach ($buildings_possible as $building) {
                $data['possible_buildings'][] = ['id' => $building->id,
                    'title' => $building->getTitle(),
                    'turns' => ceil($building->cost / $city->pwork)];
            }
        }
    } else {
        $data['type'] = 'none';
    }
}
