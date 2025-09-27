<?php

namespace App\Tests;

require_once __DIR__ . "/FunctionalTestBase.php";

use App\MyDB;

/**
 * Общий базовый класс для всех тестов с устранением дублирования
 */
class CommonTestBase extends FunctionalTestBase
{
    /**
     * Настройка для unit тестов
     */
    protected function setUpUnitTest(): void
    {
        DatabaseTestAdapter::resetTestDatabase();
        parent::setUp();
    }

    /**
     * Настройка для integration тестов
     */
    protected function setUpIntegrationTest(): void
    {
        DatabaseTestAdapter::resetTestDatabase();
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
        $this->initializeGameTypes();
        $this->clearTestData();
    }

    /**
     * Создает полную тестовую игру с пользователями и планетой
     */
    protected function createCompleteTestGame($gameData = [], $userNames = ["Игрок1", "Игрок2"]): array
    {
        $defaultGameData = [
            "name" => "Тестовая игра",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
        ];
        $gameData = array_merge($defaultGameData, $gameData);

        $game = $this->createTestGame($gameData);
        $planetId = $this->createTestPlanet(["game_id" => $game->id]);

        $users = [];
        foreach ($userNames as $index => $name) {
            $user = $this->createTestUser([
                "game" => $game->id,
                "login" => $name,
                "turn_order" => $index + 1,
                "turn_status" => $index === 0 ? "play" : "wait",
            ]);
            $users[] = $user;
        }

        return [
            "game" => $game,
            "planet" => $planetId,
            "users" => $users,
        ];
    }

    /**
     * Проверяет базовые свойства созданной игры
     */
    protected function assertGameBasics($game, $expectedName, $expectedMapW = 50, $expectedMapH = 50, $expectedTurnType = "byturn"): void
    {
        $this->assertNotNull($game, "Игра должна быть создана");
        $this->assertEquals($expectedName, $game["name"]);
        $this->assertEquals($expectedMapW, $game["map_w"]);
        $this->assertEquals($expectedMapH, $game["map_h"]);
        $this->assertEquals($expectedTurnType, $game["turn_type"]);
    }

    /**
     * Проверяет базовые свойства пользователей
     */
    protected function assertUsersBasics($users, $expectedCount = 2): void
    {
        $this->assertCount($expectedCount, $users, "Должно быть {$expectedCount} пользователя");

        foreach ($users as $index => $user) {
            $this->assertEquals(50, $user["money"], "Начальные деньги игрока");
            $this->assertEquals(1, $user["age"], "Начальная эра игрока");
            $this->assertEquals($index + 1, $user["turn_order"], "Порядок ходов");
            $this->assertNotEmpty($user["color"], "Цвет пользователя");
            $this->assertStringStartsWith("#", $user["color"], "Цвет должен начинаться с #");
            $this->assertEquals(7, strlen($user["color"]), "Цвет должен быть в формате #RRGGBB");
        }
    }

    /**
     * Проверяет, что игра создана через веб-интерфейс
     */
    protected function assertGameCreatedViaWeb($result, $expectedGameData): void
    {
        $this->assertPageHasNoError($result);

        $gameRecord = $this->getLastRecord("game");
        $this->assertGameBasics($gameRecord, $expectedGameData["name"],
            $expectedGameData["map_w"] ?? 50,
            $expectedGameData["map_h"] ?? 50,
            $expectedGameData["turn_type"] ?? "byturn");

        if (isset($expectedGameData["users"])) {
            $users = MyDB::query(
                "SELECT * FROM user WHERE game = :game_id ORDER BY turn_order",
                ["game_id" => $gameRecord["id"]]
            );
            $this->assertUsersBasics($users, count($expectedGameData["users"]));

            foreach ($users as $index => $user) {
                $this->assertEquals($expectedGameData["users"][$index], $user["login"]);
            }
        }
    }

    /**
     * Выполняет создание игры через страницу и проверяет результат
     */
    protected function createGameViaPageAndAssert($gameData): array
    {
        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php", $gameData);
        $this->assertGameCreatedViaWeb($result, $gameData);

        $gameRecord = $this->getLastRecord("game");
        return [
            "result" => $result,
            "game" => $gameRecord,
        ];
    }

    /**
     * Проверяет уникальность цветов пользователей
     */
    protected function assertUniqueUserColors($gameId): void
    {
        $colors = MyDB::query("SELECT color FROM user WHERE game = :game_id", ["game_id" => $gameId]);
        $colorValues = array_column($colors, "color");
        $this->assertCount(count($colorValues), array_unique($colorValues), "Цвета пользователей должны быть уникальными");
    }

    /**
     * Проверяет порядок ходов пользователей
     */
    protected function assertUserTurnOrder($gameId, $expectedUserNames): void
    {
        $userOrder = MyDB::query("SELECT login FROM user WHERE game = :game_id ORDER BY turn_order", ["game_id" => $gameId]);
        $actualNames = array_column($userOrder, "login");
        $this->assertEquals($expectedUserNames, $actualNames, "Порядок пользователей должен соответствовать ожидаемому");
    }
}
