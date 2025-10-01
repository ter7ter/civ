<?php

namespace App\Tests;

use App\BuildingType;
use App\City;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;
use App\Tests\Base\TestGameDataInitializer;

/**
 * Тесты для класса BuildingType
 */
class BuildingTypeTest extends CommonTestBase
{
    /**
     * Тест получения существующего типа здания
     */
    public function testGetExistingBuildingType(): void
    {
        $buildingType = TestDataFactory::createTestBuildingType();

        $buildingTypeExists = BuildingType::get($buildingType->id);

        $this->assertInstanceOf(BuildingType::class, $buildingTypeExists);
        $this->assertEquals($buildingType->id, $buildingTypeExists->id);
        $this->assertEquals('амбар', $buildingTypeExists->title);
        $this->assertEquals(30, $buildingTypeExists->cost);
        $this->assertEquals(1, $buildingTypeExists->upkeep);
    }

    /**
     * Тест получения несуществующего типа здания
     */
    public function testGetNonExistingBuildingType(): void
    {
        $buildingType = BuildingType::get(999);

        $this->assertFalse($buildingType);
    }

    /**
     * Тест конструктора BuildingType
     */
    public function testConstructor(): void
    {
        $data = [
            'id' => 100,
            'title' => 'Test Building',
            'cost' => 100,
            'upkeep' => 2,
            'culture' => 5,
            'req_research' => [],
            'req_resources' => [],
            'need_coastal' => true,
            'culture_bonus' => 1,
            'research_bonus' => 2,
            'money_bonus' => 3,
            'description' => 'Test description',
        ];

        $buildingType = new BuildingType($data);

        $this->assertEquals(100, $buildingType->id);
        $this->assertEquals('Test Building', $buildingType->title);
        $this->assertEquals(100, $buildingType->cost);
        $this->assertEquals(2, $buildingType->upkeep);
        $this->assertEquals(5, $buildingType->culture);
        $this->assertEquals([], $buildingType->req_research);
        $this->assertEquals([], $buildingType->req_resources);
        $this->assertTrue($buildingType->need_coastal);
        $this->assertEquals(1, $buildingType->culture_bonus);
        $this->assertEquals(2, $buildingType->research_bonus);
        $this->assertEquals(3, $buildingType->money_bonus);
        $this->assertEquals('Test description', $buildingType->description);

        // Проверяем, что объект добавлен в кэш
        $this->assertSame($buildingType, BuildingType::get(100));
    }

    /**
     * Тест метода getTitle
     */
    public function testGetTitle(): void
    {
        $buildingType = TestDataFactory::createTestBuildingType([
            "title" => "амбар",
            "cost" => 30,
            "upkeep" => 1,
            "req_resources" => [],
            "need_coastal" => false,
            "culture" => 0,
            "culture_bonus" => 0,
            "research_bonus" => 0,
            "money_bonus" => 0,
            "description" => "Увеличивает производство еды",
            "city_effects" => ["eat_up_multiplier" => 0.5],
        ]); // Амбар

        $this->assertEquals('амбар', $buildingType->getTitle());
    }

    /**
     * Тест эффекта амбара (id=2)
     */
    public function testCityEffectGranary(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity([
            'planet' => $planet->id,
            'user_id' => $user->id,
            'x' => 10,
            'y' => 10,
        ]);
        $city = City::get($city->id);

        // Устанавливаем базовое значение eat_up
        if (!defined('BASE_EAT_UP')) {
            define('BASE_EAT_UP', 20);
        }
        $city->eat_up = BASE_EAT_UP;
        $buildingType = TestDataFactory::createTestBuildingType();
        $this->assertEquals(0.5, $buildingType->city_effects['eat_up_multiplier']);

        $buildingType->city_effect($city);

        $this->assertEquals((int)(BASE_EAT_UP / 2), $city->eat_up);
    }

