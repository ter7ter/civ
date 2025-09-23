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

        // Устанавливаем глобальные переменные для тестов
        Cell::$map_planet = 0;
        Cell::$map_width = 100;
        Cell::$map_height = 100;
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

        // Устанавливаем глобальные переменные для тестов
        Cell::$map_planet = 0;
        Cell::$map_width = 100;
        Cell::$map_height = 100;
    }

    /**
     * Создает тестовую игру
     */
    protected function createTestGame($data = []): array
    {
        $defaultData = [
            "name" => "Test Game",
            "map_w" => 100,
            "map_h" => 100,
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
    ): void {
        for ($x = $startX; $x < $startX + $width; $x++) {
            for ($y = $startY; $y < $startY + $height; $y++) {
                $cellData = [
                    "x" => $x,
                    "y" => $y,
                    "planet" => 0,
                    "type" => "plains",
                ];
                MyDB::insert("cell", $cellData);
            }
        }
    }

    /**
     * Создает тестовую клетку
     */
    protected function createTestCell($data = []): array
    {
        $defaultData = [
            "x" => 0,
            "y" => 0,
            "planet" => 1,
            "type" => "plains",
        ];

        $cellData = array_merge($defaultData, $data);

        MyDB::query(
            "INSERT OR REPLACE INTO cell (x, y, planet, type) VALUES (:x, :y, :planet, :type)",
            $cellData,
        );

        return $cellData;
    }

    /**
     * Создает тестовый город
     */
    protected function createTestCity($data = []): array
    {
        $defaultData = [
            "user_id" => 1,
            "x" => 10,
            "y" => 10,
            "planet" => 1,
            "title" => "Test City",
            "people" => 1,
            "pmoney" => 10,
            "presearch" => 5,
        ];

        $cityData = array_merge($defaultData, $data);

        $cityData["id"] = MyDB::insert("city", $cityData);
        return $cityData;
    }

    /**
     * Создает тестовый юнит
     */
    protected function createTestUnit($data = []): array
    {
        $defaultData = [
            "x" => 5,
            "y" => 5,
            "planet" => 1,
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

        $users = [];
        for ($i = 0; $i < $userCount; $i++) {
            $userData = [
                "game" => $game["id"],
                "login" => "TestUser" . ($i + 1),
                "turn_order" => $i + 1,
                "color" => "#ff000" . $i,
            ];
            $users[] = $this->createTestUser($userData);
        }

        return [
            "game" => $game,
            "users" => $users,
        ];
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
