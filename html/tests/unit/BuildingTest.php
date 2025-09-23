<?php

require_once __DIR__ . "/../TestBase.php";

/**
 * Тесты для класса Building
 */
class BuildingTest extends TestBase
{
    /**
     * Тест получения существующего здания
     */
    public function testGetExistingBuilding(): void
    {
        $this->initializeGameTypes();
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
        ];
        $cityId = MyDB::insert("city", $cityData);
        $city = City::get($cityId);

        // Создаем здание
        $buildingData = [
            "city_id" => $city->id,
            "type" => 1,
        ];
        $buildingId = MyDB::insert("building", $buildingData);

        $building = Building::get($buildingId);

        $this->assertInstanceOf(Building::class, $building);
        $this->assertEquals($buildingId, $building->id);
        $this->assertInstanceOf(City::class, $building->city);
        $this->assertEquals($city->id, $building->city->id);
        $this->assertInstanceOf(BuildingType::class, $building->type);
        $this->assertNotEmpty($building->type->get_title());
    }

    /**
     * Тест конструктора здания
     */
    public function testConstructor(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 5,
            "y" => 15,
            "planet" => 0,
            "title" => "Constructor City",
            "population" => 1,
        ];
        $cityId = MyDB::insert("city", $cityData);

        $buildingData = [
            "id" => 1,
            "city_id" => $cityId,
            "type" => 2,
        ];

        $building = new Building($buildingData);

        $this->assertEquals(1, $building->id);
        $this->assertInstanceOf(City::class, $building->city);
        $this->assertEquals($cityId, $building->city->id);
        $this->assertInstanceOf(BuildingType::class, $building->type);
        $this->assertNotEmpty($building->type->get_title());
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 8,
            "y" => 12,
            "planet" => 0,
            "title" => "Title City",
            "population" => 1,
        ];
        $cityId = MyDB::insert("city", $cityData);

        $buildingData = [
            "id" => 2,
            "city_id" => $cityId,
            "type" => 4,
        ];

        $building = new Building($buildingData);
        $title = $building->get_title();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
        // Название должно соответствовать типу здания
        $this->assertEquals($building->type->get_title(), $title);
    }

    /**
     * Тест сохранения нового здания
     */
    public function testSaveNew(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 3,
            "y" => 7,
            "planet" => 0,
            "title" => "Save City",
            "population" => 1,
        ];
        $cityId = MyDB::insert("city", $cityData);
        $city = City::get($cityId);

        $buildingData = [
            "city_id" => $city->id,
            "type" => 3,
        ];

        $building = new Building($buildingData);
        $building->save();

        $this->assertNotNull($building->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM building WHERE id = :id",
            ["id" => $building->id],
            "row",
        );
        $this->assertNotNull($savedData);
        $this->assertEquals($city->id, $savedData["city_id"]);
        $this->assertEquals(3, $savedData["type"]);
    }

    /**
     * Тест обновления существующего здания
     */
    public function testSaveUpdate(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 6,
            "y" => 9,
            "planet" => 0,
            "title" => "Update City",
            "population" => 1,
        ];
        $cityId = MyDB::insert("city", $cityData);
        $city = City::get($cityId);

        // Создаем здание
        $buildingData = [
            "city_id" => $city->id,
            "type" => 1,
        ];
        $buildingId = MyDB::insert("building", $buildingData);
        $building = Building::get($buildingId);
        $originalId = $building->id;

        // Обновляем тип здания
        $building->type = BuildingType::get(4);
        $building->save();

        $this->assertEquals($originalId, $building->id);

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM building WHERE id = :id",
            ["id" => $building->id],
            "row",
        );
        $this->assertEquals(4, $updatedData["type"]);
        $this->assertEquals($city->id, $updatedData["city_id"]);
    }

    /**
     * Тест кэширования зданий
     */
    public function testBuildingCache(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 15,
            "y" => 25,
            "planet" => 0,
            "title" => "Cache City",
            "population" => 1,
        ];
        $cityId = MyDB::insert("city", $cityData);

        // Создаем здание
        $buildingData = [
            "city_id" => $cityId,
            "type" => 5,
        ];
        $buildingId = MyDB::insert("building", $buildingData);

        // Первое получение - из БД
        $building1 = Building::get($buildingId);
        $this->assertInstanceOf(Building::class, $building1);

        // Второе получение - из кэша
        $building2 = Building::get($buildingId);
        $this->assertSame(
            $building1,
            $building2,
            "Второй вызов должен вернуть тот же объект из кэша",
        );
    }

    /**
     * Тест связи здания с городом
     */
    public function testBuildingCityRelation(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 20,
            "y" => 30,
            "planet" => 0,
            "title" => "Relation City",
            "population" => 1,
        ];
        $cityId = MyDB::insert("city", $cityData);

        $buildingData = [
            "city_id" => $cityId,
            "type" => 2,
        ];

        $building = new Building($buildingData);

        // Проверяем что здание правильно связано с городом
        $this->assertInstanceOf(City::class, $building->city);
        $this->assertEquals($cityId, $building->city->id);
        $this->assertEquals("Relation City", $building->city->title);

        // Проверяем что город принадлежит правильному пользователю
        $this->assertEquals($user->id, $building->city->user->id);
    }

    /**
     * Тест связи здания с типом здания
     */
    public function testBuildingTypeRelation(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 25,
            "y" => 35,
            "planet" => 0,
            "title" => "Type City",
            "population" => 1,
        ];
        $cityId = MyDB::insert("city", $cityData);

        $buildingData = [
            "city_id" => $cityId,
            "type" => 3,
        ];

        $building = new Building($buildingData);

        // Проверяем что здание правильно связано с типом
        $this->assertInstanceOf(BuildingType::class, $building->type);
        $this->assertNotEmpty($building->type->get_title());

        // Проверяем что можем получить название типа
        $this->assertIsString($building->type->get_title());
        $this->assertNotEmpty($building->type->get_title());
    }

    /**
     * Тест создания здания с различными типами
     */
    public function testDifferentBuildingTypes(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем город
        $cityData = [
            "user_id" => $user->id,
            "x" => 40,
            "y" => 50,
            "planet" => 0,
            "title" => "Types City",
            "population" => 1,
        ];
        $cityId = MyDB::insert("city", $cityData);

        $buildingTypes = [1, 2, 4, 3, 5];

        foreach ($buildingTypes as $type) {
            $buildingData = [
                "city_id" => $cityId,
                "type" => $type,
            ];

            $building = new Building($buildingData);
            $building->save();

            $this->assertNotNull($building->id);
            $this->assertEquals($type, $building->type->id);
            $this->assertIsString($building->get_title());
            $this->assertNotEmpty($building->get_title());
        }
    }
}
