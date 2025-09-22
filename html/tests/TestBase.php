<?php

// В тестах НЕ подключаем includes.php, чтобы избежать конфликтов с моками
// require_once __DIR__ . "/../includes.php";
require_once __DIR__ . "/DatabaseMocks.php";

// Предотвращаем подключение includes.php в тестах
if (!defined('TEST_INCLUDES_LOADED')) {
    define('TEST_INCLUDES_LOADED', true);
}

/**
 * Базовый класс для всех тестов
 */
class TestBase extends PHPUnit\Framework\TestCase
{
    protected static $pdo;
    protected static $originalDb;

    public static function setUpBeforeClass(): void
    {
        // Инициализируем тестовое окружение с моками
        initializeTestEnvironment();

        // Сохраняем ссылку на тестовую БД
        self::$pdo = DatabaseTestAdapter::getConnection();

        // Очищаем БД перед запуском тестов класса
        DatabaseTestAdapter::resetTestDatabase();
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

        $gameData["id"] = DatabaseTestAdapter::insert("game", $gameData);
        return $gameData;
    }

    /**
     * Создает тестового пользователя
     */
    protected function createTestUser($data = []): array
    {
        $defaultData = [
            "login" => "TestUser",
            "color" => "#ff0000",
            "game" => 1,
            "turn_order" => 1,
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];

        $userData = array_merge($defaultData, $data);

        $userData["id"] = DatabaseTestAdapter::insert("user", $userData);
        return $userData;
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
        return (int)DatabaseTestAdapter::query(
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

        $result = DatabaseTestAdapter::query($sql, $params, "elem");
        return $result > 0;
    }

    /**
     * Возвращает последнюю запись из таблицы
     */
    protected function getLastRecord($tableName): ?array
    {
        return DatabaseTestAdapter::query(
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
}
