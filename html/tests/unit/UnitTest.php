<?php

namespace App\Tests;

use App\Unit;
use App\User;
use App\UnitType;
use App\MyDB;
use App\Cell;
use App\Tests\Factory\TestDataFactory;
use App\Tests\base\CommonTestBase;

/**
 * Тесты для класса Unit
 */
class UnitTest extends CommonTestBase
{
    /**
     * Тест получения существующего юнита
     */
    public function testGetExistingUnit(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $result['game']->id]);
        $user = TestDataFactory::createTestUser(["game" => $result['game']->id]);

        // Создаем тип юнита
        $unitTypeData = [
            "title" => "Test Unit",
            "points" => 2,
        ];
        $unitType = TestDataFactory::createTestUnitType($unitTypeData);

        $cell = TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planet->id]);
        $unit = $cell->create_unit($unitType, $user, 3, 2);

        $unitGet = Unit::get($unit->id);

        $this->assertInstanceOf(Unit::class, $unit);
        $this->assertEquals(5, $unitGet->x);
        $this->assertEquals(5, $unitGet->y);
        $this->assertEquals(3, $unitGet->health);
        $this->assertEquals(2, $unitGet->points);
        $this->assertEquals("Test Unit", $unit->getTitle());
    }

    /**
     * Тест получения несуществующего юнита
     */
    public function testGetNonExistingUnit(): void
    {
        $unit = Unit::get(999);

        $this->assertNull($unit);
    }

    /**
     * Тест конструктора
     */
    public function testConstruct(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitTypeData = [
            "title" => "Construct Unit",
            "points" => 1,
        ];
        $unitType = TestDataFactory::createTestUnitType($unitTypeData);

        $data = [
            "id" => 1,
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 5,
            "y" => 15,
            "planet" => $planet->id,
            "health" => 2,
            "points" => 1,
            "auto" => "none",
        ];

        $unit = TestDataFactory::createTestUnit($data);

        $this->assertEquals(1, $unit->id);
        $this->assertEquals(5, $unit->x);
        $this->assertEquals(15, $unit->y);
        $this->assertEquals(2, $unit->health);
        $this->assertEquals(1, $unit->points);
        $this->assertEquals("none", $unit->auto);
        $this->assertInstanceOf(User::class, $unit->user);
        $this->assertInstanceOf(UnitType::class, $unit->type);
    }

    /**
     * Тест сохранения нового юнита
     */
    public function testSaveNew(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitTypeData = [
            "title" => "Save Unit",
            "points" => 3,
        ];
        $unitType = TestDataFactory::createTestUnitType($unitTypeData);

        $data = [
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 1,
            "y" => 2,
            "planet" => $planet->id,
            "health" => 3,
            "points" => 3,
        ];

        TestDataFactory::createTestCell(['x' => 1, 'y' => 2, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit($data);

        $this->assertNotNull($unit->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM unit WHERE id = :id",
            ["id" => $unit->id],
            "row",
        );
        $this->assertEquals(1, $savedData["x"]);
        $this->assertEquals(2, $savedData["y"]);
        $this->assertEquals(3, $savedData["health"]);
        $this->assertEquals(3, $savedData["points"]);
        $this->assertEquals($unitType->id, $savedData["type"]);
    }

    /**
     * Тест обновления существующего юнита
     */
    public function testSaveUpdate(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Update Unit",
            "points" => 2,
        ]);

        // Создаем юнит
        $data = [
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 3,
            "y" => 4,
            "planet" => $planet->id,
            "health" => 3,
            "points" => 2,
        ];
        TestDataFactory::createTestCell(['x' => 3, 'y' => 4, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit($data);
        $originalId = $unit->id;

        // Обновляем
        $unit->health = 1;
        $unit->points = 0;
        $unit->save();

        $this->assertEquals($originalId, $unit->id);

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM unit WHERE id = :id",
            ["id" => $unit->id],
            "row",
        );
        $this->assertEquals(1, $updatedData["health"]);
        $this->assertEquals(0, $updatedData["points"]);
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitTypeData = [
            "title" => "Рабочий",
            "points" => 1,
        ];
        $unitType = TestDataFactory::CreateTestUnitType($unitTypeData);
        TestDataFactory::createTestCell(['x' => 6, 'y' => 7, 'planet' => $planet->id, 'type' => 'plains']);
        $data = [
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 6,
            "y" => 7,
            "planet" => $planet->id,
        ];

        $unit = TestDataFactory::CreateTestUnit($data);

        $this->assertEquals("Рабочий", $unit->getTitle());
    }

    /**
     * Тест метода remove
     */
    public function testRemove(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 6,
            "title" => "Remove Unit",
            "points" => 1,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        // Создаем юнит
        $data = [
            "user_id" => $user->id,
            "type" => 6,
            "x" => 8,
            "y" => 9,
            "planet" => $planet->id,
        ];
        TestDataFactory::createTestCell(['x' => 8, 'y' => 9, 'planet' => $planet->id]);
        $unit = new Unit($data);
        $unit->save();
        $unitId = $unit->id;

        // Удаляем
        $unit->remove();

        // Проверяем, что юнит удален из БД
        $deletedData = MyDB::query(
            "SELECT * FROM unit WHERE id = :id",
            ["id" => $unitId],
            "row",
        );
        $this->assertFalse($deletedData);
    }

    /**
     * Тест метода get_mission_types
     */
    public function testGetMissionTypes(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита с миссиями
        $unitTypeData = [
            "id" => 7,
            "title" => "Mission Unit",
            "points" => 1,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        // Создаем объект UnitType с миссиями
        new UnitType([
            "id" => 7,
            "title" => "Mission Unit",
            "points" => 1,
            "missions" => ["move_to"], // Только move_to, так как MissionType не определен
        ]);

        TestDataFactory::createTestCell(['x' => 10, 'y' => 10, 'planet' => $planet->id, 'type' => 'plains']);

        $data = [
            "user_id" => $user->id,
            "type" => 7,
            "x" => 10,
            "y" => 10,
            "planet" => $planet->id,
        ];

        $unit = new Unit($data);

        $missionTypes = $unit->get_mission_types();

        $this->assertIsArray($missionTypes);
        $this->assertArrayHasKey('move_to', $missionTypes);
        // Проверяем, что возвращается массив с миссиями
        $this->assertGreaterThanOrEqual(1, count($missionTypes));
    }

    /**
     * Тест метода can_move
     */
    public function testCanMove(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 8,
            "title" => "Move Unit",
            "points" => 2,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planetId, 'type' => 'plains']);
        TestDataFactory::createTestCell(['x' => 6, 'y' => 5, 'planet' => $planetId, 'type' => 'plains']);

        $data = [
            "user_id" => $user->id,
            "type" => 8,
            "x" => 5,
            "y" => 5,
            "planet" => $planetId,
            "points" => 2,
        ];

        $unit = new Unit($data);
        $targetCell = Cell::get(6, 5, $planetId);

        $canMove = $unit->can_move($targetCell);

        $this->assertTrue($canMove);
    }

    /**
     * Тест метода move_to
     */
    public function testMoveTo(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 9,
            "title" => "Move To Unit",
            "points" => 2,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        TestDataFactory::createTestCell(['x' => 7, 'y' => 7, 'planet' => $planetId, 'type' => 'plains']);
        TestDataFactory::createTestCell(['x' => 8, 'y' => 7, 'planet' => $planetId, 'type' => 'plains']);

        $data = [
            "user_id" => $user->id,
            "type" => 9,
            "x" => 7,
            "y" => 7,
            "planet" => $planetId,
            "points" => 2,
        ];

        $unit = new Unit($data);
        $targetCell = Cell::get(8, 7, $planetId);

        $moved = $unit->move_to($targetCell);

        $this->assertTrue($moved);
        $this->assertEquals(8, $unit->x);
        $this->assertEquals(7, $unit->y);
        $this->assertLessThan(2, $unit->points); // Очки уменьшились
    }

    /**
     * Тест метода get_all
     */
    public function testGetAll(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 10,
            "title" => "Get All Unit",
            "points" => 1,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        $cell = TestDataFactory::createTestCell(['x' => 11, 'y' => 11, 'planet' => $planetId]);

        // Создаем несколько юнитов
        $cell->create_unit(
            UnitType::get(10),
            $user,
            3,
            1
        );
        $cell->create_unit(
            UnitType::get(10),
            $user,
            3,
            1
        );

        $allUnits = Unit::getAll();

        $this->assertIsArray($allUnits);
        $this->assertGreaterThanOrEqual(2, count($allUnits));
        foreach ($allUnits as $unit) {
            $this->assertInstanceOf(Unit::class, $unit);
        }
    }
}
