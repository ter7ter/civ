<?php

namespace App\Tests;

use App\Building;
use App\Cell;
use App\City;
use App\Game;
use App\MyDB;
use App\Planet;
use App\Unit;
use App\User;

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

        // Для ParaTest устанавливаем уникальную базу данных
        if ($isParaTest) {
            if (empty($testToken)) {
                // Если TEST_TOKEN не установлен, используем PID
                $testToken = getmypid();
            }

            // Создаем уникальное имя базы данных
            $uniqueDbName = MyDB::$dbname . '_' . $testToken;
            
            // Устанавливаем уникальное имя базы данных
            MyDB::$dbname = $uniqueDbName;

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
        if ($isParaTest) {
            if (empty($testToken)) {
                // Если TEST_TOKEN не установлен, используем PID
                $testToken = getmypid();
            }

            $uniqueDbName = MyDB::$dbname; // Имя базы данных уже установлено в setUpBeforeClass

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
            // Для обычных тестов откатываем транзакцию
            if (MyDB::get()->inTransaction()) {
                MyDB::get()->rollBack();
            }
        }
        // Для ParaTest не очищаем таблицы в tearDown, поскольку каждый процесс имеет свою базу данных
        // и данные будут очищены в следующем setUp()

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
     * Создает тестовую игру
     */
    protected function createTestGame($data = []): Game
    {
        $defaultData = [
            "name" => "Test Game",
            "map_w" => 20,
            "map_h" => 20,
            "turn_type" => "byturn",
            "turn_num" => 1,
        ];

        $gameData = array_merge($defaultData, $data);

        $game = new Game($gameData);
        $game->save();

        return $game;
    }

    /**
     * Создает тестового пользователя
     */
    protected function createTestUser($data = []): array
    {
        // Создаем игру, если её нет
        if (!isset($data["game"])) {
            $game = $this->createTestGame();
            $gameId = $game->id;
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

        $user = new User($userData);
        $user->save();

        return [
            "id" => $user->id,
            "login" => $user->login,
            "color" => $user->color,
            "game" => $user->game,
            "turn_order" => $user->turn_order,
            "turn_status" => $user->turn_status,
            "money" => $user->money,
            "age" => $user->age,
        ];
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

        $cell = new Cell($cellData);
        $cell->save();

        return $cellData;
    }

    /**
     * Создает тестовый город
     */
    protected function createTestCity($data = []): City
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

        $user = User::get($cityData["user_id"]);
        $city = City::new_city($user, $cityData["x"], $cityData["y"], $cityData["title"], $cityData["planet"]);

        return $city;
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

        $unit = new Unit($unitData);
        $unit->save();

        return $unit;
    }

    /**
     * Создает тестовую планету
     */
    protected function createTestPlanet($data = []): int
    {
        // Создаем игру, если её нет
        if (!isset($data["game_id"])) {
            $game = $this->createTestGame();
            $gameId = $game->id;
        } else {
            $gameId = $data["game_id"];
        }

        $defaultData = [
            "name" => "Test Planet",
            "game_id" => $gameId,
        ];

        $planetData = array_merge($defaultData, $data);

        $planet = new Planet($planetData);
        $planet->save();
        return $planet->id;
    }

    /**
     * Создает тестовую игру с планетой
     */
    protected function createTestGameWithPlanet($gameData = [], $planetData = []): array
    {
        $game = $this->createTestGame($gameData);
        $planetId = $this->createTestPlanet(array_merge(['game_id' => $game->id], $planetData));

        return [
            'game' => $game,
            'planet' => $planetId,
        ];
    }

    /**
     * Создает тестовую игру с планетой и пользователем
     */
    protected function createTestGameWithPlanetAndUser($gameData = [], $planetData = [], $userData = []): array
    {
        $result = $this->createTestGameWithPlanet($gameData, $planetData);
        $user = $this->createTestUser(array_merge(['game' => $result['game']->id], $userData));

        return array_merge($result, [
            'user' => $user,
        ]);
    }

    /**
     * Создает тестовую игру с планетой, пользователем и городом
     */
    protected function createTestGameWithPlanetUserAndCity($gameData = [], $planetData = [], $userData = [], $cityData = []): array
    {
        $result = $this->createTestGameWithPlanetAndUser($gameData, $planetData, $userData);
        $city = $this->createTestCity(array_merge([
            'user_id' => $result['user']['id'],
            'planet' => $result['planet']
        ], $cityData));

        return array_merge($result, [
            'city' => $city,
        ]);
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
        $result = [];
        foreach ($usersData as $userData) {
            $result[] = $this->createTestUser($userData);
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

    protected function assertDatabaseHas($tableName, $conditions)
    {
        $this->assertTrue($this->recordExists($tableName, $conditions));
    }

    protected function assertDatabaseMissing($tableName, $conditions)
    {
        $this->assertFalse($this->recordExists($tableName, $conditions));
    }
}
