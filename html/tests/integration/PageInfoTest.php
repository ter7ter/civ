<?php

namespace App\Tests;

use App\User;
use App\Game;
use App\Tests\Factory\TestDataFactory;
use App\Tests\base\CommonTestBase;

class PageInfoTest extends CommonTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];

        // Подключаем классы проекта
        require_once PROJECT_ROOT . "/includes.php";
    }

    public function testCellInfoPage()
    {
        // 1. Create test data
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(["game" => $game->id]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);

        // 2. Set session
        $this->setSession([
            "user_id" => $user->id,
            "game_id" => $game->id,
        ]);

        // 3. Simulate request
        $_REQUEST['x'] = 10;
        $_REQUEST['y'] = 10;

        $user = User::get($user->id);
        $game = Game::get($game->id);

        // 4. Include page and check for errors
        $vars = mockIncludeFile(__DIR__ . "/../../pages/cellinfo.php", [
            "user" => $user,
            "game" => $game,
        ]);

        $this->assertArrayNotHasKey("error", $vars, "Не должно быть ошибок");
    }

    public function testTurnInfoPage()
    {
        // 1. Create test data
        $game = TestDataFactory::createTestGame();
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // 2. Set session
        $this->setSession([
            "user_id" => $user->id,
            "game_id" => $game->id,
        ]);

        $user = User::get($user->id);
        $game = Game::get($game->id);

        // 3. Include page and check for errors
        $vars = mockIncludeFile(__DIR__ . "/../../pages/turninfo.php", [
            "user" => $user,
            "game" => $game,
        ]);

        $this->assertArrayNotHasKey("error", $vars, "Не должно быть ошибок");
    }
}
