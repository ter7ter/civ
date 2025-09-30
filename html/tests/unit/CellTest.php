<?php

namespace App\Tests;

use App\Cell;
use App\CellType;
use App\Game;
use App\MyDB;
use App\Planet;
use App\Unit;
use App\User;
use App\MissionType;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;
use App\Tests\Base\TestGameDataInitializer;

/**
 * Тесты для класса Cell
 */
class CellTest extends CommonTestBase
{
    protected function setUp(): void
    {
        $this->setUpUnitTest();
        CellType::$all = []; // Очистить кэш CellType
        TestGameDataInitializer::initializeCellTypes();
    }
    /**
     * Тест получения существующей клетки
     */
    public function testGetExistingCell(): void
    {
        $testData = TestDataFactory::createCompleteTestGame();
        TestDataFactory::createTestCell(['x' => 5, 'y' => 10, 'planet' => $testData['planet'], 'type' => 'plains']);

        $cell = Cell::get(5, 10, $testData['planet']);

        $this->assertInstanceOf(Cell::class, $cell);
        $this->assertEquals(5, $cell->x);
        $this->assertEquals(10, $cell->y);
        $this->assertEquals($testData['planet'], $cell->planet);
        $this->assertInstanceOf(CellType::class, $cell->type);
        $this->assertEquals('plains', $cell->type->id);
    }

    /**
     * Тест получения несуществующей клетки
     */
    public function testGetNonExistingCell(): void
    {
        $testData = TestDataFactory::createCompleteTestGame();

        $cell = Cell::get(999, 999, $testData['planet']);

        $this->assertFalse($cell);
    }

    /**
     * Тест конструктора Cell
     */
    public function testConstructor(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(['game' => $game->id]);

        $cellData = [
            'x' => 3,
            'y' => 7,
            'planet' => $planetId,
            'type' => 'plains',
            'owner' => $user->id,
            'owner_culture' => 5,
            'road' => 'road',
            'improvement' => 'mine',
        ];

        $cell = new Cell($cellData);

        $this->assertEquals(3, $cell->x);
        $this->assertEquals(7, $cell->y);
        $this->assertEquals($planetId, $cell->planet);
        $this->assertInstanceOf(CellType::class, $cell->type);
        $this->assertInstanceOf(User::class, $cell->owner);
        $this->assertEquals($user->id, $cell->owner->id);
        $this->assertEquals(5, $cell->owner_culture);
        $this->assertEquals('road', $cell->road);
        $this->assertEquals('mine', $cell->improvement);

        // Проверяем, что клетка добавлена в кэш
        $this->assertSame($cell, Cell::get(3, 7, $planetId));
    }

    /**
     * Тест конструктора без владельца
     */
    public function testConstructorWithoutOwner(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;

        $cellData = [
            'x' => 1,
            'y' => 2,
            'planet' => $planetId,
            'type' => 'plains',
            'owner' => null,
            'owner_culture' => 0,
            'road' => 'none',
            'improvement' => 'none',
        ];

        $cell = new Cell($cellData);

        $this->assertNull($cell->owner);
        $this->assertEquals(0, $cell->owner_culture);
        $this->assertFalse($cell->road);
        $this->assertFalse($cell->improvement);
    }

    /**
     * Тест метода getTitle
     */
    public function testGetTitle(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        TestDataFactory::createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        $this->assertIsString($cell->getTitle());
        $this->assertNotEmpty($cell->getTitle());
    }

    /**
     * Тест метода get_planet
     */
    public function testGetPlanet(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        TestDataFactory::createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        $planet = $cell->get_planet();
        $this->assertInstanceOf(Planet::class, $planet);
        $this->assertEquals($planetId, $planet->id);
    }

