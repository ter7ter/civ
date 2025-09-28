<?php

namespace App\Tests;

use App\MissionType;
use App\Unit;
use App\City;
use App\Cell;

class MissionTypeTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->headers = [];

        // Подключаем классы проекта
        require_once PROJECT_ROOT . "/includes.php";

        // Инициализируем игровые типы
        $this->initializeGameTypes();
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
        $mission = MissionType::get('build_city');
        $this->assertInstanceOf(MissionType::class, $mission);
        $this->assertEquals('build_city', $mission->id);
    }

    public function testGetTitle()
    {
        $mission = MissionType::get('build_city');
        $this->assertEquals('Основать город', $mission->get_title());
    }

    public function testCheckCell()
    {
        $game = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $game->id]);
        $user = $this->createTestUser(["game" => $game->id]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);

        $mission = MissionType::get('build_city');
        $this->assertTrue($mission->check_cell(10, 10, $planetId));

        // Test with a city
        City::clearCache();
        $this->createTestCity(["planet" => $planetId, "x" => 10, "y" => 10, "user_id" => $user->id]);
        $this->assertTrue($mission->check_cell(10, 10, $planetId)); // Method doesn't check for existing cities

        // Test with wrong cell type
        $mission = MissionType::get('build_road');
        $this->createTestCell(["planet" => $planetId, "x" => 11, "y" => 10, "type" => "plains"]);
        $this->assertTrue($mission->check_cell(11, 10, $planetId)); // build_road can be done on plains
    }

    public function testComplete()
    {
        $game = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $game->id]);
        $user = $this->createTestUser(["game" => $game->id]);
        $this->createTestMapCells(10, 10, 1, 1, $planetId);
        $unit = $this->createTestUnit(["planet" => $planetId, "user_id" => $user->id, "x" => 10, "y" => 10]);
        $unit = Unit::get($unit->id);

        // Test build_city
        $mission = MissionType::get('build_city');
        $mission->complete($unit, 'Test City');

        $city = City::by_coords(10, 10, $planetId);
        $this->assertInstanceOf(City::class, $city);
        $this->assertEquals('Test City', $city->title);

        // Test build_road
        $mission = MissionType::get('build_road');
        $mission->complete($unit);
        $cell = Cell::get(10, 10, $planetId);
        $this->assertEquals('road', $cell->road);

        // Test irrigation
        $mission = MissionType::get('irrigation');
        $mission->complete($unit);
        $cell = Cell::get(10, 10, $planetId);
        $this->assertEquals('irrigation', $cell->improvement);

        // Test mine
        $mission = MissionType::get('mine');
        $mission->complete($unit);
        $cell = Cell::get(10, 10, $planetId);
        $this->assertEquals('mine', $cell->improvement);
    }
}
