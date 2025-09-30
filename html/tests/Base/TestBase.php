<?php

namespace App\Tests\Base;

use App\Building;
use App\Cell;
use App\Game;
use App\MyDB;
use App\Planet;
use App\User;
use App\Tests\Mocks\DatabaseTestAdapter;
use App\Tests\Factory\TestDataFactory;
use PDO;

/**
 * Базовый класс для всех тестов
 * Использует реальные классы игры вместо моков
 */
class TestBase extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {

        // Проверяем, используется ли ParaTest
        $testToken = getenv('TEST_TOKEN');
        $paraTestFlag = getenv('PARATEST');
        $isParaTest = !empty($testToken) || $paraTestFlag === '1';

        if (!$isParaTest) {

        }

        // Очищаем БД один раз перед всеми тестами класса
        DatabaseTestAdapter::resetTestDatabase();

        // Очищаем кэши реальных классов
        Game::clearCache();
        User::clearCache();
        Cell::clearCache();
        Planet::clearCache();
        Building::clearCache();

        // Устанавливаем глобальные переменные для тестов
        Cell::$map_width = 20;
        Cell::$map_height = 20;
    }

    public static function tearDownAfterClass(): void
    {
        // Проверяем, используется ли ParaTest
        $testToken = getenv('TEST_TOKEN');
        $paraTestFlag = getenv('PARATEST');
        $isParaTest = !empty($testToken) || $paraTestFlag === '1';

        // Для ParaTest удаляем уникальную базу данных

        MyDB::rollbackTransaction();
        if ($isParaTest) {
            $uniqueDbName = 'civ_for_tests_'.$testToken; // Имя базы данных уже установлено в setUpBeforeClass

            // Подключаемся к MySQL без указания базы данных
            $tempDsn = "mysql:host=" . MyDB::$dbhost . ";port=" . MyDB::$dbport . ";charset=utf8";
            $tempPdo = new PDO($tempDsn, MyDB::$dbuser, MyDB::$dbpass);
            $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Удаляем уникальную базу данных
            $tempPdo->exec("DROP DATABASE IF EXISTS `{$uniqueDbName}`");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Проверяем, используется ли ParaTest
        $testToken = getenv('TEST_TOKEN');
        $isParaTest = !empty($testToken);

        // Только кэши и переменные, не всю БД
        Game::clearCache();
        User::clearCache();
        Cell::clearCache();
        Planet::clearCache();
        Building::clearCache();
        Cell::$map_width = 20;
        Cell::$map_height = 20;
    }

    protected function tearDown(): void
    {
        // Проверяем, используется ли ParaTest
        $testToken = getenv('TEST_TOKEN');
        $isParaTest = !empty($testToken);

        if (!$isParaTest) {
            // Для ParaTest не очищаем таблицы в tearDown, поскольку каждый процесс имеет свою базу данных
            // и данные будут очищены в следующем setUp()
            if (MyDB::get()->inTransaction()) {
                MyDB::get()->rollBack();
            }
        }
        // Очищаем все кэши и статические переменные
        Game::clearCache();
        User::clearCache();
        Cell::clearCache();
        Planet::clearCache();
        Building::clearCache();
        TestGameDataInitializer::clearAll();
        Cell::$map_width = 20;
        Cell::$map_height = 20;
        parent::tearDown();
    }

    /**
     * Очищает тестовые данные (оставлено для совместимости, но не вызывает resetTestDatabase)
     */
    protected function clearTestData(): void
    {
        // Не вызываем DatabaseTestAdapter::resetTestDatabase();
        Game::clearCache();
        User::clearCache();
        Cell::clearCache();
        Planet::clearCache();
        // Устанавливаем глобальные переменные для тестов
        Cell::$map_width = 20;
        Cell::$map_height = 20;
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



    protected function createTestUsers(array $usersData): array
    {
        return TestDataFactory::createTestUsers($usersData);
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

    protected function assertDatabaseHas($tableName, $conditions)
    {
        $this->assertTrue($this->recordExists($tableName, $conditions));
    }

    protected function assertDatabaseMissing($tableName, $conditions)
    {
        $this->assertFalse($this->recordExists($tableName, $conditions));
    }
}
