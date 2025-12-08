<?php

namespace App\Tests;

use App\Tests\Base\TestGameDataInitializer;
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
    public function setUp(): void
    {
        parent::setUp();
        TestGameDataInitializer::initializeCellTypes();
    }
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
        TestDataFactory::createTestCell(['x' => 3, 'y' => 4, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 3,
            "y" => 4,
            "planet" => $planet->id,
            "health" => 3,
            "points" => 2,
        ]);
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
     * Тест метода getTitle
     */
    public function testGetTitle(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Рабочий",
            "points" => 1,
        ]);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 6,
            "y" => 7,
            "planet" => $planet->id,
        ]);

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
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Remove Unit",
            "points" => 1,
        ]);

        // Создаем юнит
        TestDataFactory::createTestCell(['x' => 8, 'y' => 9, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 8,
            "y" => 9,
            "planet" => $planet->id,
        ]);

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
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Mission Unit",
            "points" => 1,
        ]);

        TestDataFactory::createTestCell(['x' => 10, 'y' => 10, 'planet' => $planet->id, 'type' => 'plains']);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 10,
            "y" => 10,
            "planet" => $planet->id,
        ]);

        $missionTypes = $unit->getMissionTypes();

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
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Move Unit",
            "points" => 2,
        ]);

        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planetId, 'type' => 'plains']);
        TestDataFactory::createTestCell(['x' => 6, 'y' => 5, 'planet' => $planetId, 'type' => 'plains']);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planetId,
            "points" => 2,
        ]);

        $targetCell = Cell::get(6, 5, $planetId);

        $canMove = $unit->canMove($targetCell);

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
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Move To Unit",
            "points" => 2,
        ]);

        TestDataFactory::createTestCell(['x' => 7, 'y' => 7, 'planet' => $planetId, 'type' => 'plains']);
        TestDataFactory::createTestCell(['x' => 8, 'y' => 7, 'planet' => $planetId, 'type' => 'plains']);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 7,
            "y" => 7,
            "planet" => $planetId,
            "points" => 2,
        ]);

        $targetCell = Cell::get(8, 7, $planetId);

        $moved = $unit->moveTo($targetCell);

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
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Get All Unit",
            "points" => 1,
        ]);

        $cell = TestDataFactory::createTestCell(['x' => 11, 'y' => 11, 'planet' => $planetId]);

        // Создаем несколько юнитов
        $cell->create_unit(
            $unitType,
            $user,
            3,
            1
        );
        $cell->create_unit(
            $unitType,
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

    /**
     * Тест метода getCurrentMissionNeedTurns без миссии
     */
    public function testGetCurrentMissionNeedTurnsNoMission(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "No Mission Unit",
            "points" => 1,
        ]);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 12,
            "y" => 12,
            "planet" => $planet->id,
        ]);

        $needTurns = $unit->getCurrentMissionNeedTurns();

        $this->assertEquals(0, $needTurns);
    }

    /**
     * Тест метода getCurrentMissionNeedTurns с миссией
     */
    public function testGetCurrentMissionNeedTurnsWithMission(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита с миссиями
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Mission Test Unit",
            "points" => 2,
        ]);

        // Создаем тестовую миссию с need_points
        $mission = new \App\MissionType([
            'id' => 'test_build',
            'title' => 'Test Build',
            'unit_lost' => false,
            'cell_types' => ['plains'],
            'need_points' => ['plains' => 20],
        ]);

        TestDataFactory::createTestCell(['x' => 13, 'y' => 13, 'planet' => $planet->id, 'type' => 'plains']);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 13,
            "y" => 13,
            "planet" => $planet->id,
            "mission" => $mission->id,
        ]);

        $needTurns = $unit->getCurrentMissionNeedTurns();

        // need_points = 20, points per turn = 2 (один юнит), так что ceil(20/2) = 10
        $this->assertEquals(10, $needTurns);
    }

    /**
     * Тест метода getMissionNeedTurns
     */
    public function testGetMissionNeedTurns(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита с 1 очком
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Mission Need Turns Unit",
            "points" => 1,
        ]);

        // Создаем тестовую миссию с need_points
        $mission = new \App\MissionType([
            'id' => 'test_road',
            'title' => 'Test Road',
            'unit_lost' => false,
            'cell_types' => ['plains'],
            'need_points' => ['plains' => 15],
        ]);

        TestDataFactory::createTestCell(['x' => 14, 'y' => 14, 'planet' => $planet->id, 'type' => 'plains']);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 14,
            "y" => 14,
            "planet" => $planet->id,
        ]);

        $needTurns = $unit->getMissionNeedTurns($mission);

        // need_points = 15, points per turn = 1 (unit1), так что ceil(15/1) = 15
        $this->assertEquals(15, $needTurns);
    }

    /**
     * Тест метода getCurrentMissionNeedTurns с несколькими юнитами на миссии
     */
    public function testGetCurrentMissionNeedTurnsMultipleUnits(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита с 2 очками
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Multi Unit Test",
            "points" => 2,
        ]);

        // Создаем тестовую миссию с need_points = 30
        $mission = new \App\MissionType([
            'id' => 'test_multi',
            'title' => 'Test Multi',
            'unit_lost' => false,
            'cell_types' => ['plains'],
            'need_points' => ['plains' => 30],
        ]);

        // Создаем клетку
        TestDataFactory::createTestCell(['x' => 15, 'y' => 15, 'planet' => $planet->id, 'type' => 'plains']);

        // Создаем первый юнит с миссией
        $unit1 = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 15,
            "y" => 15,
            "planet" => $planet->id,
            "mission" => $mission->id,
        ]);

        // Создаем второй юнит с той же миссией на той же клетке
        $unit2 = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 15,
            "y" => 15,
            "planet" => $planet->id,
            "mission" => $mission->id,
        ]);

        $needTurns1 = $unit1->getCurrentMissionNeedTurns();
        $needTurns2 = $unit2->getCurrentMissionNeedTurns();

        // Оба юнита работают вместе, так что needTurns должно быть одинаковым и меньше, чем если бы работал один юнит
        $this->assertEquals($needTurns1, $needTurns2);
        $this->assertGreaterThan(0, $needTurns1);
        $this->assertLessThanOrEqual(15, $needTurns1); // Если бы работал один юнит: ceil(30/2) = 15
    }

    /**
     * Тест метода getMissionNeedTurns с несколькими юнитами
     */
    public function testGetMissionNeedTurnsMultipleUnits(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита с 3 очками
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Multi Unit Need Turns",
            "points" => 3,
        ]);

        // Создаем тестовую миссию с need_points = 25
        $mission = new \App\MissionType([
            'id' => 'test_multi_need',
            'title' => 'Test Multi Need',
            'unit_lost' => false,
            'cell_types' => ['plains'],
            'need_points' => ['plains' => 25],
        ]);

        // Создаем клетку
        TestDataFactory::createTestCell(['x' => 16, 'y' => 16, 'planet' => $planet->id, 'type' => 'plains']);

        // Создаем первый юнит
        $unit1 = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 16,
            "y" => 16,
            "planet" => $planet->id,
        ]);

        // Второй юнит уже работает над миссией
        $unit2 = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 16,
            "y" => 16,
            "planet" => $planet->id,
            "mission" => $mission->id,
        ]);

        $needTurns = $unit1->getMissionNeedTurns($mission);

        // unitGetNeedTurns учитывает unit1 + юниты, уже работающие над миссией
        // Так что с двумя юнитами needTurns меньше, чем если бы работал только unit1
        $this->assertIsInt($needTurns);
        $this->assertGreaterThan(0, $needTurns);
        $this->assertLessThanOrEqual(9, $needTurns); // Если бы работал только unit1: ceil(25/3) ≈ 9
    }

    /**
     * Тест метода getCurrentMissionNeedTurns с нулевыми need_points
     */
    public function testGetCurrentMissionNeedTurnsZeroPoints(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Zero Points Unit",
            "points" => 1,
        ]);

        // Создаем миссию с нулевыми need_points
        $mission = new \App\MissionType([
            'id' => 'test_zero',
            'title' => 'Test Zero',
            'unit_lost' => false,
            'cell_types' => ['plains'],
            'need_points' => ['plains' => 0],
        ]);

        TestDataFactory::createTestCell(['x' => 17, 'y' => 17, 'planet' => $planet->id, 'type' => 'plains']);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 17,
            "y" => 17,
            "planet" => $planet->id,
            "mission" => $mission->id,
        ]);

        $needTurns = $unit->getCurrentMissionNeedTurns();

        // need_points = 0, должен вернуть 0
        $this->assertEquals(0, $needTurns);
    }

    /**
     * Тест метода cancelMission
     */
    public function testCancelMission(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "Cancel Mission Unit",
            "points" => 2,
        ]);

        // Создаем миссию
        $mission = new \App\MissionType([
            'id' => 'test_cancel',
            'title' => 'Test Cancel',
            'unit_lost' => false,
            'cell_types' => ['plains'],
            'need_points' => ['plains' => 10],
        ]);

        TestDataFactory::createTestCell(['x' => 18, 'y' => 18, 'planet' => $planet->id, 'type' => 'plains']);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 18,
            "y" => 18,
            "planet" => $planet->id,
            "mission" => $mission->id,
            "points" => 0,
            "mission_points" => 5,
        ]);

        // Отменяем миссию
        $result = $unit->cancelMission();

        $this->assertTrue($result);
        $this->assertFalse($unit->mission);
        $this->assertEquals(0, $unit->mission_points);
        $this->assertEquals(0, $unit->points); // Очки остаются потраченными
    }

    /**
     * Тест метода cancelMission без миссии
     */
    public function testCancelMissionNoMission(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(["game_id" => $game->id]);
        $user = TestDataFactory::createTestUser(["game" => $game->id]);

        // Создаем тип юнита
        $unitType = TestDataFactory::createTestUnitType([
            "title" => "No Mission Cancel Unit",
            "points" => 1,
        ]);

        TestDataFactory::createTestCell(['x' => 19, 'y' => 19, 'planet' => $planet->id, 'type' => 'plains']);

        $unit = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $unitType->id,
            "x" => 19,
            "y" => 19,
            "planet" => $planet->id,
            "points" => 1,
        ]);

        // Пытаемся отменить миссию, когда её нет
        $result = $unit->cancelMission();

        $this->assertFalse($result);
        $this->assertEquals(1, $unit->points); // Очки не изменились
    }
}
