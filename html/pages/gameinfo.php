<?php

use App\Game;

if ((int)$_REQUEST['id']) {
    $game = Game::get((int)$_REQUEST['id']);
    foreach (['id', 'name', 'map_w', 'map_h'] as $field) {
        $data[$field] = $game->$field;
    }
    $data['users'] = [];
    foreach ($game->users as $user) {
        $udata = [];
        foreach (['id', 'login', 'color'] as $field) {
            $udata[$field] = $user->$field;
        }
        $data['users'][] = $udata;
    }
}
