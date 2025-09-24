<?php

/**
 * Базовый класс для всех тестов
 * Использует реальные классы игры вместо моков
 */
class TestBase extends PHPUnit\Framework\TestCase
{
    protected static $pdo;

    public static function setUpBeforeClass(): void
    {
        // Сохраняем ссылку на тестовую БД
        self::$pdo = DatabaseTestAdapter::getConnection();

        // Очищаем БД перед запуском тестов класса
        DatabaseTestAdapter::resetTestDatabase();

        // Очищаем кэши реальных классов
        Game::clearCache();
        User::clearCache();
        Cell::clearCache();
        Planet::clearCache();

        // Устанавливаем глобальные переменные для тестов
        Cell::$map_width = 20;
        Cell::$map_height = 20;
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
        // Очистка происходит автоматически при завершении тестов
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearTestData();
    }

    protected function tearDown(): void
    {
        $this->clearTestData();
        parent::tearDown();
    }

    /**
     * Очищает тестовые данные
     */
    protected function clearTestData(): void
    {
        DatabaseTestAdapter::resetTestDatabase();

        // Очищаем кэши реальных классов
        Game::clearCache();
        User::clearCache();
        Cell::clearCache();
        Planet::clearCache();

        // Устанавливаем глобальные переменные для тестов
        Cell::$map_width = 20;
        Cell::$map_height = 20;
    }

    /**
     * Создает тестовую игру
     */
    protected function createTestGame($data = []): array
    {
        $defaultData = [
            "name" => "Test Game",
            "map_w" => 20,
            "map_h" => 20,
            "turn_type" => "byturn",
            "turn_num" => 1,
        ];

        $gameData = array_merge($defaultData, $data);

        $gameData["id"] = MyDB::insert("game", $gameData);
        return $gameData;
    }

    /**
     * Создает тестового пользователя
     */
    protected function createTestUser($data = []): array
    {
        // Создаем игру, если её нет
        if (!isset($data["game"])) {
            $gameData = $this->createTestGame();
            $gameId = $gameData["id"];
        } else {
            $gameId = $data["game"];
        }

        $defaultData = [
            "login" => "TestUser" . rand(1000, 9999),
            "color" => "#ff0000",
            "game" => $gameId,
            "turn_order" => 1,
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];

        $userData = array_merge($defaultData, $data);

        $userData["id"] = MyDB::insert("user", $userData);
        return $userData;
    }

    /**
     * Инициализирует базовые игровые типы
     */
    protected function initializeGameTypes(): void
    {
        TestGameDataInitializer::initializeAll();
    }

    /**
     * Создает тестовые клетки карты
     */
    protected function createTestMapCells(
        $startX,
        $startY,
        $width,
        $height,
        $planet
    ): void {
        $values = [];
        $params = [];
        for ($x = $startX; $x < $startX + $width; $x++) {
            for ($y = $startY; $y < $startY + $height; $y++) {
                $values[] = "(?, ?, ?, ?)";
                $params[] = $x;
                $params[] = $y;
                $params[] = $planet;
                $params[] = "plains";
            }
        }

        if (empty($values)) {
            return;
        }

        $sql = "INSERT INTO cell (x, y, planet, type) VALUES " .
            implode(", ", $values) .
            " ON DUPLICATE KEY UPDATE type = VALUES(type)";

        MyDB::query($sql, $params);
    }

    /**
     * Создает тестовую клетку
     */
    protected function createTestCell($data = []): array
    {
        if (!isset($data["planet"])) {
            throw new Exception("Planet ID is required to create a test cell");
        }

        $defaultData = [
            "x" => 0,
            "y" => 0,
            "type" => "plains",
        ];

        $cellData = array_merge($defaultData, $data);



        // Проверяем существует ли уже такая клетка
        $existing = MyDB::query(
            "SELECT * FROM cell WHERE x = :x AND y = :y AND planet = :planet",
            ["x" => $cellData["x"], "y" => $cellData["y"], "planet" => $cellData["planet"]],
            "row"
        );

        if (!$existing) {
            // Вставляем новую клетку (таблица cell не имеет автоинкрементного id)
            MyDB::query(
                "INSERT INTO cell (x, y, planet, type) VALUES (:x, :y, :planet, :type)",
                $cellData
            );
        } else {
            // Обновляем существующую клетку
            MyDB::update("cell", ["type" => $cellData["type"]], "x = {$cellData["x"]} AND y = {$cellData["y"]} AND planet = {$cellData["planet"]}");
        }

        return $cellData;
    }

    /**
     * Создает тестовый город
     */
    protected function createTestCity($data = []): array
    {
        if (!isset($data["planet"])) {
            throw new Exception("Planet ID is required to create a test city");
        }

        $defaultData = [
            "user_id" => 1,
            "x" => 10,
            "y" => 10,
            "title" => "Test City",
            "population" => 1,
            "pmoney" => 0,
            "presearch" => 0,
        ];

        $cityData = array_merge($defaultData, $data);

        // Создаем клетку, если она не существует
        $this->createTestCell([
            "x" => $cityData["x"],
            "y" => $cityData["y"],
            "planet" => $cityData["planet"],
            "type" => "plains",
        ]);

        // Убираем id из данных перед вставкой, так как это автоинкрементное поле
        unset($cityData["id"]);
        $insertId = MyDB::insert("city", $cityData);
        $cityData["id"] = $insertId;
        return $cityData;
    }

