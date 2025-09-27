<?php

require_once __DIR__ . "/../bootstrap.php";

/**
 * Тесты для класса Building
 */
class BuildingTest extends TestBase
{
    /**
     * Тест получения здания по ID
     */
    public function testGet(): void
    {
        $this->initializeGameTypes();
        $testData = $this->createTestGameWithPlanetUserAndCity();
        $game = $testData['game'];
        $planetId = $testData['planet'];
        $user = $testData['user'];
        $city = $testData['city'];

        // Создаем здание через объект
        $building = new Building([
            'city_id' => $city->id,
            'type' => 1, // Бараки
        ]);
        $building->save();


        $this->assertInstanceOf(Building::class, $building);
        $this->assertNotNull($building->id);
        $this->assertGreaterThan(0, (int)$building->id);
        $this->assertInstanceOf(City::class, $building->city);
        $this->assertGreaterThan(0, (int)$building->city->id);
        $this->assertInstanceOf(BuildingType::class, $building->type);
        $this->assertEquals(1, $building->type->id);
    }

    /**
     * Тест конструктора Building
     */
    public function testConstructor(): void
    {
        $this->initializeGameTypes();
        $testData = $this->createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        $data = [
            'id' => 1,
            'city_id' => $city->id,
            'type' => 1, // Бараки
        ];

        $building = new Building($data);

        $this->assertEquals(1, $building->id);
        $this->assertInstanceOf(City::class, $building->city);
        $this->assertEquals($city->id, $building->city->id);
        $this->assertInstanceOf(BuildingType::class, $building->type);
        $this->assertEquals(1, $building->type->id);

        // Проверяем, что объект добавлен в кэш
        $this->assertSame($building, Building::get(1));
    }

    /**
     * Тест конструктора без ID
     */
    public function testConstructorWithoutId(): void
    {
        $this->initializeGameTypes();
        $testData = $this->createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        $data = [
            'city_id' => $city->id,
            'type' => 2, // Храм
        ];

        $building = new Building($data);

        $this->assertNull($building->id);
        $this->assertInstanceOf(City::class, $building->city);
        $this->assertInstanceOf(BuildingType::class, $building->type);
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $this->initializeGameTypes();
        $testData = $this->createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        $data = [
            'city_id' => $city->id,
            'type' => 1, // Бараки
        ];

        $building = new Building($data);

        $this->assertEquals('бараки', $building->get_title());
    }

    /**
     * Тест сохранения нового здания
     */
    public function testSaveNew(): void
    {
        $this->initializeGameTypes();
        $testData = $this->createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        $data = [
            'city_id' => $city->id,
            'type' => 1, // Бараки
        ];

        $building = new Building($data);
        $building->save();

        $this->assertNotNull($building->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM building WHERE id = :id",
            ["id" => $building->id],
            "row"
        );
        $this->assertNotNull($savedData);
        $this->assertEquals($city->id, $savedData['city_id']);
        $this->assertEquals(1, $savedData['type']);
    }

    /**
     * Тест обновления существующего здания
     */
    public function testSaveUpdate(): void
    {
        $this->initializeGameTypes();
        $testData = $this->createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        // Создаем здание через объект
        $building = new Building([
            'city_id' => $city->id,
            'type' => 1, // Бараки
        ]);
        $building->save();

        $building->type = BuildingType::get(2); // Меняем тип на Храм
        $building->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM building WHERE id = :id",
            ["id" => $building->id],
            "row"
        );
        $this->assertEquals(2, $updatedData['type']);
    }

    /**
     * Тест кэширования зданий
     */
    public function testCaching(): void
    {
        $this->initializeGameTypes();
        $testData = $this->createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        // Создаем здание через объект
        $building = new Building([
            'city_id' => $city->id,
            'type' => 1,
        ]);
        $building->save();

        // Получаем здание первый раз
        $building1 = Building::get($building->id);

        // Получаем здание второй раз - должен вернуться тот же объект из кэша
        $building2 = Building::get($building->id);

        $this->assertSame($building1, $building2);
    }

    /**
     * Тест создания здания разных типов
     */
    public function testDifferentBuildingTypes(): void
    {
        $this->initializeGameTypes();
        $testData = $this->createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        $buildingTypes = [1, 2, 3]; // Бараки, Храм, Рынок

        foreach ($buildingTypes as $typeId) {
            $data = [
                'city_id' => $city->id,
                'type' => $typeId,
            ];

            $building = new Building($data);

            $this->assertInstanceOf(BuildingType::class, $building->type);
            $this->assertEquals($typeId, $building->type->id);
            $this->assertIsString($building->get_title());
            $this->assertNotEmpty($building->get_title());
        }
    }

    /**
     * Тест метода city_effect для разных типов зданий
     * @todo Недоделан - будет переделан после доработки объекта BuildingType
     */
    public function testCityEffect(): void
    {
        $this->markTestIncomplete('Тест недоделан - будет переделан после доработки объекта BuildingType');
        return;
        $this->initializeGameTypes();
        $game = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $game->id]);
        $user = $this->createTestUser(['game' => $game->id]);
        $city = $this->createTestCity(['user_id' => $user['id'], 'planet' => $planetId]);

        // Тест Амбара (id=2) - уменьшает eat_up вдвое
        $buildingType2 = BuildingType::get(2);
        $originalEatUp = $city->eat_up;
        $buildingType2->city_effect($city);
        $this->assertEquals((int) (BASE_EAT_UP / 2), $city->eat_up);

        // Сброс города для следующего теста
        $city->eat_up = $originalEatUp;

        // Тест Храма (id=3) - изменяет people_norm и people_happy
        $buildingType3 = BuildingType::get(3);
        $this->assertNotNull($buildingType3, 'BuildingType 3 should exist');
        $this->assertEquals(3, $buildingType3->id);
        $originalNorm = $city->people_norm;
        $originalHappy = $city->people_happy;
        $buildingType3->city_effect($city);
        $this->assertEquals(0, $city->people_norm, 'People norm should be 0 after temple effect');
        $this->assertEquals(1, $city->people_happy, 'People happy should be 1 after temple effect');

        // Сброс города
        $city->people_norm = $originalNorm;
        $city->people_happy = $originalHappy;

        // Тест Библиотеки (id=4) - увеличивает presearch в 1.5 раза
        $buildingType4 = BuildingType::get(4);
        $originalPresearch = $city->presearch;
        $buildingType4->city_effect($city);
        $this->assertEquals($originalPresearch * 1.5, $city->presearch);

        // Сброс города
        $city->presearch = $originalPresearch;

        // Тест Рынка (id=6) - увеличивает pmoney в 1.5 раза
        $buildingType6 = BuildingType::get(6);
        $originalPmoney = $city->pmoney;
        $buildingType6->city_effect($city);
        $this->assertEquals($originalPmoney * 1.5, $city->pmoney);

        // Сброс города
        $city->pmoney = $originalPmoney;

        // Тест Колизая (id=10) - изменяет people_dis и people_norm
        $buildingType10 = BuildingType::get(10);
        $originalDis = $city->people_dis;
        $originalNorm = $city->people_norm;
        $originalHappy = $city->people_happy;
        $buildingType10->city_effect($city);
        // Колизай уменьшает people_dis на 2, но не ниже 0
        $expectedDis = max(0, $originalDis - 2);
        $this->assertEquals($expectedDis, $city->people_dis);
        $this->assertEquals($originalNorm + 2, $city->people_norm);
        // Если people_dis стал отрицательным, излишек идет в people_happy
        if ($originalDis < 2) {
            $this->assertEquals($originalHappy + ($originalDis - 2), $city->people_happy);
        }
    }
}
