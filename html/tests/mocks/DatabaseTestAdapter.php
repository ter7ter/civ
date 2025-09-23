<?php

/**
 * Тестовый адаптер для базы данных
 */
class DatabaseTestAdapter
{
    /**
     * @var PDO
     */
    private static $pdo = null;
    private static $queries = [];
    private static $transactionActive = false;

    public static function clearQueries()
    {
        self::$queries = [];
    }

    public static function logQuery($query, $vars = [])
    {
        self::$queries[] = [
            "query" => $query,
            "vars" => $vars,
            "timestamp" => microtime(true),
        ];
    }

    public static function getQueries()
    {
        return self::$queries;
    }

    public static function getConnection()
    {
        return MyDB::get();
    }

    /**
     * Создание тестовых таблиц
     */
    public static function createTestTables()
    {
        $tables = [
            "game" => "
                CREATE TABLE IF NOT EXISTS game (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    map_w INTEGER DEFAULT 100,
                    map_h INTEGER DEFAULT 100,
                    turn_type TEXT DEFAULT 'byturn',
                    turn_num INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            "planet" => "
                CREATE TABLE IF NOT EXISTS planet (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    game_id INTEGER NOT NULL
                )
            ",
            "user" => "
                CREATE TABLE IF NOT EXISTS user (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    login TEXT NOT NULL,
                    color TEXT NOT NULL,
                    game INTEGER NOT NULL,
                    turn_order INTEGER DEFAULT 1,
                    turn_status TEXT DEFAULT 'wait',
                    money INTEGER DEFAULT 50,
                    age INTEGER DEFAULT 1,
                    income INTEGER DEFAULT 0,
                    research_amount INTEGER DEFAULT 0,
                    research_percent INTEGER DEFAULT 0,
                    process_research_complete INTEGER DEFAULT 0,
                    process_research_turns INTEGER DEFAULT 0,
                    process_research_type INTEGER DEFAULT 0,
                    pass TEXT DEFAULT '',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            "cell" => "
                CREATE TABLE IF NOT EXISTS cell (
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    type TEXT DEFAULT 'plains',
                    owner INTEGER DEFAULT NULL,
                    owner_culture INTEGER DEFAULT 0,
                    road TEXT DEFAULT 'none',
                    improvement TEXT DEFAULT 'none',
                    PRIMARY KEY (x, y, planet)
                )
            ",
            "unit_type" => "
                CREATE TABLE IF NOT EXISTS unit_type (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    points INTEGER DEFAULT 2,
                    mission_points INTEGER DEFAULT 2
                )
            ",
            "unit" => "
                CREATE TABLE IF NOT EXISTS unit (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    type INTEGER NOT NULL,
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    health INTEGER NOT NULL,
                    health_max INTEGER NOT NULL DEFAULT 3,
                    points REAL NOT NULL,
                    mission_points INTEGER NOT NULL DEFAULT 0,
                    mission TEXT DEFAULT NULL,
                    auto TEXT DEFAULT 'none'
                )
            ",
            "city" => "
                CREATE TABLE IF NOT EXISTS city (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    population INTEGER DEFAULT 1,
                    pmoney INTEGER DEFAULT 0,
                    presearch INTEGER DEFAULT 0,
                    resource_group INTEGER DEFAULT NULL,
                    eat INTEGER DEFAULT 0,
                    eat_up INTEGER DEFAULT 20,
                    culture INTEGER DEFAULT 0,
                    culture_level INTEGER DEFAULT 0,
                    production INTEGER DEFAULT NULL,
                    production_type TEXT DEFAULT 'unit',
                    production_complete INTEGER DEFAULT 0,
                    people_dis INTEGER DEFAULT 0,
                    people_norm INTEGER DEFAULT 1,
                    people_happy INTEGER DEFAULT 0,
                    people_artist INTEGER DEFAULT 0,
                    is_coastal INTEGER DEFAULT 0,
                    pwork INTEGER DEFAULT 1,
                    peat INTEGER DEFAULT 2
                )
            ",
            "city_people" => "
                CREATE TABLE IF NOT EXISTS city_people (
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    city_id INTEGER NOT NULL,
                    PRIMARY KEY (x, y, planet)
                )
            ",
            "resource_group" => "
                CREATE TABLE IF NOT EXISTS resource_group (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    group_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    resource_id INTEGER NOT NULL
                )
            ",
            "research" => "
                CREATE TABLE IF NOT EXISTS research (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            "message" => "
                CREATE TABLE IF NOT EXISTS message (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    from_id INTEGER DEFAULT NULL,
                    to_id INTEGER NOT NULL,
                    text TEXT NOT NULL,
                    type TEXT DEFAULT 'user',
                    date DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            "event" => "
                CREATE TABLE IF NOT EXISTS event (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL,
                    user_id INTEGER NOT NULL,
                    object TEXT DEFAULT NULL,
                    source INTEGER DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            "resource_type" => "
                CREATE TABLE IF NOT EXISTS resource_type (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    type TEXT NOT NULL
                )
            ",
            "resource" => "
                CREATE TABLE IF NOT EXISTS resource (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    type INTEGER NOT NULL,
                    amount INTEGER DEFAULT 0
                )
            ",
            "research_type" => "
                CREATE TABLE IF NOT EXISTS research_type (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    age INTEGER DEFAULT 1,
                    cost INTEGER DEFAULT 100
                )
            ",
            "building_type" => "
                CREATE TABLE IF NOT EXISTS building_type (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    cost INTEGER DEFAULT 50
                )
            ",
            "building" => "
                CREATE TABLE IF NOT EXISTS building (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type INTEGER NOT NULL,
                    city_id INTEGER NOT NULL
                )
            ",
            "mission_type" => "
                CREATE TABLE IF NOT EXISTS mission_type (
                    id TEXT PRIMARY KEY,
                    title TEXT NOT NULL
                )
            ",
        ];

        foreach ($tables as $tableName => $sql) {
            MyDB::query($sql);
        }
    }

    /**
     * Очистка всех таблиц
     */
    public static function clearAllTables()
    {
        $pdo = MyDB::get();
        $tables = [
            "event",
            "resource",
            "resource_type",
            "message",
            "research",
            "research_type",
            "resource_group",
            "city_people",
            "city",
            "building",
            "building_type",
            "unit",
            "unit_type",
            "mission_type",
            "cell",
            "user",
            "planet",
            "game",
        ];

        foreach ($tables as $table) {
            $pdo->exec("DELETE FROM {$table}");
        }
    }

    /**
     * Сброс автоинкрементов
     */
    public static function resetAutoIncrements()
    {
        $pdo = MyDB::get();
        $pdo->exec("DELETE FROM sqlite_sequence");
    }

    /**
     * Полная очистка тестовой БД
     */
    public static function resetTestDatabase()
    {
        self::clearAllTables();
        self::resetAutoIncrements();
        self::clearQueries();

        // Очистка кэшей классов
        if (class_exists("City") && method_exists("City", "clearCache")) {
            City::clearCache();
        }
        if (class_exists("User") && method_exists("User", "clearCache")) {
            User::clearCache();
        }
        if (class_exists("Game") && method_exists("Game", "clearCache")) {
            Game::clearCache();
        }
        if (class_exists("Unit") && method_exists("Unit", "clearCache")) {
            Unit::clearCache();
        }
        if (class_exists("UnitType")) {
            UnitType::$all = [];
        }
        // Очистка кэшей моков
        if (class_exists("GameTestMock")) {
            GameTestMock::$_all = [];
        }
        if (class_exists("UserTestMock")) {
            UserTestMock::$_all = [];
        }
    }
}
