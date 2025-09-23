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

    public static function query($query, $vars = [])
    {
        // Используем основной класс MyDB из проекта
        return MyDB::query($query, $vars);
    }

    public static function insert($table, $values)
    {
        return MyDB::insert($table, $values);
    }

    public static function update($table, $values, $where)
    {
        return MyDB::update($table, $values, $where);
    }


    /**
     * Статический метод для создания таблиц в переданном PDO соединении
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
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    type TEXT DEFAULT 'plains',
                    owner INTEGER DEFAULT NULL,
                    owner_culture INTEGER DEFAULT 0,
                    road INTEGER DEFAULT 0
                )
            ",
            "unit_type" => "
                CREATE TABLE IF NOT EXISTS unit_type (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    points INTEGER DEFAULT 2
                )
            ",
            "unit" => "
                CREATE TABLE IF NOT EXISTS unit (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    type INTEGER DEFAULT 1,
                    health INTEGER DEFAULT 3,
                    health_max INTEGER DEFAULT 3,
                    points INTEGER DEFAULT 2
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
                    resource_group INTEGER DEFAULT NULL
                )
            ",
            "city_people" => "
                CREATE TABLE IF NOT EXISTS city_people (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    city_id INTEGER NOT NULL,
                    FOREIGN KEY (city_id) REFERENCES city(id)
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
            "event" => "
                CREATE TABLE IF NOT EXISTS event (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL,
                    user_id INTEGER NOT NULL,
                    object TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
            "message",
            "research",
            "resource_group",
            "city_people",
            "city",
            "unit",
            "unit_type",
            "cell",
            "user",
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

        // Очистка кэшей моков
        if (class_exists("GameTestMock")) {
            GameTestMock::$_all = [];
        }
        if (class_exists("UserTestMock")) {
            UserTestMock::$_all = [];
        }
    }
}
