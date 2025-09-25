<?php

require_once __DIR__ . '/../bootstrap.php';

class AdminBuildingTypeTest extends TestBase
{
    public function testGetAllBuildingTypes()
    {
        // Создаем тестовые данные
        $buildingTypeData = [
            'title' => 'Test Library',
            'cost' => 50,
            'req_research' => json_encode([]),
            'req_resources' => json_encode([]),
            'need_coastal' => 0,
            'culture' => 2,
            'upkeep' => 1,
            'need_research' => json_encode([]),
            'culture_bonus' => 50,
            'research_bonus' => 0,
            'money_bonus' => 0,
            'description' => 'Test building',
        ];

        MyDB::insert('building_type', $buildingTypeData);

        $buildingTypes = BuildingType::getAll();

        $this->assertIsArray($buildingTypes);
        $this->assertGreaterThan(0, count($buildingTypes));

        $found = false;
        foreach ($buildingTypes as $bt) {
            if ($bt->title === 'Test Library') {
                $found = true;
                $this->assertEquals(50, $bt->cost);
                $this->assertEquals(2, $bt->culture);
                $this->assertEquals(1, $bt->upkeep);
                break;
            }
        }
        $this->assertTrue($found, 'Test building type not found');
    }

    public function testSaveNewBuildingType()
    {
        $buildingType = new BuildingType([]);
        $buildingType->title = 'New Test Building';
        $buildingType->cost = 100;
        $buildingType->culture = 3;
        $buildingType->upkeep = 2;
        $buildingType->need_coastal = true;
        $buildingType->req_research = ['research1'];

        $buildingType->save();

        $this->assertNotNull($buildingType->id);
        $this->assertDatabaseHas('building_type', ['id' => $buildingType->id, 'title' => 'New Test Building']);
    }

    public function testUpdateBuildingType()
    {
        // Создаем тип постройки
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Update Test Building';
        $buildingType->cost = 75;
        $buildingType->save();

        $originalId = $buildingType->id;

        // Обновляем
        $buildingType->title = 'Updated Test Building';
        $buildingType->cost = 150;
        $buildingType->culture = 5;
        $buildingType->save();

        $this->assertEquals($originalId, $buildingType->id);
        $this->assertDatabaseHas('building_type', ['id' => $buildingType->id, 'title' => 'Updated Test Building', 'cost' => 150, 'culture' => 5]);
    }

    public function testDeleteBuildingType()
    {
        // Создаем тип постройки
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Delete Test Building';
        $buildingType->save();

        $id = $buildingType->id;

        // Удаляем
        $buildingType->delete();

        $this->assertDatabaseMissing('building_type', ['id' => $id]);
    }

    public function testGetBuildingTypeById()
    {
        // Создаем тип постройки
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Get Test Building';
        $buildingType->save();

        $retrieved = BuildingType::get($buildingType->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($buildingType->title, $retrieved->title);
        $this->assertEquals($buildingType->id, $retrieved->id);
    }

    public function testJsonFieldsHandling()
    {
        $buildingType = new BuildingType([]);
        $buildingType->title = 'JSON Test Building';
        $buildingType->req_research = ['research1', 'research2'];
        $buildingType->req_resources = ['resource1'];
        $buildingType->need_research = ['need1'];
        $buildingType->save();

        $retrieved = BuildingType::get($buildingType->id);

        $this->assertEquals(['research1', 'research2'], $retrieved->req_research);
        $this->assertEquals(['resource1'], $retrieved->req_resources);
        $this->assertEquals(['need1'], $retrieved->need_research);
    }

    public function testBuildingTypeProperties()
    {
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Property Test';
        $buildingType->cost = 200;
        $buildingType->culture = 10;
        $buildingType->upkeep = 5;
        $buildingType->need_coastal = true;
        $buildingType->culture_bonus = 100;
        $buildingType->research_bonus = 25;
        $buildingType->money_bonus = 50;
        $buildingType->description = 'Test description';
        $buildingType->save();

        $retrieved = BuildingType::get($buildingType->id);

        $this->assertEquals(200, $retrieved->cost);
        $this->assertEquals(10, $retrieved->culture);
        $this->assertEquals(5, $retrieved->upkeep);
        $this->assertTrue($retrieved->need_coastal);
        $this->assertEquals(100, $retrieved->culture_bonus);
        $this->assertEquals(25, $retrieved->research_bonus);
        $this->assertEquals(50, $retrieved->money_bonus);
        $this->assertEquals('Test description', $retrieved->description);
    }
}
