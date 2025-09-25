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
}
