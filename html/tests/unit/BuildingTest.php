<?php

namespace App\Tests;

use App\Building;
use App\BuildingType;
use App\City;
use App\MyDB;
use App\Tests\Factory\TestDataFactory;
use App\Tests\base\CommonTestBase;
use App\Tests\Base\TestGameDataInitializer;

/**
 * Тесты для класса Building
 */
class BuildingTest extends CommonTestBase
{
    /**
     * Тест получения здания по ID
     */
    public function testGet(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        $buildingType = TestDataFactory::createTestBuildingType(['title' => 'бараки']);

        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity(['user_id' => $user->id, 'planet' => $planet->id]);

        // Создаем здание через объект
        $building = new Building([
            'city_id' => $city->id,
            'type' => $buildingType->id, // Бараки
        ]);
        $building->save();


        $this->assertInstanceOf(Building::class, $building);
        $this->assertNotNull($building->id);
        $this->assertGreaterThan(0, (int)$building->id);
        $this->assertInstanceOf(City::class, $building->city);
        $this->assertGreaterThan(0, (int)$building->city->id);
        $this->assertInstanceOf(BuildingType::class, $building->type);
        $this->assertEquals($buildingType->id, $building->type->id);
    }

    /**
     * Тест конструктора Building
     */
    public function testConstructor(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity(['user_id' => $user->id, 'planet' => $planet->id]);
        // Ensure BuildingType with id=1 exists ("бараки")
        TestDataFactory::createTestBuildingType(['id' => 1, 'title' => 'бараки']);

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
        $buildingType = TestDataFactory::createTestBuildingType(['title' => 'храм']);
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity(['user_id' => $user->id, 'planet' => $planet->id]);

        $data = [
            'city_id' => $city->id,
            'type' => $buildingType->id, // Храм
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
        $testData = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        $buildingType = TestDataFactory::createTestBuildingType(['title' => 'бараки']);

        $data = [
            'city_id' => $city->id,
            'type' => $buildingType->id, // Бараки
        ];

        $building = new Building($data);

        $this->assertEquals($buildingType->title, $building->get_title());
    }

    /**
     * Тест сохранения нового здания
     */
    public function testSaveNew(): void
    {
        $testData = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        $buildingType = TestDataFactory::createTestBuildingType(['title' => 'бараки']);
        $data = [
            'city_id' => $city->id,
            'type' => $buildingType->id, // Бараки
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
        $this->assertEquals($buildingType->id, $savedData['type']);
    }

    /**
     * Тест обновления существующего здания
     */
    public function testSaveUpdate(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        $testData = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        // Создаем здание через объект
        $building = new Building([
            'city_id' => $city->id,
            'type' => 1, // Бараки
        ]);
        $building->save();

        $buildingTypeTemple = TestDataFactory::createTestBuildingType([
            'title' => 'храм',
            'cost' => 30,
            'culture' => 2,
            'upkeep' => 1,
            'city_effects' => ['people_happy' => 1]
        ]);

        $building->type = $buildingTypeTemple;
        $building->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM building WHERE id = :id",
            ["id" => $building->id],
            "row"
        );
        $this->assertEquals($buildingTypeTemple->id, $updatedData['type']);
    }

    /**
     * Тест кэширования зданий
     */
    public function testCaching(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        $testData = TestDataFactory::createTestGameWithPlanetUserAndCity();
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
        TestGameDataInitializer::initializeCellTypes();
        $buildingType1 = TestDataFactory::createTestBuildingType(['title' => 'храм']);
        $buildingType2 = TestDataFactory::createTestBuildingType(['title' => 'рынок']);
        $buildingType3 = TestDataFactory::createTestBuildingType(['title' => 'бараки']);
        $testData = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $city = $testData['city'];

        $buildingTypes = [
            $buildingType1->id,
            $buildingType2->id,
            $buildingType3->id
        ]; // Бараки, Храм, Рынок

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
        TestGameDataInitializer::initializeAll();
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity(['user_id' => $user['id'], 'planet_id' => $planet->id]);

        // Тест Амбара (id=2) - уменьшает eat_up вдвое
        TestDataFactory::createTestBuildingType([
            'title' => 'амбар',
            'cost' => 30,
            'upkeep' => 1,
            'city_effects' => ['eat_up_multiplier' => 0.5]
        ]);
        $originalEatUp = $city->eat_up;
        $buildingType2->city_effect($city);
        $this->assertEquals((int) (BASE_EAT_UP / 2), $city->eat_up);

        // Сброс города для следующего теста
        $city->eat_up = $originalEatUp;

        // Тест Храма (id=3) - изменяет people_norm и people_happy
        $buildingType3 = TestDataFactory::createTestBuildingType([
            'title' => 'храм',
            'cost' => 30,
            'culture' => 2,
            'upkeep' => 1,
            'city_effects' => ['people_happy' => 1]
        ]);
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
        $buildingType4 = TestDataFactory::createTestBuildingType([
            'title' => 'библиотека',
            'cost' => 50,
            'culture' => 3,
            'upkeep' => 1,
            'research_bonus' => 50,
            'city_effects' => ['research_multiplier' => 1.5]
        ]);
        $originalPresearch = $city->presearch;
        $buildingType4->city_effect($city);
        $this->assertEquals($originalPresearch * 1.5, $city->presearch);

        // Сброс города
        $city->presearch = $originalPresearch;

        // Тест Рынка (id=6) - увеличивает pmoney в 1.5 раза
        $buildingType6 = TestDataFactory::createTestBuildingType([
            'title' => 'рынок',
            'cost' => 50,
            'upkeep' => 1,
            'money_bonus' => 50,
            'city_effects' => ['money_multiplier' => 1.5]
        ]);
        $originalPmoney = $city->pmoney;
        $buildingType6->city_effect($city);
        $this->assertEquals($originalPmoney * 1.5, $city->pmoney);

        // Сброс города
        $city->pmoney = $originalPmoney;

        // Тест Колизая (id=10) - изменяет people_dis и people_norm
        $buildingType10 = TestDataFactory::createTestBuildingType([
            'title' => 'колизей',
            'cost' => 80,
            'upkeep' => 2,
            'req_research' => [], // Further adjust if needed
            'city_effects' => ['people_norm' => 2]
        ]);
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
