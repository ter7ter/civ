<?php

/**
 * Моки для классов базы данных в тестах
 * Используют тестовые константы БД вместо основных
 */

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

    /**
     * Получить подключение к тестовой БД
     */
    public static function getConnection()
    {
        if (!self::$pdo) {
            self::connect();
        }
        return self::$pdo;
    }

    /**
     * Подключение к тестовой БД (SQLite в памяти)
     */
    private static function connect()
    {
        try {
            self::$pdo = new PDO("sqlite::memory:");
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::createTestTables();
        } catch (Exception $e) {
            throw new Exception(
                "Test DB connection failed: " . $e->getMessage(),
            );
        }
    }

    /**
     * Выполнение запроса с параметрами
     */
    public static function query($sql, $params = [], $mode = "all")
    {
        $pdo = self::getConnection();

        // Replace ?key with :key for named parameters
        $sql = preg_replace('/\?(\w+)/', ':$1', $sql);

        // Логируем запрос для отладки
        self::$queries[] = [
            "sql" => $sql,
            "params" => $params,
            "mode" => $mode,
        ];

        try {
            // Remove debug logs
            error_log("Executing SQL: " . $sql);

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params); // Pass parameters directly to execute

            switch ($mode) {
                case "row":
                    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                case "elem":
                    return $stmt->fetchColumn();
                case "all":
                default:
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            throw new Exception(
                "Test DB query failed: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params),
            );
        }
    }

    /**
     * Вставка записи
     */
    public static function insert($table, $data)
    {
        $pdo = self::getConnection();

        $fields = array_keys($data);
        $placeholders = array_map(function ($field) {
            return ":" . $field;
        }, $fields);

        $sql =
            "INSERT INTO {$table} (" .
            implode(", ", $fields) .
            ") VALUES (" .
            implode(", ", $placeholders) .
            ")";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return $pdo->lastInsertId();
    }

    /**
     * Обновление записи
     */
    public static function update($table, $data, $id)
    {
        $pdo = self::getConnection();

        $sets = [];
        foreach ($data as $field => $value) {
            $sets[] = "{$field} = :{$field}";
        }

        $sql =
            "UPDATE {$table} SET " . implode(", ", $sets) . " WHERE id = :id";
        $data["id"] = $id;

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Удаление записи
     */
    public static function delete($table, $id)
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
        return $stmt->execute(["id" => $id]);
    }

    /**
     * Начало транзакции
     */
    public static function beginTransaction()
    {
        if (!self::$transactionActive) {
            self::getConnection()->beginTransaction();
            self::$transactionActive = true;
        }
    }

    /**
     * Завершение транзакции
     */
    public static function commit()
    {
        if (self::$transactionActive) {
            self::getConnection()->commit();
            self::$transactionActive = false;
        }
    }

    /**
     * Откат транзакции
     */
    public static function rollback()
    {
        if (self::$transactionActive) {
            self::getConnection()->rollback();
            self::$transactionActive = false;
        }
    }

    /**
     * Получить список выполненных запросов (для отладки)
     */
    public static function getQueries()
    {
        return self::$queries;
    }

    /**
     * Очистить список запросов
     */
    public static function clearQueries()
    {
        self::$queries = [];
    }

    /**
     * Создание тестовых таблиц
     */
    private static function createTestTables()
    {
        $pdo = self::getConnection();

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
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (game) REFERENCES game(id)
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
                    road INTEGER DEFAULT 0,
                    FOREIGN KEY (owner) REFERENCES user(id),
                    FOREIGN KEY (planet) REFERENCES game(id)
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
                    points INTEGER DEFAULT 2,
                    FOREIGN KEY (user_id) REFERENCES user(id),
                    FOREIGN KEY (planet) REFERENCES game(id)
                )
            ",
            "city" => "
                CREATE TABLE IF NOT EXISTS city (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    x INTEGER NOT NULL,
                    y INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    planet INTEGER NOT NULL,
                    population INTEGER DEFAULT 1,
                    pmoney INTEGER DEFAULT 0,
                    presearch INTEGER DEFAULT 0,
                    resource_group INTEGER DEFAULT NULL,
                    FOREIGN KEY (user_id) REFERENCES user(id),
                    FOREIGN KEY (planet) REFERENCES game(id)
                )
            ",
            "resource_group" => "
                CREATE TABLE IF NOT EXISTS resource_group (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    group_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    resource_id INTEGER NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES user(id)
                )
            ",
            "research" => "
                CREATE TABLE IF NOT EXISTS research (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES user(id)
                )
            ",
            "message" => "
                CREATE TABLE IF NOT EXISTS message (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    from_id INTEGER DEFAULT NULL,
                    to_id INTEGER NOT NULL,
                    text TEXT NOT NULL,
                    type TEXT DEFAULT 'user',
                    date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (from_id) REFERENCES user(id),
                    FOREIGN KEY (to_id) REFERENCES user(id)
                )
            ",
            "event" => "
                CREATE TABLE IF NOT EXISTS event (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL,
                    user_id INTEGER NOT NULL,
                    object TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES user(id)
                )
            ",
        ];

        foreach ($tables as $tableName => $sql) {
            $pdo->exec($sql);
        }
    }

    /**
     * Очистка всех таблиц
     */
    public static function clearAllTables()
    {
        $pdo = self::getConnection();
        $tables = [
            "event",
            "message",
            "research",
            "resource_group",
            "city",
            "unit",
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
        $pdo = self::getConnection();
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
    }
}

