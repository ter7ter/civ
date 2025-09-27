<?php

require_once __DIR__ . '/../bootstrap.php';

class AdminBuildingTypeTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeGameTypes();
    }

    public function testGetAllBuildingTypes()
    {
        // Создаем тестовые данные
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Test Library';
        $buildingType->cost = 50;
        $buildingType->req_research = [];
        $buildingType->req_resources = [];
        $buildingType->need_coastal = false;
        $buildingType->culture = 2;
        $buildingType->upkeep = 1;
        $buildingType->need_research = [];
        $buildingType->culture_bonus = 50;
        $buildingType->research_bonus = 0;
        $buildingType->money_bonus = 0;
        $buildingType->description = 'Test building';
        $buildingType->save();

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

    public function testGetNonExistentBuildingType()
    {
        $result = BuildingType::get(99999);
        $this->assertFalse($result);
    }

    public function testBuildingTypeConstructorWithInvalidJson()
    {
        // Создаем запись с некорректным JSON
        MyDB::insert('building_type', [
            'title' => 'Invalid JSON Building Test',
            'req_research' => 'invalid json',
            'req_resources' => 'also invalid',
        ]);

        $buildingType = BuildingType::get(MyDB::get()->lastInsertId());

        // Должен обработать некорректный JSON как null или пустой массив
        $this->assertIsArray($buildingType->req_research);
        $this->assertIsArray($buildingType->req_resources);
    }

    public function testBuildingTypeDefaultValues()
    {
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Defaults Building Test';
        $buildingType->save();

        $retrieved = BuildingType::get($buildingType->id);

        $this->assertEquals(0, $retrieved->cost);
        $this->assertEquals(0, $retrieved->culture);
        $this->assertEquals(0, $retrieved->upkeep);
        $this->assertFalse($retrieved->need_coastal);
        $this->assertEquals(0, $retrieved->culture_bonus);
        $this->assertEquals(0, $retrieved->research_bonus);
        $this->assertEquals(0, $retrieved->money_bonus);
        $this->assertEquals('', $retrieved->description);
        $this->assertIsArray($retrieved->req_research);
        $this->assertIsArray($retrieved->req_resources);
        $this->assertIsArray($retrieved->need_research);
    }

    public function testBuildingTypeWithComplexData()
    {
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Complex Building Test';
        $buildingType->cost = 500;
        $buildingType->culture = 20;
        $buildingType->upkeep = 10;
        $buildingType->need_coastal = true;
        $buildingType->culture_bonus = 200;
        $buildingType->research_bonus = 100;
        $buildingType->money_bonus = 150;
        $buildingType->description = 'Advanced building with complex properties';
        $buildingType->req_research = ['research1', 'research2', 'research3'];
        $buildingType->req_resources = ['resource1', 'resource2'];
        $buildingType->need_research = ['need1', 'need2'];
        $buildingType->save();

        $retrieved = BuildingType::get($buildingType->id);

        $this->assertEquals('Complex Building Test', $retrieved->title);
        $this->assertEquals(500, $retrieved->cost);
        $this->assertEquals(20, $retrieved->culture);
        $this->assertEquals(10, $retrieved->upkeep);
        $this->assertTrue($retrieved->need_coastal);
        $this->assertEquals(200, $retrieved->culture_bonus);
        $this->assertEquals(100, $retrieved->research_bonus);
        $this->assertEquals(150, $retrieved->money_bonus);
        $this->assertEquals('Advanced building with complex properties', $retrieved->description);
        $this->assertEquals(['research1', 'research2', 'research3'], $retrieved->req_research);
        $this->assertEquals(['resource1', 'resource2'], $retrieved->req_resources);
        $this->assertEquals(['need1', 'need2'], $retrieved->need_research);
    }

    public function testBuildingTypeCityEffect()
    {
        $this->markTestIncomplete('Тест недоделан - дублирует BuildingTypeTest.php');
        return;
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Effect Test Building';
        $buildingType->id = 2; // Амбар
        $buildingType->save();

        // Создаем тестового пользователя, планету и город
        $userData = $this->createTestUser();
        $planetId = $this->createTestPlanet();
        $city = $this->createTestCity(['user_id' => $userData['id'], 'planet' => $planetId]);

        // Применяем эффект здания
        $buildingType->city_effect($city);

        // Проверяем, что эффект применился (для амбара eat_up должен уменьшиться)
        $this->assertEquals(10, $city->eat_up); // BASE_EAT_UP / 2 = 20 / 2 = 10
    }

    public function testBuildingTypeGetTitle()
    {
        $buildingType = new BuildingType([]);
        $buildingType->title = 'Title Test Building';
        $buildingType->save();

        $this->assertEquals('Title Test Building', $buildingType->get_title());
    }
}
