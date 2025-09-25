<?php

/**
 * Простые unit-тесты для создания игр (только классы, без веб-страниц)
 */
class CreateGameSimpleTest extends TestBase
{
    protected function setUp(): void
    {
        DatabaseTestAdapter::resetTestDatabase();
        parent::setUp();
    }

    /**
     * Тест 1.1: Создание базовой игры через класс Game
     */
    public function testCreateBasicGameClass(): void
    {
        $gameData = [
            "name" => "Тестовая игра",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "turn_num" => 1,
        ];

        $game = new Game($gameData);
        $game->save();

        // Проверяем, что игра создана в БД
        $gameCount = $this->getTableCount("game");
        $this->assertEquals(1, $gameCount);

        // Проверяем данные игры в БД
        $savedGame = $this->getLastRecord("game");
        $this->assertEquals("Тестовая игра", $savedGame["name"]);
        $this->assertEquals(100, $savedGame["map_w"]);
        $this->assertEquals(100, $savedGame["map_h"]);
        $this->assertEquals("byturn", $savedGame["turn_type"]);
        $this->assertEquals(1, $savedGame["turn_num"]);
    }

    /**
     * Тест 1.2: Создание игры с разными типами ходов
     */
    public function testCreateGameWithDifferentTurnTypes(): void
    {
        $turnTypes = ["concurrently", "byturn", "onewindow"];

        foreach ($turnTypes as $turnType) {
            $this->clearTestData(); // Очищаем данные между тестами

            $gameData = [
                "name" => "Игра с типом $turnType",
                "map_w" => 100,
                "map_h" => 100,
                "turn_type" => $turnType,
                "turn_num" => 1,
            ];

            $game = new Game($gameData);
            $game->save();

            $savedGame = $this->getLastRecord("game");
            $this->assertEquals($turnType, $savedGame["turn_type"]);
        }
    }

    /**
     * Тест 1.3: Создание игры с разными размерами карты
     */
    public function testCreateGameWithDifferentMapSizes(): void
    {
        $mapSizes = [[50, 50], [100, 100], [200, 150], [500, 500]];

        foreach ($mapSizes as [$width, $height]) {
            $this->clearTestData();

            $gameData = [
                "name" => "Игра {$width}x{$height}",
                "map_w" => $width,
                "map_h" => $height,
                "turn_type" => "byturn",
                "turn_num" => 1,
            ];

            $game = new Game($gameData);
            $game->save();

            $savedGame = $this->getLastRecord("game");
            $this->assertEquals($width, $savedGame["map_w"]);
            $this->assertEquals($height, $savedGame["map_h"]);
        }
    }

    /**
     * Тест 2.1: Создание пользователей для игры
     */
    public function testCreateUsersForGame(): void
    {
        // Создаем игру
        $gameData = [
            "name" => "Тестовая игра",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "turn_num" => 1,
        ];
        $game = new Game($gameData);
        $game->save();

        // Создаем пользователей
        $userData1 = [
            "login" => "Игрок1",
            "color" => "#ff0000",
            "game" => $game->id,
            "turn_order" => 1,
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];
        $user1 = new User($userData1);
        $user1->save();

        $userData2 = [
            "login" => "Игрок2",
            "color" => "#00ff00",
            "game" => $game->id,
            "turn_order" => 2,
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];
        $user2 = new User($userData2);
        $user2->save();

        // Проверяем количество пользователей
        $userCount = $this->getTableCount("user");
        $this->assertEquals(2, $userCount);

        // Проверяем данные первого пользователя
        $savedUser1 = MyDB::query(
            "SELECT * FROM user WHERE id = :id",
            ["id" => $user1->id],
            "row",
        );
        $this->assertEquals("Игрок1", $savedUser1["login"]);
        $this->assertEquals("#ff0000", $savedUser1["color"]);
        $this->assertEquals($game->id, $savedUser1["game"]);
        $this->assertEquals(1, $savedUser1["turn_order"]);
        $this->assertEquals(50, $savedUser1["money"]);
    }

    /**
     * Тест 2.2: Автоматическая генерация цветов игроков
     */
    public function testUserColorGeneration(): void
    {
        $game = $this->createTestGame();

        // Создаем пользователей с автоматической генерацией цветов
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $color = $this->generatePlayerColor($i);

            $userData = [
                "login" => "Игрок$i",
                "color" => $color,
                "game" => $game["id"],
                "turn_order" => $i,
                "turn_status" => "wait",
                "money" => 50,
                "age" => 1,
            ];

            $user = new User($userData);
            $user->save();
            $users[] = $user;
        }

