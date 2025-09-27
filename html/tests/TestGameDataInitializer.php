<?php

namespace App\Tests;

use App\MyDB;
use App\CellType;
use App\ResourceType;
use App\ResearchType;
use App\BuildingType;
use App\UnitType;
use App\MissionType;

/**
 * Инициализатор тестовых игровых данных
 * Содержит функции для инициализации базовых типов игровых объектов для тестов
 */
class TestGameDataInitializer
{
    /**
     * Инициализирует все базовые типы данных для тестов
     */
    public static function initializeAll(): void
    {
        self::initializeCellTypes();
        self::initializeResearchTypes();
        self::initializeResourceTypes();
        self::initializeBuildingTypes();
        self::initializeUnitTypes();
        self::initializeMissionTypes();
    }

    /**
     * Инициализирует типы клеток
     */
    public static function initializeCellTypes(): void
    {
        // Очищаем старые данные
        CellType::$all = [];

        $cellTypes = [
            [
                "id" => "plains",
                "title" => "Равнины",
                "work" => 1,
                "eat" => 2,
                "money" => 0,
                "base_chance" => 15,
                "chance_inc1" => 8,
                "chance_inc2" => 6,
                "chance_inc_other" => ["mountains" => [15, 8]],
                "border_no" => ["water2", "water3"],
            ],
            [
                "id" => "plains2",
                "title" => "Равнины 2",
                "work" => 2,
                "eat" => 2,
                "money" => 0,
                "base_chance" => 10,
                "chance_inc1" => 5,
                "chance_inc2" => 3,
                "chance_inc_other" => [],
                "border_no" => ["water2", "water3"],
            ],
            [
                "id" => "forest",
                "title" => "Лес",
                "work" => 2,
                "eat" => 1,
                "money" => 0,
                "base_chance" => 12,
                "chance_inc1" => 6,
                "chance_inc2" => 4,
                "chance_inc_other" => [],
                "border_no" => ["water2", "water3"],
            ],
            [
                "id" => "hills",
                "title" => "Холмы",
                "work" => 1,
                "eat" => 1,
                "money" => 0,
                "base_chance" => 8,
                "chance_inc1" => 4,
                "chance_inc2" => 2,
                "chance_inc_other" => [],
                "border_no" => ["water2", "water3"],
            ],
            [
                "id" => "water",
                "title" => "Вода",
                "work" => 0,
                "eat" => 2,
                "money" => 2,
                "base_chance" => 5,
                "chance_inc1" => 10,
                "chance_inc2" => 8,
                "chance_inc_other" => [],
                "border_no" => [],
            ],
            [
                "id" => "water1",
                "title" => "Прибрежная вода",
                "work" => 0,
                "eat" => 3,
                "money" => 2,
                "base_chance" => 7,
                "chance_inc1" => 12,
                "chance_inc2" => 10,
                "chance_inc_other" => [],
                "border_no" => [],
            ],
            [
                "id" => "desert",
                "title" => "Пустыня",
                "work" => 0,
                "eat" => 0,
                "money" => 1,
                "base_chance" => 3,
                "chance_inc1" => 2,
                "chance_inc2" => 1,
                "chance_inc_other" => [],
                "border_no" => ["water2", "water3"],
            ],
            [
                "id" => "mountains",
                "title" => "Горы",
                "work" => 1,
                "eat" => 0,
                "money" => 0,
                "base_chance" => 5,
                "chance_inc1" => 3,
                "chance_inc2" => 2,
                "chance_inc_other" => [],
                "border_no" => ["water2", "water3"],
            ],
            [
                "id" => "tundra",
                "title" => "Тундра",
                "work" => 1,
                "eat" => 1,
                "money" => 0,
                "base_chance" => 4,
                "chance_inc1" => 2,
                "chance_inc2" => 1,
                "chance_inc_other" => [],
                "border_no" => ["water2", "water3"],
            ],
        ];

        foreach ($cellTypes as $type) {
            new CellType($type);
        }
    }

