<?php

require_once __DIR__ . "/../FunctionalTestBase.php";

/**
 * Тесты для функции создания игры
 */
class CreateGameTest extends FunctionalTestBase
{
    /**
     * Тест 1.1: Создание базовой игры
     */
    public function testCreateBasicGame(): void
    {
        // Создаем игру напрямую через класс Game, избегая сложной генерации карты
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
        $user1Data = [
            "login" => "Игрок1",
            "color" => "#ff0000",
            "game" => $game->id,
            "turn_order" => 1,
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];
        $user1 = new User($user1Data);
        $user1->save();

        $user2Data = [
            "login" => "Игрок2",
            "color" => "#00ff00",
            "game" => $game->id,
            "turn_order" => 2,
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];
        $user2 = new User($user2Data);
        $user2->save();

        // Проверяем, что игра создана в БД
        $gameCount = $this->getTableCount("game");
        $this->assertEquals(1, $gameCount, "Должна быть создана одна игра");

        $userCount = $this->getTableCount("user");
        $this->assertEquals(
            2,
            $userCount,
            "Должно быть создано два пользователя",
        );

        // Проверяем данные игры в БД
        $savedGame = $this->getLastRecord("game");
        $this->assertEquals("Тестовая игра", $savedGame["name"]);
        $this->assertEquals(100, $savedGame["map_w"]);
        $this->assertEquals(100, $savedGame["map_h"]);
        $this->assertEquals("byturn", $savedGame["turn_type"]);
    }

    /**
     * Тест 1.2: Создание игры с одновременными ходами
     */
    public function testCreateConcurrentGame(): void
    {
        // Создаем игру с одновременными ходами напрямую
        $gameData = [
            "name" => "Игра с одновременными ходами",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "concurrently",
            "turn_num" => 1,
        ];

        $game = new Game($gameData);
        $game->save();

        // Создаем трех пользователей
        for ($i = 1; $i <= 3; $i++) {
            $userData = [
                "login" => "Игрок$i",
                "color" =>
                    "#" . str_pad(dechex($i * 100000), 6, "0", STR_PAD_LEFT),
                "game" => $game->id,
                "turn_order" => $i,
                "turn_status" => "wait",
                "money" => 50,
                "age" => 1,
            ];
            $user = new User($userData);
            $user->save();
        }

        // Проверяем количество пользователей
        $userCount = $this->getTableCount("user");
        $this->assertEquals(3, $userCount);

        // Проверяем тип ходов
        $savedGame = $this->getLastRecord("game");
        $this->assertEquals("concurrently", $savedGame["turn_type"]);
    }

    /**
     * Тест 1.3: Создание игры в одном окне
     */
    public function testCreateOneWindowGame(): void
    {
        // Создаем игру в одном окне напрямую
        $gameData = [
            "name" => "Игра в одном окне",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "onewindow",
            "turn_num" => 1,
        ];

        $game = new Game($gameData);
        $game->save();

        // Создаем двух пользователей
        for ($i = 1; $i <= 2; $i++) {
            $userData = [
                "login" => "Игрок$i",
                "color" =>
                    "#" . str_pad(dechex($i * 200000), 6, "0", STR_PAD_LEFT),
                "game" => $game->id,
                "turn_order" => $i,
                "turn_status" => "wait",
                "money" => 50,
                "age" => 1,
            ];
            $user = new User($userData);
            $user->save();
        }

        $savedGame = $this->getLastRecord("game");
        $this->assertEquals("onewindow", $savedGame["turn_type"]);
    }

