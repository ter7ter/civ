<?php

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
    $event = $user->get_next_event();
    if ($event) {
        $data['id'] = $event->id;
        $data['type'] = $event->type;
        if ($event->type == 'research') {
            $data['research_title'] = $event->object->get_title();
            $data['aresearch'] = [];
            $aresearch = $user->get_available_research();
            foreach ($aresearch as $research) {
                $data['aresearch'][] = ['id' => $research->id,
                                        'title' => $research->get_title(),
                                        'turns' => $user->get_research_need_turns($research)];
            }
        } elseif ($event->type == 'city_building' || $event->type == 'city_unit') {
            $city = $event->soruce;
            $data['city_id'] = $city->id;
            $data['city_title'] = $city->get_title();
            $data['build_title'] = $event->object->get_title();
            $data['possible_units'] = [];
            $data['possible_buildings'] = [];
            $units_possible = $city->get_possible_units();
            $buildings_possible = $city->get_possible_buildings();

            foreach ($units_possible as $unit) {
                $data['possible_units'][] = ['id' => $unit->id,
                    'title' => $unit->get_title(),
                    'turns' => ceil($unit->cost / $city->pwork) ];
            }
            foreach ($buildings_possible as $building) {
                $data['possible_buildings'][] = ['id' => $building->id,
                    'title' => $building->get_title(),
                    'turns' => ceil($building->cost / $city->pwork)];
            }
        }
    } else {
        $data['type'] = 'none';
    }
}
