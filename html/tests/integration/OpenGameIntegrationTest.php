<?php

namespace App\Tests\Integration;

use App\Game;
use App\User;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Mocks\DatabaseTestAdapter;
use App\Tests\Base\FunctionalTestBase;

/**
 * Интеграционные тесты для полного процесса открытия игры через веб-интерфейс.
 */
class OpenGameIntegrationTest extends FunctionalTestBase
{
    protected function setUp(): void
    {
        DatabaseTestAdapter::resetTestDatabase();
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
        $this->clearTestData(); // Добавлено для обеспечения чистого состояния перед каждым тестом
    }

    /**
     * Тест полного процесса открытия игры.
     */
    public function testFullGameOpeningProcess(): void
    {
        $game = TestDataFactory::createTestGame(["name" => "Интеграционная игра"]);
        $player = TestDataFactory::createTestUser(["login" => "Игрок1", "game" => $game->id]);

        $_REQUEST = [
            "method" => "login",
            "gid" => $game->id,
            "uid" => $player->id,
        ];

        $this->simulateGameOpening();

        $this->assertEquals($game->id, $_SESSION["game_id"]);
        $this->assertEquals($player->id, $_SESSION["user_id"]);

        $this->assertTrue($this->recordExists("game", ["id" => $game->id]));
        $this->assertTrue($this->recordExists("user", ["id" => $player->id, "game" => $game->id]));
    }

    /**
     * Тест открытия игры и проверки данных в сессии.
     */
    public function testGameOpeningSessionPersistence(): void
    {
        $game = TestDataFactory::createTestGame(["name" => "Тест сессии"]);
        $user = TestDataFactory::createTestUser(["login" => "Тестовый пользователь", "game" => $game->id]);

        $_REQUEST = [
            "method" => "login",
            "gid" => $game->id,
            "uid" => $user->id,
        ];

        $this->simulateGameOpening();

        $this->assertEquals($game->id, $_SESSION["game_id"]);
        $this->assertEquals($user->id, $_SESSION["user_id"]);

        $sessionGame = Game::get($_SESSION["game_id"]);
        $sessionUser = User::get($_SESSION["user_id"]);

        $this->assertNotNull($sessionGame);
        $this->assertNotNull($sessionUser);
        $this->assertEquals("Тест сессии", $sessionGame->name);
        $this->assertEquals("Тестовый пользователь", $sessionUser->login);
    }

    /**
     * Тест открытия игры с различными конфигурациями.
     */
    public function testGameOpeningWithDifferentConfigurations(): void
    {
        $configurations = [
            ["name" => "Маленькая карта", "map_w" => 50, "map_h" => 50, "turn_type" => "byturn"],
            ["name" => "Большая карта", "map_w" => 500, "map_h" => 500, "turn_type" => "concurrently"],
        ];

        foreach ($configurations as $config) {
            $this->clearTestData();
            $this->clearSession();

            $game = TestDataFactory::createTestGame($config);
            $user = TestDataFactory::createTestUser(["login" => "Игрок", "game" => $game->id]);

            $_REQUEST = [
                "method" => "login",
                "gid" => $game->id,
                "uid" => $user->id,
            ];

            $this->simulateGameOpening();
            $this->assertEquals($game->id, $_SESSION["game_id"], "Сессия должна быть установлена для конфигурации: {$config["name"]}");
        }
    }

    /**
     * Тест списка игр с различным количеством игроков.
     * Этот тест не взаимодействует напрямую с веб-интерфейсом открытия игры, поэтому остается без изменений.
     */
    public function testGameListWithDifferentPlayerCounts(): void
    {
        $gameConfigs = [
            ["name" => "Одиночная игра", "players" => 1],
            ["name" => "Дуэль", "players" => 2],
        ];

        foreach ($gameConfigs as $config) {
            $game = TestDataFactory::createTestGame(["name" => $config["name"]]);
            for ($i = 1; $i <= $config["players"]; $i++) {
                TestDataFactory::createTestUser(["login" => "Игрок{$i}", "game" => $game->id]);
            }
        }

        $gamelist = Game::game_list();
        $this->assertCount(2, $gamelist);
    }

    /**
     * Тест обработки ошибок при открытии игры.
     */
    public function testErrorHandlingInGameOpening(): void
    {
        // Тест с несуществующей игрой
        $_REQUEST = ["method" => "login", "gid" => 999, "uid" => 1];
        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals("game error", $output);

        // Тест с несуществующим пользователем
        $game = TestDataFactory::createTestGame();
        $_REQUEST = ["method" => "login", "gid" => $game->id, "uid" => 999];
        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals("user error", $output);

        // Тест с пользователем из другой игры
        $game1 = TestDataFactory::createTestGame(["name" => "Игра 1"]);
        $game2 = TestDataFactory::createTestGame(["name" => "Игра 2"]);
        $userFromGame2 = TestDataFactory::createTestUser(["game" => $game2->id]);
        $_REQUEST = ["method" => "login", "gid" => $game1->id, "uid" => $userFromGame2->id];
        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals("user error", $output);
    }