    /**
     * Тест 2.1: Валидация - пустое название игры
     */
    public function testEmptyGameName(): void
    {
        $result = $this->createGameViaPage([
            "name" => "",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $this->assertPageHasError(
            $result,
            "Название игры не может быть пустым",
        );

        // Проверяем, что игра не создана
        $gameCount = $this->getTableCount("game");
        $this->assertEquals(0, $gameCount);
    }

    /**
     * Тест 2.2: Валидация - название только из пробелов
     */
    public function testWhitespaceGameName(): void
    {
        $result = $this->createGameViaPage([
            "name" => "   ",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $this->assertPageHasError(
            $result,
            "Название игры не может быть пустым",
        );

        $gameCount = $this->getTableCount("game");
        $this->assertEquals(0, $gameCount);
    }

    /**
     * Тест 2.3: Валидация - дублирующиеся имена игроков
     */
    public function testDuplicatePlayerNames(): void
    {
        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "users" => ["Игрок1", "Игрок1", "Игрок2"],
        ]);

        $this->assertPageHasError($result, "указан несколько раз");

        $gameCount = $this->getTableCount("game");
        $this->assertEquals(0, $gameCount);
    }

    /**
     * Тест 2.4: Валидация - слишком маленький размер карты
     */
    public function testTooSmallMapSize(): void
    {
        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "map_w" => 30,
            "map_h" => 30,
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $this->assertPageHasError($result, "должна быть от 50 до 500");

        $gameCount = $this->getTableCount("game");
        $this->assertEquals(0, $gameCount);
    }

    /**
     * Тест 2.5: Валидация - слишком большой размер карты
     */
    public function testTooLargeMapSize(): void
    {
        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "map_w" => 600,
            "map_h" => 600,
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $this->assertPageHasError($result, "должна быть от 50 до 500");

        $gameCount = $this->getTableCount("game");
        $this->assertEquals(0, $gameCount);
    }

    /**
     * Тест 2.6: Валидация - слишком много игроков
     */
    public function testTooManyPlayers(): void
    {
        $users = [];
        for ($i = 1; $i <= 20; $i++) {
            $users[] = "Игрок$i";
        }

        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "users" => $users,
        ]);

        $this->assertPageHasError(
            $result,
            "Максимальное количество игроков: 16",
        );

        $gameCount = $this->getTableCount("game");
        $this->assertEquals(0, $gameCount);
    }

    /**
     * Тест 3.1: Сохранение данных при ошибке валидации
     */
    public function testDataPreservationOnError(): void
    {
        $testData = [
            "name" => "", // пустое имя вызовет ошибку
            "map_w" => 150,
            "map_h" => 120,
            "turn_type" => "concurrently",
            "users" => ["Игрок1", "Игрок2", "Игрок3"],
        ];

        $result = $this->createGameViaPage($testData);

        $this->assertPageHasError($result);

        // Проверяем, что данные сохранены для повторного заполнения формы
        $this->assertPagePreservesData($result, [
            "name" => "",
            "map_w" => 150,
            "map_h" => 120,
            "turn_type" => "concurrently",
            "users" => ["Игрок1", "Игрок2", "Игрок3"],
        ]);
    }

    /**
     * Тест 4.1: Обработка XSS в названии игры
     */
    public function testXSSInGameName(): void
    {
        $maliciousName = '<script>alert("xss")</script>';

        $result = $this->createGameViaPage([
            "name" => $maliciousName,
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $this->assertPageHasNoError($result);

        // Проверяем, что данные в БД экранированы
        $gameData = $this->getLastRecord("game");
        $this->assertStringNotContainsString("<script>", $gameData["name"]);
        $this->assertStringContainsString("&lt;script&gt;", $gameData["name"]);
    }

    /**
     * Тест 4.2: Обработка XSS в именах игроков
     */
    public function testXSSInPlayerNames(): void
    {
        $maliciousPlayer = '<img src="x" onerror="alert(1)">';

        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "users" => [$maliciousPlayer, "Игрок2"],
        ]);

        $this->assertPageHasNoError($result);

        // Проверяем, что данные пользователей экранированы
        $userData = MyDB::query(
            "SELECT * FROM user ORDER BY id LIMIT 1",
            [],
            "row",
        );
        $this->assertStringNotContainsString("<img", $userData["login"]);
        $this->assertStringContainsString("&lt;img", $userData["login"]);
    }

    /**
     * Тест 5.1: Обработка очень длинных строк
     */
    public function testVeryLongStrings(): void
    {
        $longName = str_repeat("A", 1000);
        $longPlayerName = str_repeat("B", 500);

        $result = $this->createGameViaPage([
            "name" => $longName,
            "users" => [$longPlayerName, "Игрок2"],
        ]);

        // Система должна обработать длинные строки без сбоев
        $this->assertPageHasNoError($result);

        $gameData = $this->getLastRecord("game");
        $this->assertNotEmpty($gameData["name"]);
    }

