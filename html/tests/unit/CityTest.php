<?php

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

    public function testConstructor()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        
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
    }

    public function testByCoords()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);

        $city = City::by_coords(10, 10, $planetId);
        $this->assertInstanceOf(City::class, $city);
        $this->assertEquals($cityData["id"], $city->id);
    }

    public function testGetTitle()
    {


        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $this->assertEquals('Test City', $city->get_title());
    }

    public function testGetCultureCells()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 3, 3, $planetId); // Create a 3x3 map
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 11, "y" => 11]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $cells = $city->get_culture_cells();
        $this->assertIsArray($cells);
        $this->assertCount(8, $cells); // 8 cells around the city
    }

    public function testGetPossibleUnits()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $units = $city->get_possible_units();
        $this->assertIsArray($units);
        $this->assertGreaterThan(0, count($units));
    }

    public function testCalculateEat()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $city->peat = 5; // Set some value
        $city->population = 2;
        $city->eat = 0;
        $city->calculate(); // This will call calculate_eat implicitly

        $this->assertEquals(1, $city->eat); // 0 + 5 - (2 * 2) = 1
    }

    public function testCalculateWork()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $city->pwork = 5; // Set some value
        $city->calculate(); // This will call calculate_work implicitly

        $this->assertEquals(1, $city->pwork); // Should be reset to 1 by calculate_people
    }

    public function testCalculateMoney()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $city->pmoney = 10; // Set some value
        $city->calculate(); // This will call calculate_money implicitly

        $this->assertEquals(1, $city->pmoney); // Should be reset to 1 by calculate_people
    }

    public function testCalculateResearch()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $city->presearch = 5; // Set some value
        $city->calculate(); // This will call calculate_people implicitly, which resets presearch to 0

        $this->assertEquals(0, $city->presearch); // Should be reset to 0 by calculate_people
    }

    public function testCalculateCulture()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $city->culture = 0;
        $city->culture_level = 0;
        $city->calculate(); // This will call calculate_culture implicitly

        $this->assertEquals(0, $city->culture); // Should remain 0 if no buildings affect it
        $this->assertEquals(0, $city->culture_level); // Should remain 0 if no buildings affect it
    }

    public function testAddPeople()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10, "population" => 1]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $city->add_people();
        $this->assertEquals(2, $city->population);
    }

    public function testRemovePeople()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10, "population" => 2]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        $city->remove_people();
        $this->assertEquals(1, $city->population);
    }

    public function testSave()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10, "title" => "Original Title"]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);
        $city->title = "New Title";
        $city->save();

        City::clearCache();
        $updatedCity = City::get($cityData["id"]);
        $this->assertEquals("New Title", $updatedCity->title);
    }

    public function testCreateUnit()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
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

    public function testCreateBuilding()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        TestGameDataInitializer::initializeBuildingTypes();
        $buildingType = BuildingType::get(1); // Granary
        $building = $city->create_building($buildingType);

        $this->assertInstanceOf(Building::class, $building);
        $this->assertEquals($buildingType->id, $building->type->id);
        $this->assertEquals($city->id, $building->city->id);
    }

    public function testCalculateBuildings()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        TestGameDataInitializer::initializeBuildingTypes();
        $buildingType = BuildingType::get(1); // Granary
        $city->create_building($buildingType);

        $originalPmoney = $city->pmoney;
        $city->calculate_buildings();

        $this->assertEquals($originalPmoney - $buildingType->upkeep, $city->pmoney);
    }

    public function testCheckMood()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
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

    public function testSelectNextProduction()
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $cityData = $this->createTestCity(["planet" => $planetId, "user_id" => $userData["id"], "x" => 10, "y" => 10]);
        
        City::clearCache();
        $city = City::get($cityData["id"]);

        TestGameDataInitializer::initializeUnitTypes();
        $unitType = UnitType::get(1); // Settler
        $city->production_type = "buil";
        $city->production = 1; // Some building ID

        $city->select_next_production();

        $this->assertEquals("unit", $city->production_type);
        $this->assertEquals($unitType->id, $city->production);
    }
}