        // Проверяем, что цвета уникальны и правильно сформированы
        $colors = [];
        foreach ($users as $user) {
            $savedUser = MyDB::query(
                "SELECT color FROM user WHERE id = :id",
                ["id" => $user->id],
                "row",
            );

            $color = $savedUser["color"];
            $this->assertStringStartsWith("#", $color);
            $this->assertEquals(7, strlen($color)); // #RRGGBB
            $this->assertNotContains(
                $color,
                $colors,
                "Цвета должны быть уникальными",
            );
            $colors[] = $color;
        }
    }

    /**
     * Тест 3.1: Метод game_list
     */
    public function testGameList(): void
    {
        // Создаем несколько игр с пользователями
        $game1 = $this->createTestGame(["name" => "Первая игра"]);
        $this->createTestUser(["game" => $game1["id"], "login" => "Игрок1"]);

        $game2 = $this->createTestGame(["name" => "Вторая игра"]);
        $this->createTestUser(["game" => $game2["id"], "login" => "Игрок2"]);
        $this->createTestUser(["game" => $game2["id"], "login" => "Игрок3"]);

        $game3 = $this->createTestGame(["name" => "Третья игра"]);
        $this->createTestUser(["game" => $game3["id"], "login" => "Игрок4"]);
        $this->createTestUser(["game" => $game3["id"], "login" => "Игрок5"]);
        $this->createTestUser(["game" => $game3["id"], "login" => "Игрок6"]);

        // Получаем список игр
        $games = Game::game_list();

        $this->assertCount(3, $games);

        // Игры должны быть отсортированы по id DESC (новые сначала)
        $this->assertEquals("Третья игра", $games[0]["name"]);
        $this->assertEquals(3, $games[0]["ucount"]);

        $this->assertEquals("Вторая игра", $games[1]["name"]);
        $this->assertEquals(2, $games[1]["ucount"]);

        $this->assertEquals("Первая игра", $games[2]["name"]);
        $this->assertEquals(1, $games[2]["ucount"]);
    }

    /**
     * Тест 4.1: Проверка метода get для игры
     */
    public function testGameGetMethod(): void
    {
        $gameData = $this->createTestGame(["name" => "Test Get Game"]);

        $game = Game::get($gameData["id"]);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals($gameData["id"], $game->id);
        $this->assertEquals("Test Get Game", $game->name);
    }

    /**
     * Тест 4.2: Проверка кэширования в классе Game
     */
    public function testGameCaching(): void
    {
        $gameData = $this->createTestGame(["name" => "Cached Game"]);

        $game1 = Game::get($gameData["id"]);
        $game2 = Game::get($gameData["id"]);

        // Объекты должны быть одинаковыми (кэширование)
        $this->assertSame($game1, $game2);

        // Очищаем кэш и проверяем, что создается новый объект
        Game::clearCache();
        $game3 = Game::get($gameData["id"]);

        $this->assertNotSame($game1, $game3);
        $this->assertEquals($game1->id, $game3->id);
    }

    /**
     * Тест 5.1: Обновление данных игры
     */
    public function testUpdateGameData(): void
    {
        $gameData = $this->createTestGame([
            "name" => "Оригинальное название",
            "turn_num" => 1,
        ]);

        $game = Game::get($gameData["id"]);
        $game->name = "Обновленное название";
        $game->turn_num = 5;
        $game->save();

        // Проверяем в БД
        $savedGame = MyDB::query(
            "SELECT * FROM game WHERE id = :id",
            ["id" => $game->id],
            "row",
        );

        $this->assertEquals("Обновленное название", $savedGame["name"]);
        $this->assertEquals(5, $savedGame["turn_num"]);
    }

    /**
     * Тест 6.1: Экранирование HTML в названии игры
     */
    public function testHtmlEscapingInGameName(): void
    {
        $maliciousName = '<script>alert("xss")</script>';

        $gameData = [
            "name" => $maliciousName,
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "turn_num" => 1,
        ];

        $game = new Game($gameData);
        $game->save();

        // Проверяем, что в БД сохранилось исходное значение (экранирование делает веб-страница)
        $savedGame = $this->getLastRecord("game");
        $this->assertEquals($maliciousName, $savedGame["name"]); // Game класс не экранирует, это делает веб-страница
    }

    /**
     * Тест 6.2: Экранирование HTML в именах пользователей
     */
    public function testHtmlEscapingInUserLogin(): void
    {
        $game = $this->createTestGame();
        $maliciousLogin = '<img src="x" onerror="alert(1)">';

        $userData = [
            "login" => $maliciousLogin,
            "color" => "#ff0000",
            "game" => $game["id"],
            "turn_order" => 1,
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];

        $user = new User($userData);
        $user->save();

        // Проверяем, что в БД сохранилось исходное значение (экранирование на уровне представления)
        $savedUser = MyDB::query(
            "SELECT login FROM user WHERE id = :id",
            ["id" => $user->id],
            "row",
        );

        $this->assertEquals($maliciousLogin, $savedUser["login"]); // User класс не экранирует
    }

    /**
     * Вспомогательный метод для генерации цвета игрока (как в оригинальном коде)
     */
    private function generatePlayerColor($playerNumber): string
    {
        $color = "#";
        $sym = "ff";

        if ($playerNumber > 8) {
            $sym = "88";
            $playerNumber = $playerNumber - 8;
        }

        if (($playerNumber & 4) > 0) {
            $color .= $sym;
        } else {
            $color .= "00";
        }

        if (($playerNumber & 2) > 0) {
            $color .= $sym;
        } else {
            $color .= "00";
        }

        if (($playerNumber & 1) > 0) {
            $color .= $sym;
        } else {
            $color .= "00";
        }

        return $color;
    }
}
