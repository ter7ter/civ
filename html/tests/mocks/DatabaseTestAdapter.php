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
        $pdo = MyDB::get();
        $isMySQL = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === "mysql";

        // Отладочный вывод для проверки используемой базы данных
        $testToken = getenv('TEST_TOKEN');
        if (!empty($testToken)) {
            echo "\n[DEBUG] DatabaseTestAdapter using TEST_TOKEN: {$testToken}\n";
        }

        $autoIncrement = $isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT";
        $unsigned = $isMySQL ? "UNSIGNED" : "";

        $id_column = $isMySQL
            ? "INT NOT NULL AUTO_INCREMENT PRIMARY KEY"
            : "INTEGER PRIMARY KEY AUTOINCREMENT";

        // Проверяем, созданы ли уже таблицы
        try {
            $result = $pdo->query("SHOW TABLES LIKE 'game'");
            if ($result && $result->rowCount() > 0) {
                return; // Таблицы уже созданы
            }
        } catch (Exception $e) {
            // Продолжаем создание таблиц
        }

        $tables = [
            "game" => "CREATE TABLE IF NOT EXISTS game (id $id_column, name VARCHAR(255) NOT NULL, map_w INT DEFAULT 100, map_h INT DEFAULT 100, turn_type VARCHAR(50) DEFAULT 'byturn', turn_num INT DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
            "user" => "CREATE TABLE IF NOT EXISTS user (id $id_column, login VARCHAR(255) NOT NULL, color VARCHAR(10) NOT NULL, game INT NOT NULL, turn_order INT DEFAULT 1, turn_status VARCHAR(10) DEFAULT 'wait', money INT DEFAULT 50, age INT DEFAULT 1, income INT DEFAULT 0, research_amount INT DEFAULT 0, research_percent INT DEFAULT 0, process_research_complete INT DEFAULT 0, process_research_turns INT DEFAULT 0, process_research_type INT DEFAULT 0, pass VARCHAR(255) DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
            "planet" => "CREATE TABLE IF NOT EXISTS planet (id $id_column, name VARCHAR(255) NOT NULL, game_id INT NOT NULL)",
            "cell" =>
                "CREATE TABLE IF NOT EXISTS cell (x INT NOT NULL, y INT NOT NULL, planet INT NOT NULL, type VARCHAR(50) DEFAULT 'plains', owner INT DEFAULT NULL, owner_culture INT DEFAULT 0, road VARCHAR(10) DEFAULT 'none', improvement VARCHAR(20) DEFAULT 'none', PRIMARY KEY (x, y, planet))",
            "unit_type" => "CREATE TABLE IF NOT EXISTS unit_type (id $id_column, title VARCHAR(255) NOT NULL, points INT DEFAULT 2, mission_points INT DEFAULT 2)",
            "unit" => "CREATE TABLE IF NOT EXISTS unit (id INT $unsigned $autoIncrement PRIMARY KEY, user_id INT $unsigned NOT NULL, type INT $unsigned NOT NULL, x INT NOT NULL, y INT NOT NULL, planet INT $unsigned NOT NULL, health INT NOT NULL, health_max INT NOT NULL DEFAULT 3, points DECIMAL(10,2) NOT NULL, mission_points INT NOT NULL DEFAULT 0, mission VARCHAR(50) DEFAULT NULL, auto VARCHAR(20) DEFAULT 'none')",
            "city" => "CREATE TABLE IF NOT EXISTS city (id $id_column, title VARCHAR(255) NOT NULL, x INT NOT NULL, y INT NOT NULL, user_id INT NOT NULL, planet INT NOT NULL, population INT DEFAULT 1, pmoney INT DEFAULT 0, presearch INT DEFAULT 0, resource_group INT DEFAULT NULL, eat INT DEFAULT 0, eat_up INT DEFAULT 20, culture INT DEFAULT 0, culture_level INT DEFAULT 0, production INT DEFAULT NULL, production_type VARCHAR(20) DEFAULT 'unit', production_complete INT DEFAULT 0, people_dis INT DEFAULT 0, people_norm INT DEFAULT 1, people_happy INT DEFAULT 0, people_artist INT DEFAULT 0, is_coastal TINYINT DEFAULT 0, pwork INT DEFAULT 1, peat INT DEFAULT 2)",
            "city_people" =>
                "CREATE TABLE IF NOT EXISTS city_people (x INT NOT NULL, y INT NOT NULL, planet INT NOT NULL, city_id INT NOT NULL, PRIMARY KEY (x, y, planet))",
            "resource_group" => "CREATE TABLE IF NOT EXISTS resource_group (group_id INT $unsigned NOT NULL, user_id INT $unsigned NOT NULL, resource_id INT $unsigned NOT NULL, PRIMARY KEY (group_id, user_id, resource_id))",
            "research" => "CREATE TABLE IF NOT EXISTS research (id $id_column, type INT NOT NULL, user_id INT NOT NULL, completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
            "message" => "CREATE TABLE IF NOT EXISTS message (id $id_column, from_id INT DEFAULT NULL, to_id INT NOT NULL, text TEXT NOT NULL, type VARCHAR(20) DEFAULT 'user', date TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
            "event" => "CREATE TABLE IF NOT EXISTS event (id $id_column, type VARCHAR(50) NOT NULL, user_id INT NOT NULL, object VARCHAR(255) DEFAULT NULL, source INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
            "resource_type" => "CREATE TABLE IF NOT EXISTS resource_type (id $id_column, title VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL)",
            "resource" => "CREATE TABLE IF NOT EXISTS resource (id $id_column, x INT NOT NULL, y INT NOT NULL, planet INT NOT NULL, type VARCHAR(50) NOT NULL, amount INT DEFAULT 0)",
            "research_type" => "CREATE TABLE IF NOT EXISTS research_type (id $id_column, title VARCHAR(255) NOT NULL, age INT DEFAULT 1, cost INT DEFAULT 100)",
            "building_type" => "CREATE TABLE IF NOT EXISTS building_type (id $id_column, title VARCHAR(255) NOT NULL, cost INT DEFAULT 50)",
            "building" => "CREATE TABLE IF NOT EXISTS building (id $id_column, type INT NOT NULL, city_id INT NOT NULL)",
            "mission_type" =>
                "CREATE TABLE IF NOT EXISTS mission_type (id VARCHAR(50) PRIMARY KEY, title VARCHAR(255) NOT NULL)",
            "mission_order" => "CREATE TABLE IF NOT EXISTS mission_order (unit_id INT $unsigned NOT NULL, number INT $unsigned NOT NULL, type VARCHAR(20) NOT NULL, target_x INT $unsigned NOT NULL, target_y INT $unsigned NOT NULL, PRIMARY KEY (unit_id, number))",
        ];

        $pdo = MyDB::get();
        foreach ($tables as $tableName => $sql) {
            // Убираем лишние пробелы и переносы строк
            $sql = trim(preg_replace("/\s+/", " ", $sql));
            $pdo->exec($sql);
        }

        // Добавляем индексы и foreign keys только если они не существуют
        $indexesAndConstraints = [
            // Индексы
            ["table" => "building", "index" => "city_id", "sql" => "ALTER TABLE building ADD KEY city_id (city_id)"],
            ["table" => "cell", "index" => "cell_ibfk_1", "sql" => "ALTER TABLE cell ADD KEY cell_ibfk_1 (owner)"],
            ["table" => "city", "index" => "x", "sql" => "ALTER TABLE city ADD UNIQUE KEY x (x, y, planet)"],
            ["table" => "city", "index" => "user_id", "sql" => "ALTER TABLE city ADD KEY user_id (user_id)"],
            ["table" => "city_people", "index" => "city_id", "sql" => "ALTER TABLE city_people ADD KEY city_id (city_id)"],
            ["table" => "message", "index" => "from_id", "sql" => "ALTER TABLE message ADD KEY from_id (from_id)"],
            ["table" => "message", "index" => "to_id", "sql" => "ALTER TABLE message ADD KEY to_id (to_id)"],
            ["table" => "research", "index" => "user_id", "sql" => "ALTER TABLE research ADD UNIQUE KEY user_id (user_id, type)"],
            ["table" => "resource", "index" => "type", "sql" => "ALTER TABLE resource ADD KEY type (type)"],
            ["table" => "resource_group", "index" => "resource_id", "sql" => "ALTER TABLE resource_group ADD KEY resource_id (resource_id)"],
            ["table" => "resource_group", "index" => "user_id", "sql" => "ALTER TABLE resource_group ADD KEY user_id (user_id)"],
            ["table" => "unit", "index" => "user_id", "sql" => "ALTER TABLE unit ADD KEY user_id (user_id)"],
            ["table" => "unit", "index" => "x", "sql" => "ALTER TABLE unit ADD KEY x (x, y, planet)"],

            // Foreign keys
            ["table" => "building", "constraint" => "building_ibfk_1", "sql" => "ALTER TABLE building ADD CONSTRAINT building_ibfk_1 FOREIGN KEY (city_id) REFERENCES city (id)"],
            ["table" => "cell", "constraint" => "cell_ibfk_1", "sql" => "ALTER TABLE cell ADD CONSTRAINT cell_ibfk_1 FOREIGN KEY (owner) REFERENCES user (id) ON DELETE SET NULL"],
            ["table" => "city", "constraint" => "city_ibfk_1", "sql" => "ALTER TABLE city ADD CONSTRAINT city_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id)"],
            ["table" => "city", "constraint" => "city_ibfk_2", "sql" => "ALTER TABLE city ADD CONSTRAINT city_ibfk_2 FOREIGN KEY (x, y, planet) REFERENCES cell (x, y, planet)"],
            ["table" => "city_people", "constraint" => "city_people_ibfk_1", "sql" => "ALTER TABLE city_people ADD CONSTRAINT city_people_ibfk_1 FOREIGN KEY (city_id) REFERENCES city (id)"],
            ["table" => "city_people", "constraint" => "city_people_ibfk_2", "sql" => "ALTER TABLE city_people ADD CONSTRAINT city_people_ibfk_2 FOREIGN KEY (x, y, planet) REFERENCES cell (x, y, planet)"],
            ["table" => "message", "constraint" => "message_ibfk_1", "sql" => "ALTER TABLE message ADD CONSTRAINT message_ibfk_1 FOREIGN KEY (from_id) REFERENCES user (id)"],
            ["table" => "message", "constraint" => "message_ibfk_2", "sql" => "ALTER TABLE message ADD CONSTRAINT message_ibfk_2 FOREIGN KEY (to_id) REFERENCES user (id)"],
            ["table" => "mission_order", "constraint" => "mission_order_ibfk_1", "sql" => "ALTER TABLE mission_order ADD CONSTRAINT mission_order_ibfk_1 FOREIGN KEY (unit_id) REFERENCES unit (id)"],
            ["table" => "research", "constraint" => "research_ibfk_1", "sql" => "ALTER TABLE research ADD CONSTRAINT research_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id)"],
            ["table" => "resource_group", "constraint" => "resource_group_ibfk_1", "sql" => "ALTER TABLE resource_group ADD CONSTRAINT resource_group_ibfk_1 FOREIGN KEY (resource_id) REFERENCES resource (id)"],
            ["table" => "resource_group", "constraint" => "resource_group_ibfk_2", "sql" => "ALTER TABLE resource_group ADD CONSTRAINT resource_group_ibfk_2 FOREIGN KEY (user_id) REFERENCES user (id)"],
            ["table" => "unit", "constraint" => "unit_ibfk_1", "sql" => "ALTER TABLE unit ADD CONSTRAINT unit_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id)"],
            ["table" => "unit", "constraint" => "unit_ibfk_2", "sql" => "ALTER TABLE unit ADD CONSTRAINT unit_ibfk_2 FOREIGN KEY (x, y, planet) REFERENCES cell (x, y, planet)"],
        ];

        foreach ($indexesAndConstraints as $item) {
            try {
                // Проверяем, существует ли индекс или constraint
                $exists = false;
                if (isset($item["index"])) {
                    $result = $pdo->query("SHOW INDEX FROM `{$item["table"]}` WHERE Key_name = '{$item["index"]}'");
                    $exists = $result && $result->rowCount() > 0;
                } elseif (isset($item["constraint"])) {
                    $result = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = '{$item["table"]}' AND CONSTRAINT_NAME = '{$item["constraint"]}'");
                    $exists = $result && $result->rowCount() > 0;
                }

                if (!$exists) {
                    $pdo->exec($item["sql"]);
                }
            } catch (Exception $e) {
                // Игнорируем ошибки при добавлении индексов/foreign keys
            }
        }

        // Блокировка файла не используется в тестах
    }

    /**
     * Очистка всех таблиц с использованием TRUNCATE для производительности
     */
    public static function clearAllTables()
    {
        $pdo = MyDB::get();

        // Порядок важен: сначала таблицы, которые ссылаются на другие (дочерние), потом родительские
        $tables = [
            "event",
            "resource",
            "resource_type",
            "message",
            "research",
            "research_type",
            "resource_group",
            "city_people",
            "building", // Сначала здания
            "city", // Потом города
            "building_type",
            "mission_order", // Сначала миссии
            "unit", // Потом юниты
            "unit_type",
            "mission_type",
            "cell", // Клетки могут ссылаться на пользователей
            "user", // Пользователи могут быть в разных таблицах
            "planet", // Планеты ссылаются на игры
            "game", // Игры в конце
        ];

        // Сначала отключаем foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($tables as $table) {
            try {
                echo "\nTruncating table: {$table}\n";
                $pdo->exec("TRUNCATE TABLE `{$table}`");
            } catch (Exception $e) {
                // Если TRUNCATE не работает, используем DELETE
                try {
                    echo "\nDeleting from table: {$table}\n";
                    $pdo->exec("DELETE FROM `{$table}`");
                } catch (Exception $e2) {
                    // Игнорируем ошибки при очистке
                }
            }
        }

        // Включаем foreign key checks обратно
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Сброс автоинкрементов
     */
    public static function resetAutoIncrements()
    {
        $pdo = MyDB::get();
        $tables = [
            "game",
            "planet",
            "user",
            "cell",
            "unit_type",
            "unit",
            "city",
            "city_people",
            "resource_group",
            "research",
            "message",
            "event",
            "resource_type",
            "resource",
            "research_type",
            "building_type",
            "building",
            "mission_type",
            "mission_order",
        ];

        foreach ($tables as $table) {
            try {
                $pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT = 1");
            } catch (Exception $e) {
                // Игнорируем ошибки, если таблица не существует или нет автоинкремента
            }
        }
    }

    /**
     * Полная очистка тестовой БД
     */
    public static function resetTestDatabase()
    {
        // Сначала создаем таблицы, если их нет
        self::createTestTables();

        // Потом очищаем их
        self::clearAllTables();

        // TRUNCATE уже сбрасывает автоинкременты, resetAutoIncrements не нужен
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
