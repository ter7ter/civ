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
        self::initializeResearchTypes();
        self::initializeResourceTypes();
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
                "req_research" => [ResearchType::get(4)], // Верховая езда
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
                "title" => "Верховая езда",
                "age" => 1,
                "cost" => 80,
                "need_research" => [3],
                "description" => "Использование лошадей для передвижения",
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
                "title" => "Мистицизм",
                "age" => 1,
                "cost" => 60,
                "need_research" => [],
                "description" => "Изучение сверхъестественного",
            ],
            [
                "id" => 12,
                "title" => "Строительство",
                "age" => 1,
                "cost" => 80,
                "need_research" => [1],
                "description" => "Искусство строительства",
            ],
            [
                "id" => 13,
                "title" => "Свод законов",
                "age" => 1,
                "cost" => 120,
                "need_research" => [5],
                "description" => "Организация правовой системы",
            ],
            [
                "id" => 15,
                "title" => "Литература",
                "age" => 1,
                "cost" => 90,
                "need_research" => [5],
                "description" => "Искусство письма и литературы",
            ],
            [
                "id" => 16,
                "title" => "Создание карт",
                "age" => 1,
                "cost" => 70,
                "need_research" => [4],
                "description" => "Составление географических карт",
            ],
            [
                "id" => 18,
                "title" => "Конструкции",
                "age" => 2,
                "cost" => 140,
                "need_research" => [12],
                "description" => "Инженерное дело и конструкции",
            ],
            [
                "id" => 19,
                "title" => "Деньги",
                "age" => 1,
                "cost" => 100,
                "need_research" => [1],
                "description" => "Монетарная система",
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
        // Проверяем, есть ли уже данные в БД
        $existing = MyDB::query("SELECT COUNT(*) FROM unit_type", [], "elem");
        if ($existing > 0) {
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
