<?php
/** @var User $user */
$x = (isset($_REQUEST['x'])) ? (int)$_REQUEST['x'] : 0;
$y = (isset($_REQUEST['y'])) ? (int)$_REQUEST['y'] : 0;
$unit_id = (isset($_REQUEST['unit_id'])) ? (int)$_REQUEST['unit_id'] : false;
$cell = Cell::get($x, $y);
if ($cell) {
	$building = false;
	$data['x'] = $cell->x;
	$data['y'] = $cell->y;
	$data['type'] = $cell->type->id;
    $data['road'] = $cell->road;
	$data['title'] = $cell->get_title();
	$data['work'] = $cell->get_work();
	$data['eat'] = $cell->get_eat();
	$data['money'] = $cell->get_money();
    $data['user_login'] = $user->login;
	$data['user_money'] = $user->money;
	$data['user_age'] = $user->age;
	$data['user_income'] = ($user->income < 0) ? $user->income : '+'.$user->income;
	$data['turn_status'] = $user->turn_status;
	$game = Game::get($user->game);
	$data['players'] = [];
	foreach ($game->users as $player) {
	    $data['players'][] = [
	        'id' => $player->id,
            'login' => $player->login,
            'turn_order' => $player->turn_order,
            'color' => $player->color,
            'turn_status' => $player->turn_status
        ];
    }
    $data['turn_num'] = $game->turn_num;
	if ($cell->owner) {
        $data['owner_name'] = $cell->owner->login;
        $data['owner_culture'] = $cell->owner_culture;
    } else {
	    $data['owner_name'] = false;
    }
	if ($cell->road) {
	    $data['road'] = 'дорога';
    }
	if ($cell->improvement) {
        $data['improvement'] = Cell::$UPGRADE_NAMES[$cell->improvement];
    }
	if ($cell->resource) {
        $data['resource'] = $cell->resource->get_title();
    }
	if ($user->process_research_type) {
		$data['user_research_type'] = $user->process_research_type->get_title();
		$data['user_research_turns'] = $user->get_research_need_turns();
		if (!$data['user_research_turns']) {
			$data['user_research_turns'] = '--';
		}
	} else {
		$data['user_research_type'] = false;
	}
	if ($unit_id) {
		$unit = Unit::get($unit_id);
		if ($unit && $unit->user == $user) {
		    if ($unit->mission) {
                $mission = $unit->mission->get_title();
            } else {
                $mission = false;
            }

			$data['unit'] = ['type' => $unit->type->id,
							 'title' => $unit->get_title(),
							 'points' => $unit->points,
							 'max_points' => $unit->type->points,
                             'health' => $unit->health,
                             'health_max' => $unit->health_max,
                             'owner_name' => $unit->user->login,
                             'mission' => $mission,
							 'missions' => []];
			if ($unit->points > 0) {
				$mtypes = $unit->get_mission_types();
				foreach ($mtypes as $mtype) {
					$data['unit']['missions'][] = ['id' => $mtype->id,
												   'title' => $mtype->get_title(),
												   'points' => (isset($mtype->need_points[$cell->type->id])) ? $mtype->need_points[$cell->type->id] : 0,
												   'unit_lost' => $mtype->unit_lost];
				}
			}
		}
	}
} else {
	$error = '404 Not found';
}
?>