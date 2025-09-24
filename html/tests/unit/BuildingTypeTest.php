<?php

/**
 * Тесты для класса BuildingType
 */
class BuildingTypeTest extends TestBase
{
    /**
     * Тест получения существующего типа здания
     */
    public function testGetExistingBuildingType(): void
    {
        $this->initializeGameTypes();

        $buildingType = BuildingType::get(1);

        $this->assertInstanceOf(BuildingType::class, $buildingType);
        $this->assertEquals(1, $buildingType->id);
        $this->assertEquals('бараки', $buildingType->title);
        $this->assertEquals(30, $buildingType->cost);
        $this->assertEquals(1, $buildingType->upkeep);
    }

    /**
     * Тест получения несуществующего типа здания
     */
    public function testGetNonExistingBuildingType(): void
    {
        $this->initializeGameTypes();

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
            'need_research' => [],
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
        $this->assertEquals([], $buildingType->need_research);
        $this->assertEquals(1, $buildingType->culture_bonus);
        $this->assertEquals(2, $buildingType->research_bonus);
        $this->assertEquals(3, $buildingType->money_bonus);
        $this->assertEquals('Test description', $buildingType->description);

        // Проверяем, что объект добавлен в кэш
        $this->assertSame($buildingType, BuildingType::get(100));
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $this->initializeGameTypes();

        $buildingType = BuildingType::get(2); // Амбар

        $this->assertEquals('амбар', $buildingType->get_title());
    }

