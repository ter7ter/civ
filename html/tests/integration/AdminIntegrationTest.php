<?php

/**
 * Интеграционные тесты для админ-панели управления типами юнитов и построек
 */
class AdminIntegrationTest extends FunctionalTestBase
{
    protected function setUp(): void
    {
        DatabaseTestAdapter::resetTestDatabase();
        parent::setUp();
        $this->initializeGameTypes();
        $this->clearTestData();
    }

    /**
     * Тест создания типа юнита через прямой вызов функций
     */
    public function testUnitTypeCreationWorkflow(): void
    {
        // Создаем новый тип юнита
        $unitType = new UnitType([]);
        $unitType->title = "Integration Test Unit";
        $unitType->cost = 50;
        $unitType->type = "land";
        $unitType->attack = 5;
        $unitType->defence = 3;
        $unitType->missions = ["move_to", "attack"];

        $unitType->save();

        // Проверяем, что он сохранен
        $this->assertDatabaseHas("unit_type", ["title" => "Integration Test Unit"]);

        // Получаем его из БД
        $retrieved = UnitType::get($unitType->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals("Integration Test Unit", $retrieved->title);
        $this->assertEquals(["move_to", "attack"], $retrieved->missions);

        // Обновляем
        $retrieved->cost = 75;
        $retrieved->save();

        $updated = UnitType::get($unitType->id);
        $this->assertEquals(75, $updated->cost);

        // Удаляем
        $retrieved->delete();
        $this->assertDatabaseMissing("unit_type", ["id" => $unitType->id]);
    }

    /**
     * Тест создания типа постройки через прямой вызов функций
     */
    public function testBuildingTypeCreationWorkflow(): void
    {
        // Создаем новый тип постройки
        $buildingType = new BuildingType([]);
        $buildingType->title = "Integration Test Building";
        $buildingType->cost = 200;
        $buildingType->culture = 10;
        $buildingType->upkeep = 5;
        $buildingType->need_coastal = true;
        $buildingType->req_research = ["research1"];

        $buildingType->save();

        // Проверяем, что он сохранен
        $this->assertDatabaseHas("building_type", ["title" => "Integration Test Building"]);

        // Получаем его из БД
        $retrieved = BuildingType::get($buildingType->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals("Integration Test Building", $retrieved->title);
        $this->assertEquals(10, $retrieved->culture);
        $this->assertTrue($retrieved->need_coastal);
        $this->assertEquals(["research1"], $retrieved->req_research);

        // Обновляем
        $retrieved->culture = 15;
        $retrieved->save();

        $updated = BuildingType::get($buildingType->id);
        $this->assertEquals(15, $updated->culture);

        // Удаляем
        $retrieved->delete();
        $this->assertDatabaseMissing("building_type", ["id" => $buildingType->id]);
    }

    /**
     * Тест получения всех типов через статические методы
     */
    public function testGetAllTypes(): void
    {
        // Создаем несколько типов
        $unit1 = new UnitType([]);
        $unit1->title = "Unit 1";
        $unit1->save();

        $unit2 = new UnitType([]);
        $unit2->title = "Unit 2";
        $unit2->save();

        $building1 = new BuildingType([]);
        $building1->title = "Building 1";
        $building1->save();

        // Получаем все
        $allUnits = UnitType::getAll();
        $allBuildings = BuildingType::getAll();

        $this->assertGreaterThanOrEqual(2, count($allUnits));
        $this->assertGreaterThanOrEqual(1, count($allBuildings));

        // Проверяем, что наши типы есть в списке
        $unitTitles = array_column($allUnits, 'title');
        $buildingTitles = array_column($allBuildings, 'title');

        $this->assertContains("Unit 1", $unitTitles);
        $this->assertContains("Unit 2", $unitTitles);
        $this->assertContains("Building 1", $buildingTitles);
    }

    /**
     * Тест обработки некорректных данных
     */
    public function testInvalidDataHandling(): void
    {
        // Создаем тип с некорректным JSON
        MyDB::insert('unit_type', [
            'title' => 'Invalid JSON Unit',
            'missions' => 'invalid json',
            'can_move' => 'also invalid',
        ]);

        $unit = UnitType::get(MyDB::get()->lastInsertId());

        // Должен корректно обработать - некорректный JSON должен стать пустым массивом
        $this->assertIsArray($unit->missions);
        $this->assertIsArray($unit->can_move);
        // Проверяем, что массивы содержат значения по умолчанию (некорректный JSON обработан)
        $this->assertEquals([], $unit->missions);
        $this->assertEquals([], $unit->can_move);
    }

    /**
     * Тест комплексного сценария с несколькими типами
     */
    public function testComplexScenario(): void
    {
        // Создаем несколько типов юнитов
        $units = [];
        for ($i = 1; $i <= 3; $i++) {
            $unit = new UnitType([]);
            $unit->title = "Complex Unit $i";
            $unit->cost = 10 * $i;
            $unit->attack = $i;
            $unit->defence = $i + 1;
            $unit->missions = ["move_to"];
            if ($i == 1) {
                $unit->can_found_city = true;
            }
            $unit->save();
            $units[] = $unit;
        }

        // Создаем несколько типов построек
        $buildings = [];
        for ($i = 1; $i <= 2; $i++) {
            $building = new BuildingType([]);
            $building->title = "Complex Building $i";
            $building->cost = 50 * $i;
            $building->culture = 5 * $i;
            $building->req_research = ["research$i"];
            $building->save();
            $buildings[] = $building;
        }

        // Проверяем, что все сохранено
        $this->assertEquals(3, $this->getTableCount("unit_type"));
        $this->assertEquals(2, $this->getTableCount("building_type"));

        // Проверяем конкретные значения
        $foundUnit = UnitType::get($units[0]->id);
        $this->assertTrue($foundUnit->can_found_city);

        $foundBuilding = BuildingType::get($buildings[1]->id);
        $this->assertEquals(100, $foundBuilding->cost);
        $this->assertEquals(10, $foundBuilding->culture);
        $this->assertEquals(["research2"], $foundBuilding->req_research);

        // Очищаем
        foreach ($units as $unit) {
            $unit->delete();
        }
        foreach ($buildings as $building) {
            $building->delete();
        }

        $this->assertEquals(0, $this->getTableCount("unit_type"));
        $this->assertEquals(0, $this->getTableCount("building_type"));
    }

    /**
     * Тест добавления нового типа юнита через веб-интерфейс
     */
    public function testAddUnitTypeViaWeb(): void
    {
        $unitData = [
            "page" => "unit_types",
            "action" => "save",
            "title" => "Test Web Unit",
            "points" => 2,
            "cost" => 25,
            "population_cost" => 1,
            "type" => "land",
            "attack" => 3,
            "defence" => 2,
            "health" => 1,
            "movement" => 1,
            "upkeep" => 1,
            "can_found_city" => "1",
            "can_build" => "0",
            "description" => "Unit created via web interface",
            "age" => 1,
            "missions" => "move_to,attack",
            "can_move" => '{"plains":1,"forest":2}',
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $unitData);

        $this->assertPageHasNoError($result);

        // Проверяем редирект
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        // Проверяем, что юнит создан в БД
        $this->assertDatabaseHas("unit_type", ["title" => "Test Web Unit"]);

        $unit = MyDB::query("SELECT * FROM unit_type WHERE title = ?", ["Test Web Unit"], "row");
        $this->assertNotNull($unit);
        $this->assertEquals(2, $unit["points"]);
        $this->assertEquals(25, $unit["cost"]);
        $this->assertEquals("land", $unit["type"]);
        $this->assertEquals(3, $unit["attack"]);
        $this->assertEquals(2, $unit["defence"]);
        $this->assertEquals(1, $unit["can_found_city"]);
        $this->assertEquals(0, $unit["can_build"]);
    }

    /**
     * Тест добавления нового типа постройки через веб-интерфейс
     */
    public function testAddBuildingTypeViaWeb(): void
    {
        $buildingData = [
            "page" => "building_types",
            "action" => "save",
            "title" => "Test Web Building",
            "cost" => 100,
            "need_coastal" => "1",
            "culture" => 5,
            "upkeep" => 2,
            "culture_bonus" => 50,
            "research_bonus" => 25,
            "money_bonus" => 30,
            "description" => "Building created via web interface",
            "req_research" => '["research1","research2"]',
            "req_resources" => '["resource1"]',
            "need_research" => '["need1"]',
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $buildingData);

        $this->assertPageHasNoError($result);

        // Проверяем редирект
        $this->assertPageRedirects($result, "index.php?page=building_types");

        // Проверяем, что постройка создана в БД
        $this->assertDatabaseHas("building_type", ["title" => "Test Web Building"]);

        $building = MyDB::query("SELECT * FROM building_type WHERE title = ?", ["Test Web Building"], "row");
        $this->assertNotNull($building);
        $this->assertEquals(100, $building["cost"]);
        $this->assertEquals(1, $building["need_coastal"]);
        $this->assertEquals(5, $building["culture"]);
        $this->assertEquals(2, $building["upkeep"]);
        $this->assertEquals(50, $building["culture_bonus"]);
        $this->assertEquals(25, $building["research_bonus"]);
        $this->assertEquals(30, $building["money_bonus"]);
    }

    /**
     * Тест редактирования типа юнита через веб-интерфейс
     */
    public function testEditUnitTypeViaWeb(): void
    {
        // Сначала создаем юнит
        $unitType = new UnitType([]);
        $unitType->title = "Original Unit";
        $unitType->cost = 20;
        $unitType->save();

        $unitId = $unitType->id;

        // Теперь редактируем через веб
        $editData = [
            "page" => "unit_types",
            "action" => "save",
            "id" => $unitId,
            "title" => "Edited Unit",
            "points" => 3,
            "cost" => 30,
            "population_cost" => 2,
            "type" => "water",
            "attack" => 4,
            "defence" => 3,
            "health" => 2,
            "movement" => 2,
            "upkeep" => 2,
            "can_found_city" => "0",
            "can_build" => "1",
            "description" => "Edited via web interface",
            "age" => 2,
            "missions" => "move_to,build_road",
            "can_move" => '{"water1":1,"water2":1}',
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $editData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        // Проверяем изменения в БД
        $updatedUnit = MyDB::query("SELECT * FROM unit_type WHERE id = ?", [$unitId], "row");
        $this->assertEquals("Edited Unit", $updatedUnit["title"]);
        $this->assertEquals(3, $updatedUnit["points"]);
        $this->assertEquals(30, $updatedUnit["cost"]);
        $this->assertEquals("water", $updatedUnit["type"]);
        $this->assertEquals(4, $updatedUnit["attack"]);
        $this->assertEquals(3, $updatedUnit["defence"]);
        $this->assertEquals(0, $updatedUnit["can_found_city"]);
        $this->assertEquals(1, $updatedUnit["can_build"]);
    }

    /**
     * Тест редактирования типа постройки через веб-интерфейс
     */
    public function testEditBuildingTypeViaWeb(): void
    {
        // Сначала создаем постройку
        $buildingType = new BuildingType([]);
        $buildingType->title = "Original Building";
        $buildingType->cost = 50;
        $buildingType->culture = 2;
        $buildingType->save();

        $buildingId = $buildingType->id;

        // Теперь редактируем через веб
        $editData = [
            "page" => "building_types",
            "action" => "save",
            "id" => $buildingId,
            "title" => "Edited Building",
            "cost" => 75,
            "need_coastal" => "0",
            "culture" => 4,
            "upkeep" => 3,
            "culture_bonus" => 100,
            "research_bonus" => 50,
            "money_bonus" => 75,
            "description" => "Edited via web interface",
            "req_research" => '["research3"]',
            "req_resources" => '["resource2","resource3"]',
            "need_research" => '["need2","need3"]',
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $editData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=building_types");

        // Проверяем изменения в БД
        $updatedBuilding = MyDB::query("SELECT * FROM building_type WHERE id = ?", [$buildingId], "row");
        $this->assertEquals("Edited Building", $updatedBuilding["title"]);
        $this->assertEquals(75, $updatedBuilding["cost"]);
        $this->assertEquals(0, $updatedBuilding["need_coastal"]);
        $this->assertEquals(4, $updatedBuilding["culture"]);
        $this->assertEquals(3, $updatedBuilding["upkeep"]);
        $this->assertEquals(100, $updatedBuilding["culture_bonus"]);
        $this->assertEquals(50, $updatedBuilding["research_bonus"]);
        $this->assertEquals(75, $updatedBuilding["money_bonus"]);
    }

    /**
     * Тест удаления типа юнита через веб-интерфейс
     */
    public function testDeleteUnitTypeViaWeb(): void
    {
        // Создаем юнит для удаления
        $unitType = new UnitType([]);
        $unitType->title = "Unit To Delete";
        $unitType->save();

        $unitId = $unitType->id;

        // Удаляем через веб
        $deleteData = [
            "page" => "unit_types",
            "action" => "delete",
            "id" => $unitId,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $deleteData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        // Проверяем, что юнит удален
        $this->assertDatabaseMissing("unit_type", ["id" => $unitId]);
    }

    /**
     * Тест удаления типа постройки через веб-интерфейс
     */
    public function testDeleteBuildingTypeViaWeb(): void
    {
        // Создаем постройку для удаления
        $buildingType = new BuildingType([]);
        $buildingType->title = "Building To Delete";
        $buildingType->save();

        $buildingId = $buildingType->id;

        // Удаляем через веб
        $deleteData = [
            "page" => "building_types",
            "action" => "delete",
            "id" => $buildingId,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $deleteData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=building_types");

        // Проверяем, что постройка удалена
        $this->assertDatabaseMissing("building_type", ["id" => $buildingId]);
    }

    /**
     * Тест валидации данных при добавлении типа юнита
     */
    public function testUnitTypeValidation(): void
    {
        // Попытка создать юнит без названия
        $invalidData = [
            "page" => "unit_types",
            "action" => "save",
            "title" => "", // Пустое название
            "points" => 1,
            "cost" => 10,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $invalidData);

        // Проверяем, что форма вернулась с ошибкой (нет редиректа)
        $this->assertStringContainsString("Unit Types", $result["output"]);
        $this->assertStringContainsString("Add New Unit Type", $result["output"]);

        // Проверяем, что юнит не создан
        $this->assertEquals(0, $this->getTableCount("unit_type"));
    }

    /**
     * Тест валидации данных при добавлении типа постройки
     */
    public function testBuildingTypeValidation(): void
    {
        // Попытка создать постройку без названия
        $invalidData = [
            "page" => "building_types",
            "action" => "save",
            "title" => "", // Пустое название
            "cost" => 50,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $invalidData);

        // Проверяем, что форма вернулась с ошибкой (нет редиректа)
        $this->assertStringContainsString("Building Types", $result["output"]);
        $this->assertStringContainsString("Add New Building Type", $result["output"]);

        // Проверяем, что постройка не создана
        $this->assertEquals(0, $this->getTableCount("building_type"));
    }

    /**
     * Тест обработки некорректного JSON в веб-интерфейсе
     */
    public function testInvalidJsonHandlingViaWeb(): void
    {
        $invalidData = [
            "page" => "unit_types",
            "action" => "save",
            "title" => "Invalid JSON Unit",
            "missions" => "invalid json string",
            "can_move" => "also invalid",
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $invalidData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        // Проверяем, что юнит создан с пустыми массивами вместо некорректного JSON
        $this->assertDatabaseHas("unit_type", ["title" => "Invalid JSON Unit"]);

        $unit = MyDB::query("SELECT * FROM unit_type WHERE title = ?", ["Invalid JSON Unit"], "row");
        $this->assertEquals("[]", $unit["missions"]); // Пустой JSON массив
        $this->assertEquals("[]", $unit["can_move"]); // Пустой JSON массив
    }

    /**
     * Тест просмотра формы редактирования типа юнита
     */
    public function testEditUnitTypeForm(): void
    {
        // Создаем юнит
        $unitType = new UnitType([]);
        $unitType->title = "Form Test Unit";
        $unitType->cost = 15;
        $unitType->type = "air";
        $unitType->save();

        // Запрашиваем форму редактирования
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", [
            "page" => "unit_types",
            "action" => "edit",
            "id" => $unitType->id,
        ]);

        $this->assertPageHasNoError($result);
        $this->assertStringContainsString("Form Test Unit", $result["output"]);
        $this->assertStringContainsString("air", $result["output"]);
        $this->assertStringContainsString("Save", $result["output"]);
        $this->assertStringContainsString("Cancel", $result["output"]);
    }

    /**
     * Тест просмотра формы редактирования типа постройки
     */
    public function testEditBuildingTypeForm(): void
    {
        // Создаем постройку
        $buildingType = new BuildingType([]);
        $buildingType->title = "Form Test Building";
        $buildingType->cost = 75;
        $buildingType->culture = 3;
        $buildingType->save();

        // Запрашиваем форму редактирования
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", [
            "page" => "building_types",
            "action" => "edit",
            "id" => $buildingType->id,
        ]);

        $this->assertPageHasNoError($result);
        $this->assertStringContainsString("Form Test Building", $result["output"]);
        $this->assertStringContainsString("75", $result["output"]);
        $this->assertStringContainsString("3", $result["output"]);
        $this->assertStringContainsString("Save", $result["output"]);
        $this->assertStringContainsString("Cancel", $result["output"]);
    }

    /**
     * Тест попытки редактирования несуществующего типа юнита
     */
    public function testEditNonExistentUnitType(): void
    {
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", [
            "page" => "unit_types",
            "action" => "edit",
            "id" => 99999,
        ]);

        $this->assertStringContainsString("Unit type not found", $result["output"]);
    }

    /**
     * Тест попытки редактирования несуществующего типа постройки
     */
    public function testEditNonExistentBuildingType(): void
    {
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", [
            "page" => "building_types",
            "action" => "edit",
            "id" => 99999,
        ]);

