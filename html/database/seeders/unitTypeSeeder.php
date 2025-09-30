<?php

require_once 'baseSeeder.php';

use App\UnitType;
use App\ResearchType;
use App\CellType;

setupDatabase();
checkTables(['unit_type']);
$clear = parseClearArgv($argv);
clearData($clear, ["DELETE FROM unit_type;"]);

// Загружаем типы клеток из базы
CellType::loadAll();
// Загружаем исследования
ResearchType::loadAll();

// Данные типов юнитов из цивилизации 3
$units = [
    [
        'title' => 'Поселенец',
        'points' => 1,
        'cost' => 30,
        'type' => 'land',
        'attack' => 0,
        'defence' => 1,
        'can_found_city' => true,
        'description' => 'Может основывать города'
    ],
    [
        'title' => 'Рабочий',
        'points' => 1,
        'cost' => 20,
        'type' => 'land',
        'attack' => 0,
        'defence' => 0,
        'can_build' => true,
        'description' => 'Строит улучшения'
    ],
    [
        'title' => 'Воин',
        'points' => 1,
        'cost' => 10,
        'type' => 'land',
        'attack' => 1,
        'defence' => 1,
        'description' => 'Базовый воин'
    ],
    [
        'title' => 'Копейщик',
        'points' => 1,
        'cost' => 20,
        'type' => 'land',
        'attack' => 1,
        'defence' => 2
    ],
    [
        'title' => 'Лучник',
        'points' => 1,
        'cost' => 30,
        'type' => 'land',
        'attack' => 3,
        'defence' => 2
    ],
    [
        'title' => 'Мечник',
        'points' => 1,
        'cost' => 30,
        'type' => 'land',
        'attack' => 2,
        'defence' => 2
    ],
    [
        'title' => 'Секироносек',
        'points' => 1,
        'cost' => 25,
        'type' => 'land',
        'attack' => 3,
        'defence' => 1
    ],
    [
        'title' => 'Колесница',
        'points' => 2,
        'cost' => 30,
        'type' => 'land',
        'attack' => 2,
        'defence' => 1
    ],
    [
        'title' => 'Конник',
        'points' => 2,
        'cost' => 50,
        'type' => 'land',
        'attack' => 2,
        'defence' => 1,
        'req_research' => [ResearchType::get(4)] // Верховая езда
    ],
    [
        'title' => 'Рыцарь',
        'points' => 2,
        'cost' => 80,
        'type' => 'land',
        'attack' => 4,
        'defence' => 2
    ],
    [
        'title' => 'Катапульта',
        'points' => 1,
        'cost' => 50,
        'type' => 'land',
        'attack' => 4,
        'defence' => 1
    ],
    [
        'title' => 'Требушет',
        'points' => 1,
        'cost' => 50,
        'type' => 'land',
        'attack' => 8,
        'defence' => 1
    ],
    [
        'title' => 'Галера',
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
        'title' => 'Каравелла',
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
        'title' => 'Бронирован',
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
        'title' => 'Истребитель',
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
        'title' => 'Бомбардировщик',
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