    /**
     * Тест производительности открытия игры.
     */
    public function testPerformanceGameOpening(): void
    {
        $game = TestDataFactory::createTestGame(["map_w" => 500, "map_h" => 500]);
        $player = TestDataFactory::createTestUser(["game" => $game->id]);

        $_REQUEST = ["method" => "login", "gid" => $game->id, "uid" => $player->id];

        $startTime = microtime(true);
        $this->simulateGameOpening();
        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $executionTime);
        $this->assertEquals($game->id, $_SESSION["game_id"]);
    }

    /**
     * Тест безопасности: попытка открыть игру с некорректными ID.
     */
    public function testSecurityInvalidIds(): void
    {
        $_REQUEST = ["method" => "login", "gid" => -1, "uid" => -1];
        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals("game error", $output);

        $_REQUEST = ["method" => "login", "gid" => PHP_INT_MAX, "uid" => PHP_INT_MAX];
        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals("game error", $output);

        $_REQUEST = ["method" => "login", "gid" => "not_a_number", "uid" => "also_not_a_number"];
        ob_start();
        $this->simulateGameOpening();
        $output = ob_get_clean();
        $this->assertEquals("game error", $output);
    }

    /**
     * Тест множественного открытия игр.
     */
    public function testMultipleGameOpenings(): void
    {
        $games = [];
        for ($i = 1; $i <= 2; $i++) {
            $game = TestDataFactory::createTestGame(["name" => "Игра {$i}"]);
            $user = TestDataFactory::createTestUser(["login" => "Игрок игры {$i}", "game" => $game->id]);
            $games[] = ["game" => $game, "user" => $user];
        }

        foreach ($games as $gameData) {
            $this->clearSession();
            $_REQUEST = ["method" => "login", "gid" => $gameData["game"]->id, "uid" => $gameData["user"]->id];
            $this->simulateGameOpening();
            $this->assertEquals($gameData["game"]->id, $_SESSION["game_id"]);
        }
    }

    /**
     * Тест открытия игры с проверкой целостности данных.
     */
    public function testGameOpeningDataIntegrity(): void
    {
        $game = TestDataFactory::createTestGame(["turn_num" => 5]);
        $user = TestDataFactory::createTestUser(["game" => $game->id, "money" => 200, "age" => 3]);

        $_REQUEST = ["method" => "login", "gid" => $game->id, "uid" => $user->id];
        $this->simulateGameOpening();

        $loadedGame = Game::get($_SESSION["game_id"]);
        $loadedUser = User::get($_SESSION["user_id"]);

        $this->assertEquals(5, $loadedGame->turn_num);
        $this->assertEquals(200, $loadedUser->money);
        $this->assertEquals(3, $loadedUser->age);
    }

    /**
     * Тест открытия игры с проверкой связей между объектами.
     */
    public function testGameOpeningObjectRelationships(): void
    {
        $game = TestDataFactory::createTestGame();
        $users = [];
        for ($i = 1; $i <= 2; $i++) {
            $users[] = TestDataFactory::createTestUser(["login" => "Игрок{$i}", "game" => $game->id]);
        }

        $_REQUEST = ["method" => "login", "gid" => $game->id, "uid" => $users[0]->id];
        $this->simulateGameOpening();

        $sessionGame = Game::get($_SESSION["game_id"]);
        $sessionUser = User::get($_SESSION["user_id"]);

        $this->assertCount(2, $sessionGame->users);
        $this->assertArrayHasKey($sessionUser->id, $sessionGame->users);
        $this->assertEquals($sessionGame->id, $sessionUser->game);
    }

    /**
     * Вспомогательный метод для симуляции логики открытия игры из index.php
     */
    private function simulateGameOpening(): void
    {
        // Определяем страницу как в index.php
        $page = isset($_REQUEST["method"]) ? $_REQUEST["method"] : "map";

        if (
            $page == "login" &&
            isset($_REQUEST["gid"]) &&
            isset($_REQUEST["uid"])
        ) {
            $game = Game::get((int) $_REQUEST["gid"]);
            if (!$game) {
                echo "game error";
                return;
            }
            $user = User::get((int) $_REQUEST["uid"]);
            if (!$user) {
                echo "user error";
                return;
            }
            if ($user->game != $game->id) {
                echo "user error";
                return;
            }
            $_SESSION["game_id"] = $game->id;
            $_SESSION["user_id"] = $user->id;
        } elseif (
            $page == "login" &&
            (!isset($_REQUEST["gid"]) || !isset($_REQUEST["uid"]))
        ) {
            // Обработка случаев, когда параметры отсутствуют
            if (!isset($_REQUEST["gid"])) {
                echo "game error";
            } elseif (!isset($_REQUEST["uid"])) {
                echo "user error";
            }
        }
    }
}
