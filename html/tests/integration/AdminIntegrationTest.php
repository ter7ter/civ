<?php

namespace App\Tests;

use App\Tests\Mocks\DatabaseTestAdapter;
use App\Tests\Base\FunctionalTestBase;
use App\UnitType;
use App\BuildingType;
use App\ResearchType;
use App\ResourceType;
use App\MyDB;
use App\Tests\Factory\TestDataFactory;

/**
 * Интеграционные тесты для админ-панели управления типами юнитов и построек
 */
class AdminIntegrationTest extends FunctionalTestBase
{

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
        $researchType->requirements = []; // Пустой массив требований
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
        $this->assertEquals([], $retrieved->requirements); // Пустой массив требований
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
        UnitType::clearCache();

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
        // Проверяем, что страница research_types загружается без ошибок
        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", ["page" => "research_types"]);

        $this->assertPageHasNoError($result);
        // Проверяем наличие элементов страницы research_types
        $this->assertStringContainsString("research_types", $result["output"]); // Или другой маркер
    }

    /**
     * Тест комплексного сценария редактирования типа постройки с требованиями
     */
    public function testBuildingTypeEditWorkflow(): void
    {
        // 1. Создание типа постройки
        $buildingData = [
            "page" => "building_types",
            "action" => "save",
            "title" => "Edit Building Test",
            "cost" => 100,
            "culture" => 5,
            "upkeep" => 2,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $buildingData);
        $this->assertPageHasNoError($result);

        // Проверяем, что постройка создана
        $this->assertDatabaseHas("building_type", ["title" => "Edit Building Test"]);

        $building = MyDB::query("SELECT * FROM building_type WHERE title = ?", ["Edit Building Test"], "row");
        $this->assertNotNull($building);
        $buildingId = $building["id"];

        // Создаем требуемые исследования и ресурсы для теста
        $requiredResearch = new ResearchType([]);
        $requiredResearch->title = "Required Research for Building";
        $requiredResearch->cost = 50;
        $requiredResearch->save();

        $requiredResource = new ResourceType([
            'id' => 'test_resource_building_' . time(),
            'title' => "Required Resource for Building",
            'type' => 'mineral',
            'work' => 1,
            'eat' => 0,
            'money' => 0,
            'chance' => 0.01,
            'min_amount' => 20,
            'max_amount' => 100,
        ]);
        $requiredResource->save();

        // 2. Редактирование через страницу админки с добавлением требований
        $editData = [
            "page" => "building_types",
            "action" => "save",
            "id" => $buildingId,
            "title" => "Edit Building Test Updated",
            "cost" => 120,
            "culture" => 8,
            "upkeep" => 3,
            "req_research" => [$requiredResearch->id],
            "req_resources" => [$requiredResource->id],
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $editData);
        $this->assertPageHasNoError($result);

        // Проверяем изменения в главной таблице
        $editedBuilding = MyDB::query("SELECT * FROM building_type WHERE id = ?", [$buildingId], "row");
        $this->assertEquals("Edit Building Test Updated", $editedBuilding["title"]);
        $this->assertEquals(120, $editedBuilding["cost"]);
        $this->assertEquals(8, $editedBuilding["culture"]);
        $this->assertEquals(3, $editedBuilding["upkeep"]);

        // Проверяем через объект (что требования загружаются правильно)
        $retrievedBuilding = BuildingType::get($buildingId);
        $this->assertCount(1, $retrievedBuilding->req_research);
        $this->assertEquals($requiredResearch->id, $retrievedBuilding->req_research[0]->id);
        $this->assertCount(1, $retrievedBuilding->req_resources);
        $this->assertEquals($requiredResource->id, $retrievedBuilding->req_resources[0]->id);

        // 3. Удаление
        $deleteData = [
            "page" => "building_types",
            "action" => "delete",
            "id" => $buildingId,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $deleteData);
        $this->assertPageHasNoError($result);

        // Проверяем, что постройка и требования удалены
        $this->assertDatabaseMissing("building_type", ["id" => $buildingId]);
        $this->assertDatabaseMissing("building_requirements_research", ["building_type_id" => $buildingId]);
        $this->assertDatabaseMissing("building_requirements_resources", ["building_type_id" => $buildingId]);

        // Очищаем тестовые данные
        $requiredResearch->delete();
        $requiredResource->delete();
    }

    /**
     * Тест комплексного сценария редактирования типа исследования с требованиями
     */
    public function testResearchTypeEditWorkflow(): void
    {
        // 1. Создание типа исследования
        $researchData = [
            "page" => "research_types",
            "action" => "save",
            "title" => "Edit Research Test",
            "cost" => 200,
            "m_top" => 50,
            "m_left" => 100,
            "age" => 2,
            "age_need" => 0, // без галочки
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $researchData);
        $this->assertPageHasNoError($result);

        // Проверяем, что исследование создано
        $this->assertDatabaseHas("research_type", ["title" => "Edit Research Test"]);

        $research = MyDB::query("SELECT * FROM research_type WHERE title = ?", ["Edit Research Test"], "row");
        $this->assertNotNull($research);
        $researchId = $research["id"];

        // Создаем требуемое исследование для теста
        $requiredResearch = new ResearchType([]);
        $requiredResearch->title = "Required Research for Test";
        $requiredResearch->cost = 75;
        $requiredResearch->save();

        // 2. Редактирование с добавлением требований
        $editData = [
            "page" => "research_types",
            "action" => "save",
            "id" => $researchId,
            "title" => "Edit Research Test Updated",
            "cost" => 250,
            "m_top" => 60,
            "m_left" => 110,
            "age" => 3,
            "age_need" => 1, // с галочкой
            "requirements" => [$requiredResearch->id],
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $editData);
        $this->assertPageHasNoError($result);

        // Проверяем изменения в главной таблице
        $editedResearch = MyDB::query("SELECT * FROM research_type WHERE id = ?", [$researchId], "row");
        $this->assertEquals("Edit Research Test Updated", $editedResearch["title"]);
        $this->assertEquals(250, $editedResearch["cost"]);
        $this->assertEquals(60, $editedResearch["m_top"]);
        $this->assertEquals(110, $editedResearch["m_left"]);
        $this->assertEquals(3, $editedResearch["age"]);
        $this->assertEquals(1, $editedResearch["age_need"]);

        // Проверяем через объект (что требования загружаются правильно)
        $retrievedResearch = ResearchType::get($researchId);
        $this->assertCount(1, $retrievedResearch->requirements);
        $this->assertEquals($requiredResearch->id, $retrievedResearch->requirements[0]->id);

        // 3. Удаление
        $deleteData = [
            "page" => "research_types",
            "action" => "delete",
            "id" => $researchId,
        ];

        $result = $this->executePage(PROJECT_ROOT . "/admin/index.php", $deleteData);
        $this->assertPageHasNoError($result);

        // Проверяем, что исследование и требования удалены
        $this->assertDatabaseMissing("research_type", ["id" => $researchId]);
        $this->assertDatabaseMissing("research_requirements", ["research_type_id" => $researchId]);

        // Очищаем тестовые данные
        $requiredResearch->delete();
    }
}
