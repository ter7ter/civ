<?php

require_once 'baseSeeder.php';

use App\UnitType;
use App\ResearchType;
use App\CellType;

setupDatabase();
checkTables(['unit_type']);
$clear = ($SHOULD_CLEAN ?? false) || parseClearArgv($argv);
clearData($clear, ["DELETE FROM unit_type;"]);

// Загружаем типы клеток из базы
CellType::loadAll();
// Загружаем исследования
ResearchType::loadAll();

// Данные типов юнитов из цивилизации 3
$units = [
    [
        'id' => 1,
        'title' => 'Поселенец',
        'image_file' => 'settler.svg',
        'points' => 1,
        'cost' => 30,
        'type' => 'land',
        'attack' => 0,
        'defence' => 1,
        'can_found_city' => true,
        'missions' => ['move_to', 'build_city'],
        'description' => 'Может основывать города',
    ],
    [
        'id' => 2,
        'title' => 'Рабочий',
        'image_file' => 'worker.svg',
        'points' => 1,
        'cost' => 20,
        'type' => 'land',
        'attack' => 0,
        'defence' => 0,
        'can_build' => true,
        'missions' => ['move_to', 'build_road', 'build_road_to', 'mine', 'irrigation'],
        'description' => 'Строит улучшения'
    ],
    [
        'id' => 3,
        'title' => 'Воин',
        'image_file' => 'warrior.svg',
        'points' => 1,
        'cost' => 10,
        'type' => 'land',
        'attack' => 1,
        'defence' => 1,
        'missions' => ['move_to', 'attack'],
        'description' => 'Базовый воин'
    ],
    [
        'id' => 4,
        'title' => 'Копейщик',
        'image_file' => 'spearman.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 1,
        'cost' => 20,
        'type' => 'land',
        'attack' => 1,
        'defence' => 2,
    ],
    [
        'id' => 5,
        'title' => 'Лучник',
        'image_file' => 'archer.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 1,
        'cost' => 30,
        'type' => 'land',
        'attack' => 3,
        'defence' => 2
    ],
    [
        'id' => 6,
        'title' => 'Мечник',
        'image_file' => 'swordsman.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 1,
        'cost' => 30,
        'type' => 'land',
        'attack' => 2,
        'defence' => 2
    ],
    [
        'id' => 7,
        'title' => 'Секироносек',
        'image_file' => 'axeman.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 1,
        'cost' => 25,
        'type' => 'land',
        'attack' => 3,
        'defence' => 1
    ],
    [
        'id' => 8,
        'title' => 'Колесница',
        'image_file' => 'chariot.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 2,
        'cost' => 30,
        'type' => 'land',
        'attack' => 2,
        'defence' => 1
    ],
    [
        'id' => 9,
        'title' => 'Конник',
        'image_file' => 'horseman.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 2,
        'cost' => 50,
        'type' => 'land',
        'attack' => 2,
        'defence' => 1,
        'req_research' => [ResearchType::get(4)] // Верховая езда
    ],
    [
        'id' => 10,
        'title' => 'Рыцарь',
        'image_file' => 'knight.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 2,
        'cost' => 80,
        'type' => 'land',
        'attack' => 4,
        'defence' => 2
    ],
    [
        'id' => 11,
        'title' => 'Катапульта',
        'image_file' => 'catapult.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 1,
        'cost' => 50,
        'type' => 'land',
        'attack' => 4,
        'defence' => 1
    ],
    [
        'id' => 12,
        'title' => 'Требушет',
        'image_file' => 'trebuchet.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 1,
        'cost' => 50,
        'type' => 'land',
        'attack' => 8,
        'defence' => 1
    ],
    [
        'id' => 13,
        'title' => 'Галера',
        'image_file' => 'galley.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 3,
        'cost' => 30,
        'type' => 'water',
        'attack' => 1,
        'defence' => 2,
        'can_move' => [
            'water1' => 1,
            'water2' => 2,
            'water3' => 2
        ]
    ],
    [
        'id' => 14,
        'title' => 'Каравелла',
        'image_file' => 'caravel.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 3,
        'cost' => 50,
        'type' => 'water',
        'attack' => 2,
        'defence' => 3,
        'can_move' => [
            'water1' => 1,
            'water2' => 1,
            'water3' => 1
        ]
    ],
    [
        'id' => 15,
        'title' => 'Бронирован',
        'image_file' => 'ironclad.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 4,
        'cost' => 80,
        'type' => 'water',
        'attack' => 4,
        'defence' => 4,
        'can_move' => [
            'water1' => 1,
            'water2' => 1,
            'water3' => 1
        ]
    ],
    [
        'id' => 16,
        'title' => 'Истребитель',
        'image_file' => 'fighter.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 5,
        'cost' => 60,
        'type' => 'air',
        'attack' => 5,
        'defence' => 3,
        'can_move' => [
            'plains' => 1,
            'plains2' => 1,
            'forest' => 1,
            'hills' => 1,
            'mountains' => 1,
            'desert' => 1,
            'water1' => 1,
            'water2' => 1,
            'water3' => 1,
        ]
    ],
    [
        'id' => 17,
        'title' => 'Бомбардировщик',
        'image_file' => 'bomber.svg',
        'missions' => ['move_to', 'attack'],
        'points' => 4,
        'cost' => 90,
        'type' => 'air',
        'attack' => 8,
        'defence' => 3,
        'can_move' => [
            'plains' => 1,
            'plains2' => 1,
            'forest' => 1,
            'hills' => 1,
            'mountains' => 1,
            'desert' => 1,
            'water1' => 1,
            'water2' => 1,
            'water3' => 1,
        ]
    ]
];

// Создаем объекты UnitType и сохраняем
foreach ($units as $data) {
    $can_move = $data['can_move'] ?? null;
    unset($data['can_move']);
    if ($can_move) {
        // Добавляем всем юнитам перемещение по city
        $can_move['city'] = 1;
        // Для юнитов не типа 'water' добавляем остальные типы клеток
        if ($data['type'] !== 'water') {
            $default_land_moves = [
                "plains" => 1,
                "plains2" => 1,
                "forest" => 1,
                "hills" => 1,
                "mountains" => 2,
                "desert" => 1,
            ];
            $can_move = array_merge($default_land_moves, $can_move);
        }
    }
    $unit = new UnitType($data);
    $unit->save();
    if ($can_move) {
        $unit->can_move = $can_move;
        $unit->save();
    }
}

echo "Seeder выполнен успешно. Добавлено " . count($units) . " типов юнитов.\n";
