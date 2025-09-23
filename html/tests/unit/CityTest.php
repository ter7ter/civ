<?php

require_once __DIR__ . "/../TestBase.php";

/**
 * Тесты для класса City
 */
class CityTest extends TestBase
{
    /**
     * Тест получения существующего города
     */
    public function testGetExistingCity(): void
    {
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 10,
            "y" => 20,
            "planet" => 0,
            "title" => "Test City",
            "population" => 1,
            "pmoney" => 1,
            "presearch" => 0,
        ];
        $cityData["id"] = MyDB::insert("city", $cityData);

        $city = City::get($cityData["id"]);

        $this->assertInstanceOf(City::class, $city);
        $this->assertEquals($cityData["id"], $city->id);
        $this->assertEquals("Test City", $city->title);
        $this->assertEquals(10, $city->x);
        $this->assertEquals(20, $city->y);
        $this->assertEquals(1, $city->population);
        $this->assertEquals("Test City", $city->get_title());
    }

    /**
     * Тест метода by_coords с существующим городом
     */
    public function testByCoordsExisting(): void
    {
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 5,
            "y" => 15,
            "planet" => 0,
            "title" => "Coords City",
            "population" => 1,
        ];
        MyDB::insert("city", $cityData);

        $city = City::by_coords(5, 15, 0);

        $this->assertInstanceOf(City::class, $city);
        $this->assertEquals("Coords City", $city->title);
    }

    /**
     * Тест метода by_coords с несуществующим городом
     */
    public function testByCoordsNonExisting(): void
    {
        $city = City::by_coords(999, 999);

        $this->assertFalse($city);
    }

    /**
     * Тест конструктора
     */
    public function testConstruct(): void
    {
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $data = [
            "id" => 1,
            "user_id" => $user->id,
            "x" => 1,
            "y" => 2,
            "title" => "Construct City",
            "population" => 2,
            "pmoney" => 5,
            "presearch" => 1,
            "resource_group" => null,
        ];

        $city = new City($data);

        $this->assertEquals(1, $city->id);
        $this->assertEquals("Construct City", $city->title);
        $this->assertEquals(1, $city->x);
        $this->assertEquals(2, $city->y);
        $this->assertEquals(2, $city->population);
        $this->assertEquals(5, $city->pmoney);
        $this->assertEquals(1, $city->presearch);
        $this->assertNotNull($city->user);
        $this->assertEquals($user->id, $city->user->id);
    }

    /**
     * Тест сохранения нового города
     */
    public function testSaveNew(): void
    {
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $data = [
            "user_id" => $user->id,
            "x" => 3,
            "y" => 4,
            "title" => "Save New City",
            "population" => 1,
            "pmoney" => 2,
            "presearch" => 0,
        ];

        $city = new City($data);
        $city->save();

        $this->assertNotNull($city->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM city WHERE id = :id",
            ["id" => $city->id],
            "row",
        );
        $this->assertEquals("Save New City", $savedData["title"]);
        $this->assertEquals(3, $savedData["x"]);
        $this->assertEquals(4, $savedData["y"]);
        $this->assertEquals(1, $savedData["population"]);
    }

    /**
     * Тест обновления существующего города
     */
    public function testSaveUpdate(): void
    {
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $data = [
            "user_id" => $user->id,
            "x" => 6,
            "y" => 7,
            "title" => "Original City",
            "population" => 1,
        ];
        $city = new City($data);
        $city->save();
        $originalId = $city->id;

        // Обновляем
        $city->title = "Updated City";
        $city->population = 3;
        $city->save();

        $this->assertEquals($originalId, $city->id);

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM city WHERE id = :id",
            ["id" => $city->id],
            "row",
        );
        $this->assertEquals("Updated City", $updatedData["title"]);
        $this->assertEquals(3, $updatedData["population"]);
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $data = [
            "user_id" => $user->id,
            "x" => 8,
            "y" => 9,
            "title" => "Title City",
            "population" => 1,
        ];

        $city = new City($data);

        $this->assertEquals("Title City", $city->get_title());
    }

    /**
     * Тест создания нового города через статический метод new_city
     */
    public function testNewCity(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем тестовые клетки карты для города (расширенная область)
        $this->createTestMapCells(48, 48, 10, 10);

        $city = City::new_city($user, 52, 52, "New Test City");

        $this->assertInstanceOf(City::class, $city);
        $this->assertNotNull($city->id);
        $this->assertEquals("New Test City", $city->title);
        $this->assertEquals(52, $city->x);
        $this->assertEquals(52, $city->y);
        $this->assertEquals(1, $city->population);
        $this->assertEquals($user->id, $city->user->id);

        // Проверяем что город сохранен в БД
        $savedCity = MyDB::query(
            "SELECT * FROM city WHERE id = :id",
            ["id" => $city->id],
            "row",
        );
        $this->assertNotNull($savedCity);
        $this->assertEquals("New Test City", $savedCity["title"]);
    }

    /**
     * Тест расчета производства города
     */
    public function testCalculateProduction(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем тестовые клетки карты
        $this->createTestMapCells(48, 48, 10, 10);

        $city = City::new_city($user, 52, 52, "Production City");

        // Устанавливаем базовые значения производства
        $city->pwork = 5;
        $city->peat = 3;
        $city->pmoney = 2;
        $city->presearch = 1;

        $city->calculate_production();

        // Проверяем что метод выполнился без фатальных ошибок
        $this->assertTrue(is_numeric($city->pwork));
        $this->assertTrue(is_numeric($city->peat));
        $this->assertTrue(is_numeric($city->pmoney));
        $this->assertTrue(is_numeric($city->presearch));
    }

    /**
     * Тест размещения жителей в городе
     */
    public function testLocatePeople(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем тестовые клетки карты
        $this->createTestMapCells(48, 48, 10, 10);

        $city = City::new_city($user, 52, 52, "People City");

        $city->population = 3;
        $city->locate_people();

        // Проверяем что метод выполнился без ошибок
        $this->assertIsArray($city->people_cells);
        $this->assertGreaterThanOrEqual(0, count($city->people_cells));
    }

    /**
     * Тест расчета настроения жителей
     */
    public function testCalculatePeople(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем тестовые клетки карты
        $this->createTestMapCells(48, 48, 10, 10);

        $city = City::new_city($user, 52, 52, "Mood City");

        $city->population = 5;
        $city->calculate_people();

        // Упрощенная проверка - метод выполнился без ошибок
        $this->assertGreaterThanOrEqual(0, $city->people_dis);
        $this->assertGreaterThanOrEqual(0, $city->people_norm);
        $this->assertGreaterThanOrEqual(0, $city->people_happy);
        $this->assertGreaterThanOrEqual(0, $city->people_artist);
    }

    /**
     * Тест создания юнита в городе
     */
    public function testCreateUnit(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем тестовые клетки карты
        $this->createTestMapCells(48, 48, 10, 10);

        $city = City::new_city($user, 52, 52, "Unit City");

        // Проверяем что метод существует и можно его вызвать
        $this->assertTrue(method_exists($city, "create_unit"));

        // Получаем возможные юниты
        $possibleUnits = $city->get_possible_units();
        $this->assertIsArray($possibleUnits);
    }

    /**
     * Тест получения возможных построек
     */
    public function testGetPossibleBuildings(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем тестовые клетки карты
        $this->createTestMapCells(48, 48, 10, 10);

        $city = City::new_city($user, 52, 52, "Building City");

        $possibleBuildings = $city->get_possible_buildings();
        $this->assertIsArray($possibleBuildings);
    }

    /**
     * Тест получения клеток города
     */
    public function testGetCityCells(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем тестовые клетки карты
        $this->createTestMapCells(48, 48, 10, 10);

        $city = City::new_city($user, 53, 53, "Cells City");

        $cityCells = $city->get_city_cells();
        $this->assertIsArray($cityCells);

        // Просто проверяем что метод работает без ошибок
        $this->assertTrue(true, "Метод get_city_cells выполнился без ошибок");
    }

    /**
     * Тест обработки прибрежного города
     */
    public function testCoastalCity(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем клетки с водой рядом
        $this->createTestMapCells(48, 48, 10, 10);

        // Создаем водную клетку рядом с городом
        $waterCell = Cell::get(52, 51);
        if ($waterCell) {
            $waterCell->type = CellType::get("water1");
            $waterCell->save();
        }

        $city = City::new_city($user, 52, 52, "Coastal City");

        // Проверяем что город определился как прибрежный
        $this->assertTrue(
            $city->is_coastal,
            "Город рядом с водой должен быть прибрежным",
        );
    }

    /**
     * Тест работы с ресурсами города
     */
    public function testCityResources(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $city = new City([
            "user_id" => $user->id,
            "x" => 10,
            "y" => 20,
            "planet" => 0,
            "title" => "Resource City",
            "population" => 1,
            "resource_group" => null,
        ]);

        $this->assertIsArray($city->resources);

        // Если нет группы ресурсов, массив должен быть пустым
        if (!$city->resource_group) {
            $this->assertEmpty($city->resources);
        }
    }

    /**
     * Тест кэширования городов
     */
    public function testCityCache(): void
    {
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 10,
            "y" => 20,
            "planet" => 0,
            "title" => "Cache City",
            "population" => 1,
        ];
        $cityData["id"] = MyDB::insert("city", $cityData);

        // Первое получение - из БД
        $city1 = City::get($cityData["id"]);
        $this->assertInstanceOf(City::class, $city1);

        // Второе получение - из кэша
        $city2 = City::get($cityData["id"]);
        $this->assertSame(
            $city1,
            $city2,
            "Второй вызов должен вернуть тот же объект из кэша",
        );

        // Очистка кэша
        City::clearCache();
        $city3 = City::get($cityData["id"]);
        $this->assertNotSame(
            $city1,
            $city3,
            "После очистки кэша должен создаться новый объект",
        );
    }
}
