<?php

/**
 * Тесты для класса Cell
 */
class CellTest extends TestBase
{
    /**
     * Тест получения существующей клетки
     */
    public function testGetExistingCell(): void
    {
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 5, 'y' => 10, 'planet' => $planetId, 'type' => 'plains']);

        $cell = Cell::get(5, 10, $planetId);

        $this->assertInstanceOf(Cell::class, $cell);
        $this->assertEquals(5, $cell->x);
        $this->assertEquals(10, $cell->y);
        $this->assertEquals($planetId, $cell->planet);
        $this->assertInstanceOf(CellType::class, $cell->type);
        $this->assertEquals('plains', $cell->type->id);
    }

    /**
     * Тест получения несуществующей клетки
     */
    public function testGetNonExistingCell(): void
    {
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        $cell = Cell::get(999, 999, $planetId);

        $this->assertFalse($cell);
    }

    /**
     * Тест конструктора Cell
     */
    public function testConstructor(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        $cellData = [
            'x' => 3,
            'y' => 7,
            'planet' => $planetId,
            'type' => 'plains',
            'owner' => $userData['id'],
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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

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
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        $this->assertIsString($cell->get_title());
        $this->assertNotEmpty($cell->get_title());
    }

    /**
     * Тест метода get_planet
     */
    public function testGetPlanet(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        $cellData = [
            'x' => 10,
            'y' => 20,
            'planet' => $planetId,
            'type' => 'plains',
            'owner' => $userData['id'],
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
        $this->assertEquals($userData['id'], $savedData['owner']);
        $this->assertEquals(3, $savedData['owner_culture']);
        $this->assertEquals('road', $savedData['road']);
        $this->assertEquals('mine', $savedData['improvement']);
    }

    /**
     * Тест обновления существующей клетки
     */
    public function testSaveUpdate(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        // Создаем клетку
        $this->createTestCell(['x' => 5, 'y' => 5, 'planet' => $planetId, 'type' => 'plains']);

        Cell::clearCache();
        $cell = Cell::get(5, 5, $planetId);
        $cell->owner = User::get($userData['id']);
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
        $this->assertEquals($userData['id'], $updatedData['owner']);
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
        $x = 5; $y = 5;
        Cell::calc_coord($x, $y, 2, 3);
        $this->assertEquals(7, $x);
        $this->assertEquals(8, $y);

        // Тест зацикливания вправо
        $x = 8; $y = 5;
        Cell::calc_coord($x, $y, 3, 0);
        $this->assertEquals(1, $x);

        // Тест зацикливания влево
        $x = 1; $y = 5;
        Cell::calc_coord($x, $y, -3, 0);
        $this->assertEquals(8, $x);

        // Тест зацикливания вниз
        $x = 5; $y = 8;
        Cell::calc_coord($x, $y, 0, 3);
        $this->assertEquals(1, $y);

        // Тест зацикливания вверх
        $x = 5; $y = 1;
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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 5, 'y' => 5, 'planet' => $planetId, 'type' => 'plains']);

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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        // Создаем несколько клеток
        for ($x = 0; $x < 5; $x++) {
            for ($y = 0; $y < 5; $y++) {
                $this->createTestCell(['x' => $x, 'y' => $y, 'planet' => $planetId, 'type' => 'plains']);
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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $this->createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        // Создаем юнит на клетке
        $this->createTestUnit(['x' => 0, 'y' => 0, 'planet' => $planetId, 'user_id' => $userData['id']]);

        Cell::clearCache();
        $cell = Cell::get(0, 0, $planetId);

        $units = $cell->get_units();
        $this->assertIsArray($units);
        $this->assertCount(1, $units);
        $this->assertInstanceOf(Unit::class, $units[0]);
    }

    /**
     * Тест метода load_cells
     */
    public function testLoadCells(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        // Создаем клетки
        $this->createTestCell(['x' => 1, 'y' => 1, 'planet' => $planetId, 'type' => 'plains']);
        $this->createTestCell(['x' => 2, 'y' => 2, 'planet' => $planetId, 'type' => 'plains']);

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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $this->createTestCell(['x' => 0, 'y' => 0, 'planet' => $planetId, 'type' => 'plains']);

        // Получаем клетку, чтобы она попала в кэш
        $cell1 = Cell::get(0, 0, $planetId);
        $this->assertInstanceOf(Cell::class, $cell1);

        Cell::clearCache();

        // После очистки кэша, клетка должна быть загружена заново
        $cell2 = Cell::get(0, 0, $planetId);
        $this->assertInstanceOf(Cell::class, $cell2);
        $this->assertNotSame($cell1, $cell2); // Должны быть разные объекты
    }
}