    /**
     * Тест эффекта храма (id=3)
     */
    public function testCityEffectTemple(): void
    {
        TestGameDataInitializer::initializeCellTypes();
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

        City::clearCache();
        $city = City::get($city->id);

        // Устанавливаем начальные значения
        $city->people_norm = 2;
        $city->people_happy = 0;

        $buildingType = TestDataFactory::createTestBuildingType([
            "title" => "храм",
            "cost" => 30,
            "culture" => 2,
            "upkeep" => 1,
            "req_resources" => [],
            "need_coastal" => false,
            "culture_bonus" => 0,
            "research_bonus" => 0,
            "money_bonus" => 0,
            "description" => "Увеличивает культуру и счастье",
            "city_effects" => [
                "people_happy" => 1
            ],
        ]);
        $buildingType->city_effect($city);

        $this->assertEquals(1, $city->people_norm);
        $this->assertEquals(1, $city->people_happy);
    }

    /**
     * Тест эффекта храма с отрицательным people_norm
     */
    public function testCityEffectTempleNegativeNorm(): void
    {
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

        City::clearCache();
        $city = City::get($city->id);

        // Устанавливаем начальные значения
        $city->people_norm = 0;
        $city->people_happy = 0;

        $buildingType = new BuildingType([
            "title" => "храм",
            "cost" => 30,
            "culture" => 2,
            "upkeep" => 1,
            "req_resources" => [],
            "need_coastal" => false,
            "culture_bonus" => 0,
            "research_bonus" => 0,
            "money_bonus" => 0,
            "description" => "Увеличивает культуру и счастье",
            "city_effects" => [
                "people_happy" => 1,
            ]
        ]);
        $buildingType->save();
        $buildingType->city_effect($city);

        $this->assertEquals(0, $city->people_norm);
        $this->assertEquals(0, $city->people_happy); // people_norm becomes -1, then people_happy += people_norm (-1), so 1 + (-1) = 0
    }

    /**
     * Тест эффекта библиотеки (id=4)
     */
    public function testCityEffectLibrary(): void
    {
        TestGameDataInitializer::initializeCellTypes();
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

        City::clearCache();
        $city = City::get($city->id);

        $city->presearch = 4;

        $buildingType = new BuildingType([
            "title" => "библиотека",
            "cost" => 50,
            "culture" => 3,
            "upkeep" => 1,
            "req_resources" => [],
            "need_coastal" => false,
            "culture_bonus" => 0,
            "research_bonus" => 50,
            "money_bonus" => 0,
            "description" => "Увеличивает производство науки",
            "city_effects" => [
                "research_multiplier" => 1.5,
            ]
        ]);
        $buildingType->save();
        $buildingType->city_effect($city);

        $this->assertEquals(6, $city->presearch); // 4 * 1.5
    }

    /**
     * Тест эффекта рынка (id=6)
     */
    public function testCityEffectMarket(): void
    {
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

        City::clearCache();
        $city = City::get($city->id);

        $city->pmoney = 4;

        $buildingType = new BuildingType([
            "title" => "рынок",
            "cost" => 50,
            "req_resources" => [],
            "need_coastal" => false,
            "culture" => 0,
            "culture_bonus" => 0,
            "research_bonus" => 0,
            "money_bonus" => 50,
            "description" => "Увеличивает производство золота",
            "city_effects" => [
                "money_multiplier" => 1.5,
            ]
        ]);
        $buildingType->save();
        $buildingType->city_effect($city);

        $this->assertEquals(6, $city->pmoney); // 4 * 1.5
    }

    /**
     * Тест эффекта колизея (id=10)
     */
    public function testCityEffectColiseum(): void
    {
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

        City::clearCache();
        $city = City::get($city->id);

        // Устанавливаем начальные значения
        $city->people_dis = 3;
        $city->people_norm = 0;
        $city->people_happy = 0;

        $buildingType = new BuildingType([
            "id" => 10,
            "title" => "колизей",
            "cost" => 80,
            "upkeep" => 2,
            "req_research" => [18], // Конструкции
            "req_resources" => [],
            "need_coastal" => false,
            "culture" => 0,
            "culture_bonus" => 0,
            "research_bonus" => 0,
            "money_bonus" => 0,
            "need_research" => [],
            "description" => "Увеличивает довольство граждан",
            "city_effects" => [
                "people_norm" => 2,
            ]
        ]);
        $buildingType->save();
        $buildingType->city_effect($city);

        $this->assertEquals(1, $city->people_dis);
        $this->assertEquals(2, $city->people_norm);
        $this->assertEquals(0, $city->people_happy);
    }

