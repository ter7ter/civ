<?php

namespace App\Tests;

use App\UnitType;
use App\BuildingType;
use App\ResearchType;

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
        // Очищаем кэш моделей
        UnitType::$all = [];
        BuildingType::$all = [];
        ResearchType::$all = [];
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
     * Тест создания типа исследования через прямой вызов функций
     */
    public function testResearchTypeCreationWorkflow(): void
    {
        // Создаем новый тип исследования
        $researchType = new ResearchType([]);
        $researchType->title = "Integration Test Research";
        $researchType->cost = 150;
        $researchType->requirements = [ResearchType::get(1), ResearchType::get(2)];
        $researchType->m_top = 100;
        $researchType->m_left = 200;
        $researchType->age = 1;
        $researchType->age_need = true;

        $researchType->save();

        // Проверяем, что он сохранен
        $this->assertDatabaseHas("research_type", ["title" => "Integration Test Research"]);

        // Получаем его из БД
        $retrieved = ResearchType::get($researchType->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals("Integration Test Research", $retrieved->title);
        $this->assertEquals([1, 2], array_map(fn($r) => $r->id, $retrieved->requirements));
        $this->assertEquals(100, $retrieved->m_top);
        $this->assertEquals(200, $retrieved->m_left);
        $this->assertEquals(1, $retrieved->age);
        $this->assertTrue($retrieved->age_need);

        // Обновляем
        $retrieved->cost = 200;
        $retrieved->save();

        $updated = ResearchType::get($researchType->id);
        $this->assertEquals(200, $updated->cost);

        // Удаляем
        $retrieved->delete();
        $this->assertDatabaseMissing("research_type", ["id" => $researchType->id]);
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
        // Очищаем кэш UnitType
        UnitType::$all = [];

        // Создаем тип с некорректным JSON
        $id = MyDB::insert('unit_type', [
            'title' => 'Invalid JSON Unit',
            'missions' => 'invalid json',
            'can_move' => 'also invalid',
        ]);

        $unit = UnitType::get($id);

        // Должен корректно обработать - некорректный JSON должен стать пустым массивом
        $this->assertIsArray($unit->missions);
        $this->assertIsArray($unit->can_move);
        // Проверяем, что некорректный JSON обработан в пустой массив
        $this->assertEquals([], $unit->missions); // Некорректный JSON -> пустой массив
        $this->assertEquals([], $unit->can_move); // Некорректный JSON -> пустой массив
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

        // Проверяем, что юнит создан в БД
        $this->assertDatabaseHas("unit_type", ["title" => "CRUD Test Unit"]);

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

        // Проверяем изменения в БД
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

        // Проверяем, что юнит удален
        $this->assertDatabaseMissing("unit_type", ["id" => $unitId]);
    }

    /**
     * Тест загрузки страницы production в админке
     */
    public function testProductionPageLoad(): void
    {
        $this->initializeGameTypes();

        // Проверяем, что страница production загружается без ошибок
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", ["page" => "production"]);

        $this->assertPageHasNoError($result);
        $this->assertStringContainsString("Название", $result["output"]); // Проверяем наличие формы
        $this->assertStringContainsString("Время производства", $result["output"]); // Проверяем наличие поля времени
    }

    /**
     * Тест загрузки страницы unit_types в админке
     */
    public function testUnitTypesPageLoad(): void
    {
        $this->initializeGameTypes();

        // Проверяем, что страница unit_types загружается без ошибок
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", ["page" => "unit_types"]);

        $this->assertPageHasNoError($result);
        // Проверяем наличие элементов страницы unit_types
        $this->assertStringContainsString("unit_types", $result["output"]); // Или другой маркер
    }

    /**
     * Тест загрузки страницы building_types в админке
     */
    public function testBuildingTypesPageLoad(): void
    {
        $this->initializeGameTypes();

        // Проверяем, что страница building_types загружается без ошибок
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", ["page" => "building_types"]);

        $this->assertPageHasNoError($result);
        // Проверяем наличие элементов страницы building_types
        $this->assertStringContainsString("building_types", $result["output"]); // Или другой маркер
    }

    /**
     * Тест загрузки страницы research_types в админке
     */
    public function testResearchTypesPageLoad(): void
    {
        $this->initializeGameTypes();

        // Проверяем, что страница research_types загружается без ошибок
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", ["page" => "research_types"]);

        $this->assertPageHasNoError($result);
        // Проверяем наличие элементов страницы research_types
        $this->assertStringContainsString("research_types", $result["output"]); // Или другой маркер
    }
}
