<?php

require_once 'baseSeeder.php';

use App\MyDB;
use App\CellType;

setupDatabase();
checkTables(['cell_type']);
$clear = parseClearArgv($argv);
clearData($clear, ["DELETE FROM cell_type;"]);

// Данные типов клеток из ct.data
$cellTypes = [
    [
        'id' => 'plains',
        'title' => 'равнина',
        'base_chance' => 15,
        'chance_inc1' => 8,
        'chance_inc2' => 6,
        'eat' => 2,
        'work' => 1,
        'money' => 1,
        'chance_inc_other' => ['mountains' => [15, 8]],
        'border_no' => ['water2', 'water3']
    ],
    [
        'id' => 'plains2',
        'title' => 'равнина',
        'base_chance' => 15,
        'chance_inc1' => 8,
        'chance_inc2' => 6,
        'eat' => 2,
        'work' => 0,
        'money' => 1,
        'chance_inc_other' => ['plains2' => [15, 8]],
        'border_no' => ['water2', 'water3']
    ],
    [
        'id' => 'forest',
        'title' => 'лес',
        'base_chance' => 15,
        'chance_inc1' => 10,
        'chance_inc2' => 6,
        'eat' => 1,
        'work' => 2,
        'money' => 1,
        'border_no' => ['water2', 'water3']
    ],
    [
        'id' => 'hills',
        'title' => 'холмы',
        'base_chance' => 10,
        'chance_inc1' => 5,
        'chance_inc2' => 3,
        'eat' => 1,
        'work' => 2,
        'money' => 0,
        'chance_inc_other' => ['mountains' => [3, 2]],
        'border_no' => ['water2', 'water3']
    ],
    [
        'id' => 'mountains',
        'title' => 'горы',
        'base_chance' => 4,
        'chance_inc1' => 5,
        'chance_inc2' => 2,
        'eat' => 0,
        'work' => 1,
        'money' => 1,
        'chance_inc_other' => ['hills' => [3, 2]],
        'border_no' => ['water2', 'water3']
    ],
    [
        'id' => 'desert',
        'title' => 'пустыня',
        'base_chance' => 7,
        'chance_inc1' => 6,
        'chance_inc2' => 4,
        'eat' => 0,
        'work' => 1,
        'money' => 2,
        'border_no' => ['water2', 'water3']
    ],
    [
        'id' => 'water1',
        'title' => 'вода',
        'base_chance' => 5,
        'chance_inc1' => 20,
        'chance_inc2' => 15,
        'eat' => 2,
        'work' => 0,
        'money' => 1,
        'chance_inc_other' => ['water2' => [25, 11]],
        'border_no' => ['water3']
    ],
    [
        'id' => 'water2',
        'title' => 'море',
        'base_chance' => 0,
        'chance_inc1' => 35,
        'chance_inc2' => 16,
        'eat' => 1,
        'work' => 0,
        'money' => 0,
        'chance_inc_other' => ['water1' => [14, 8], 'water3' => [20, 10]],
        'border_no' => ['plains', 'plains2', 'forest', 'hills', 'mountains']
    ],
    [
        'id' => 'water3',
        'title' => 'океан',
        'base_chance' => 0,
        'chance_inc1' => 35,
        'chance_inc2' => 17,
        'eat' => 1,
        'work' => 0,
        'money' => 0,
        'chance_inc_other' => ['water2' => [10, 6]],
        'border_no' => ['plains', 'plains2', 'forest', 'hills', 'mountains', 'water1']
    ]
];

// Создаем объекты CellType и сохраняем
foreach ($cellTypes as $data) {
    $ct = new CellType($data);
    $ct->save();
}

echo "Seeder выполнен успешно. Добавлено " . count($cellTypes) . " типов клеток.\n";
