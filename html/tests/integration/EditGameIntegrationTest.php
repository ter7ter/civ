<?php

namespace App\Tests\Integration;

use App\MyDB;
use App\Tests\Mocks\DatabaseTestAdapter;
use App\Tests\Base\FunctionalTestBase;
use App\Tests\Factory\TestDataFactory;

/**
 * Интеграционные тесты для полного процесса редактирования игры через веб-интерфейс.
 * Тестирует функциональность страницы editgame.php, включая валидацию, сохранение данных,
 * безопасность и производительность.
 */
class EditGameIntegrationTest extends FunctionalTestBase
{
    /**
     * Поставщик данных для тестов с разными типами ходов.
     */
    public function turnTypeProvider(): array
    {
        return [
            ['concurrently'],
            ['byturn'],
            ['onewindow'],
        ];
    }

    /**
     * Поставщик данных для тестов производительности с разным количеством игроков.
     */
    public function playerCountProvider(): array
    {
        return [
            [1],
            [8],
            [16],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
    }

    /**
     * Тест полного процесса редактирования игры.
     */
    public function testFullGameEditProcess(): void
    {
        $originalGame = TestDataFactory::createTestGame(["name" => "Исходная игра"]);
        TestDataFactory::createTestUser(["login" => "Алиса", "game" => $originalGame->id]);

        $editData = [
            "game_id" => $originalGame->id,
            "name" => "Отредактированная игра",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "concurrently",
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $editData);

        $this->assertPageHasNoError($result);
        $this->assertTrue($this->recordExists("game", ["name" => "Отредактированная игра"]));
        $this->assertFalse($this->recordExists("game", ["name" => "Исходная игра"]));
        $this->assertTrue($this->recordExists("user", ["login" => "Алиса"]));
    }

    /**
     * Тест сохранения измененных данных игры.
     */
    public function testEditedGameDataPersistence(): void
    {
        $originalGame = TestDataFactory::createTestGame();
        $editData = [
            "game_id" => $originalGame->id,
            "name" => "Обновленное название",
            "map_w" => 60,
            "map_h" => 60,
            "turn_type" => "onewindow",
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $editData);
        $this->assertPageHasNoError($result);

        $gameRecord = $this->getLastRecord("game");
        $this->assertEquals("Обновленное название", $gameRecord["name"]);
        $this->assertEquals(60, $gameRecord["map_w"]);
        $this->assertEquals("onewindow", $gameRecord["turn_type"]);
    }

    /**
     * Тест редактирования с разными типами ходов.
     *
     * @dataProvider turnTypeProvider
     */
    public function testEditDifferentTurnTypes(string $turnType): void
    {
        $game = TestDataFactory::createTestGame();

        $editData = [
            "game_id" => $game->id,
            "name" => "Игра с типом $turnType",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => $turnType,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $editData);
        $this->assertPageHasNoError($result);

        $gameRecord = $this->getLastRecord("game");
        $this->assertEquals($turnType, $gameRecord["turn_type"]);
    }

    /**
     * Тест сохранения порядка игроков при редактировании.
     */
    public function testPlayerOrderPreservationOnEdit(): void
    {
        $game = TestDataFactory::createTestGame();
        $players = ["Альфа", "Бета", "Гамма"];
        foreach ($players as $i => $name) {
            TestDataFactory::createTestUser(["login" => $name, "game" => $game->id, "turn_order" => $i + 1]);
        }

        $editData = ["game_id" => $game->id, "name" => "Новое имя"];
        $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $editData);

        $userOrder = MyDB::query("SELECT login FROM user WHERE game = ? ORDER BY turn_order", [$game->id]);
        $this->assertEquals($players, array_column($userOrder, "login"));
    }

    /**
     * Тест сохранения параметров игроков при редактировании.
     */
    public function testPlayerParametersPreservationOnEdit(): void
    {
        $game = TestDataFactory::createTestGame();
        TestDataFactory::createTestUser(["login" => "Богач", "game" => $game->id, "money" => 100, "age" => 2]);

        $editData = ["game_id" => $game->id, "name" => "Новое имя"];
        $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $editData);