    /**
     * Инициализирует типы ресурсов
     */
    public static function initializeResourceTypes(): void
    {
        if (!empty(ResourceType::getAll())) {
            return; // Уже инициализированы
        }

        $resourceTypes = [
            [
                "id" => 'iron',
                "title" => "железо",
                "type" => "mineral",
                "work" => 2,
                "eat" => 0,
                "money" => 1,
                "req_research" => [], // Обработка железа - оставим пустым для тестов
                "cell_types" => [CellType::get("hills"), CellType::get("mountains")],
                "chance" => 0.015,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'horse',
                "title" => "лошади",
                "type" => "mineral",
                "work" => 1,
                "eat" => 0,
                "money" => 1,
                "req_research" => [ResearchType::getByTitle('Верховая езда')], // Верховая езда
                "cell_types" => [CellType::get("plains"), CellType::get("plains2")],
                "chance" => 0.02,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'coal',
                "title" => "уголь",
                "type" => "mineral",
                "work" => 2,
                "eat" => 0,
                "money" => 1,
                "req_research" => [],
                "cell_types" => [CellType::get("hills"), CellType::get("mountains")],
                "chance" => 0.01,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'oil',
                "title" => "нефть",
                "type" => "mineral",
                "work" => 2,
                "eat" => 0,
                "money" => 2,
                "req_research" => [],
                "cell_types" => [CellType::get("desert"), CellType::get("plains"), CellType::get("plains2")],
                "chance" => 0.01,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'saltpetre',
                "title" => "селитра",
                "type" => "mineral",
                "work" => 2,
                "eat" => 0,
                "money" => 1,
                "req_research" => [],
                "cell_types" => [CellType::get("desert"), CellType::get("plains"), CellType::get("plains2"), CellType::get("hills"), CellType::get("mountains")],
                "chance" => 0.01,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'rubber',
                "title" => "резина",
                "type" => "mineral",
                "work" => 1,
                "eat" => 0,
                "money" => 2,
                "req_research" => [],
                "cell_types" => [CellType::get("desert"), CellType::get("plains"), CellType::get("plains2"), CellType::get("mountains")],
                "chance" => 0.01,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'uranium',
                "title" => "уран",
                "type" => "mineral",
                "work" => 1,
                "eat" => 0,
                "money" => 1,
                "req_research" => [],
                "cell_types" => [CellType::get("desert"), CellType::get("hills"), CellType::get("mountains")],
                "chance" => 0.01,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'vine',
                "title" => "виноград",
                "type" => "luxury",
                "work" => 0,
                "eat" => 1,
                "money" => 2,
                "req_research" => [],
                "cell_types" => [CellType::get("plains"), CellType::get("plains2")],
                "chance" => 0.02,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'ivory',
                "title" => "слоновая кость",
                "type" => "luxury",
                "work" => 1,
                "eat" => 0,
                "money" => 2,
                "req_research" => [],
                "cell_types" => [CellType::get("desert")],
                "chance" => 0.01,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'silk',
                "title" => "шёлк",
                "type" => "luxury",
                "work" => 2,
                "eat" => 0,
                "money" => 1,
                "req_research" => [],
                "cell_types" => [CellType::get("plains"), CellType::get("plains2"), CellType::get("hills")],
                "chance" => 0.02,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'furs',
                "title" => "меха",
                "type" => "luxury",
                "work" => 1,
                "eat" => 1,
                "money" => 1,
                "req_research" => [],
                "cell_types" => [CellType::get("forest")],
                "chance" => 0.01,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'fish',
                "title" => "рыба",
                "type" => "bonuce",
                "work" => 0,
                "eat" => 2,
                "money" => 0,
                "req_research" => [],
                "cell_types" => [CellType::get("water1")],
                "chance" => 0.05,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
            [
                "id" => 'whale',
                "title" => "киты",
                "type" => "bonuce",
                "work" => 0,
                "eat" => 1,
                "money" => 1,
                "req_research" => [],
                "cell_types" => [CellType::get("water2")],
                "chance" => 0.03,
                "min_amount" => 50,
                "max_amount" => 500,
            ],
        ];

        foreach ($resourceTypes as $type) {
            new ResourceType($type);
        }
    }

    /**
     * Инициализирует типы исследований
     */
    public static function initializeResearchTypes(): void
    {
        if (!empty(ResearchType::getAllLoaded())) {
            return; // Уже инициализированы
        }

        ResearchType::clearAll(); // Очищаем кэш

        $rt1 = new ResearchType(["title" => "Гончарное дело",
            "age" => 1,
            "cost" => 50,
            "m_top" => 30,
            "m_left" => 30,
            "age_need" => true]);
        $rt1->save();

        $rt2 = new ResearchType(["title" => "Бронзовое дело",
                "age" => 1,
                "cost" => 80,
                "m_top" => 130,
                "m_left" => 30,
                "age_need" => true]);
        $rt2->addRequirement($rt1);
        $rt2->save();

        $rt3 = new ResearchType([
                "title" => "Животноводство",
                "age" => 1,
                "cost" => 60,
                "m_top" => 230,
                "m_left" => 30,
                "age_need" => true,
            ]);
        $rt3->save();

        $rt4 = new ResearchType([
            "title" => "Верховая езда",
            "age" => 1,
            "cost" => 80,
            "m_top" => 330,
            "m_left" => 30,
            "age_need" => true,
        ]);
        $rt4->addRequirement($rt3);
        $rt4->save();

        $rt5 = new ResearchType([
            "title" => "Верховая езда",
            "age" => 1,
            "cost" => 80,
            "m_top" => 330,
            "m_left" => 30,
            "age_need" => true,
        ]);
        $rt5->addRequirement($rt1);
        $rt5->save();

        $rt6 = new ResearchType([
            "title" => "Мистицизм",
            "age" => 1,
            "cost" => 60,
            "m_top" => 530,
            "m_left" => 30,
            "age_need" => true,
        ]);
        $rt6->save();

        $rt7 = new ResearchType([
            "title" => "Обработка железа",
            "age" => 1,
            "cost" => 150,
            "m_top" => 30,
            "m_left" => 300,
            "age_need" => true,
        ]);
        $rt7->addRequirement($rt1);
        $rt7->save();

        $rt8 = new ResearchType([
            "title" => "Математика",
            "age" => 1,
            "cost" => 150,
            "m_top" => 130,
            "m_left" => 300,
            "age_need" => true,
        ]);
        $rt8->addRequirement($rt2);
        $rt8->addRequirement($rt3);
        $rt8->save();

        $rt9 = new ResearchType([
            "title" => "Строительство",
            "age" => 1,
            "cost" => 80,
            "m_top" => 30,
            "m_left" => 570,
            "age_need" => true,
        ]);
        $rt9->addRequirement($rt1);
        $rt9->save();

        $rt10 = new ResearchType([
            "title" => "Свод законов",
            "age" => 1,
            "cost" => 120,
            "m_top" => 140,
            "m_left" => 570,
            "age_need" => true,
        ]);
        $rt10->addRequirement($rt5);
        $rt10->save();

        $rt11 = new ResearchType([
                "title" => "Литература",
                "age" => 1,
                "cost" => 90,
                "m_top" => 320,
                "m_left" => 570,
                "age_need" => false,
            ]);
        $rt11->addRequirement($rt5);
        $rt11->save();

        $rt12 = new ResearchType([
            "title" => "Создание карт",
            "age" => 1,
            "cost" => 70,
            "m_top" => 410,
            "m_left" => 570,
            "age_need" => true,
        ]);
        $rt12->addRequirement($rt4);
        $rt12->save();

        $rt13 = new ResearchType([
            "title" => "Конструкции",
            "age" => 2,
            "cost" => 140,
            "m_top" => 30,
            "m_left" => 840,
            "age_need" => true,
        ]);
        $rt13->addRequirement($rt9);
        $rt13->save();

        $rt14 = new ResearchType([
            "title" => "Деньги",
            "age" => 1,
            "cost" => 100,
            "m_top" => 110,
            "m_left" => 840,
            "age_need" => true,
        ]);
        $rt14->addRequirement($rt1);
        $rt14->save();
    }

    /**
     * Инициализирует типы зданий
     */
    public static function initializeBuildingTypes(): void
    {
        // Проверяем, есть ли уже данные в БД
        $existing = MyDB::query("SELECT COUNT(*) FROM building_type", [], "elem");
        if ($existing > 0) {
            return; // Уже инициализированы
        }

        $buildingTypes = [
            [
                "id" => 1,
                "title" => "бараки",
                "cost" => 30,
                "upkeep" => 1,
                "req_research" => [],
                "req_resources" => [],
                "need_coastal" => false,
                "culture" => 0,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Базовое здание для размещения юнитов",
            ],
            [
                "id" => 2,
                "title" => "амбар",
                "cost" => 30,
                "upkeep" => 1,
                "req_research" => [5], // Гончарное дело
                "req_resources" => [],
                "need_coastal" => false,
                "culture" => 0,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Увеличивает производство еды",
            ],
            [
                "id" => 3,
                "title" => "храм",
                "cost" => 30,
                "culture" => 2,
                "upkeep" => 1,
                "req_research" => [6], // Мистицизм
                "req_resources" => [],
                "need_coastal" => false,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Увеличивает культуру и счастье",
            ],
            [
                "id" => 4,
                "title" => "библиотека",
                "cost" => 50,
                "culture" => 3,
                "upkeep" => 1,
                "req_research" => [15], // Литература
                "req_resources" => [],
                "need_coastal" => false,
                "culture_bonus" => 0,
                "research_bonus" => 50,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Увеличивает производство науки",
            ],
            [
                "id" => 5,
                "title" => "стены",
                "cost" => 30,
                "req_research" => [12], // Строительство
                "req_resources" => [],
                "need_coastal" => false,
                "culture" => 0,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Защищает город",
            ],
            [
                "id" => 6,
                "title" => "рынок",
                "cost" => 50,
                "req_research" => [19], // Деньги
                "req_resources" => [],
                "need_coastal" => false,
                "culture" => 0,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 50,
                "need_research" => [],
                "description" => "Увеличивает производство золота",
            ],
            [
                "id" => 7,
                "title" => "суд",
                "cost" => 60,
                "req_research" => [13], // Свод законов
                "req_resources" => [],
                "need_coastal" => false,
                "culture" => 0,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Увеличивает довольство граждан",
            ],
            [
                "id" => 8,
                "title" => "гавань",
                "cost" => 60,
                "upkeep" => 1,
                "req_research" => [16], // Создание карт
                "req_resources" => [],
                "need_coastal" => true,
                "culture" => 0,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Позволяет строить морские юниты",
            ],
            [
                "id" => 9,
                "title" => "акведук",
                "cost" => 80,
                "upkeep" => 1,
                "req_research" => [18], // Конструкции
                "req_resources" => [],
                "need_coastal" => false,
                "culture" => 0,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Увеличивает рост населения",
            ],
            [
                "id" => 10,
                "title" => "колизей",
                "cost" => 80,
                "upkeep" => 2,
                "req_research" => [18], // Конструкции
                "req_resources" => [],
                "need_coastal" => false,
                "culture" => 0,
                "culture_bonus" => 0,
                "research_bonus" => 0,
                "money_bonus" => 0,
                "need_research" => [],
                "description" => "Увеличивает довольство граждан",
            ],
        ];

        foreach ($buildingTypes as $type) {
            $buildingType = new BuildingType($type);
            $buildingType->save();
        }
    }

    /**
     * Инициализирует типы юнитов
     */
    public static function initializeUnitTypes(): void
    {
        // Всегда инициализируем, чтобы перезаписать старые данные

        $unitTypes = [
            [
                "id" => 1,
                "title" => "Поселенец",
                "cost" => 40,
                "upkeep" => 1,
                "attack" => 0,
                "defence" => 1,
                "health" => 1,
                "movement" => 1,
                "can_found_city" => true,
                "need_research" => [],
                "description" => "Основывает новые города",
                "missions" => ["move_to", "build_city"],
                "can_move" => ["plains" => 1, "plains2" => 1, "forest" => 1, "hills" => 1, "mountains" => 2, "desert" => 1, "city" => 1],
            ],
            [
                "id" => 2,
                "title" => "Воин",
                "cost" => 30,
                "upkeep" => 1,
                "attack" => 2,
                "defence" => 1,
                "health" => 1,
                "movement" => 1,
                "can_found_city" => false,
                "need_research" => [],
                "description" => "Базовый боевой юнит",
                "missions" => ["move_to"],
                "can_move" => ["plains" => 1, "plains2" => 1, "forest" => 1, "hills" => 1, "mountains" => 2, "desert" => 1, "city" => 1],
            ],
            [
                "id" => 3,
                "title" => "Копейщик",
                "cost" => 35,
                "upkeep" => 1,
                "attack" => 3,
                "defence" => 2,
                "health" => 1,
                "movement" => 1,
                "can_found_city" => false,
                "need_research" => [2], // Бронзовое дело
                "description" => "Воин с копьем",
                "missions" => ["move_to"],
                "can_move" => ["plains" => 1, "plains2" => 1, "forest" => 1, "hills" => 1, "mountains" => 2, "desert" => 1, "city" => 1],
            ],
            [
                "id" => 4,
                "title" => "Лучник",
                "cost" => 40,
                "upkeep" => 1,
                "attack" => 3,
                "defence" => 1,
                "health" => 1,
                "movement" => 1,
                "can_found_city" => false,
                "need_research" => [3], // Животноводство
                "description" => "Дальнобойный боевой юнит",
                "missions" => ["move_to"],
                "can_move" => ["plains" => 1, "plains2" => 1, "forest" => 1, "hills" => 1, "mountains" => 2, "desert" => 1, "city" => 1],
            ],
            [
                "id" => 5,
                "title" => "Рабочий",
                "cost" => 30,
                "upkeep" => 1,
                "attack" => 0,
                "defence" => 1,
                "health" => 1,
                "movement" => 2,
                "can_found_city" => false,
                "can_build" => true,
                "need_research" => [],
                "description" => "Строит улучшения на клетках",
                "missions" => ["move_to", "build_road", "mine", "irrigation"],
                "can_move" => ["plains" => 1, "plains2" => 1, "forest" => 1, "hills" => 1, "mountains" => 2, "desert" => 1, "city" => 1],
            ],
        ];

        foreach ($unitTypes as $type) {
            $unitType = new UnitType($type);
            $unitType->save();
        }
    }

    /**
     * Инициализирует типы миссий
     */
    public static function initializeMissionTypes(): void
    {
        if (!class_exists("MissionType") || !empty(MissionType::$all)) {
            return; // Класс не существует или уже инициализированы
        }

        $missionTypes = [
            [
                "id" => 1,
                "title" => "Строительство дороги",
                "need_points" => ["plains" => 2, "forest" => 3, "hills" => 4],
                "description" => "Постройка дороги на клетке",
            ],
            [
                "id" => 2,
                "title" => "Строительство рудника",
                "need_points" => ["hills" => 6, "mountains" => 8],
                "description" => "Постройка рудника на клетке с ресурсами",
            ],
            [
                "id" => 3,
                "title" => "Строительство ирригации",
                "need_points" => ["plains" => 4, "desert" => 6],
                "description" => "Постройка системы орошения",
            ],
        ];

        foreach ($missionTypes as $type) {
            $mission_type = new MissionType($type);
            $mission_type->save();
        }
    }

    public static function clearAll(): void
    {
        if (class_exists("CellType")) {
            CellType::$all = [];
        }
        if (class_exists("ResourceType")) {
            ResourceType::clearAll();
        }
        if (class_exists("ResearchType")) {
            ResearchType::clearAll();
        }
        if (class_exists("BuildingType")) {
            BuildingType::clearAll();
        }
        if (class_exists("UnitType")) {
            UnitType::clearAll();
        }
    }

    /**
     * Устанавливает схему базы данных из всех SQL-файлов в каталоге sql.
     */
    public static function setupDatabaseSchema(): void
    {
        $pdo = MyDB::get();

        // Отключаем foreign key checks для MySQL
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $sqlFiles = [
            PROJECT_ROOT . "/sql/civ.sql",
            PROJECT_ROOT . "/sql/add_building_type_table.sql",
            PROJECT_ROOT . "/sql/add_planet_table.sql",
            PROJECT_ROOT . "/sql/add_research_type_table.sql",
            PROJECT_ROOT . "/sql/add_unit_type_table.sql",
            PROJECT_ROOT . "/sql/add_resource_type_table.sql",
            PROJECT_ROOT . "/sql/add_mission_type_table.sql",
        ];

        foreach ($sqlFiles as $sqlFile) {
            if (!file_exists($sqlFile)) {
                throw new Exception("SQL file not found: " . $sqlFile);
            }
            $sqlContent = file_get_contents($sqlFile);
            // Разделяем SQL-запросы по точке с запятой, но учитываем, что точка с запятой может быть внутри строк
            $queries = array_filter(array_map('trim', explode(';', $sqlContent)));

            foreach ($queries as $query) {
                if (!empty($query)) {
                    try {
                        MyDB::query($query);
                    } catch (PDOException $e) {
                        // Игнорируем ошибки, если таблица уже существует (CREATE TABLE IF NOT EXISTS)
                        // или другие незначительные ошибки при создании схемы
                        // Также игнорируем ошибки, связанные с внешними ключами при DROP TABLE,
                        // так как они могут возникать, если таблицы уже были удалены или в другом порядке.
                        if (strpos($e->getMessage(), "already exists") === false &&
                            strpos($e->getMessage(), "Cannot delete or update a parent row") === false &&
                            strpos($e->getMessage(), "a foreign key constraint fails") === false &&
                            strpos($e->getMessage(), "Unknown table") === false) {
                            throw $e;
                        }
                    }
                }
            }
        }

        // Включаем foreign key checks обратно
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}
