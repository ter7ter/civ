<?php

/**
 * Тесты для класса Unit
 */
class UnitTest extends TestBase
{
    /**
     * Тест получения существующего юнита
     */
    public function testGetExistingUnit(): void
    {
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $user = User::get($userData["id"]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 1,
            "title" => "Test Unit",
            "points" => 2,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        $this->createTestCell(['x' => 10, 'y' => 20, 'planet' => $planetId]);

        // Создаем юнит
        $unitData = [
            "user_id" => $user->id,
            "type" => 1,
            "x" => 10,
            "y" => 20,
            "planet" => $planetId,
            "health" => 3,
            "points" => 2,
        ];
        $unitData["id"] = MyDB::insert("unit", $unitData);

        $unit = Unit::get($unitData["id"]);

        $this->assertInstanceOf(Unit::class, $unit);
        $this->assertEquals($unitData["id"], $unit->id);
        $this->assertEquals(10, $unit->x);
        $this->assertEquals(20, $unit->y);
        $this->assertEquals(3, $unit->health);
        $this->assertEquals(2, $unit->points);
        $this->assertEquals("Test Unit", $unit->get_title());
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
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $user = User::get($userData["id"]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 2,
            "title" => "Construct Unit",
            "points" => 1,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        $data = [
            "id" => 1,
            "user_id" => $user->id,
            "type" => 2,
            "x" => 5,
            "y" => 15,
            "planet" => $planetId,
            "health" => 2,
            "points" => 1,
            "auto" => "none",
        ];

        $unit = new Unit($data);

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
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $user = User::get($userData["id"]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 3,
            "title" => "Save Unit",
            "points" => 3,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        $data = [
            "user_id" => $user->id,
            "type" => 3,
            "x" => 1,
            "y" => 2,
            "planet" => $planetId,
            "health" => 3,
            "points" => 3,
        ];

        $this->createTestCell(['x' => 1, 'y' => 2, 'planet' => $planetId]);
        $unit = new Unit($data);
        $unit->save();

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
        $this->assertEquals(3, $savedData["type"]);
    }

    /**
     * Тест обновления существующего юнита
     */
    public function testSaveUpdate(): void
    {
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $user = User::get($userData["id"]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 4,
            "title" => "Update Unit",
            "points" => 2,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        // Создаем юнит
        $data = [
            "user_id" => $user->id,
            "type" => 4,
            "x" => 3,
            "y" => 4,
            "planet" => $planetId,
            "health" => 3,
            "points" => 2,
        ];
        $this->createTestCell(['x' => 3, 'y' => 4, 'planet' => $planetId]);
        $unit = new Unit($data);
        $unit->save();
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
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $user = User::get($userData["id"]);

        // Создаем тип юнита
        $unitTypeData = [
            "id" => 5,
            "title" => "Title Unit",
            "points" => 1,
        ];
        MyDB::insert("unit_type", $unitTypeData);

        $data = [
            "user_id" => $user->id,
            "type" => 5,
            "x" => 6,
            "y" => 7,
            "planet" => $planetId,
        ];

        $unit = new Unit($data);

        $this->assertEquals("Title Unit", $unit->get_title());
    }

    /**
     * Тест метода remove
     */
    public function testRemove(): void
    {
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $gameData["id"]]);
        $userData = $this->createTestUser(["game" => $gameData["id"]]);
        $user = User::get($userData["id"]);

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
            "planet" => $planetId,
        ];
        $this->createTestCell(['x' => 8, 'y' => 9, 'planet' => $planetId]);
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
}