        $user = $this->getLastRecord("user");
        $this->assertEquals(100, $user["money"]);
        $this->assertEquals(2, $user["age"]);
    }

    /**
     * Тест ошибок валидации при редактировании.
     */
    public function testValidationErrorsOnEdit(): void
    {
        $game = TestDataFactory::createTestGame(["name" => "Исходная игра"]);
        $invalidData = [
            "game_id" => $game->id,
            "name" => "",
            "map_w" => 9999,
            "map_h" => 50, // Добавлено
            "turn_type" => "byturn", // Добавлено
        ];

        $result = $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $invalidData);

        $this->assertPageHasError($result, "Название игры не может быть пустым");
        $this->assertPageHasError($result, "должна быть от 50 до 500");

        $gameRecord = $this->getLastRecord("game");
        $this->assertEquals("Исходная игра", $gameRecord["name"]);
    }

    /**
     * Тест безопасности: SQL-инъекции при редактировании.
     */
    public function testSQLInjectionProtectionOnEdit(): void
    {
        $game = TestDataFactory::createTestGame(["name" => "Безопасная игра"]);
        $maliciousData = [
            "game_id" => $game->id,
            "name" => "'; DROP TABLE game; --",
        ];

        $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $maliciousData);

        // Проверяем, что таблица game все еще существует
        $result = MyDB::query("SHOW TABLES LIKE 'game'");
        $this->assertNotEmpty($result, "Таблица game должна существовать после попытки SQL-инъекции");
    }

    /**
     * Тест редактирования с сохранением связанных данных.
     */
    public function testEditWithRelatedDataPreservation(): void
    {
        $game = TestDataFactory::createTestGame(["turn_num" => 5]);
        TestDataFactory::createTestUser(["game" => $game->id, "money" => 150, "age" => 3]);

        $editData = ["game_id" => $game->id, "name" => "Переименованная игра"];
        $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $editData);

        $updatedGame = $this->getLastRecord("game");
        $this->assertEquals(5, $updatedGame["turn_num"]);

        $user = $this->getLastRecord("user");
        $this->assertEquals(150, $user["money"]);
        $this->assertEquals(3, $user["age"]);
    }

    // Тест производительности редактирования игры с максимальным количеством игроков
    public function testPerformanceEditWithMaxPlayers(): void
    {
        $game = TestDataFactory::createTestGame([
            "name" => "Тест производительности редактирования",
            "map_w" => 200,
            "map_h" => 200,
            "turn_type" => "byturn",
        ]);

        // Создаем максимальное количество игроков
        for ($i = 1; $i <= 16; $i++) {
            TestDataFactory::createTestUser([
                "login" => "Игрок{$i}",
                "game" => $game->id,
                "turn_order" => $i,
            ]);
        }

        $editData = [
            "game_id" => $game->id,
            "name" => "Отредактированная игра с 16 игроками",
            "map_w" => 500,
            "map_h" => 500,
            "turn_type" => "concurrently",
        ];

        $startTime = microtime(true);

        $result = $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $editData);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertPageHasNoError($result);
        $this->assertLessThan(
            3.0,
            $executionTime,
            "Редактирование игры должно занимать менее 3 секунд",
        );

        // Проверяем, что все данные обновились корректно
        $gameRecord = $this->getLastRecord("game");
        $this->assertEquals(
            "Отредактированная игра с 16 игроками",
            $gameRecord["name"],
        );
        $this->assertEquals(500, $gameRecord["map_w"]);
        $this->assertEquals(500, $gameRecord["map_h"]);
        $this->assertEquals("concurrently", $gameRecord["turn_type"]);

        // Проверяем, что все игроки остались
        $playerCount = MyDB::query("SELECT COUNT(*) FROM user WHERE game = ?", [$game->id])[0]["COUNT(*)"];

        $this->assertEquals(
            16,
            $playerCount,
            "Должно остаться 16 игроков",
        );
    }

    /**
     * Тест редактирования нескольких игр подряд
     */
    public function testMultipleGameEdits(): void
    {
        $games = [];

        // Создаем несколько игр
        for ($i = 1; $i <= 3; $i++) {
            $game = TestDataFactory::createTestGame([
                "name" => "Игра {$i}",
                "map_w" => 100,
                "map_h" => 100,
                "turn_type" => "byturn",
            ]);

            TestDataFactory::createTestUser(["login" => "А{$i}", "game" => $game->id]);
            TestDataFactory::createTestUser(["login" => "Б{$i}", "game" => $game->id]);

            $games[] = $game;
        }

        // Редактируем каждую игру
        foreach ($games as $index => $game) {
            $editData = [
                "game_id" => $game->id,
                "name" => "Отредактированная игра " . ($index + 1),
                "map_w" => 150 + $index * 50,
                "map_h" => 150 + $index * 50,
                "turn_type" => "concurrently",
            ];

            $result = $this->executePage(PROJECT_ROOT . "/pages/editgame.php", $editData);
            $this->assertPageHasNoError($result);
        }

        // Проверяем, что все игры были отредактированы корректно
        $allGames = MyDB::query("SELECT * FROM game WHERE id IN (?, ?, ?)", [$games[0]->id, $games[1]->id, $games[2]->id]);
        $this->assertEquals(3, count($allGames), "Должно быть 3 игры");

        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                "Отредактированная игра " . ($i + 1),
                $allGames[$i]["name"],
            );
            $this->assertEquals(150 + $i * 50, $allGames[$i]["map_w"]);
            $this->assertEquals(150 + $i * 50, $allGames[$i]["map_h"]);
            $this->assertEquals("concurrently", $allGames[$i]["turn_type"]);
        }

        // Проверяем общее количество игроков
        $this->assertEquals(
            6,
            $this->getTableCount("user"),
            "Должно быть 6 игроков всего",
        );
    }
}
