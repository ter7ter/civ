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
     * Тест метода getTitle
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

        $building = TestDataFactory::createTestBuilding($data);

        $this->assertEquals('бараки', $building->getTitle());
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

        $buildingType = TestDataFactory::createTestBuildingType(['title' => 'бараки']);
        // Создаем здание через объект
        $building = new Building([
            'city_id' => $city->id,
            'type' => $buildingType->id,
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

        $buildingType = TestDataFactory::createTestBuildingType(['title' => 'бараки']);
        // Создаем здание через объект
        $building = new Building([
            'city_id' => $city->id,
            'type' => $buildingType->id,
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
            $this->assertIsString($building->getTitle());
            $this->assertNotEmpty($building->getTitle());
        }
    }

    /**
     * Тест применения эффектов зданий разных типов при расчете города
     */
    public function testCityEffect(): void
    {
        if (!defined('BASE_EAT_UP')) {
            define('BASE_EAT_UP', 20);
        }

        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity([
            'planet' => $planetId,
            'user_id' => $user->id,
            'x' => 10,
            'y' => 10,
        ]);
        $city = City::get($city->id);

        // Устанавливаем начальные значения
        $city->population = 2;
        $city->eat_up = BASE_EAT_UP;
        $city->people_norm = 2;
        $city->people_happy = 0;
        $city->people_dis = 0;
        $city->presearch = 4;
        $city->pmoney = 4;
        $city->pwork = 10;

        // Создаем здания разных типов
        $granaryType = TestDataFactory::createTestBuildingType([
            'title' => 'амбар',
            'cost' => 30,
            'upkeep' => 1,
            'city_effects' => ['eat_up_multiplier' => 0.5]
        ]);
        $granary = TestDataFactory::createTestBuilding([
            'city_id' => $city->id,
            'type' => $granaryType->id,
        ]);

        $templeType = TestDataFactory::createTestBuildingType([
            'title' => 'храм',
            'cost' => 30,
            'culture' => 2,
            'upkeep' => 1,
            'city_effects' => ['people_happy' => 1]
        ]);
        $temple = TestDataFactory::createTestBuilding([
            'city_id' => $city->id,
            'type' => $templeType->id,
        ]);

        $libraryType = TestDataFactory::createTestBuildingType([
            'title' => 'библиотека',
            'cost' => 50,
            'culture' => 3,
            'upkeep' => 1,
            'research_bonus' => 50,
            'city_effects' => ['research_multiplier' => 1.5]
        ]);
        $library = TestDataFactory::createTestBuilding([
            'city_id' => $city->id,
            'type' => $libraryType->id,
        ]);

        $marketType = TestDataFactory::createTestBuildingType([
            'title' => 'рынок',
            'cost' => 50,
            'upkeep' => 1,
            'money_bonus' => 50,
            'city_effects' => ['money_multiplier' => 1.5]
        ]);
        $market = TestDataFactory::createTestBuilding([
            'city_id' => $city->id,
            'type' => $marketType->id,
        ]);

        $coliseumType = TestDataFactory::createTestBuildingType([
            'title' => 'колизей',
            'cost' => 80,
            'upkeep' => 2,
            'req_research' => [],
            'city_effects' => ['people_norm' => 2]
        ]);
        $coliseum = TestDataFactory::createTestBuilding([
            'city_id' => $city->id,
            'type' => $coliseumType->id,
        ]);

        // Проверяем, что здания сохранены
        $this->assertEquals(5, count($city->buildings));

        // Расчет зданий применяет эффекты
        $city->calculate_buildings();

        // Проверяем эффекты
        $this->assertEquals((int)(BASE_EAT_UP / 2), $city->eat_up, 'Granary should halve eat_up');
        $this->assertEquals(1, $city->people_norm, 'Buildings should adjust people_norm');
        $this->assertEquals(1, $city->people_happy, 'Temple should add happy people');
        $this->assertEquals(6, $city->presearch, 'Library should multiply research');
        $this->assertEquals(6, $city->pmoney, 'Market should multiply money');
    }
}
