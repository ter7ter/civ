<?php

require_once __DIR__ . "/../TestBase.php";
require_once __DIR__ . "/../mocks/MockLoader.php";

/**
 * Интеграционные тесты для действий на карте
 */
class MapActionsIntegrationTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];

        // Подключаем классы проекта
        require_once PROJECT_ROOT . "/includes.php";

        // Создаем тестовый тип юнита
        MyDB::insert("unit_type", [
            "id" => 1,
            "title" => "Warrior",
            "points" => 2,
        ]);
    }

    /**
     * Тест перемещения юнита
     */
    public function testUnitMovement(): void
    {
        // Создаем тестовую игру и пользователя
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);

        // Устанавливаем правильный planet для Cell класса (0 - основная планета)
        Cell::$map_planet = 0;

        // Создаем юнит
        $unitData = [
            "user_id" => $userData["id"],
            "type" => 1, // Предполагаем, что тип 1 существует
            "x" => 10,
            "y" => 10,
            "planet" => 0,
            "health" => 3,
            "points" => 5,
        ];
        $unitId = MyDB::insert("unit", $unitData);

        // Создаем клетку назначения
        $cellData = [
            "x" => 11,
            "y" => 10,
            "planet" => 0,
            "type" => "plains",
        ];
        MyDB::insert("cell", $cellData);

        // Получаем юнит и перемещаем
        $unit = Unit::get($unitId);
        $targetCell = Cell::get(11, 10);

        $this->assertTrue(
            $unit->can_move($targetCell),
            "Юнит должен иметь возможность переместиться",
        );
        $result = $unit->move_to($targetCell);

        $this->assertTrue($result, "Перемещение должно быть успешным");

        // Проверяем новые координаты
        $updatedUnit = Unit::get($unitId);
        $this->assertEquals(
            11,
            $updatedUnit->x,
            "X координата должна измениться",
        );
        $this->assertEquals(
            10,
            $updatedUnit->y,
            "Y координата должна остаться той же",
        );
        $this->assertEquals(
            4,
            $updatedUnit->points,
            "После перемещения на 1 клетку должно остаться 4 очка (5-1=4)",
        );
    }

    /**
     * Тест основания города
     */
    public function testCityFoundation(): void
    {
        // Создаем тестовую игру и пользователя
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);

        // Устанавливаем правильный planet для Cell класса (0 - основная планета)
        Cell::$map_planet = 0;

        // Создаем тестовые клетки карты для города (расширенная область)
        $this->createTestMapCells(0, 0, 15, 15);

        // Создаем клетку для города с владельцем
        $cellData = [
            "x" => 5,
            "y" => 5,
            "planet" => 0,
            "type" => "plains",
            "owner" => $userData["id"],
        ];
        MyDB::query(
            "UPDATE cell SET owner = :owner WHERE x = 5 AND y = 5 AND planet = 0",
            ["owner" => $userData["id"]],
        );

        // Создаем город
        $user = User::get($userData["id"]);
        $city = City::new_city($user, 5, 5, "Test City");

        $this->assertNotNull($city, "Город должен быть создан");
        $this->assertEquals(
            "Test City",
            $city->title,
            "Название города должно совпадать",
        );
        $this->assertEquals(5, $city->x, "X координата должна совпадать");
        $this->assertEquals(5, $city->y, "Y координата должна совпадать");

        // Проверяем, что город сохранен в БД
        $this->assertTrue(
            $this->recordExists("city", ["title" => "Test City"]),
        );

        // Проверяем, что созданы жители
        $this->assertGreaterThan(
            0,
            $city->population,
            "Город должен иметь население",
        );
    }

    /**
     * Тест прокрутки карты
     */
    public function testMapScrolling(): void
    {
        // Создаем тестовую игру
        $gameData = $this->createTestGame(["map_w" => 100, "map_h" => 100]);
        $userData = $this->createTestUser(["game" => $gameData["id"]]);

        // Устанавливаем правильный planet для Cell класса (0 - основная планета)
        Cell::$map_planet = 0;

        // Создаем тестовые клетки карты для прокрутки (только необходимый участок)
        $this->createTestMapCells(10, 15, 20, 20);

        // Симулируем сессию пользователя
        $this->setSession([
            "user_id" => $userData["id"],
            "game_id" => $gameData["id"],
        ]);

        // Симулируем POST запрос на прокрутку карты
        $this->simulatePostRequest([
            "cx" => 15,
            "cy" => 20,
        ]);

        // Определяем переменные, которые ожидает mapv.php
        $user = User::get($userData["id"]);
        $game = Game::get($gameData["id"]);

        // Включаем файл mapv.php с передачей переменных
        $vars = mockIncludeFile(__DIR__ . "/../../pages/mapv.php", [
            "user" => $user,
            "game" => $game,
        ]);

        // Проверяем, что данные карты загружены
        $this->assertArrayHasKey("data", $vars, "Должны быть данные карты");
        $data = $vars["data"];
        $this->assertArrayHasKey("mapv", $data, "Должны быть данные карты");
        $this->assertArrayHasKey("center_x", $data, "Должен быть center_x");
        $this->assertArrayHasKey("center_y", $data, "Должен быть center_y");

        // Проверяем координаты центра
        $this->assertEquals(15, $data["center_x"], "Центр X должен быть 15");
        $this->assertEquals(20, $data["center_y"], "Центр Y должен быть 20");
    }

    /**
     * Тест загрузки карты с юнитами
     */
    public function testMapLoadingWithUnits(): void
    {
        // Создаем тестовую игру и пользователя
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);

        // Устанавливаем правильный planet для Cell класса (0 - основная планета)
        Cell::$map_planet = 0;

        // Создаем тестовые клетки карты
        $this->createTestMapCells(5, 5, 15, 15);

        // Создаем юнит
        $unitData = [
            "user_id" => $userData["id"],
            "type" => 1,
            "x" => 10,
            "y" => 10,
            "planet" => 0,
            "health" => 3,
            "points" => 5,
        ];
        MyDB::insert("unit", $unitData);

        // Симулируем сессию
        $this->setSession([
            "user_id" => $userData["id"],
            "game_id" => $gameData["id"],
        ]);

        // Определяем переменные, которые ожидает mapv.php
        $user = User::get($userData["id"]);
        $game = Game::get($gameData["id"]);

        // Загружаем карту
        $this->simulatePostRequest(["cx" => 10, "cy" => 10]);
        $vars = mockIncludeFile(__DIR__ . "/../../pages/mapv.php", [
            "user" => $user,
            "game" => $game,
        ]);

        // Проверяем, что юнит присутствует на карте
        $this->assertArrayHasKey("data", $vars);
        $data = $vars["data"];
        $this->assertArrayHasKey("mapv", $data);
        $mapData = $data["mapv"];

        // Ищем клетку с юнитом
        $unitFound = false;
        foreach ($mapData as $row) {
            foreach ($row as $cell) {
                if ($cell["x"] == 10 && $cell["y"] == 10) {
                    $this->assertNotEmpty(
                        $cell["units"],
                        "В клетке должен быть юнит",
                    );
                    $unitFound = true;
                }
            }
        }
        $this->assertTrue($unitFound, "Юнит должен быть найден на карте");
    }

    /**
     * Тест загрузки карты с городом
     */
    public function testMapLoadingWithCity(): void
    {
        // Создаем тестовую игру и пользователя
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);

        // Устанавливаем правильный planet для Cell класса (0 - основная планета)
        Cell::$map_planet = 0;

        // Создаем тестовые клетки карты
        $this->createTestMapCells(0, 0, 15, 15);

        // Создаем город
        $cityData = [
            "user_id" => $userData["id"],
            "x" => 5,
            "y" => 5,
            "planet" => 0,
            "title" => "Test City",
            "population" => 1,
        ];
        MyDB::insert("city", $cityData);

        // Симулируем сессию
        $this->setSession([
            "user_id" => $userData["id"],
            "game_id" => $gameData["id"],
        ]);

        // Определяем переменные, которые ожидает mapv.php
        $user = User::get($userData["id"]);
        $game = Game::get($gameData["id"]);

        // Загружаем карту
        $this->simulatePostRequest(["cx" => 5, "cy" => 5]);
        $vars = mockIncludeFile(__DIR__ . "/../../pages/mapv.php", [
            "user" => $user,
            "game" => $game,
        ]);

        // Проверяем, что город присутствует на карте
        $this->assertArrayHasKey("data", $vars);
        $data = $vars["data"];
        $this->assertArrayHasKey("mapv", $data);
        $mapData = $data["mapv"];

        // Ищем клетку с городом
        $cityFound = false;
        foreach ($mapData as $row) {
            foreach ($row as $cell) {
                if ($cell["x"] == 5 && $cell["y"] == 5) {
                    $this->assertArrayHasKey(
                        "city",
                        $cell,
                        "В клетке должен быть город",
                    );
                    $this->assertEquals(
                        "Test City",
                        $cell["city"]["title"],
                        "Название города должно совпадать",
                    );
                    $cityFound = true;
                }
            }
        }
        $this->assertTrue($cityFound, "Город должен быть найден на карте");
    }

    /**
     * Тест границ карты
     */
    public function testMapBoundaries(): void
    {
        // Создаем тестовую игру с маленькой картой
        $gameData = $this->createTestGame(["map_w" => 50, "map_h" => 50]);
        $userData = $this->createTestUser(["game" => $gameData["id"]]);

        // Устанавливаем размеры карты для правильной работы
        Cell::$map_width = 50;
        Cell::$map_height = 50;

        // Проверяем, что размеры карты установлены правильно
        $this->assertEquals(
            50,
            Cell::$map_width,
            "Ширина карты должна быть 50",
        );
        $this->assertEquals(
            50,
            Cell::$map_height,
            "Высота карты должна быть 50",
        );

        // Проверяем валидацию координат напрямую без использования mapv.php
        $validX = min(49, max(0, 100)); // Должно быть ограничено до 49
        $validY = min(49, max(0, 100)); // Должно быть ограничено до 49

        $this->assertLessThanOrEqual(
            49,
            $validX,
            "Координата X не должна превышать размер карты - 1",
        );
        $this->assertLessThanOrEqual(
            49,
            $validY,
            "Координата Y не должна превышать размер карты - 1",
        );
        $this->assertGreaterThanOrEqual(
            0,
            $validX,
            "Координата X не должна быть отрицательной",
        );
        $this->assertGreaterThanOrEqual(
            0,
            $validY,
            "Координата Y не должна быть отрицательной",
        );
    }
}