    /**
     * Тест сохранения новой клетки
     */
    public function testSaveNew(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(['game' => $game->id]);

        $cellData = [
            'x' => 10,
            'y' => 20,
            'planet' => $planetId,
            'type' => 'plains',
            'owner' => $user->id,
            'owner_culture' => 3,
            'road' => 'road',
            'improvement' => 'mine',
        ];

        $cell = new Cell($cellData);
        $cell->save();

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM cell WHERE x = :x AND y = :y AND planet = :planet",
            ['x' => 10, 'y' => 20, 'planet' => $planetId],
            "row"
        );
        $this->assertNotNull($savedData);
        $this->assertEquals('plains', $savedData['type']);
        $this->assertEquals($user->id, $savedData['owner']);
        $this->assertEquals(3, $savedData['owner_culture']);
        $this->assertEquals('road', $savedData['road']);
        $this->assertEquals('mine', $savedData['improvement']);
    }

    /**
     * Тест обновления существующей клетки
     */
    public function testSaveUpdate(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(['game' => $game->id]);

        // Создаем клетку
        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(5, 5, $planetId);
        $cell->owner = User::get($user->id);
        $cell->owner_culture = 10;
        $cell->road = 'iron';
        $cell->improvement = 'irrigation';
        $cell->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM cell WHERE x = :x AND y = :y AND planet = :planet",
            ['x' => 5, 'y' => 5, 'planet' => $planetId],
            "row"
        );
        $this->assertEquals($user->id, $updatedData['owner']);
        $this->assertEquals(10, $updatedData['owner_culture']);
        $this->assertEquals('iron', $updatedData['road']);
        $this->assertEquals('irrigation', $updatedData['improvement']);
    }

    /**
     * Тест метода calc_coord
     */
    public function testCalcCoord(): void
    {
        Cell::$map_width = 10;
        Cell::$map_height = 10;

        // Тест нормального перемещения
        $x = 5;
        $y = 5;
        Cell::calc_coord($x, $y, 2, 3);
        $this->assertEquals(7, $x);
        $this->assertEquals(8, $y);

        // Тест зацикливания вправо
        $x = 8;
        $y = 5;
        Cell::calc_coord($x, $y, 3, 0);
        $this->assertEquals(1, $x);

        // Тест зацикливания влево
        $x = 1;
        $y = 5;
        Cell::calc_coord($x, $y, -3, 0);
        $this->assertEquals(8, $x);

        // Тест зацикливания вниз
        $x = 5;
        $y = 8;
        Cell::calc_coord($x, $y, 0, 3);
        $this->assertEquals(1, $y);

        // Тест зацикливания вверх
        $x = 5;
        $y = 1;
        Cell::calc_coord($x, $y, 0, -3);
        $this->assertEquals(8, $y);
    }

    /**
     * Тест метода calc_distance
     */
    public function testCalcDistance(): void
    {
        Cell::$map_width = 10;
        Cell::$map_height = 10;

        // Тест обычного расстояния
        $distance = Cell::calc_distance(0, 0, 3, 4);
        $this->assertEquals(4, $distance);

        // Тест с зацикливанием
        $distance = Cell::calc_distance(0, 0, 9, 9);
        $this->assertEquals(1, $distance); // Короткий путь через край

        // Тест нулевого расстояния
        $distance = Cell::calc_distance(5, 5, 5, 5);
        $this->assertEquals(0, $distance);
    }

    /**
     * Тест метода d_coord
     */
    public function testDCoord(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planetId, 'type' => 'plains']);

        $cell = Cell::d_coord(5, 5, 0, 0, true, $planetId);
        $this->assertInstanceOf(Cell::class, $cell);
        $this->assertEquals(5, $cell->x);
        $this->assertEquals(5, $cell->y);

        // Тест без загрузки
        $cell = Cell::d_coord(10, 10, 0, 0, false, $planetId);
        $this->assertFalse($cell);
    }

    /**
     * Тест метода get_cells_around
     */
    public function testGetCellsAround(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;

        // Создаем несколько клеток
        for ($x = 0; $x < 5; $x++) {
            for ($y = 0; $y < 5; $y++) {
                TestDataFactory::createTestCell(['x' => $x, 'y' => $y, 'planet' => $planetId, 'type' => 'plains']);
            }
        }

        $cells = Cell::get_cells_around(2, 2, 3, 3, $planetId);

        $this->assertCount(3, $cells); // 3 строки
        $this->assertCount(3, $cells[0]); // 3 столбца в каждой строке

        // Проверяем центральную клетку
        $this->assertEquals(2, $cells[1][1]->x);
        $this->assertEquals(2, $cells[1][1]->y);
    }

    /**
     * Тест метода get_work
     */
    public function testGetWork(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        TestDataFactory::createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        $work = $cell->get_work();
        $this->assertIsInt($work);
        $this->assertGreaterThanOrEqual(0, $work);

        // Тест с улучшением mine
        $cell->improvement = 'mine';
        $workWithMine = $cell->get_work();
        $this->assertEquals($work + 1, $workWithMine);
    }

    /**
     * Тест метода get_eat
     */
    public function testGetEat(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        TestDataFactory::createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        $eat = $cell->get_eat();
        $this->assertIsInt($eat);
        $this->assertGreaterThanOrEqual(0, $eat);

        // Тест с улучшением irrigation
        $cell->improvement = 'irrigation';
        $eatWithIrrigation = $cell->get_eat();
        $this->assertEquals($eat + 1, $eatWithIrrigation);
    }

    /**
     * Тест метода get_money
     */
    public function testGetMoney(): void
    {
        $game =TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        TestDataFactory::createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        $money = $cell->get_money();
        $this->assertIsInt($money);
        $this->assertGreaterThanOrEqual(0, $money);

        // Тест с дорогой
        $cell->road = 'road';
        $moneyWithRoad = $cell->get_money();
        $this->assertEquals($money + 1, $moneyWithRoad);
    }

    /**
     * Тест метода get_units
     */
    public function testGetUnits(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        TestDataFactory::createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        // Создаем юнит на клетке
        $unit = TestDataFactory::createTestUnit(['x' => 0, 'y' => 0, 'planet' => $planetId, 'user_id' => $user->id]);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        $units = $cell->get_units();
        $this->assertIsArray($units);
        $this->assertCount(1, $units);
        $this->assertInstanceOf(Unit::class, $units[0]);
        $this->assertEquals($unit->id, $units[0]->id);
    }

    /**
     * Тест метода load_cells
     */
    public function testLoadCells(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;

        // Создаем клетки
        TestDataFactory::createTestCell(['x' => 1, 'y' => 1, 'planet' => $planetId, 'type' => 'plains']);
        TestDataFactory::createTestCell(['x' => 2, 'y' => 2, 'planet' => $planetId, 'type' => 'plains']);

        $coords = [
            ['x' => 1, 'y' => 1],
            ['x' => 2, 'y' => 2],
        ];

        $cells = Cell::load_cells($coords, $planetId);

        $this->assertCount(2, $cells);
        $this->assertInstanceOf(Cell::class, $cells[0]);
        $this->assertInstanceOf(Cell::class, $cells[1]);
    }

    /**
     * Тест метода get_mission_need_points
     */
    public function testGetMissionNeedPoints(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        TestDataFactory::createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        // Создаем тестовый тип миссии
        $missionType = new MissionType([
            'id' => 'test_mission',
            'title' => 'Test Mission',
            'unit_lost' => false,
            'cell_types' => ['plains'],
            'need_points' => ['plains' => 10],
        ]);

        $needPoints = $cell->get_mission_need_points($missionType);
        $this->assertEquals(10, $needPoints);
    }

    /**
     * Тест очистки кэша
     */
    public function testClearCache(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        TestDataFactory::createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        // Получаем клетку, чтобы она попала в кэш
        $cell1 = Cell::get(0, 0, $planetId);
        $this->assertInstanceOf(Cell::class, $cell1);

        Cell::clearCache();

        // После очистки кэша, клетка должна быть загружена заново
        $cell2 = Cell::get(0, 0, $planetId);
        $this->assertInstanceOf(Cell::class, $cell2);
        $this->assertNotSame($cell1, $cell2); // Должны быть разные объекты
    }

    /**
     * Тест метода generate_type
     */
    public function testGenerateType(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;

        // Устанавливаем маленький размер карты для теста
        Cell::$map_width = 10;
        Cell::$map_height = 10;

        // Создаем несколько клеток вокруг для тестирования соседей
        for ($x = 0; $x < 10; $x++) {
            for ($y = 0; $y < 10; $y++) {
                TestDataFactory::createTestCell(['x' => $x, 'y' => $y, 'planet' => $planetId, 'type' => 'plains']);
            }
        }

        $cellType = Cell::generate_type(5, 5, $planetId);

        $this->assertInstanceOf(CellType::class, $cellType);
        $this->assertIsString($cellType->id);
        $this->assertNotEmpty($cellType->id);
    }

    /**
     * Тест метода generate_map (упрощенная версия)
     */
    public function testGenerateMap(): void
    {
        $game = TestDataFactory::createTestGame(['map_w' => 5, 'map_h' => 5]);
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;

        // Устанавливаем маленький размер карты для быстрого теста
        Cell::$map_width = 5;
        Cell::$map_height = 5;

        // Очищаем существующие клетки
        MyDB::query("DELETE FROM cell WHERE planet = :planet", ['planet' => $planetId]);
        MyDB::query("DELETE FROM resource WHERE planet = :planet", ['planet' => $planetId]);

        Cell::generate_map($planetId, $game->id);

        // Проверяем, что клетки созданы
        $cellCount = MyDB::query("SELECT COUNT(*) FROM cell WHERE planet = :planet", ['planet' => $planetId], 'elem');
        $this->assertEquals(25, $cellCount); // 5x5 = 25 клеток

        // Проверяем, что все клетки имеют типы
        $cells = MyDB::query("SELECT * FROM cell WHERE planet = :planet", ['planet' => $planetId]);
        $this->assertCount(25, $cells);
        foreach ($cells as $cell) {
            $this->assertNotEmpty($cell['type'], "Cell at ({$cell['x']}, {$cell['y']}) has empty type");
        }
    }
}