        $this->assertStringContainsString("Building type not found", $result["output"]);
    }

    /**
     * Тест комплексного сценария: создание, редактирование, удаление
     */
    public function testFullCrudScenario(): void
    {
        // 1. Создание
        $createData = [
            "page" => "unit_types",
            "action" => "save",
            "title" => "CRUD Test Unit",
            "cost" => 20,
            "type" => "land",
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $createData);
        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        $unit = MyDB::query("SELECT * FROM unit_type WHERE title = ?", ["CRUD Test Unit"], "row");
        $this->assertNotNull($unit);
        $unitId = $unit["id"];

        // 2. Редактирование
        $editData = [
            "page" => "unit_types",
            "action" => "save",
            "id" => $unitId,
            "title" => "CRUD Test Unit Edited",
            "cost" => 25,
            "type" => "water",
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $editData);
        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        $editedUnit = MyDB::query("SELECT * FROM unit_type WHERE id = ?", [$unitId], "row");
        $this->assertEquals("CRUD Test Unit Edited", $editedUnit["title"]);
        $this->assertEquals(25, $editedUnit["cost"]);
        $this->assertEquals("water", $editedUnit["type"]);

        // 3. Удаление
        $deleteData = [
            "page" => "unit_types",
            "action" => "delete",
            "id" => $unitId,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $deleteData);
        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        $this->assertDatabaseMissing("unit_type", ["id" => $unitId]);
    }

    /**
     * Тест добавления нового типа юнита через веб-интерфейс
     */
    public function testAddUnitTypeViaWeb(): void
    {
        $unitData = [
            "page" => "unit_types",
            "action" => "save",
            "title" => "Test Web Unit",
            "points" => 2,
            "cost" => 25,
            "population_cost" => 1,
            "type" => "land",
            "attack" => 3,
            "defence" => 2,
            "health" => 1,
            "movement" => 1,
            "upkeep" => 1,
            "can_found_city" => "1",
            "can_build" => "0",
            "description" => "Unit created via web interface",
            "age" => 1,
            "missions" => "move_to,attack",
            "can_move" => '{"plains":1,"forest":2}',
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $unitData);

        $this->assertPageHasNoError($result);

        // Проверяем редирект
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        // Проверяем, что юнит создан в БД
        $this->assertDatabaseHas("unit_type", ["title" => "Test Web Unit"]);

        $unit = MyDB::query("SELECT * FROM unit_type WHERE title = ?", ["Test Web Unit"], "row");
        $this->assertNotNull($unit);
        $this->assertEquals(2, $unit["points"]);
        $this->assertEquals(25, $unit["cost"]);
        $this->assertEquals("land", $unit["type"]);
        $this->assertEquals(3, $unit["attack"]);
        $this->assertEquals(2, $unit["defence"]);
        $this->assertEquals(1, $unit["can_found_city"]);
        $this->assertEquals(0, $unit["can_build"]);
    }

    /**
     * Тест добавления нового типа постройки через веб-интерфейс
     */
    public function testAddBuildingTypeViaWeb(): void
    {
        $buildingData = [
            "page" => "building_types",
            "action" => "save",
            "title" => "Test Web Building",
            "cost" => 100,
            "need_coastal" => "1",
            "culture" => 5,
            "upkeep" => 2,
            "culture_bonus" => 50,
            "research_bonus" => 25,
            "money_bonus" => 30,
            "description" => "Building created via web interface",
            "req_research" => '["research1","research2"]',
            "req_resources" => '["resource1"]',
            "need_research" => '["need1"]',
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $buildingData);

        $this->assertPageHasNoError($result);

        // Проверяем редирект
        $this->assertPageRedirects($result, "index.php?page=building_types");

        // Проверяем, что постройка создана в БД
        $this->assertDatabaseHas("building_type", ["title" => "Test Web Building"]);

        $building = MyDB::query("SELECT * FROM building_type WHERE title = ?", ["Test Web Building"], "row");
        $this->assertNotNull($building);
        $this->assertEquals(100, $building["cost"]);
        $this->assertEquals(1, $building["need_coastal"]);
        $this->assertEquals(5, $building["culture"]);
        $this->assertEquals(2, $building["upkeep"]);
        $this->assertEquals(50, $building["culture_bonus"]);
        $this->assertEquals(25, $building["research_bonus"]);
        $this->assertEquals(30, $building["money_bonus"]);
    }

    /**
     * Тест редактирования типа юнита через веб-интерфейс
     */
    public function testEditUnitTypeViaWeb(): void
    {
        // Сначала создаем юнит
        $unitType = new UnitType([]);
        $unitType->title = "Original Unit";
        $unitType->cost = 20;
        $unitType->save();

        $unitId = $unitType->id;

        // Теперь редактируем через веб
        $editData = [
            "page" => "unit_types",
            "action" => "save",
            "id" => $unitId,
            "title" => "Edited Unit",
            "points" => 3,
            "cost" => 30,
            "population_cost" => 2,
            "type" => "water",
            "attack" => 4,
            "defence" => 3,
            "health" => 2,
            "movement" => 2,
            "upkeep" => 2,
            "can_found_city" => "0",
            "can_build" => "1",
            "description" => "Edited via web interface",
            "age" => 2,
            "missions" => "move_to,build_road",
            "can_move" => '{"water1":1,"water2":1}',
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $editData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        // Проверяем изменения в БД
        $updatedUnit = MyDB::query("SELECT * FROM unit_type WHERE id = ?", [$unitId], "row");
        $this->assertEquals("Edited Unit", $updatedUnit["title"]);
        $this->assertEquals(3, $updatedUnit["points"]);
        $this->assertEquals(30, $updatedUnit["cost"]);
        $this->assertEquals("water", $updatedUnit["type"]);
        $this->assertEquals(4, $updatedUnit["attack"]);
        $this->assertEquals(3, $updatedUnit["defence"]);
        $this->assertEquals(0, $updatedUnit["can_found_city"]);
        $this->assertEquals(1, $updatedUnit["can_build"]);
    }

    /**
     * Тест редактирования типа постройки через веб-интерфейс
     */
    public function testEditBuildingTypeViaWeb(): void
    {
        // Сначала создаем постройку
        $buildingType = new BuildingType([]);
        $buildingType->title = "Original Building";
        $buildingType->cost = 50;
        $buildingType->culture = 2;
        $buildingType->save();

        $buildingId = $buildingType->id;

        // Теперь редактируем через веб
        $editData = [
            "page" => "building_types",
            "action" => "save",
            "id" => $buildingId,
            "title" => "Edited Building",
            "cost" => 75,
            "need_coastal" => "0",
            "culture" => 4,
            "upkeep" => 3,
            "culture_bonus" => 100,
            "research_bonus" => 50,
            "money_bonus" => 75,
            "description" => "Edited via web interface",
            "req_research" => '["research3"]',
            "req_resources" => '["resource2","resource3"]',
            "need_research" => '["need2","need3"]',
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $editData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=building_types");

        // Проверяем изменения в БД
        $updatedBuilding = MyDB::query("SELECT * FROM building_type WHERE id = ?", [$buildingId], "row");
        $this->assertEquals("Edited Building", $updatedBuilding["title"]);
        $this->assertEquals(75, $updatedBuilding["cost"]);
        $this->assertEquals(0, $updatedBuilding["need_coastal"]);
        $this->assertEquals(4, $updatedBuilding["culture"]);
        $this->assertEquals(3, $updatedBuilding["upkeep"]);
        $this->assertEquals(100, $updatedBuilding["culture_bonus"]);
        $this->assertEquals(50, $updatedBuilding["research_bonus"]);
        $this->assertEquals(75, $updatedBuilding["money_bonus"]);
    }

    /**
     * Тест удаления типа юнита через веб-интерфейс
     */
    public function testDeleteUnitTypeViaWeb(): void
    {
        // Создаем юнит для удаления
        $unitType = new UnitType([]);
        $unitType->title = "Unit To Delete";
        $unitType->save();

        $unitId = $unitType->id;

        // Удаляем через веб
        $deleteData = [
            "page" => "unit_types",
            "action" => "delete",
            "id" => $unitId,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $deleteData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        // Проверяем, что юнит удален
        $this->assertDatabaseMissing("unit_type", ["id" => $unitId]);
    }

    /**
     * Тест удаления типа постройки через веб-интерфейс
     */
    public function testDeleteBuildingTypeViaWeb(): void
    {
        // Создаем постройку для удаления
        $buildingType = new BuildingType([]);
        $buildingType->title = "Building To Delete";
        $buildingType->save();

        $buildingId = $buildingType->id;

        // Удаляем через веб
        $deleteData = [
            "page" => "building_types",
            "action" => "delete",
            "id" => $buildingId,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $deleteData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=building_types");

        // Проверяем, что постройка удалена
        $this->assertDatabaseMissing("building_type", ["id" => $buildingId]);
    }

    /**
     * Тест валидации данных при добавлении типа юнита
     */
    public function testUnitTypeValidation(): void
    {
        // Попытка создать юнит без названия
        $invalidData = [
            "page" => "unit_types",
            "action" => "save",
            "title" => "", // Пустое название
            "points" => 1,
            "cost" => 10,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $invalidData);

        // Проверяем, что форма вернулась с ошибкой (нет редиректа)
        $this->assertStringContainsString("Unit Types", $result["output"]);
        $this->assertStringContainsString("Add New Unit Type", $result["output"]);

        // Проверяем, что юнит не создан
        $this->assertEquals(0, $this->getTableCount("unit_type"));
    }

    /**
     * Тест валидации данных при добавлении типа постройки
     */
    public function testBuildingTypeValidation(): void
    {
        // Попытка создать постройку без названия
        $invalidData = [
            "page" => "building_types",
            "action" => "save",
            "title" => "", // Пустое название
            "cost" => 50,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $invalidData);

        // Проверяем, что форма вернулась с ошибкой (нет редиректа)
        $this->assertStringContainsString("Building Types", $result["output"]);
        $this->assertStringContainsString("Add New Building Type", $result["output"]);

        // Проверяем, что постройка не создана
        $this->assertEquals(0, $this->getTableCount("building_type"));
    }

    /**
     * Тест обработки некорректного JSON в веб-интерфейсе
     */
    public function testInvalidJsonHandlingViaWeb(): void
    {
        $invalidData = [
            "page" => "unit_types",
            "action" => "save",
            "title" => "Invalid JSON Unit",
            "missions" => "invalid json string",
            "can_move" => "also invalid",
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $invalidData);

        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        // Проверяем, что юнит создан с пустыми массивами вместо некорректного JSON
        $this->assertDatabaseHas("unit_type", ["title" => "Invalid JSON Unit"]);

        $unit = MyDB::query("SELECT * FROM unit_type WHERE title = ?", ["Invalid JSON Unit"], "row");
        $this->assertEquals("[]", $unit["missions"]); // Пустой JSON массив
        $this->assertEquals("[]", $unit["can_move"]); // Пустой JSON массив
    }

    /**
     * Тест просмотра формы редактирования типа юнита
     */
    public function testEditUnitTypeForm(): void
    {
        // Создаем юнит
        $unitType = new UnitType([]);
        $unitType->title = "Form Test Unit";
        $unitType->cost = 15;
        $unitType->type = "air";
        $unitType->save();

        // Запрашиваем форму редактирования
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", [
            "page" => "unit_types",
            "action" => "edit",
            "id" => $unitType->id,
        ]);

        $this->assertPageHasNoError($result);
        $this->assertStringContainsString("Form Test Unit", $result["output"]);
        $this->assertStringContainsString("air", $result["output"]);
        $this->assertStringContainsString("Save", $result["output"]);
        $this->assertStringContainsString("Cancel", $result["output"]);
    }

    /**
     * Тест просмотра формы редактирования типа постройки
     */
    public function testEditBuildingTypeForm(): void
    {
        // Создаем постройку
        $buildingType = new BuildingType([]);
        $buildingType->title = "Form Test Building";
        $buildingType->cost = 75;
        $buildingType->culture = 3;
        $buildingType->save();

        // Запрашиваем форму редактирования
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", [
            "page" => "building_types",
            "action" => "edit",
            "id" => $buildingType->id,
        ]);

        $this->assertPageHasNoError($result);
        $this->assertStringContainsString("Form Test Building", $result["output"]);
        $this->assertStringContainsString("75", $result["output"]);
        $this->assertStringContainsString("3", $result["output"]);
        $this->assertStringContainsString("Save", $result["output"]);
        $this->assertStringContainsString("Cancel", $result["output"]);
    }

    /**
     * Тест попытки редактирования несуществующего типа юнита
     */
    public function testEditNonExistentUnitType(): void
    {
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", [
            "page" => "unit_types",
            "action" => "edit",
            "id" => 99999,
        ]);

        $this->assertStringContainsString("Unit type not found", $result["output"]);
    }

    /**
     * Тест попытки редактирования несуществующего типа постройки
     */
    public function testEditNonExistentBuildingType(): void
    {
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", [
            "page" => "building_types",
            "action" => "edit",
            "id" => 99999,
        ]);

        $this->assertStringContainsString("Building type not found", $result["output"]);
    }

    /**
     * Тест комплексного сценария: создание, редактирование, удаление
     */
    public function testFullCrudScenario(): void
    {
        // 1. Создание
        $createData = [
            "page" => "unit_types",
            "action" => "save",
            "title" => "CRUD Test Unit",
            "cost" => 20,
            "type" => "land",
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $createData);
        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        $unit = MyDB::query("SELECT * FROM unit_type WHERE title = ?", ["CRUD Test Unit"], "row");
        $this->assertNotNull($unit);
        $unitId = $unit["id"];

        // 2. Редактирование
        $editData = [
            "page" => "unit_types",
            "action" => "save",
            "id" => $unitId,
            "title" => "CRUD Test Unit Edited",
            "cost" => 25,
            "type" => "water",
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $editData);
        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        $editedUnit = MyDB::query("SELECT * FROM unit_type WHERE id = ?", [$unitId], "row");
        $this->assertEquals("CRUD Test Unit Edited", $editedUnit["title"]);
        $this->assertEquals(25, $editedUnit["cost"]);
        $this->assertEquals("water", $editedUnit["type"]);

        // 3. Удаление
        $deleteData = [
            "page" => "unit_types",
            "action" => "delete",
            "id" => $unitId,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $deleteData);
        $this->assertPageHasNoError($result);
        $this->assertPageRedirects($result, "index.php?page=unit_types");

        $this->assertDatabaseMissing("unit_type", ["id" => $unitId]);
    }
}
