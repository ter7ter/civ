<?php

use App\User;
use App\Unit;
use App\Cell;
use App\Game;

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
            $game = Game::get($user->game);
            $planet = $game->get_first_planet();
            $cell = Cell::get($x, $y, $planet->id);
            if (!$cell) {
                $error = "Cell not found";
                break;
            }
            if (!$unit->moveTo($cell)) {
                $error = "Can not move";
                break;
            }
            break;
        case 'move_to':
            if (!is_array($_REQUEST['path'])) {
                break;
            }
            $unit->movePath($_REQUEST['path']);
            $unit->calculate();
            break;
        case 'build_road_to':
            if (!is_array($_REQUEST['path'])) {
                break;
            }
            $path = $_REQUEST['path'];
            $unit->roadPath($path);
            $unit->calculate();
            break;
        case 'mission':
            if (!isset($_REQUEST['mission'])) {
                $error = "Mission not found";
                break;
            }
            $mtypes = $unit->getMissionTypes();
            if (!isset($mtypes[$_REQUEST['mission']])) {
                $error = "Mission not found";
                break;
            }
            
            $mission_types = $unit->getMissionTypes();
            if (!isset($mission_types[$_REQUEST['mission']])) {
                $error = "Mission not found";
                break;
            }
            
            // Для миссии атаки нужно передавать координаты цели
            if ($_REQUEST['mission'] === 'attack') {
                if (!isset($_REQUEST['x']) || !isset($_REQUEST['y'])) {
                    $error = "Target coordinates not specified for attack";
                    break;
                }
                
                // Проверяем, что координаты находятся рядом с юнитом
                $target_x = (int)$_REQUEST['x'];
                $target_y = (int)$_REQUEST['y'];
                
                // Проверяем, что целевая клетка находится в пределах одного шага от юнита
                $dx = abs($unit->x - $target_x);
                $dy = abs($unit->y - $target_y);
                
                // Учитываем цикличность карты
                $max_x = Cell::$map_width - 1;
                $max_y = Cell::$map_height - 1;
                if ($dx > $max_x / 2) {
                    $dx = $max_x - $dx;
                }
                if ($dy > $max_y / 2) {
                    $dy = $max_y - $dy;
                }
                
                if ($dx > 1 || $dy > 1 || ($dx == 0 && $dy == 0)) {
                    $error = "Target is too far for attack";
                    break;
                }
                
                // Проверяем, есть ли вражеские юниты на целевой клетке
                $target_cell = Cell::get($target_x, $target_y, $unit->planet);
                if (!$target_cell) {
                    $error = "Target cell not found";
                    break;
                }
                
                $target_cell->get_units();
                $has_enemy = false;
                foreach ($target_cell->units as $target_unit) {
                    if ($target_unit->user->id != $unit->user->id) {
                        $has_enemy = true;
                        break;
                    }
                }
                
                if (!$has_enemy) {
                    $error = "No enemy units at target location";
                    break;
                }
                
                // Выполняем атаку - юнит перемещается на клетку с целью и начинает атаку
                $unit->x = $target_x;
                $unit->y = $target_y;

            }
            $title = '';
            if (isset($_REQUEST['title'])) {
                $title = htmlspecialchars($_REQUEST['title']);
            }
            $result_message = null;
            $result = $unit->startMission($mission_types[$_REQUEST['mission']], $result_message, $title);
            if (!$result) {
                $error = $result_message;
            }
            if ($result === 'unit_lost' || is_null($unit)) {
                $data['unit_lost'] = 1;
            }
            break;
        case 'cancel_mission':
            $unit->cancelMission();
            break;
    }

}
if (!$error) {
    $data['points'] = $unit->points;
    $data['id'] = $unit->id;
}
