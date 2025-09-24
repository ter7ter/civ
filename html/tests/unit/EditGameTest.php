<?php

/**
 * Тесты для функции редактирования игры
 */
class EditGameTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
    }

    /**о
     * Тест 5.1: Загрузка игры без указания ID
     */
    public function testLoadGameWithoutId(): void
    {
        $this->expectException(Exception::class);

        $_REQUEST = []; // ID не указан
        $_GET = [];

        mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
    }

    /**
     * Тест 1.1: Редактирование базовых параметров игры
     */
    public function testEditBasicGameParameters(): void
    {
        // Создаем тестовую игру
        $game = $this->createTestGame([
            "name" => "Старое название",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        // Создаем тестовых пользователей
        $this->createTestUser([
            "login" => "Игрок1",
            "game" => $game["id"],
            "turn_order" => 1,
        ]);
        $this->createTestUser([
            "login" => "Игрок2",
            "game" => $game["id"],
            "turn_order" => 2,
        ]);

        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "Новое название",
            "map_w" => 150,
            "map_h" => 200,
            "turn_type" => "concurrently",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = isset($vars["error"]) ? $vars["error"] : false;

        // Проверяем, что редактирование прошло успешно
        $this->assertFalse(
            $error,
            "Не должно быть ошибок при редактировании базовых параметров: " .
                (is_string($error) ? $error : ""),
        );

        // Проверяем, что данные обновились в БД
        $updatedGame = $this->getLastRecord("game");
        $this->assertNotNull($updatedGame, "Игра должна существовать в БД");
        $this->assertEquals("Новое название", $updatedGame["name"]);
        $this->assertEquals(150, $updatedGame["map_w"]);
        $this->assertEquals(200, $updatedGame["map_h"]);
        $this->assertEquals("concurrently", $updatedGame["turn_type"]);
    }

    /**
     * Тест 1.2: Редактирование только названия игры
     */
    public function testEditGameNameOnly(): void
    {
        $game = $this->createTestGame([
            "name" => "Старое название",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "Обновленное название",
            "map_w" => 100, // оставляем прежние значения
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = isset($vars["error"]) ? $vars["error"] : false;

        $this->assertFalse(
            $error,
            "Не должно быть ошибок при редактировании только названия: " .
                (is_string($error) ? $error : ""),
        );

        $updatedGame = $this->getLastRecord("game");
        $this->assertEquals("Обновленное название", $updatedGame["name"]);
        $this->assertEquals(100, $updatedGame["map_w"]); // должно остаться прежним
        $this->assertEquals(100, $updatedGame["map_h"]); // должно остаться прежним
    }

    /**
     * Тест 1.3: Редактирование размеров карты
     */
    public function testEditMapSize(): void
    {
        $game = $this->createTestGame();
        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "Test Game",
            "map_w" => 500, // максимальный размер
            "map_h" => 50, // минимальный размер
            "turn_type" => "byturn",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        $this->assertFalse(
            $error,
            "Не должно быть ошибок при редактировании размеров карты: " .
                (is_string($error) ? $error : ""),
        );

        $updatedGame = $this->getLastRecord("game");
        $this->assertEquals(500, $updatedGame["map_w"]);
        $this->assertEquals(50, $updatedGame["map_h"]);
    }

    /**
     * Тест 1.4: Редактирование типа ходов
     */
    public function testEditTurnType(): void
    {
        $game = $this->createTestGame(["turn_type" => "byturn"]);
        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        $turnTypes = ["concurrently", "byturn", "onewindow"];

        foreach ($turnTypes as $turnType) {
            $this->simulatePostRequest([
                "game_id" => $game["id"],
                "name" => "Test Game",
                "map_w" => 100,
                "map_h" => 100,
                "turn_type" => $turnType,
            ]);

            $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
            $error = $vars["error"] ?? false;

            $this->assertFalse(
                $error,
                "Не должно быть ошибок для типа ходов: {$turnType}: " .
                    (is_string($error) ? $error : ""),
            );

            $updatedGame = $this->getLastRecord("game");
            $this->assertEquals($turnType, $updatedGame["turn_type"]);
        }
    }

    /**
     * Тест 1.5: Загрузка данных игры для редактирования (GET запрос)
     */
    public function testLoadGameDataForEditing(): void
    {
        $game = $this->createTestGame([
            "name" => "Тестовая игра для загрузки",
            "map_w" => 150,
            "map_h" => 200,
            "turn_type" => "concurrently",
        ]);

        $this->createTestUser([
            "login" => "Первый игрок",
            "game" => $game["id"],
            "turn_order" => 1,
        ]);
        $this->createTestUser([
            "login" => "Второй игрок",
            "game" => $game["id"],
            "turn_order" => 2,
        ]);
        $this->createTestUser([
            "login" => "Третий игрок",
            "game" => $game["id"],
            "turn_order" => 3,
        ]);

        // Симулируем GET запрос
        $_REQUEST = ["game_id" => $game["id"]];
        $_GET = ["game_id" => $game["id"]];

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $data = $vars["data"] ?? [];

        // Проверяем, что данные загрузились корректно
        $this->assertEquals($game["id"], $data["game_id"]);
        $this->assertEquals("Тестовая игра для загрузки", $data["name"]);
        $this->assertEquals(150, $data["map_w"]);
        $this->assertEquals(200, $data["map_h"]);
        $this->assertEquals("concurrently", $data["turn_type"]);
        $this->assertEquals(
            ["Первый игрок", "Второй игрок", "Третий игрок"],
            $data["users"],
        );
    }

    /**
     * Тест 2.1: Редактирование с пустым названием
     */
    public function testEditWithEmptyName(): void
    {
        $game = $this->createTestGame();
        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "", // пустое название
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для пустого названия",
        );
        $this->assertEquals("Название игры не может быть пустым", $error);
    }

    /**
     * Тест 2.2: Редактирование с неверными размерами карты
     */
    public function testEditWithInvalidMapSize(): void
    {
        $game = $this->createTestGame();
        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        // Тест слишком маленькой карты
        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "Test Game",
            "map_w" => 49, // меньше минимума
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для слишком маленькой ширины карты",
        );
        $this->assertStringContainsString("должна быть от 50 до 500", $error);

        // Тест слишком большой карты
        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "Test Game",
            "map_w" => 100,
            "map_h" => 501, // больше максимума
            "turn_type" => "byturn",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для слишком большой высоты карты",
        );
        $this->assertStringContainsString("должна быть от 50 до 500", $error);
    }

    /**
     * Тест 2.3: Редактирование с неверным типом ходов
     */
    public function testEditWithInvalidTurnType(): void
    {
        $game = $this->createTestGame();
        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "Test Game",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "invalid_type", // неверный тип
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        // Должно использоваться значение по умолчанию
        $this->assertFalse(
            $error,
            "Неверный тип ходов должен заменяться на значение по умолчанию: " .
                (is_string($error) ? $error : ""),
        );

        $updatedGame = $this->getLastRecord("game");
        $this->assertEquals("byturn", $updatedGame["turn_type"]); // значение по умолчанию
    }

    /**
     * Тест 2.4: Редактирование несуществующей игры
     */
    public function testEditNonExistentGame(): void
    {
        $this->simulatePostRequest([
            "game_id" => 999, // несуществующий ID
            "name" => "Test Game",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        $exceptionThrown = false;
        $exceptionMessage = "";
        try {
            $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
            echo "Debug: No exception thrown, vars = " .
                json_encode($vars) .
                "\n";
        } catch (Exception $e) {
            $exceptionThrown = true;
            $exceptionMessage = $e->getMessage();
            echo "Debug: Exception thrown: " . $exceptionMessage . "\n";
            $this->assertStringContainsString(
                "Игра не найдена",
                $e->getMessage(),
            );
        } catch (Throwable $t) {
            echo "Debug: Other throwable: " . $t->getMessage() . "\n";
            throw $t;
        }

        $this->assertTrue(
            $exceptionThrown,
            "Exception should be thrown for non-existent game, but got: " .
                $exceptionMessage,
        );
    }

    /**
     * Тест 2.5: Редактирование без указания ID игры
     */
    public function testEditWithoutGameId(): void
    {
        $this->expectException(Exception::class);

        $this->simulatePostRequest([
            // game_id не указан
            "name" => "Test Game",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
    }

    /**
     * Тест 3.1: Сохранение данных при ошибке валидации
     */
    public function testDataPreservationOnValidationError(): void
    {
        $game = $this->createTestGame();
        $this->createTestUser([
            "login" => "Игрок1",
            "game" => $game["id"],
            "turn_order" => 1,
        ]);
        $this->createTestUser([
            "login" => "Игрок2",
            "game" => $game["id"],
            "turn_order" => 2,
        ]);

        $testData = [
            "game_id" => $game["id"],
            "name" => "", // пустое имя вызовет ошибку
            "map_w" => 150,
            "map_h" => 200,
            "turn_type" => "concurrently",
        ];

        $this->simulatePostRequest($testData);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;
        $data = $vars["data"] ?? [];

        // Проверяем, что данные сохранились
        $this->assertTrue($error !== false, "Должна быть ошибка");
        $this->assertEquals("", $data["name"], "Название должно сохраниться");
        $this->assertEquals(
            150,
            $data["map_w"],
            "Ширина карты должна сохраниться",
        );
        $this->assertEquals(
            200,
            $data["map_h"],
            "Высота карты должна сохраниться",
        );
        $this->assertEquals(
            "concurrently",
            $data["turn_type"],
            "Тип ходов должен сохраниться",
        );
        $this->assertEquals(
            ["Игрок1", "Игрок2"],
            $data["users"],
            "Список игроков должен сохраниться",
        );
    }

    /**
     * Тест 4.1: HTML-инъекции в названии при редактировании
     */
    public function testXSSInEditedGameName(): void
    {
        $game = $this->createTestGame();
        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        $maliciousName = '<script>alert("XSS")</script>';

        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => $maliciousName,
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        // Убеждаемся, что редактирование прошло без ошибок валидации
        $this->assertFalse(
            $error,
            "Не должно быть ошибок при редактировании игры с XSS в названии: " .
                (is_string($error) ? $error : ""),
        );

        // Проверяем, что название было экранировано в БД
        $updatedGame = $this->getLastRecord("game");
        $this->assertEquals(
            htmlspecialchars($maliciousName),
            $updatedGame["name"],
            "Название игры в БД должно быть экранировано.",
        );
    }

    /**
     * Тест 4.2: Очень длинные строки при редактировании
     */
    public function testVeryLongStringsInEdit(): void
    {
        $gameData = $this->createTestGame();
        $longName = str_repeat("A", 250); // разумная длина для тестирования

        $this->simulatePostRequest([
            "game_id" => $gameData["id"],
            "name" => $longName,
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        // Система должна обработать длинные строки корректно
        $this->assertFalse(
            $error,
            "Система не должна выдавать ошибок при обработке длинных строк: " .
                (is_string($error) ? $error : ""),
        );

        // Проверяем, что имя было сохранено (возможно, обрезано до лимита БД)
        $updatedGame = $this->getLastRecord("game");
        $this->assertNotEmpty(
            $updatedGame["name"],
            "Имя игры должно быть сохранено",
        );

        // Проверяем что имя не пустое (может быть обрезано БД до 255 символов)
        $this->assertLessThanOrEqual(
            255,
            strlen($updatedGame["name"]),
            "Имя не должно превышать лимит БД",
        );
    }

    /**
     * Тест 5.1: Загрузка несуществующей игры для редактирования
     */
    public function testLoadNonExistentGameForEditing(): void
    {
        $this->expectException(Exception::class);

        $_REQUEST = ["game_id" => 999]; // несуществующий ID
        $_GET = ["game_id" => 999];

        mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
    }

    /**
     * Тест 6.1: Множественные ошибки валидации
     */
    public function testMultipleValidationErrors(): void
    {
        $game = $this->createTestGame();
        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "", // пустое название
            "map_w" => 49, // слишком маленькая ширина
            "map_h" => 501, // слишком большая высота
            "turn_type" => "byturn",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        $this->assertTrue($error !== false, "Должны быть ошибки валидации");
        $this->assertStringContainsString(
            "Название игры не может быть пустым",
            $error,
        );
        $this->assertStringContainsString("должна быть от 50 до 500", $error);
    }

    /**
     * Тест 7.1: Проверка, что игра не изменилась при ошибке валидации
     */
    public function testGameNotChangedOnValidationError(): void
    {
        $originalData = [
            "name" => "Оригинальное название",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
        ];
        $game = $this->createTestGame($originalData);
        $this->createTestUser(["login" => "Игрок1", "game" => $game["id"]]);
        $this->createTestUser(["login" => "Игрок2", "game" => $game["id"]]);

        $this->simulatePostRequest([
            "game_id" => $game["id"],
            "name" => "", // пустое название - ошибка валидации
            "map_w" => 150,
            "map_h" => 200,
            "turn_type" => "concurrently",
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/editgame.php");
        $error = $vars["error"] ?? false;

        $this->assertTrue($error !== false, "Должна быть ошибка валидации");

        // Проверяем, что игра не изменилась в БД
        $gameAfterError = $this->getLastRecord("game");
        $this->assertEquals("Оригинальное название", $gameAfterError["name"]);
        $this->assertEquals(100, $gameAfterError["map_w"]);
        $this->assertEquals(100, $gameAfterError["map_h"]);
        $this->assertEquals("byturn", $gameAfterError["turn_type"]);
    }
}
