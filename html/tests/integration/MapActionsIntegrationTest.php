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
        DatabaseTestAdapter::insert("unit_type", [
            "id" => 1,
            "name" => "Warrior",
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

        // Создаем юнит
        $unitData = [
            "user_id" => $userData["id"],
            "type" => 1, // Предполагаем, что тип 1 существует
            "x" => 10,
            "y" => 10,
            "planet" => $gameData["id"],
            "health" => 3,
            "points" => 5,
        ];
        $unitId = DatabaseTestAdapter::insert("unit", $unitData);

        // Создаем клетку назначения
        $cellData = [
            "x" => 11,
            "y" => 10,
            "planet" => $gameData["id"],
            "type" => "plains",
        ];
        DatabaseTestAdapter::insert("cell", $cellData);

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
            0,
            $updatedUnit->points,
            "Очки должны быть потрачены",
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

        // Создаем клетку для города
        $cellData = [
            "x" => 5,
            "y" => 5,
            "planet" => $gameData["id"],
            "type" => "plains",
            "owner" => $userData["id"],
        ];
        DatabaseTestAdapter::insert("cell", $cellData);

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

        // Включаем файл mapv.php
        $vars = mockIncludeFile(__DIR__ . "/../../pages/mapv.php");

        // Проверяем, что данные карты загружены
        $this->assertArrayHasKey("mapv", $vars, "Должны быть данные карты");
        $this->assertArrayHasKey("center_x", $vars, "Должен быть center_x");
        $this->assertArrayHasKey("center_y", $vars, "Должен быть center_y");

        // Проверяем координаты центра
        $this->assertEquals(15, $vars["center_x"], "Центр X должен быть 15");
        $this->assertEquals(20, $vars["center_y"], "Центр Y должен быть 20");
    }

    /**
     * Тест загрузки карты с юнитами
     */
    public function testMapLoadingWithUnits(): void
    {
        // Создаем тестовую игру и пользователя
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);

        // Создаем юнит
        $unitData = [
            "user_id" => $userData["id"],
            "type" => 1,
            "x" => 10,
            "y" => 10,
            "planet" => $gameData["id"],
            "health" => 3,
            "points" => 5,
        ];
        DatabaseTestAdapter::insert("unit", $unitData);

        // Симулируем сессию
        $this->setSession([
            "user_id" => $userData["id"],
            "game_id" => $gameData["id"],
        ]);

        // Загружаем карту
        $this->simulatePostRequest(["cx" => 10, "cy" => 10]);
        $vars = mockIncludeFile(__DIR__ . "/../../pages/mapv.php");

        // Проверяем, что юнит присутствует на карте
        $this->assertArrayHasKey("mapv", $vars);
        $mapData = $vars["mapv"];

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

        // Создаем город
        $cityData = [
            "user_id" => $userData["id"],
            "x" => 5,
            "y" => 5,
            "planet" => $gameData["id"],
            "title" => "Test City",
            "population" => 1,
        ];
        DatabaseTestAdapter::insert("city", $cityData);

        // Симулируем сессию
        $this->setSession([
            "user_id" => $userData["id"],
            "game_id" => $gameData["id"],
        ]);

        // Загружаем карту
        $this->simulatePostRequest(["cx" => 5, "cy" => 5]);
        $vars = mockIncludeFile(__DIR__ . "/../../pages/mapv.php");

        // Проверяем, что город присутствует на карте
        $this->assertArrayHasKey("mapv", $vars);
        $mapData = $vars["mapv"];

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

        // Симулируем сессию
        $this->setSession([
            "user_id" => $userData["id"],
            "game_id" => $gameData["id"],
        ]);

        // Пробуем загрузить карту за пределами границ
        $this->simulatePostRequest(["cx" => 100, "cy" => 100]);
        $vars = mockIncludeFile(__DIR__ . "/../../pages/mapv.php");

        // Проверяем, что координаты скорректированы
        $this->assertLessThanOrEqual(
            50,
            $vars["center_x"],
            "Центр X не должен превышать размер карты",
        );
        $this->assertLessThanOrEqual(
            50,
            $vars["center_y"],
            "Центр Y не должен превышать размер карты",
        );
        $this->assertGreaterThanOrEqual(
            0,
            $vars["center_x"],
            "Центр X не должен быть отрицательным",
        );
        $this->assertGreaterThanOrEqual(
            0,
            $vars["center_y"],
            "Центр Y не должен быть отрицательным",
        );
    }
}
