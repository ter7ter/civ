<?php

class MapVPageTest extends TestBase
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

    public function testMapVPageLoadsWithoutErrors(): void
    {
        // 1. Создаем тестовую игру, пользователя и планету
        $game = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $game->id]);
        $userData = $this->createTestUser(["game" => $game->id]);
        $this->createTestCity([
            "user_id" => $userData["id"],
            "planet" => $planetId,
            "x" => 10,
            "y" => 10,
        ]);

        // Создаем тестовые клетки карты, чтобы избежать ошибок
        $this->createTestMapCells(0, 0, 20, 20, $planetId);

        // 2. Устанавливаем сессию
        $this->setSession([
            "user_id" => $userData["id"],
            "game_id" => $game->id,
        ]);

        // 3. Определяем переменные, которые ожидает mapv.php
        $user = User::get($userData["id"]);
        $game = Game::get($game->id);

        // 4. Вызываем mapv.php через mockIncludeFile
        $vars = mockIncludeFile(__DIR__ . "/../../pages/mapv.php", [
            "user" => $user,
            "game" => $game,
        ]);

        // 5. Проверяем, что данные карты загружены и нет ошибок
        $this->assertArrayHasKey("data", $vars, "Должны быть данные карты");
        $data = $vars["data"];
        $this->assertArrayHasKey("mapv", $data, "Должны быть данные карты");
        $this->assertArrayNotHasKey("error", $vars, "Не должно быть ошибок");
    }
}
