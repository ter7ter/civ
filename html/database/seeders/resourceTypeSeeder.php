<?php

require_once 'baseSeeder.php';

use App\ResourceType;
use App\ResearchType;
use App\CellType;
use App\MyDB;

setupDatabase();
checkTables(['resource_type']);
$clear = parseClearArgv($argv);
clearData($clear, ["DELETE FROM resource_type;"]);

// Загружаем типы клеток из базы
CellType::loadAll();

// Данные ресурсов из rt.data
$resources = [
    [
        'id' => 'iron',
        'title' => 'железо',
        'type' => 'mineral',
        'work' => 2,
        'money' => 1,
        'chance' => 0.015,
        'req_research' => [
            //ResearchType::get(7) // Обработка железа
        ],
        'cell_types' => [
            CellType::get('hills'),
            CellType::get('mountains')
        ]
    ],
    [
        'id' => 'horse',
        'title' => 'лошади',
        'type' => 'mineral',
        'work' => 1,
        'money' => 1,
        'chance' => 0.02,
        'req_research' => [
            ResearchType::get(4) // Верховая езда
        ],
        'cell_types' => [
            CellType::get('plains'),
            CellType::get('plains2')
        ]
    ],
    [
        'id' => 'coal',
        'title' => 'уголь',
        'type' => 'mineral',
        'work' => 2,
        'money' => 1,
        'chance' => 0.015,
        'req_research' => [
            //ResearchType::get(4) // Верховая езда
        ],
        'cell_types' => [
            CellType::get('hills'),
            CellType::get('mountains')
        ]
    ],
    [
        'id' => 'oil',
        'title' => 'нефть',
        'type' => 'mineral',
        'work' => 2,
        'money' => 2,
        'chance' => 0.01,
        'req_research' => [
            //ResearchType::get(4) // Верховая езда
        ],
        'cell_types' => [
            CellType::get('desert'),
            CellType::get('plains'),
            CellType::get('plains2')
        ]
    ],
    [
        'id' => 'saltpetre',
        'title' => 'селитра',
        'type' => 'mineral',
        'work' => 2,
        'money' => 1,
        'chance' => 0.01,
        'req_research' => [
            //ResearchType::get(4) // Верховая езда
        ],
        'cell_types' => [
            CellType::get('desert'),
            CellType::get('plains'),
            CellType::get('plains2'),
            CellType::get('hills'),
            CellType::get('mountains')
        ]
    ],
    [
        'id' => 'rubber',
        'title' => 'резина',
        'type' => 'mineral',
        'work' => 1,
        'money' => 2,
        'chance' => 0.01,
        'req_research' => [
            //ResearchType::get(4) // Верховая езда
        ],
        'cell_types' => [
            CellType::get('desert'),
            CellType::get('plains'),
            CellType::get('plains2'),
            CellType::get('mountains')
        ]
    ],
    [
        'id' => 'uranium',
        'title' => 'уран',
        'type' => 'mineral',
        'work' => 1,
        'money' => 1,
        'chance' => 0.005,
        'req_research' => [
            //ResearchType::get(4) // Верховая езда
        ],
        'cell_types' => [
            CellType::get('desert'),
            CellType::get('hills'),
            CellType::get('mountains')
        ]
    ],
    [
        'id' => 'vine',
        'title' => 'виноград',
        'type' => 'luxury',
        'eat' => 1,
        'money' => 2,
        'chance' => 0.02,
        'cell_types' => [
            CellType::get('plains'),
            CellType::get('plains2')
        ]
    ],
    [
        'id' => 'ivory',
        'title' => 'слоновая кость',
        'type' => 'luxury',
        'work' => 1,
        'money' => 2,
        'chance' => 0.01,
        'cell_types' => [
            CellType::get('desert')
        ]
    ],
    [
        'id' => 'silk',
        'title' => 'шёлк',
        'type' => 'luxury',
        'work' => 2,
        'money' => 1,
        'chance' => 0.02,
        'cell_types' => [
            CellType::get('plains'),
            CellType::get('plains2'),
            CellType::get('hills')
        ]
    ],
    [
        'id' => 'furs',
        'title' => 'меха',
        'type' => 'luxury',
        'work' => 1,
        'eat' => 1,
        'money' => 1,
        'chance' => 0.02,
        'cell_types' => [
            CellType::get('forest')
        ]
    ],
    [
        'id' => 'fish',
        'title' => 'рыба',
        'type' => 'bonus',
        'chance' => 0.05,
        'eat' => 2,
        'cell_types' => [
            CellType::get('water1')
        ]
    ],
    [
        'id' => 'whale',
        'title' => 'киты',
        'type' => 'bonus',
        'chance' => 0.03,
        'eat' => 1,
        'money' => 1,
        'cell_types' => [
            CellType::get('water2')
        ]
    ]
];

// Создаем объекты ResourceType и сохраняем
foreach ($resources as $data) {
    $rt = new ResourceType($data);
    $rt->save();
}

echo "Seeder выполнен успешно. Добавлено " . count($resources) . " ресурсов.\n";
