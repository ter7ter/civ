<?php

require_once __DIR__ . '/../bootstrap.php';

class AdminUnitTypeTest extends TestBase
{
    public function testGetAllUnitTypes()
    {
        // Создаем тестовые данные
        $unitTypeData = [
            'title' => 'Test Warrior',
            'points' => 1,
            'cost' => 10,
            'population_cost' => 1,
            'type' => 'land',
            'attack' => 2,
            'defence' => 1,
            'health' => 1,
            'movement' => 1,
            'upkeep' => 0,
            'can_found_city' => 0,
            'can_build' => 0,
            'need_research' => json_encode([]),
            'description' => 'Test unit',
            'mission_points' => json_encode([]),
            'age' => 1,
            'missions' => json_encode(['move_to']),
            'req_research' => json_encode([]),
            'req_resources' => json_encode([]),
            'can_move' => json_encode(['plains' => 1]),
        ];

        MyDB::insert('unit_type', $unitTypeData);

        $unitTypes = UnitType::getAll();

        $this->assertIsArray($unitTypes);
        $this->assertGreaterThan(0, count($unitTypes));

        $found = false;
        foreach ($unitTypes as $ut) {
            if ($ut->title === 'Test Warrior') {
                $found = true;
                $this->assertEquals(1, $ut->points);
                $this->assertEquals(10, $ut->cost);
                $this->assertEquals('land', $ut->type);
                break;
            }
        }
        $this->assertTrue($found, 'Test unit type not found');
    }

    public function testSaveNewUnitType()
    {
        $unitType = new UnitType([]);
        $unitType->title = 'New Test Unit';
        $unitType->points = 2;
        $unitType->cost = 20;
        $unitType->type = 'water';
        $unitType->attack = 1;
        $unitType->defence = 1;
        $unitType->missions = ['move_to', 'attack'];
        $unitType->can_move = ['water1' => 1];

        $unitType->save();

        $this->assertNotNull($unitType->id);
        $this->assertDatabaseHas('unit_type', ['id' => $unitType->id, 'title' => 'New Test Unit']);
    }

    public function testUpdateUnitType()
    {
        // Создаем юнит тип
        $unitType = new UnitType([]);
        $unitType->title = 'Update Test';
        $unitType->cost = 15;
        $unitType->save();

        $originalId = $unitType->id;

        // Обновляем
        $unitType->title = 'Updated Test';
        $unitType->cost = 25;
        $unitType->save();

        $this->assertEquals($originalId, $unitType->id);
        $this->assertDatabaseHas('unit_type', ['id' => $unitType->id, 'title' => 'Updated Test', 'cost' => 25]);
    }

    public function testDeleteUnitType()
    {
        // Создаем юнит тип
        $unitType = new UnitType([]);
        $unitType->title = 'Delete Test';
        $unitType->save();

        $id = $unitType->id;

        // Удаляем
        $unitType->delete();

        $this->assertDatabaseMissing('unit_type', ['id' => $id]);
    }

    public function testGetUnitTypeById()
    {
        // Создаем юнит тип
        $unitType = new UnitType([]);
        $unitType->title = 'Get Test';
        $unitType->save();

        $retrieved = UnitType::get($unitType->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($unitType->title, $retrieved->title);
        $this->assertEquals($unitType->id, $retrieved->id);
    }

    public function testJsonFieldsHandling()
    {
        $unitType = new UnitType([]);
        $unitType->title = 'JSON Test';
        $unitType->missions = ['move_to', 'build_city'];
        $unitType->can_move = ['plains' => 1, 'forest' => 2];
        $unitType->req_research = ['research1', 'research2'];
        $unitType->save();

        $retrieved = UnitType::get($unitType->id);

        $this->assertEquals(['move_to', 'build_city'], $retrieved->missions);
        $this->assertEquals(['plains' => 1, 'forest' => 2], $retrieved->can_move);
        $this->assertEquals(['research1', 'research2'], $retrieved->req_research);
    }

    public function testGetNonExistentUnitType()
    {
        $result = UnitType::get(99999);
        $this->assertFalse($result);
    }

    public function testUnitTypeConstructorWithInvalidJson()
    {
        // Создаем запись с некорректным JSON
        MyDB::insert('unit_type', [
            'title' => 'Invalid JSON Test',
            'missions' => 'invalid json',
            'can_move' => 'also invalid',
        ]);

        $unitType = UnitType::get(MyDB::get()->lastInsertId());

        // Должен обработать некорректный JSON как null или пустой массив
        $this->assertIsArray($unitType->missions);
        $this->assertIsArray($unitType->can_move);
    }

    public function testUnitTypeDefaultValues()
    {
        $unitType = new UnitType([]);
        $unitType->title = 'Defaults Test';
        $unitType->save();

        $retrieved = UnitType::get($unitType->id);

        $this->assertEquals(1, $retrieved->points);
        $this->assertEquals(0, $retrieved->cost);
        $this->assertEquals('land', $retrieved->type);
        $this->assertEquals(0, $retrieved->attack);
        $this->assertEquals(0, $retrieved->defence);
        $this->assertFalse($retrieved->can_found_city);
        $this->assertFalse($retrieved->can_build);
        $this->assertEquals(['move_to'], $retrieved->missions);
    }

    public function testUnitTypeWithComplexData()
    {
        $unitType = new UnitType([]);
        $unitType->title = 'Complex Test';
        $unitType->points = 3;
        $unitType->cost = 100;
        $unitType->population_cost = 3;
        $unitType->type = 'air';
        $unitType->attack = 5;
        $unitType->defence = 3;
        $unitType->health = 2;
        $unitType->movement = 4;
        $unitType->upkeep = 2;
        $unitType->can_found_city = true;
        $unitType->can_build = true;
        $unitType->description = 'Advanced unit with complex properties';
        $unitType->age = 2;
        $unitType->missions = ['move_to', 'attack', 'defend', 'special_ability'];
        $unitType->can_move = ['plains' => 1, 'forest' => 1, 'hills' => 1, 'mountains' => 1, 'desert' => 1, 'city' => 1];
        $unitType->req_research = ['research1', 'research2', 'research3'];
        $unitType->req_resources = ['resource1', 'resource2'];
        $unitType->need_research = ['need1'];
        $unitType->save();

        $retrieved = UnitType::get($unitType->id);

        $this->assertEquals('Complex Test', $retrieved->title);
        $this->assertEquals(3, $retrieved->points);
        $this->assertEquals(100, $retrieved->cost);
        $this->assertEquals(3, $retrieved->population_cost);
        $this->assertEquals('air', $retrieved->type);
        $this->assertEquals(5, $retrieved->attack);
        $this->assertEquals(3, $retrieved->defence);
        $this->assertEquals(2, $retrieved->health);
        $this->assertEquals(4, $retrieved->movement);
        $this->assertEquals(2, $retrieved->upkeep);
        $this->assertTrue($retrieved->can_found_city);
        $this->assertTrue($retrieved->can_build);
        $this->assertEquals('Advanced unit with complex properties', $retrieved->description);
        $this->assertEquals(2, $retrieved->age);
        $this->assertEquals(['move_to', 'attack', 'defend', 'special_ability'], $retrieved->missions);
        $this->assertEquals(['plains' => 1, 'forest' => 1, 'hills' => 1, 'mountains' => 1, 'desert' => 1, 'city' => 1], $retrieved->can_move);
        $this->assertEquals(['research1', 'research2', 'research3'], $retrieved->req_research);
        $this->assertEquals(['resource1', 'resource2'], $retrieved->req_resources);
        $this->assertEquals(['need1'], $retrieved->need_research);
    }
}