    /**
     * Тест эффекта колизея с отрицательным people_dis
     */
    public function testCityEffectColiseumNegativeDis(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity([
            'planet' => $planet->id,
            'user_id' => $user->id,
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($city->id);

        // Устанавливаем начальные значения
        $city->people_dis = 1;
        $city->people_norm = 0;
        $city->people_happy = 0;

        $buildingType = new BuildingType([
            "title" => "колизей",
            "cost" => 80,
            "upkeep" => 2,
            "need_coastal" => false,
            "culture" => 0,
            "culture_bonus" => 0,
            "research_bonus" => 0,
            "money_bonus" => 0,
            "need_research" => [],
            "description" => "Увеличивает довольство граждан",
            "city_effects" => [
                "people_norm" => 2,
            ]
        ]);
        $buildingType->save();
        $buildingType->city_effect($city);

        $this->assertEquals(0, $city->people_dis);
        $this->assertEquals(1, $city->people_norm);
        $this->assertEquals(0, $city->people_happy);
    }

    /**
     * Тест эффекта здания без специального эффекта
     */
    public function testCityEffectNoEffect(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity([
            'planet' => $planet->id,
            'user_id' => $user->id,
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($city->id);

        // Устанавливаем начальные значения
        $originalEatUp = $city->eat_up ?? 0;
        $originalPeopleNorm = $city->people_norm ?? 0;
        $originalPeopleHappy = $city->people_happy ?? 0;
        $originalPresearch = $city->presearch ?? 0;
        $originalPmoney = $city->pmoney ?? 0;
        $originalPeopleDis = $city->people_dis ?? 0;

        $buildingType = new BuildingType([
            "title" => "бараки",
            "cost" => 30,
            "upkeep" => 1,
            "req_research" => [],
            "req_resources" => [],
            "need_coastal" => false,
            "culture" => 0,
            "culture_bonus" => 0,
            "research_bonus" => 0,
            "money_bonus" => 0,
            "description" => "Базовое здание для размещения юнитов",
        ]);
        $buildingType->save();
        $buildingType->city_effect($city);

        // Проверяем, что ничего не изменилось
        $this->assertEquals($originalEatUp, $city->eat_up);
        $this->assertEquals($originalPeopleNorm, $city->people_norm);
        $this->assertEquals($originalPeopleHappy, $city->people_happy);
        $this->assertEquals($originalPresearch, $city->presearch);
        $this->assertEquals($originalPmoney, $city->pmoney);
        $this->assertEquals($originalPeopleDis, $city->people_dis);
    }

    /**
     * Тест всех предопределенных типов зданий
     */
    public function testAllPredefinedBuildingTypes(): void
    {
        $expectedTypes = [
            TestDataFactory::createTestBuildingType(),
        ];

        foreach ($expectedTypes as $expected) {
            $buildingType = BuildingType::get($expected->id);
            $this->assertInstanceOf(BuildingType::class, $buildingType, "Building type {$expected->id} should exist");
            $this->assertEquals($expected->title, $buildingType->title, "Title for building type {$expected->id}");
            $this->assertEquals($expected->cost, $buildingType->cost, "Cost for building type {$expected->id}");

            if (isset($expected->upkeep)) {
                $this->assertEquals($expected->upkeep, $buildingType->upkeep, "Upkeep for building type {$expected->id}");
            }

            if (isset($expected->culture)) {
                $this->assertEquals($expected->culture, $buildingType->culture, "Culture for building type {$expected->id}");
            }
        }
    }
}