/**
 * Мок-обертка для замены MyDB в тестах
 */
class MyDBTestWrapper
{
    public static $dbhost = "";
    public static $dbuser = "";
    public static $dbpass = "";
    public static $dbname = "";
    public static $dbport = "";

    public static function connect()
    {
        // В тестах подключение происходит через DatabaseTestAdapter
        return true;
    }

    public static function get()
    {
        return DatabaseTestAdapter::getConnection();
    }

    public static function query($sql, $params = [], $mode = "all")
    {
        return DatabaseTestAdapter::query($sql, $params, $mode);
    }

    public static function insert($table, $data)
    {
        return DatabaseTestAdapter::insert($table, $data);
    }

    public static function update($table, $data, $id)
    {
        return DatabaseTestAdapter::update($table, $data, $id);
    }

    public static function delete($table, $id)
    {
        return DatabaseTestAdapter::delete($table, $id);
    }

    public static function start_transaction()
    {
        return DatabaseTestAdapter::beginTransaction();
    }

    public static function end_transaction()
    {
        return DatabaseTestAdapter::commit();
    }

    public static function rollback_transaction()
    {
        return DatabaseTestAdapter::rollback();
    }

    public static function resetTestDatabase()
    {
        DatabaseTestAdapter::resetTestDatabase();
    }

    public static function clearQueries()
    {
        DatabaseTestAdapter::clearQueries();
    }

    public static function getQueries()
    {
        return DatabaseTestAdapter::getQueries();
    }
}

/**
 * Мок класса Game для тестов
 */
class GameTestMock
{
    public $id;
    public $name;
    public $map_w;
    public $map_h;
    public $turn_type;
    public $turn_num;
    public $users = [];

    private static $_all = [];

    public function __construct($data)
    {
        foreach (
            ["name", "map_w", "map_h", "turn_type", "turn_num"]
            as $field
        ) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        if (isset($data["id"])) {
            $this->id = $data["id"];
            self::$_all[$this->id] = $this;
        }
    }

