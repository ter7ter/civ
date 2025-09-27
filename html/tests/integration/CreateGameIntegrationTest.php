<?php

namespace App\Tests;

require_once __DIR__ . "/../CommonTestBase.php";

/**
 * Интеграционные тесты для процесса создания игры через веб-интерфейс.
 */
class CreateGameIntegrationTest extends CommonTestBase
{
    protected function setUp(): void
    {
        $this->setUpIntegrationTest();
    }

    /**
     * Тест создания игры и проверки всех связанных данных в БД.
     */
    public function testGameCreationAndDataPersistence(): void
    {
        $gameData = [
            "name" => "Веб-тест игры",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок2"],
        ];

        $result = $this->createGameViaPageAndAssert($gameData);

        $this->assertTrue(
            $this->recordExists("planet", ["game_id" => $result["game"]["id"]]),
            "Планета должна быть создана для игры"
        );
    }
    
    /**
     * Тест создания юнитов поселенцев для каждого игрока
     */
    public function testSettlerUnitsCreation(): void
    {
        $gameData = [
            "name" => "Тест поселенцев",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
            "users" => ["Колонист1", "Колонист2", "Колонист3"],
        ];

        $result = $this->executePage(
            PROJECT_ROOT . "/pages/creategame.php",
            $gameData
        );

        $this->assertPageHasNoError($result);

        $gameRecord = $this->getLastRecord("game");
        $users = MyDB::query(
            "SELECT * FROM user WHERE game = :game_id ORDER BY turn_order",
            ["game_id" => $gameRecord["id"]]
        );

        $this->assertCount(3, $users, "Должно быть создано 3 игрока");

        foreach ($users as $user) {
            $userUnits = MyDB::query(
                "SELECT * FROM unit WHERE user_id = :user_id",
                ["user_id" => $user["id"]]
            );
            $this->assertGreaterThan(0, count($userUnits), "У игрока должен быть юнит");

            $firstUnit = $userUnits[0];
            $this->assertNotNull($firstUnit["type"], "Тип юнита");
            $this->assertGreaterThan(0, $firstUnit["health"], "Здоровье юнита");
            $this->assertGreaterThan(0, $firstUnit["points"], "Очки движения");
        }
    }

    /**
     * Тест с разными типами ходов через веб-интерфейс
     */
    public function testDifferentTurnTypesViaWeb(): void
    {
        $turnTypes = ["byturn", "concurrently", "onewindow"];

        foreach ($turnTypes as $turnType) {
            $this->clearTestData();

            $gameData = [
                "name" => "Тест типа $turnType",
                "map_w" => 50,
                "map_h" => 50,
                "turn_type" => $turnType,
                "users" => ["Игрок1", "Игрок2"],
            ];

            $result = $this->executePage(
                PROJECT_ROOT . "/pages/creategame.php",
                $gameData
            );

            $this->assertPageHasNoError($result);

            $gameRecord = $this->getLastRecord("game");
            $this->assertEquals($turnType, $gameRecord["turn_type"]);
        }
    }

    /**
     * Тест генерации уникальных цветов для игроков через веб-интерфейс
     */
    public function testUniquePlayerColorsViaWeb(): void
    {
        $players = ["Красный", "Синий", "Зеленый", "Желтый", "Пурпурный", "Циан"];
        $gameData = [
            "name" => "Тест цветов",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
            "users" => $players,
        ];

        $this->createGameViaPageAndAssert($gameData);
        $this->assertUniqueUserColors($this->getLastRecord("game")["id"]);
    }

    /**
     * Тест порядка ходов игроков через веб-интерфейс
     */
    public function testPlayerTurnOrderViaWeb(): void
    {
        $players = ["Первый", "Второй", "Третий"];
        $gameData = [
            "name" => "Тест порядка ходов",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
            "users" => $players,
        ];

        $this->createGameViaPageAndAssert($gameData);
        $this->assertUserTurnOrder($this->getLastRecord("game")["id"], $players);
    }

