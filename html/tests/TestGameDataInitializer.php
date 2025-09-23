<?php

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
        self::initializeResourceTypes();
        self::initializeResearchTypes();
        self::initializeBuildingTypes();
        self::initializeUnitTypes();
        // self::initializeMissionTypes(); // Временно отключено
    }

    /**
     * Инициализирует типы клеток
     */
    public static function initializeCellTypes(): void
    {
        if (!empty(CellType::$all)) {
            return; // Уже инициализированы
        }

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
        if (!empty(ResourceType::$all)) {
            return; // Уже инициализированы
        }

        $resourceTypes = [
            [
                "id" => 1,
                "title" => "Железо",
                "type" => "strategic",
                "work" => 2,
                "eat" => 0,
                "money" => 0,
                "cell_types" => [CellType::get("hills")],
                "chance" => 0.1,
                "min_amount" => 5,
                "max_amount" => 15,
            ],
            [
                "id" => 2,
                "title" => "Золото",
                "type" => "luxury",
                "work" => 0,
                "eat" => 0,
                "money" => 3,
                "cell_types" => [
                    CellType::get("hills"),
                    CellType::get("mountains"),
                ],
                "chance" => 0.05,
                "min_amount" => 3,
                "max_amount" => 8,
            ],
            [
                "id" => 3,
                "title" => "Рыба",
                "type" => "food",
                "work" => 0,
                "eat" => 2,
                "money" => 1,
                "cell_types" => [CellType::get("water")],
                "chance" => 0.15,
                "min_amount" => 1,
                "max_amount" => 3,
            ],
            [
                "id" => 4,
                "title" => "Дичь",
                "type" => "food",
                "work" => 1,
                "eat" => 1,
                "money" => 0,
                "cell_types" => [CellType::get("forest")],
                "chance" => 0.12,
                "min_amount" => 1,
                "max_amount" => 2,
            ],
            [
                "id" => 5,
                "title" => "Камень",
                "type" => "strategic",
                "work" => 1,
                "eat" => 0,
                "money" => 0,
                "cell_types" => [
                    CellType::get("hills"),
                    CellType::get("mountains"),
                ],
                "chance" => 0.08,
                "min_amount" => 8,
                "max_amount" => 20,
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
        if (!empty(ResearchType::$all)) {
            return; // Уже инициализированы
        }

        $researchTypes = [
            [
                "id" => 1,
                "title" => "Гончарное дело",
                "age" => 1,
                "cost" => 50,
                "need_research" => [],
                "description" => "Изготовление керамических изделий",
            ],
            [
                "id" => 2,
                "title" => "Бронзовое дело",
                "age" => 1,
                "cost" => 80,
                "need_research" => [1],
                "description" => "Работа с бронзой",
            ],
            [
                "id" => 3,
                "title" => "Животноводство",
                "age" => 1,
                "cost" => 60,
                "need_research" => [],
                "description" => "Разведение домашних животных",
            ],
            [
                "id" => 4,
                "title" => "Земледелие",
                "age" => 1,
                "cost" => 70,
                "need_research" => [],
                "description" => "Выращивание растений",
            ],
            [
                "id" => 5,
                "title" => "Письменность",
                "age" => 1,
                "cost" => 100,
                "need_research" => [1],
                "description" => "Система записи информации",
            ],
            [
                "id" => 6,
                "title" => "Железное дело",
                "age" => 2,
                "cost" => 150,
                "need_research" => [2],
                "description" => "Работа с железом",
            ],
        ];

        foreach ($researchTypes as $type) {
            new ResearchType($type);
        }
    }

    /**
     * Инициализирует типы зданий
     */
    public static function initializeBuildingTypes(): void
    {
        if (!empty(BuildingType::$all)) {
            return; // Уже инициализированы
        }

        $buildingTypes = [
            [
                "id" => 1,
                "title" => "Гранарий",
                "cost" => 60,
                "upkeep" => 1,
                "need_research" => [],
                "eat_bonus" => 50,
                "description" => "Хранилище зерна",
            ],
            [
                "id" => 2,
                "title" => "Казармы",
                "cost" => 40,
                "upkeep" => 1,
                "need_research" => [2], // Бронзовое дело
                "description" => "Место для обучения воинов",
            ],
            [
                "id" => 3,
                "title" => "Храм",
                "cost" => 80,
                "upkeep" => 2,
                "need_research" => [5], // Письменность
                "culture_bonus" => 2,
                "description" => "Религиозное сооружение",
            ],
            [
                "id" => 4,
                "title" => "Библиотека",
                "cost" => 90,
                "upkeep" => 1,
                "need_research" => [5], // Письменность
                "research_bonus" => 25,
                "description" => "Хранилище знаний",
            ],
            [
                "id" => 5,
                "title" => "Рынок",
                "cost" => 80,
                "upkeep" => 1,
                "need_research" => [1], // Гончарное дело
                "money_bonus" => 25,
                "description" => "Место торговли",
            ],
        ];

        foreach ($buildingTypes as $type) {
            new BuildingType($type);
        }
    }

    /**
     * Инициализирует типы юнитов
     */
    public static function initializeUnitTypes(): void
    {
        if (!empty(UnitType::$all)) {
            return; // Уже инициализированы
        }

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
            ],
        ];

        foreach ($unitTypes as $type) {
            new UnitType($type);
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
            new MissionType($type);
        }
    }

    /**
     * Очищает все инициализированные данные (для тестов)
     */
    public static function clearAll(): void
    {
        if (class_exists("CellType")) {
            CellType::$all = [];
        }
        if (class_exists("ResourceType")) {
            ResourceType::$all = [];
        }
        if (class_exists("ResearchType")) {
            ResearchType::$all = [];
        }
        if (class_exists("BuildingType")) {
            BuildingType::$all = [];
        }
        if (class_exists("UnitType")) {
            UnitType::$all = [];
        }
        if (class_exists("MissionType")) {
            MissionType::$all = [];
        }
    }

    /**
     * Создает минимальный набор данных для базовых тестов
     */
    public static function initializeMinimal(): void
    {
        // Только самые необходимые типы для базовых тестов
        if (empty(CellType::$all)) {
            new CellType([
                "id" => "plains",
                "title" => "Равнины",
                "work" => 1,
                "eat" => 2,
                "money" => 0,
                "base_chance" => 15,
                "chance_inc1" => 8,
                "chance_inc2" => 6,
                "chance_inc_other" => [],
                "border_no" => [],
            ]);
        }

        if (empty(UnitType::$all)) {
            new UnitType([
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
            ]);
        }

        if (empty(ResearchType::$all)) {
            new ResearchType([
                "id" => 1,
                "title" => "Тестовое исследование",
                "age" => 1,
                "cost" => 50,
                "need_research" => [],
            ]);
        }
    }
}
