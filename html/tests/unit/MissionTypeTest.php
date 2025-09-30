<?php

namespace App\Tests;

use App\MissionType;
use App\Unit;
use App\City;
use App\Cell;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;

class MissionTypeTest extends CommonTestBase
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
        $data = [
            'id' => 'test_mission',
            'title' => 'Test Mission',
            'unit_lost' => false,
            'cell_types' => ['plains'],
            'need_points' => []
        ];
        $mission = new MissionType($data);
        $this->assertEquals('test_mission', $mission->id);
        $this->assertEquals('Test Mission', $mission->title);
    }

    public function testGet()
    {
        $mission = TestDataFactory::createTestMissionType(['id' => 'build_city']);
        $this->assertInstanceOf(MissionType::class, $mission);
        $this->assertEquals('build_city', $mission->id);
    }

    public function testGetTitle()
    {
        $mission = TestDataFactory::createTestMissionType([
            'id' => 'build_city'
        ]);
        $this->assertEquals('Основать город', $mission->getTitle());
    }

    public function testCheckCell()
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(["game" => $game->id]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);

        $mission = TestDataFactory::createTestMissionType(['id' => 'build_city']);
        $this->assertTrue($mission->check_cell(10, 10, $planetId));

        // Test with a city
        City::clearCache();
        TestDataFactory::createTestCity(["planet" => $planetId, "x" => 10, "y" => 10, "user_id" => $user->id]);
        $this->assertTrue($mission->check_cell(10, 10, $planetId)); // Method doesn't check for existing cities

        // Test with wrong cell type
        $mission = TestDataFactory::createTestMissionType(['id' => 'build_road']);
        TestDataFactory::createTestCell(["planet" => $planetId, "x" => 11, "y" => 10, "type" => "plains"]);
        $this->assertTrue($mission->check_cell(11, 10, $planetId)); // build_road can be done on plains
    }

    public function testComplete()
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(["game" => $game->id]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $unit = TestDataFactory::createTestUnit(["planet" => $planetId, "user_id" => $user->id, "x" => 10, "y" => 10]);
        $unit = Unit::get($unit->id);

        // Test build_city
        $mission = TestDataFactory::createTestMissionType(['id' => 'build_city']);
        $mission->complete($unit, 'Test City');

        $city = City::by_coords(10, 10, $planetId);
        $this->assertInstanceOf(City::class, $city);
        $this->assertEquals('Test City', $city->title);

        // Test build_road
        $mission = TestDataFactory::createTestMissionType(['id' => 'build_road']);
        $mission->complete($unit);
        $cell = Cell::get(10, 10, $planetId);
        $this->assertEquals('road', $cell->road);

        // Test irrigation
        $mission = TestDataFactory::createTestMissionType(['id' => 'irrigation']);
        $mission->complete($unit);
        $cell = Cell::get(10, 10, $planetId);
        $this->assertEquals('irrigation', $cell->improvement);

        // Test mine
        $mission = TestDataFactory::createTestMissionType(['id' => 'mine']);
        $mission->complete($unit);
        $cell = Cell::get(10, 10, $planetId);
        $this->assertEquals('mine', $cell->improvement);
    }
}