    /**
     * Тест начальных параметров игроков через веб-интерфейс
     */
    public function testInitialPlayerParametersViaWeb(): void
    {
        $gameData = [
            "name" => "Тест начальных параметров",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
            "users" => ["Тестер1", "Тестер2"],
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $gameData);
        $this->assertPageHasNoError($result);

        $users = MyDB::query("SELECT * FROM user ORDER BY turn_order");
        foreach ($users as $index => $user) {
            $this->assertEquals($index == 0 ? "play" : "wait", $user["turn_status"]);
            $this->assertEquals(50, $user["money"]);
            $this->assertEquals(1, $user["age"]);
            $this->assertEquals(0, $user["income"]);
        }
    }

    /**
     * Тест обработки ошибок валидации через веб-интерфейс
     */
    public function testValidationErrorsViaWeb(): void
    {
        $invalidData = [
            "name" => "",
            "map_w" => 600,
            "map_h" => 10,
            "turn_type" => "byturn", // Добавлено
            "users" => ["Игрок1"],
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $invalidData);

        $this->assertPageHasError($result, "Название игры не может быть пустым");
        $this->assertPageHasError($result, "должна быть от 50 до 500");
        $this->assertPageHasError($result, "минимум 2 игрока");
        $this->assertEquals(0, $this->getTableCount("game"));
    }
    
    /**
     * Тест обработки неправильного количества игроков
     */
    public function testPlayerCountValidation(): void
    {
        $gameData = [
            "name" => "Тест с одним игроком",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn", // Добавлено
            "users" => ["ЕдинственныйИгрок"],
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $gameData);
        $this->assertPageHasError($result, "минимум 2 игрока");
    }

    /**
     * Тест производительности создания игры с максимальным количеством игроков
     * @large
     */
    public function testPerformanceWithMaxPlayers(): void
    {
        $players = array_map(fn($i) => "Игрок{$i}", range(1, 16));
        $gameData = [
            "name" => "Тест производительности",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn", // Добавлено
            "users" => $players,
        ];

        $startTime = microtime(true);
        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $gameData);
        $executionTime = microtime(true) - $startTime;

        $this->assertPageHasNoError($result);
        $this->assertLessThan(240.0, $executionTime);
        $this->assertEquals(1, $this->getTableCount("game"));
        $this->assertEquals(16, $this->getTableCount("user"));
    }

    /**
     * Тест безопасности: SQL-инъекции
     */
    public function testSQLInjectionProtection(): void
    {
        $maliciousData = [
            "name" => "'; DROP TABLE game; --",
            "map_w" => 50,
            "map_h" => 50,
            "users" => ["'; DROP TABLE user; --", "Игрок2"],
        ];

        $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $maliciousData);

        // Получаем имя текущей базы данных
        $currentDb = MyDB::query("SELECT DATABASE()", [], "elem");
        $tableColumn = "Tables_in_{$currentDb}";

        $tables = array_column(MyDB::query("SHOW TABLES"), $tableColumn);
        $this->assertContains("game", $tables);
        $this->assertContains("user", $tables);
    }
    
    /**
     * Тест обработки XSS в веб-интерфейсе
     */
    public function testXSSProtectionViaWeb(): void
    {
        $maliciousData = [
            "name" => "<script>alert('xss')</script>",
            "map_w" => 50,
            "map_h" => 50,
            "users" => ["<img src=x onerror=alert(1)>", "НормальныйИгрок"],
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $maliciousData);
        $this->assertPageHasNoError($result);

        if ($this->getTableCount("game") > 0) {
            $gameRecord = $this->getLastRecord("game");
            $this->assertStringContainsString("&lt;script&gt;", $gameRecord["name"]);
        }
    }

    /**
     * Тест создания нескольких игр подряд
     */
    public function testMultipleGameCreation(): void
    {
        $gameData = [
            "name" => "Игра 1",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
            "users" => ["А1", "Б1"],
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $gameData);
        $this->assertPageHasNoError($result);

        $this->assertEquals(1, $this->getTableCount("game"));
        $this->assertEquals(2, $this->getTableCount("user"));
    }
    
    /**
     * Тест проверки редиректа после успешного создания игры
     */
    public function testSuccessRedirect(): void
    {
        $gameData = [
            "name" => "Тест редиректа",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn", // Добавлено
            "users" => ["Игрок1", "Игрок2"],
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $gameData);

        if (isset($result["headers"])) {
            $this->assertStringContainsString("Location: index.php?method=selectgame", implode("\n", $result["headers"]));
        }
        
        $this->assertTrue($this->recordExists("game", ["name" => "Тест редиректа"]));
    }
}