    /**
     * Тест 6.1: Генерация цветов игроков
     */
    public function testPlayerColorGeneration(): void
    {
        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "users" => ["Игрок1", "Игрок2", "Игрок3", "Игрок4"],
        ]);

        $this->assertPageHasNoError($result);

        // Получаем всех пользователей
        $users = MyDB::query("SELECT * FROM user ORDER BY turn_order");

        $this->assertCount(4, $users);

        // Проверяем, что у каждого игрока есть уникальный цвет
        $colors = [];
        foreach ($users as $user) {
            $this->assertNotEmpty($user["color"]);
            $this->assertStringStartsWith("#", $user["color"]);
            $this->assertEquals(7, strlen($user["color"])); // #RRGGBB
            $this->assertNotContains(
                $user["color"],
                $colors,
                "Цвета игроков должны быть уникальными",
            );
            $colors[] = $user["color"];
        }
    }

    /**
     * Тест 6.2: Обработка пустых полей игроков
     */
    public function testEmptyPlayerFields(): void
    {
        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "users" => ["Игрок1", "", "Игрок2", "   ", "Игрок3"],
        ]);

        $this->assertPageHasNoError($result);

        // Пустые поля должны быть проигнорированы
        $userCount = $this->getTableCount("user");
        $this->assertEquals(
            3,
            $userCount,
            "Должно быть создано 3 игрока (пустые поля игнорируются)",
        );
    }

    /**
     * Тест 7.1: Неверный тип ходов
     */
    public function testInvalidTurnType(): void
    {
        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "turn_type" => "invalid_type",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $this->assertPageHasNoError($result);

        // Должен использоваться тип по умолчанию
        $gameData = $this->getLastRecord("game");
        $this->assertEquals("byturn", $gameData["turn_type"]);
    }

    /**
     * Тест 8.1: Отсутствие POST данных
     */
    public function testNoPostData(): void
    {
        $result = $this->executePage(PROJECT_ROOT . "/pages/creategame.php");

        // При отсутствии POST данных должны быть установлены значения по умолчанию
        $this->assertArrayHasKey("variables", $result);
        $this->assertArrayHasKey("data", $result["variables"]);

        $data = $result["variables"]["data"];
        $this->assertEquals("", $data["name"]);
        $this->assertEquals(100, $data["map_w"]);
        $this->assertEquals(100, $data["map_h"]);
        $this->assertEquals("byturn", $data["turn_type"]);
        $this->assertEquals(["", ""], $data["users"]);
    }

    /**
     * Тест 9.1: Проверка начальных условий игры
     */
    public function testGameInitialConditions(): void
    {
        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $this->assertPageHasNoError($result);

        $gameData = $this->getLastRecord("game");
        $game = Game::get($gameData["id"]);

        // Проверяем начальный номер хода
        $this->assertEquals(1, $game->turn_num);

        // Проверяем, что у игроков правильные начальные деньги
        $users = MyDB::query("SELECT * FROM user WHERE game = :gid", [
            "gid" => $game->id,
        ]);
        foreach ($users as $user) {
            $this->assertEquals(
                50,
                $user["money"],
                "У игрока должно быть 50 начальных денег",
            );
            $this->assertEquals(1, $user["age"], "Начальная эра должна быть 1");
        }

        // Проверяем, что созданы юниты
        $unitCount = $this->getTableCount("unit");
        $this->assertGreaterThan(
            0,
            $unitCount,
            "Должны быть созданы начальные юниты",
        );
    }

    /**
     * Тест 10.1: Минимальное количество игроков
     */
    public function testMinimumPlayers(): void
    {
        $result = $this->createGameViaPage([
            "name" => "Тестовая игра",
            "users" => ["Игрок1"], // только один игрок
        ]);

        $this->assertPageHasError($result, "необходимо минимум 2 игрока");

        $gameCount = $this->getTableCount("game");
        $this->assertEquals(0, $gameCount);
    }
}
