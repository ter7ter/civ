<?php

require_once __DIR__ . "/../bootstrap.php";

/**
 * @covers City
 */
class CityTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];

        // Подключаем классы проекта
        require_once PROJECT_ROOT . "/includes.php";
    }

    /**
     * Быстро создает тестовые данные для города
     * @param array $cityOverrides
     * @return array [gameData, userData, planetId, cityData]
     */
    private function setUpTestCity(array $cityOverrides = [])
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(array_merge([
            "planet" => $planetId,
            "user_id" => $userData["id"],
            "x" => 10,
            "y" => 10,
        ], $cityOverrides));
        return [$gameData, $userData, $planetId, $cityData];
    }

    /**
     * @covers City::__construct
     */
    public function testConstructor()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        $data = [
            'user_id' => $userData["id"],
            'planet' => $planetId,
            'x' => 10,
            'y' => 10,
            'title' => 'Test City',
        ];
        $city = new City($data);
        $this->assertEquals('Test City', $city->title);
        $this->assertEquals(10, $city->x);
        $this->assertEquals(10, $city->y);
        $this->assertEquals($userData["id"], $city->user->id);
    }

    /**
     * @covers City::by_coords
     */
    public function testByCoords()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        $city = City::by_coords(10, 10, $planetId);
        $this->assertInstanceOf(City::class, $city);
        $this->assertEquals($cityData["id"], $city->id);
    }

    /**
     * @covers City::get_title
     */
    public function testGetTitle()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        $this->assertEquals('Test City', $city->get_title());
    }

    /**
     * @covers City::get_culture_cells
     */
    public function testGetCultureCells()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity([
            'x' => 11,
            'y' => 11
        ]);
        $this->createTestMapCells(10, 10, 3, 3, $planetId); // 3x3 карта
        City::clearCache();
        $city = City::get($cityData["id"]);
        $cells = $city->get_culture_cells();
        $this->assertIsArray($cells);
        $this->assertCount(8, $cells); // 8 клеток вокруг города
    }

    /**
     * @covers City::get_possible_units
     */
    public function testGetPossibleUnits()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        $units = $city->get_possible_units();
        $this->assertIsArray($units);
        $this->assertGreaterThan(0, count($units));
    }

    /**
     * @covers City::calculate_eat
     */
    public function testCalculateEat()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->peat = 5;
        $city->population = 2;
        $city->eat = 0;
        $city->calculate();
        $this->assertEquals(1, $city->eat); // 0 + 5 - (2 * 2) = 1
    }

    /**
     * @covers City::calculate_work
     */
    public function testCalculateWork()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->pwork = 5;
        $city->calculate();
        $this->assertEquals(1, $city->pwork); // Должно сбрасываться до 1
    }

    /**
     * @covers City::calculate_money
     */
    public function testCalculateMoney()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->pmoney = 10;
        $city->calculate();
        $this->assertEquals(1, $city->pmoney); // Должно сбрасываться до 1
    }

    /**
     * @covers City::calculate_research
     */
    public function testCalculateResearch()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->presearch = 5;
        $city->calculate();
        $this->assertEquals(0, $city->presearch); // Должно сбрасываться до 0
    }

    /**
     * @covers City::calculate_culture
     */
    public function testCalculateCulture()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->culture = 0;
        $city->culture_level = 0;
        $city->calculate();
        $this->assertEquals(0, $city->culture);
        $this->assertEquals(0, $city->culture_level);
    }

    /**
     * @covers City::add_people
     */
    public function testAddPeople()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity(["population" => 1]);
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->add_people();
        $this->assertEquals(2, $city->population);
    }

    /**
     * @covers City::remove_people
     */
    public function testRemovePeople()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity(["population" => 2]);
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->remove_people();
        $this->assertEquals(1, $city->population);
    }

    /**
     * @covers City::save
     */
    public function testSave()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity(["title" => "Original Title"]);
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->title = "New Title";
        $city->save();
        City::clearCache();
        $updatedCity = City::get($cityData["id"]);
        $this->assertEquals("New Title", $updatedCity->title);
    }

    /**
     * @covers City::create_unit
     */
    public function testCreateUnit()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        TestGameDataInitializer::initializeUnitTypes();
        $unitType = UnitType::get(1); // Settler
        $unit = $city->create_unit($unitType);
        $this->assertInstanceOf(Unit::class, $unit);
        $this->assertEquals($unitType->id, $unit->type->id);
        $this->assertEquals($city->x, $unit->x);
        $this->assertEquals($city->y, $unit->y);
        $this->assertEquals($city->user->id, $unit->user->id);
    }

    /**
     * @covers City::create_building
     */
    public function testCreateBuilding()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        TestGameDataInitializer::initializeBuildingTypes();
        $buildingType = BuildingType::get(1); // Granary
        $building = $city->create_building($buildingType);
        $this->assertInstanceOf(Building::class, $building);
        $this->assertEquals($buildingType->id, $building->type->id);
        $this->assertEquals($city->id, $building->city->id);
    }

    /**
     * @covers City::calculate_buildings
     */
    public function testCalculateBuildings()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        TestGameDataInitializer::initializeBuildingTypes();
        $buildingType = BuildingType::get(1); // Granary
        $city->create_building($buildingType);
        $originalPmoney = $city->pmoney;
        $city->calculate_buildings();
        $this->assertEquals($originalPmoney - $buildingType->upkeep, $city->pmoney);
    }

    /**
     * @covers City::check_mood
     */
    public function testCheckMood()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->people_dis = 10;
        $city->people_happy = 0;
        $city->pwork = 5;
        $city->pmoney = 5;
        $city->check_mood();
        $this->assertEquals(0, $city->pwork);
        $this->assertEquals(0, $city->pmoney);
    }

    /**
     * @covers City::select_next_production
     */
    public function testSelectNextProduction()
    {
        [$gameData, $userData, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData["id"]);
        TestGameDataInitializer::initializeUnitTypes();
        $unitType = UnitType::get(1); // Settler
        $city->production_type = "buil";
        $city->production = 1; // Некоторый building ID
        $city->select_next_production();
        $this->assertEquals("unit", $city->production_type);
        $this->assertEquals($unitType->id, $city->production);
    }
}
