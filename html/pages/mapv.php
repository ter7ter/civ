<?php
/** @var User $user */
if (isset($_REQUEST['cx']) && isset($_REQUEST['cy'])) {
    $x = (int)$_REQUEST['cx'];
    $y = (int)$_REQUEST['cy'];
} else {
    $coords = MyDB::query("SELECT x, y FROM city WHERE user_id = :uid LIMIT 1", ['uid' => $user->id], 'row');
    if (!$coords) {
        $coords = MyDB::query("SELECT x, y FROM unit WHERE user_id =:uid LIMIT 1", ['uid' => $user->id], 'row');
    }
    $x = $coords['x'];
    $y = $coords['y'];
}
$map = Cell::get_cells_around($x, $y, 11, 9);
$mapv = [];
$mapv = [];
foreach ($map as $row) {
    $rowv = [];
    foreach ($row as $cell) {
        $units = [];
        if (count($cell->units)) {
            foreach ($cell->units as $unit) {
                $data_unit = ['id' => $unit->id,
                    'type' => $unit->type->id,
                    'x' => $unit->x,
                    'y' => $unit->y,
                    'title' => $unit->get_title(),
                    'user_id' => $unit->user->id,
                    'health' => $unit->health,
                    'owner_name' => $unit->user->login,
                    'owner_color' => $unit->user->color,
                    'can_move' => $unit->type->can_move];
                if ($user == $unit->user) {
                    $data_unit['points'] = $unit->points;
                    $data_unit['max_points'] = $unit->type->points;
                }
                $units[] = $data_unit;
            }
        }
        $city = false;
        $info = ['title' => $cell->get_title(),
            'type' => $cell->type->id,
            'x' => $cell->x,
            'y' => $cell->y,
            'city' => $city,
            'road' => $cell->road,
            'units' => $units,
            'resource_id' => false];
        if ($cell->city) {
            $city = ['id' => $cell->city->id,
                'user_id' => $cell->city->user->id,
                'title' => $cell->city->title,
                'x' => $cell->city->x,
                'y' => $cell->city->y,
                'population' => $cell->city->population];
            $info['city'] = $city;
            $info['owner_name'] = $cell->city->user->login;
            $info['owner_color'] = $cell->city->user->color;
        } else {
            if ($cell->owner) {
                $info['owner_name'] = $cell->owner->login;
                $info['owner_color'] = $cell->owner->color;
            } else {
                $info['owner_name'] = false;
            }
            $info['city'] = false;
        }
        if ($cell->resource && $cell->resource->type && $cell->resource->type->can_use($user)) {
            $info['resource_id'] = $cell->resource->type->id;
            $info['resource_title'] = $cell->resource->type->get_title();
        }
        if ($cell->improvement) {
            $info['improvement'] = $cell->improvement;
            $mtype = MissionType::get($cell->improvement);
            if ($mtype) {
                $info['improvement_title'] = $mtype->get_title();
            } else {
                $info['improvement_title'] = 'Неизв. улучшение';
            }
        } else {
            $info['improvement'] = false;
        }

        $rowv[] = $info;
    }
    $mapv[] = $rowv;
}
$data['mapv'] = $mapv;
$data['max_x'] = Cell::$map_width - 1;
$data['max_y'] = Cell::$map_height - 1;
$data['center_x'] = $x;
$data['center_y'] = $y;
$data['user_id'] = $user->id;
$data['turn_status'] = $user->turn_status;
?>