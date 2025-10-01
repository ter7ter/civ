<?php

namespace App\Tests;


use App\Building;
use App\City;
use App\MyDB;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;
use App\Tests\Base\TestGameDataInitializer;
use App\Unit;

/**
 * @covers City
 */
class CityTest extends CommonTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];

        // Подключаем классы проекта
        require_once PROJECT_ROOT . "/includes.php";

        // Инициализируем типы клеток для тестов
        TestGameDataInitializer::initializeCellTypes();
    }

    /**
     * Быстро создает тестовые данные для города
     * @param array $cityOverrides
     * @return array [gameData, userData, planetId, cityData]
     */
    private function setUpTestCity(array $cityOverrides = [])
    {
        $result = TestDataFactory::createTestGameWithPlanetUserAndCity([], [], [], array_merge([
            "x" => 10,
            "y" => 10,
        ], $cityOverrides));
        $this->createTestMapCells(10, 10, 1, 1, $result['planet']->id);
        return [$result['game'], $result['user'], $result['planet'], $result['city']];
    }

    /**
     * @covers City::__construct
     */
    public function testConstructor()
    {
        [$game, $user, $planet, $cityData] = $this->setUpTestCity();
        $data = [
            'user_id' => $user->id,
            'planet' => $planet->id,
            'x' => 10,
            'y' => 10,
            'title' => 'Test City',
        ];
        $city = new City($data);
        $this->assertEquals('Test City', $city->title);
        $this->assertEquals(10, $city->x);
        $this->assertEquals(10, $city->y);
        $this->assertEquals($user->id, $city->user->id);
    }

    /**
     * @covers City::by_coords
     */
    public function testByCoords()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        $city = City::by_coords(10, 10, $planetId->id);
        $this->assertInstanceOf(City::class, $city);
        $this->assertEquals($cityData->id, $city->id);
    }

    /**
     * @covers City::getTitle
     */
    public function testGetTitle()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
        $this->assertEquals('Test City', $city->getTitle());
    }

    /**
     * @covers City::get_culture_cells
     */
    public function testGetCultureCells()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity([
            'x' => 11,
            'y' => 11
        ]);
        $this->createTestMapCells(10, 10, 3, 3, $planetId->id); // 3x3 карта
        City::clearCache();
        $city = City::get($cityData->id);
        $cells = $city->get_culture_cells();
        $this->assertIsArray($cells);
        $this->assertCount(8, $cells); // 8 клеток вокруг города
    }

    /**
     * @covers City::get_possible_units
     */
    public function testGetPossibleUnits()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();

        TestDataFactory::createTestUnitType();

        $city = City::get($cityData->id);
        $units = $city->get_possible_units();
        $this->assertIsArray($units);
        $this->assertGreaterThan(0, count($units));
    }

    /**
     * @covers City::calculate_eat
     */
    public function testCalculateEat()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
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
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
        $city->pwork = 5;
        $city->calculate();
        $this->assertEquals(1, $city->pwork); // Должно сбрасываться до 1
    }

    /**
     * @covers City::calculate_money
     */
    public function testCalculateMoney()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
        $city->pmoney = 10;
        $city->calculate();
        $this->assertEquals(1, $city->pmoney); // Должно сбрасываться до 1
    }

    /**
     * @covers City::calculate_research
     */
    public function testCalculateResearch()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
        $city->presearch = 5;
        $city->calculate();
        $this->assertEquals(0, $city->presearch); // Должно сбрасываться до 0
    }

    /**
     * @covers City::calculate_culture
     */
    public function testCalculateCulture()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
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
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity(["population" => 1]);
        City::clearCache();
        $city = City::get($cityData->id);
        $city->add_people();
        $this->assertEquals(2, $city->population);
    }

    /**
     * @covers City::remove_people
     */
    public function testRemovePeople()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity(["population" => 2]);
        City::clearCache();
        $city = City::get($cityData->id);
        $city->remove_people();
        $this->assertEquals(1, $city->population);
    }

    /**
     * @covers City::save
     */
    public function testSave()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity(["title" => "Original Title"]);
        City::clearCache();
        $city = City::get($cityData->id);
        $city->title = "New Title";
        $city->save();
        City::clearCache();
        $updatedCity = City::get($cityData->id);
        $this->assertEquals("New Title", $updatedCity->title);
    }

    /**
     * @covers City::create_unit
     */
    public function testCreateUnit()
    {
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
        $unitType = TestDataFactory::createTestUnitType([
            'title' => 'Поселенец',
            'cost' => 40,
            'upkeep' => 1,
            'attack' => 0,
            'defence' => 1,
            'health' => 1,
            'movement' => 1,
            'can_found_city' => true,
            'description' => 'Основывает новые города',
            'missions' => ['move_to', 'build_city'],
            'can_move' => ['plains' => 1, 'plains2' => 1, 'forest' => 1, 'hills' => 1, 'mountains' => 2, 'desert' => 1, 'city' => 1]
        ]); // Settler
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
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
        $buildingType = TestDataFactory::createTestBuildingType(); // Granary
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
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
        $buildingType = TestDataFactory::createTestBuildingType(); // Granary
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
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
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
        [$game, $user, $planetId, $cityData] = $this->setUpTestCity();
        City::clearCache();
        $city = City::get($cityData->id);
        TestGameDataInitializer::initializeCellTypes();
        $unitType = TestDataFactory::createTestUnitType([
            'title' => 'Поселенец',
            'cost' => 40,
            'upkeep' => 1,
            'attack' => 0,
            'defence' => 1,
            'health' => 1,
            'movement' => 1,
            'can_found_city' => true,
            'description' => 'Основывает новые города',
            'missions' => ['move_to', 'build_city'],
            'can_move' => ['plains' => 1, 'plains2' => 1, 'forest' => 1, 'hills' => 1, 'mountains' => 2, 'desert' => 1, 'city' => 1]
        ]); // Settler
        $city->production_type = "buil";
        $city->production = 1; // Некоторый building ID
        $city->select_next_production();
        $this->assertEquals("unit", $city->production_type);
        $this->assertGreaterThan(0, $city->production);
    }
}