    /**
     * Тест эффекта амбара (id=2)
     */
    public function testCityEffectGranary(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity([
            'planet' => $planetId,
            'user_id' => $userData['id'],
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($cityData['id']);

        // Устанавливаем базовое значение eat_up
        if (!defined('BASE_EAT_UP')) {
            define('BASE_EAT_UP', 4);
        }
        $city->eat_up = BASE_EAT_UP;

        $buildingType = BuildingType::get(2); // Амбар
        $buildingType->city_effect($city);

        $this->assertEquals((int)(BASE_EAT_UP / 2), $city->eat_up);
    }

    /**
     * Тест эффекта храма (id=3)
     */
    public function testCityEffectTemple(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity([
            'planet' => $planetId,
            'user_id' => $userData['id'],
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($cityData['id']);

        // Устанавливаем начальные значения
        $city->people_norm = 2;
        $city->people_happy = 0;

        $buildingType = BuildingType::get(3); // Храм
        $buildingType->city_effect($city);

        $this->assertEquals(1, $city->people_norm);
        $this->assertEquals(1, $city->people_happy);
    }

    /**
     * Тест эффекта храма с отрицательным people_norm
     */
    public function testCityEffectTempleNegativeNorm(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity([
            'planet' => $planetId,
            'user_id' => $userData['id'],
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($cityData['id']);

        // Устанавливаем начальные значения
        $city->people_norm = 0;
        $city->people_happy = 0;

        $buildingType = BuildingType::get(3); // Храм
        $buildingType->city_effect($city);

        $this->assertEquals(0, $city->people_norm);
        $this->assertEquals(0, $city->people_happy); // people_norm becomes -1, then people_happy += people_norm (-1), so 1 + (-1) = 0
    }

    /**
     * Тест эффекта библиотеки (id=4)
     */
    public function testCityEffectLibrary(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity([
            'planet' => $planetId,
            'user_id' => $userData['id'],
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($cityData['id']);

        $city->presearch = 4;

        $buildingType = BuildingType::get(4); // Библиотека
        $buildingType->city_effect($city);

        $this->assertEquals(6, $city->presearch); // 4 * 1.5
    }

    /**
     * Тест эффекта рынка (id=6)
     */
    public function testCityEffectMarket(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity([
            'planet' => $planetId,
            'user_id' => $userData['id'],
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($cityData['id']);

        $city->pmoney = 4;

        $buildingType = BuildingType::get(6); // Рынок
        $buildingType->city_effect($city);

        $this->assertEquals(6, $city->pmoney); // 4 * 1.5
    }

    /**
     * Тест эффекта колизея (id=10)
     */
    public function testCityEffectColiseum(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity([
            'planet' => $planetId,
            'user_id' => $userData['id'],
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($cityData['id']);

        // Устанавливаем начальные значения
        $city->people_dis = 3;
        $city->people_norm = 0;
        $city->people_happy = 0;

        $buildingType = BuildingType::get(10); // Колизей
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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity([
            'planet' => $planetId,
            'user_id' => $userData['id'],
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($cityData['id']);

        // Устанавливаем начальные значения
        $city->people_dis = 1;
        $city->people_norm = 0;
        $city->people_happy = 0;

        $buildingType = BuildingType::get(10); // Колизей
        $buildingType->city_effect($city);

        $this->assertEquals(0, $city->people_dis);
        $this->assertEquals(2, $city->people_norm);
        $this->assertEquals(-1, $city->people_happy); // people_dis becomes -1, then people_happy += people_dis (-1), so 0 + (-1) = -1
    }

    /**
     * Тест эффекта здания без специального эффекта
     */
    public function testCityEffectNoEffect(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity([
            'planet' => $planetId,
            'user_id' => $userData['id'],
            'x' => 10,
            'y' => 10,
        ]);

        City::clearCache();
        $city = City::get($cityData['id']);

        // Устанавливаем начальные значения
        $originalEatUp = $city->eat_up ?? 0;
        $originalPeopleNorm = $city->people_norm ?? 0;
        $originalPeopleHappy = $city->people_happy ?? 0;
        $originalPresearch = $city->presearch ?? 0;
        $originalPmoney = $city->pmoney ?? 0;
        $originalPeopleDis = $city->people_dis ?? 0;

        $buildingType = BuildingType::get(1); // Бараки - нет специального эффекта
        $buildingType->city_effect($city);

        // Проверяем, что ничего не изменилось
        $this->assertEquals($originalEatUp, $city->eat_up ?? 0);
        $this->assertEquals($originalPeopleNorm, $city->people_norm ?? 0);
        $this->assertEquals($originalPeopleHappy, $city->people_happy ?? 0);
        $this->assertEquals($originalPresearch, $city->presearch ?? 0);
        $this->assertEquals($originalPmoney, $city->pmoney ?? 0);
        $this->assertEquals($originalPeopleDis, $city->people_dis ?? 0);
    }

    /**
     * Тест всех предопределенных типов зданий
     */
    public function testAllPredefinedBuildingTypes(): void
    {
        $this->initializeGameTypes();

        $expectedTypes = [
            1 => ['title' => 'бараки', 'cost' => 30, 'upkeep' => 1],
            2 => ['title' => 'амбар', 'cost' => 30, 'upkeep' => 1],
            3 => ['title' => 'храм', 'cost' => 30, 'culture' => 2, 'upkeep' => 1],
            4 => ['title' => 'библиотека', 'cost' => 50, 'culture' => 3, 'upkeep' => 1],
            5 => ['title' => 'стены', 'cost' => 30],
            6 => ['title' => 'рынок', 'cost' => 50],
            7 => ['title' => 'суд', 'cost' => 60],
            8 => ['title' => 'гавань', 'cost' => 60, 'upkeep' => 1],
            9 => ['title' => 'акведук', 'cost' => 80, 'upkeep' => 1],
            10 => ['title' => 'колизей', 'cost' => 80, 'upkeep' => 2],
        ];

        foreach ($expectedTypes as $id => $expected) {
            $buildingType = BuildingType::get($id);
            $this->assertInstanceOf(BuildingType::class, $buildingType, "Building type {$id} should exist");
            $this->assertEquals($expected['title'], $buildingType->title, "Title for building type {$id}");
            $this->assertEquals($expected['cost'], $buildingType->cost, "Cost for building type {$id}");

            if (isset($expected['upkeep'])) {
                $this->assertEquals($expected['upkeep'], $buildingType->upkeep, "Upkeep for building type {$id}");
            }

            if (isset($expected['culture'])) {
                $this->assertEquals($expected['culture'], $buildingType->culture, "Culture for building type {$id}");
            }
        }
    }
}
