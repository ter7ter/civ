<?php
/** @var User $user */
$uid = @$_REQUEST['uid'];
if ($user->turn_status != 'play') {
    $error = "Not your turn";
} elseif (!$uid || !$unit = Unit::get($uid)) {
	$error = "Unit not found";
} elseif ($unit->user != $user) {
	$error = "Unit not you";
} else {
	$x = (isset($_REQUEST['x'])) ? (int)$_REQUEST['x'] : $unit->x;
	$y = (isset($_REQUEST['y'])) ? (int)$_REQUEST['y'] : $unit->y;
	$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : 'move';
	switch ($action) {
		case 'move':
			$cell = Cell::get($x, $y);
			if (!$cell) {
				$error = "Cell not found";
				break;
			}
			if (!$unit->move_to($cell)) {
				$error = "Can not move";
				break;
			}
		break;
        case 'move_to':
            if (!is_array($_REQUEST['path'])) {
                break;
            }
            $unit->move_path($_REQUEST['path']);
            $unit->calculate();
        break;
		case 'mission':
			if (!isset($_REQUEST['mission'])) {
				$error = "Mission not found";
				break;
			}
			$mtypes = $unit->get_mission_types();
			if (!isset($mtypes[$_REQUEST['mission']])) {
				$error = "Mission not found";
				break;
			}
			$title = '';
			if (isset($_REQUEST['title'])) {
				$title = htmlspecialchars($_REQUEST['title']);
			}
			$result = $unit->start_mission($mtypes[$_REQUEST['mission']], $title);
			if (!$result) {
				$error = "Неудалось";
			}
			if ($result === 'unit_lost') {
				$data['unit_lost'] = 1;
			}
		break;
	}
	
}
if (!$error) {
	$data['points'] = $unit->points;
    $data['id'] = $unit->id;
}
?>