<?php

require_once __DIR__ . "/../TestBase.php";

/**
 * Тесты для функции создания игры
 */
class CreateGameTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];
    }

    /**
     * Тест 1.1: Создание базовой игры
     */
    public function testCreateBasicGame(): void
    {
        $this->simulatePostRequest([
            "name" => "Тестовая игра",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        // Проверяем, что игра создана
        $this->assertFalse(
            $error,
            "Не должно быть ошибок при создании базовой игры: " . (is_string($error) ? $error : '')
        );

        // Проверяем, что игра создана в БД
        $gameCount = $this->getTableCount("game");
        $this->assertEquals(1, $gameCount, "Должна быть создана одна игра");

        $userCount = $this->getTableCount("user");
        $this->assertEquals(
            2,
            $userCount,
            "Должно быть создано два пользователя",
        );
    }

    /**
     * Тест 1.2: Максимальное количество игроков
     */
    public function testMaximumPlayersGame(): void
    {
        $players = [];
        for ($i = 1; $i <= 16; $i++) {
            $players[] = "Игрок{$i}";
        }

        $this->simulatePostRequest([
            "name" => "Игра с 16 игроками",
            "map_w" => 200,
            "map_h" => 200,
            "turn_type" => "concurrently",
            "users" => $players,
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertFalse(
            $error,
            "Не должно быть ошибок при создании игры с 16 игроками: " . (is_string($error) ? $error : '')
        );

        // Проверяем создание в БД
        $gameCount = $this->getTableCount("game");
        $this->assertEquals(1, $gameCount, "Должна быть создана одна игра");

        $userCount = $this->getTableCount("user");
        $this->assertEquals(
            16,
            $userCount,
            "Должно быть создано 16 пользователей",
        );
    }

    /**
     * Тест 1.3: Минимальные размеры карты
     */
    public function testMinimumMapSize(): void
    {
        $this->simulatePostRequest([
            "name" => "Маленькая карта",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertFalse(
            $error,
            "Не должно быть ошибок для минимального размера карты: " . (is_string($error) ? $error : '')
        );
    }

    /**
     * Тест 1.4: Максимальные размеры карты
     */
    public function testMaximumMapSize(): void
    {
        $this->simulatePostRequest([
            "name" => "Большая карта",
            "map_w" => 500,
            "map_h" => 500,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertFalse(
            $error,
            "Не должно быть ошибок для максимального размера карты: " . (is_string($error) ? $error : '')
        );
    }

    /**
     * Тест 1.5: Разные типы ходов
     */
    public function testDifferentTurnTypes(): void
    {
        $turnTypes = ["concurrently", "byturn", "onewindow"];

        foreach ($turnTypes as $turnType) {
            $this->simulatePostRequest([
                "name" => "Игра {$turnType}",
                "map_w" => 100,
                "map_h" => 100,
                "turn_type" => $turnType,
                "users" => ["Игрок1", "Игрок2"],
            ]);

            $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
            $error = $vars['error'] ?? false;

            $this->assertFalse(
                $error,
                "Не должно быть ошибок для типа ходов: {$turnType}: " . (is_string($error) ? $error : '')
            );
        }
    }

    /**
     * Тест 2.1: Пустое название игры
     */
    public function testEmptyGameName(): void
    {
        $this->simulatePostRequest([
            "name" => "",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для пустого названия",
        );
        $this->assertEquals("Название игры не может быть пустым", $error);


    }

    /**
     * Тест 2.2: Недостаточно игроков
     */
    public function testInsufficientPlayers(): void
    {
        $this->simulatePostRequest([
            "name" => "Тест",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Игрок1"], // только один игрок
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для недостаточного количества игроков",
        );
        $this->assertStringContainsString("минимум 2 игрока", $error);
    }

    /**
     * Тест 2.3: Дублирующиеся имена игроков
     */
    public function testDuplicatePlayerNames(): void
    {
        $this->simulatePostRequest([
            "name" => "Тест",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок1"], // дублирующиеся имена
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для дублирующихся имен",
        );
        $this->assertStringContainsString("указан несколько раз", $error);
    }

    /**
     * Тест 2.4: Слишком маленькие размеры карты
     */
    public function testTooSmallMapSize(): void
    {
        $this->simulatePostRequest([
            "name" => "Тест",
            "map_w" => 49,
            "map_h" => 49,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для слишком маленькой карты",
        );
        $this->assertStringContainsString("должна быть от 50 до 500", $error);
    }

    /**
     * Тест 2.5: Слишком большие размеры карты
     */
    public function testTooLargeMapSize(): void
    {
        $this->simulatePostRequest([
            "name" => "Тест",
            "map_w" => 501,
            "map_h" => 501,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для слишком большой карты",
        );
        $this->assertStringContainsString("должна быть от 50 до 500", $error);
    }

    /**
     * Тест 2.6: Слишком много игроков
     */
    public function testTooManyPlayers(): void
    {
        $players = [];
        for ($i = 1; $i <= 17; $i++) {
            $players[] = "Игрок{$i}";
        }

        $this->simulatePostRequest([
            "name" => "Тест",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => $players,
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertTrue(
            $error !== false,
            "Должна быть ошибка для слишком большого количества игроков",
        );
        $this->assertStringContainsString(
            "Максимальное количество игроков: 16",
            $error,
        );
    }

    /**
     * Тест 3.5: Сохранение данных при ошибке
     */
    public function testDataPreservationOnError(): void
    {
        $testData = [
            "name" => "", // пустое имя вызовет ошибку
            "map_w" => 150,
            "map_h" => 200,
            "turn_type" => "concurrently",
            "users" => ["Игрок1", "Игрок2"],
        ];

        $this->simulatePostRequest($testData);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;
        $data = $vars['data'] ?? [];

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
     * Тест 4.1: HTML-инъекции в названии
     */
    public function testXSSInGameName(): void
    {
        $maliciousName = '<script>alert("XSS")</script>';

        $this->simulatePostRequest([
            "name" => $maliciousName,
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        // 1. Убеждаемся, что скрипт отработал без ошибок валидации.
        $this->assertFalse(
            $error,
            "Не должно быть ошибок при создании игры с XSS в названии: " . (is_string($error) ? $error : '')
        );

        // 2. Проверяем, что игра была создана.
        $this->assertEquals(1, $this->getTableCount("game"), "Игра должна быть создана.");

        // 3. Получаем игру из БД и проверяем, что название было очищено.
        $stmt = self::$pdo->query("SELECT name FROM game LIMIT 1");
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($game, "Не удалось получить созданную игру из БД.");
        $this->assertEquals(htmlspecialchars($maliciousName), $game['name'], "Название игры в БД должно быть экранировано.");
    }

    /**
     * Тест 4.2: HTML-инъекции в именах игроков
     */
    public function testXSSInPlayerNames(): void
    {
        $maliciousPlayer = '<img src=x onerror=alert("XSS")>';

        $this->simulatePostRequest([
            "name" => "Тест",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Игрок1", $maliciousPlayer],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        // 1. Убеждаемся, что скрипт отработал без ошибок валидации.
        $this->assertFalse(
            $error,
            "Не должно быть ошибок при создании игры с XSS в именах игроков: " . (is_string($error) ? $error : '')
        );

        // 2. Проверяем, что пользователи были созданы.
        $this->assertEquals(2, $this->getTableCount("user"), "Должно быть создано два пользователя.");

        // 3. Получаем пользователей из БД и проверяем, что имена были очищены.
        $stmt = self::$pdo->query("SELECT login FROM user");
        $logins = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $sanitizedMaliciousName = htmlspecialchars($maliciousPlayer);
        $this->assertContains($sanitizedMaliciousName, $logins, "Очищенное имя игрока должно присутствовать в списке логинов.");

        // Дополнительная проверка, что ни в одном имени нет опасных тегов
        foreach ($logins as $login) {
            $this->assertStringNotContainsString("<img", $login, "Тег <img> не должен присутствовать в имени пользователя.");
        }
    }

    /**
     * Тест 4.3: Очень длинные строки
     */
    public function testVeryLongStrings(): void
    {
        $longName = str_repeat("A", 300);
        $longPlayerName = str_repeat("B", 100);

        $this->simulatePostRequest([
            "name" => $longName,
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Игрок1", $longPlayerName],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        // 1. Убеждаемся, что скрипт отработал без ошибок валидации.
        $this->assertFalse(
            $error,
            "Система не должна выдавать ошибок при обработке длинных строк: " . (is_string($error) ? $error : '')
        );

        // 2. Проверяем, что игра и пользователи были созданы.
        $this->assertEquals(1, $this->getTableCount("game"), "Игра должна быть создана с длинным именем.");
        $this->assertEquals(2, $this->getTableCount("user"), "Пользователи должны быть созданы, включая пользователя с длинным именем.");

        // 3. Проверяем, что данные не были обрезаны (зависит от схемы БД)
        $stmt = self::$pdo->query("SELECT name FROM game LIMIT 1");
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($longName, $game['name'], "Длинное имя игры должно быть сохранено полностью.");

        $stmt = self::$pdo->query("SELECT login FROM user WHERE login LIKE 'B%'");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($longPlayerName, $user['login'], "Длинное имя игрока должно быть сохранено полностью.");
    }

    /**
     * Тест валидации цветов игроков
     */
    public function testPlayerColorGeneration(): void
    {
        $players = ["Игрок1", "Игрок2", "Игрок3", "Игрок4", "Игрок5"];

        $this->simulatePostRequest([
            "name" => "Тест цветов",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => $players,
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertFalse(
            $error,
            "Не должно быть ошибок при генерации цветов: " . (is_string($error) ? $error : '')
        );
    }

    /**
     * Тест обработки пустых полей игроков
     */
    public function testEmptyPlayerFields(): void
    {
        $this->simulatePostRequest([
            "name" => "Тест пустых полей",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Игрок1", "", "Игрок3", ""], // пустые поля
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        $this->assertFalse(
            $error,
            "Не должно быть ошибок, пустые поля должны игнорироваться: " . (is_string($error) ? $error : '')
        );
    }

    /**
     * Тест неверного типа ходов
     */
    public function testInvalidTurnType(): void
    {
        $this->simulatePostRequest([
            "name" => "Тест неверного типа",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "invalid_type", // неверный тип
            "users" => ["Игрок1", "Игрок2"],
        ]);

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;

        // Должно использоваться значение по умолчанию
        $this->assertFalse(
            $error,
            "Неверный тип ходов должен заменяться на значение по умолчанию: " . (is_string($error) ? $error : '')
        );
    }

    /**
     * Тест отсутствия данных POST
     */
    public function testNoPostData(): void
    {
        // Не устанавливаем POST данные

        $vars = mockIncludeFile(__DIR__ . "/../../pages/creategame.php");
        $error = $vars['error'] ?? false;
        $data = $vars['data'] ?? [];

        // Должны быть установлены значения по умолчанию
        $this->assertFalse(
            $error,
            "Не должно быть ошибок при отсутствии POST данных: " . (is_string($error) ? $error : '')
        );
        $this->assertIsArray(
            $data,
            "data должно быть массивом со значениями по умолчанию",
        );
    }
}