    public static function get($id)
    {
        if (isset(self::$_all[$id])) {
            return self::$_all[$id];
        }

        $data = DatabaseTestAdapter::query(
            "SELECT * FROM game WHERE id = :id",
            ["id" => $id],
            "row",
        );
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public function save()
    {
        $values = [];
        foreach (
            ["name", "map_w", "map_h", "turn_type", "turn_num"]
            as $field
        ) {
            if (isset($this->$field)) {
                $values[$field] = $this->$field;
            }
        }

        if ($this->id) {
            DatabaseTestAdapter::update("game", $values, $this->id);
        } else {
            $this->id = DatabaseTestAdapter::insert("game", $values);
            self::$_all[$this->id] = $this;
        }
    }

    public function create_new_game()
    {
        // Упрощенная версия для тестов - просто создаем базовые юниты
        $users = DatabaseTestAdapter::query(
            "SELECT id FROM user WHERE game = :gameid ORDER BY turn_order",
            ["gameid" => $this->id],
        );

        foreach ($users as $user) {
            $unitData = [
                "x" => rand(0, $this->map_w - 1),
                "y" => rand(0, $this->map_h - 1),
                "planet" => $this->id,
                "user_id" => $user["id"],
                "type" => 1,
                "health" => 3,
                "points" => 2,
            ];
            DatabaseTestAdapter::insert("unit", $unitData);
        }

        return true;
    }

    public static function game_list()
    {
        return DatabaseTestAdapter::query("SELECT game.*, count(user.id) as ucount FROM game
                               INNER JOIN user ON user.game = game.id
                               GROUP BY user.game ORDER BY id DESC");
    }

    public function calculate()
    {
        // Mock implementation for tests
        return true;
    }

    public function all_system_message($text)
    {
        // Mock implementation for tests
        return true;
    }
}

/**
 * Мок класса User для тестов
 */
class UserTestMock
{
    public $id;
    public $login;
    public $color;
    public $game;
    public $turn_order;
    public $turn_status;
    public $money;
    public $age;
    public $income = 0;
    public $research_amount = 0;
    public $research_percent = 0;
    public $process_research_complete = 0;
    public $process_research_turns = 0;
    public $process_research_type = 0;

    private static $_all = [];

    public function __construct($data)
    {
        foreach (
            [
                "login",
                "color",
                "game",
                "turn_order",
                "turn_status",
                "money",
                "age",
                "income",
                "research_amount",
                "research_percent",
                "process_research_complete",
                "process_research_turns",
                "process_research_type",
            ]
            as $field
        ) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        if (isset($data["id"])) {
            $this->id = $data["id"];
            self::$_all[$this->id] = $this;
        }
    }

    public static function get($id)
    {
        if (isset(self::$_all[$id])) {
            return self::$_all[$id];
        }

        $data = DatabaseTestAdapter::query(
            "SELECT * FROM user WHERE id = :id",
            ["id" => $id],
            "row",
        );
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public function save()
    {
        $values = [];
        foreach (
            [
                "login",
                "color",
                "game",
                "turn_order",
                "turn_status",
                "money",
                "age",
                "income",
                "research_amount",
                "research_percent",
                "process_research_complete",
                "process_research_turns",
                "process_research_type",
            ]
            as $field
        ) {
            if (isset($this->$field)) {
                $values[$field] = $this->$field;
            }
        }

        if ($this->id) {
            DatabaseTestAdapter::update("user", $values, $this->id);
        } else {
            $this->id = DatabaseTestAdapter::insert("user", $values);
            self::$_all[$this->id] = $this;
        }
    }
}

/**
 * Функция для инициализации тестового окружения
 */
function initializeTestEnvironment()
{
    // Настройка тестовых констант БД
    MyDBTestWrapper::$dbhost = defined("TEST_DB_HOST")
        ? TEST_DB_HOST
        : "localhost";
    MyDBTestWrapper::$dbuser = defined("TEST_DB_USER")
        ? TEST_DB_USER
        : "test_user";
    MyDBTestWrapper::$dbpass = defined("TEST_DB_PASS")
        ? TEST_DB_PASS
        : "test_pass";
    MyDBTestWrapper::$dbname = defined("TEST_DB_NAME")
        ? TEST_DB_NAME
        : "test_db";
    MyDBTestWrapper::$dbport = defined("TEST_DB_PORT") ? TEST_DB_PORT : 3306;

    // Сброс тестовой БД
    DatabaseTestAdapter::resetTestDatabase();

    // Устанавливаем глобальные переменные для использования тестовых классов
    global $TESTING_MODE;
    $TESTING_MODE = true;
}

/**
 * Функция для получения тестового экземпляра Game
 */
function getTestGameClass($data = [])
{
    return new GameTestMock($data);
}

/**
 * Функция для получения тестового экземпляра User
 */
function getTestUserClass($data = [])
{
    return new UserTestMock($data);
}