    /**
     * Создает тестовый юнит
     */
    protected function createTestUnit($data = []): array
    {
        if (!isset($data["planet"])) {
            throw new Exception("Planet ID is required to create a test unit");
        }

        $defaultData = [
            "x" => 5,
            "y" => 5,
            "user_id" => 1,
            "type" => 1,
            "health" => 3,
            "points" => 2,
        ];

        $unitData = array_merge($defaultData, $data);

        $unitData["id"] = MyDB::insert("unit", $unitData);
        return $unitData;
    }

    /**
     * Создает тестовую планету
     */
    protected function createTestPlanet($data = []): int
    {
        // Создаем игру, если её нет
        if (!isset($data["game_id"])) {
            $gameData = $this->createTestGame();
            $gameId = $gameData["id"];
        } else {
            $gameId = $data["game_id"];
        }

        $defaultData = [
            "name" => "Test Planet",
            "game_id" => $gameId,
        ];

        $planetData = array_merge($defaultData, $data);

        $planetData["id"] = MyDB::insert("planet", $planetData);
        return $planetData["id"];
    }

    /**
     * Симулирует POST запрос
     */
    protected function simulatePostRequest($data): void
    {
        $_REQUEST = $data;
        $_POST = $data;
    }

    /**
     * Очищает REQUEST данные
     */
    protected function clearRequest(): void
    {
        $_REQUEST = [];
        $_POST = [];
        $_GET = [];
    }

    /**
     * Получает количество записей в таблице
     */
    protected function getTableCount($tableName): int
    {
        return (int) MyDB::query(
            "SELECT COUNT(*) FROM {$tableName}",
            [],
            "elem",
        );
    }

    /**
     * Проверяет существование записи в таблице
     */
    protected function recordExists($tableName, $conditions): bool
    {
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }

        $sql =
            "SELECT COUNT(*) FROM {$tableName} WHERE " .
            implode(" AND ", $where);

        $result = MyDB::query($sql, $params, "elem");
        return $result > 0;
    }

    /**
     * Возвращает последнюю запись из таблицы
     */
    protected function getLastRecord($tableName): ?array
    {
        return MyDB::query(
            "SELECT * FROM {$tableName} ORDER BY id DESC LIMIT 1",
            [],
            "row",
        );
    }

    /**
     * Проверяет корректность HTML вывода
     */
    protected function assertValidHtml($html): void
    {
        // Базовая проверка на валидность HTML
        $this->assertStringNotContainsString(
            "<script>",
            $html,
            "HTML не должен содержать небезопасных скриптов",
        );
        $this->assertStringNotContainsString(
            "javascript:",
            $html,
            "HTML не должен содержать javascript: ссылок",
        );
    }

    /**
     * Проверяет, что строка экранирована для HTML
     */
    protected function assertHtmlEscaped($string, $original): void
    {
        $expected = htmlspecialchars($original);
        $this->assertEquals(
            $expected,
            $string,
            "Строка должна быть экранирована для HTML",
        );
    }

    /**
     * Мок для функции header() чтобы отслеживать редиректы
     */
    protected $headers = [];

    protected function mockHeader($header): void
    {
        $this->headers[] = $header;
    }

    /**
     * Проверяет был ли выполнен редирект
     */
    protected function assertRedirectTo($expectedLocation): void
    {
        $found = false;
        foreach ($this->headers as $header) {
            if (stripos($header, "Location: " . $expectedLocation) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue(
            $found,
            "Редирект на {$expectedLocation} не был выполнен",
        );
    }

    /**
     * Устанавливает сессию для тестов
     */
    protected function setSession($data): void
    {
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Очищает сессию
     */
    protected function clearSession(): void
    {
        $_SESSION = [];
    }

    /**
     * Создает полностью настроенную тестовую игру с пользователями
     */
    protected function createCompleteTestGame(
        $gameData = [],
        $userCount = 2,
    ): array {
        $game = $this->createTestGame($gameData);

        $usersData = [];
        for ($i = 0; $i < $userCount; $i++) {
            $usersData[] = [
                "game" => $game["id"],
                "login" => "TestUser" . ($i + 1),
                "turn_order" => $i + 1,
                "color" => "#ff000" . $i,
                "money" => 50,
                "age" => 1,
                "turn_status" => "wait",
            ];
        }
        $users = $this->createTestUsers($usersData);

        return [
            "game" => $game,
            "users" => $users,
        ];
    }

    protected function createTestUsers(array $usersData): array
    {
        if (empty($usersData)) {
            return [];
        }

        $keys = array_keys($usersData[0]);
        
        $values = [];
        $params = [];
        foreach($usersData as $userData) {
            $values[] = "(" . implode(", ", array_fill(0, count($keys), "?")) . ")";
            foreach($userData as $value) {
                $params[] = $value;
            }
        }

        $sql = "INSERT INTO user (" . implode(", ", array_map(fn($k) => "`$k`", $keys)) . ") VALUES " .
            implode(", ", $values);

        MyDB::query($sql, $params);

        $firstId = MyDB::get()->lastInsertId();

        $result = [];
        for ($i = 0; $i < count($usersData); $i++) {
            $usersData[$i]['id'] = $firstId + $i;
            $result[] = $usersData[$i];
        }

        return $result;
    }

    /**
     * Проверяет, что объект является экземпляром ожидаемого класса и имеет все необходимые свойства
     */
    protected function assertValidGameObject(
        $object,
        $expectedClass,
        $requiredProperties = [],
    ): void {
        $this->assertInstanceOf($expectedClass, $object);

        foreach ($requiredProperties as $property) {
            $this->assertObjectHasAttribute($property, $object);
        }
    }
}
