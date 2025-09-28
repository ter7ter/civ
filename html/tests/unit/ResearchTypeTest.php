<?php

namespace App\Tests;

use App\ResearchType;

/**
 * Тесты для класса ResearchType
 */
class ResearchTypeTest extends TestBase
{
    /**
     * Тест получения существующего типа исследования
     * @small
     */
    public function testGetExistingResearchType(): void
    {
        // Создаем тестовый research_type вручную
        $researchType = new ResearchType([
            'title' => 'Test Research',
            'cost' => 100,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $researchType->save();
        $id = $researchType->id;

        $retrieved = ResearchType::get($id);

        $this->assertInstanceOf(ResearchType::class, $retrieved);
        $this->assertEquals($id, $retrieved->id);
        $this->assertEquals('Test Research', $retrieved->title);
        $this->assertEquals(100, $retrieved->cost);
        $this->assertEquals(1, $retrieved->age);
        $this->assertTrue($retrieved->age_need);

        // Очищаем
        $researchType->delete();
    }

    /**
     * Тест получения несуществующего типа исследования
     * @small
     */
    public function testGetNonExistingResearchType(): void
    {
        $this->initializeGameTypes();

        $researchType = ResearchType::get(999);

        $this->assertFalse($researchType);
    }

    /**
     * Тест конструктора ResearchType
     * @small
     */
    public function testConstructor(): void
    {
        $data = [
            'id' => 100,
            'title' => 'Test Research',
            'cost' => 200,
            'age' => 2,
            'age_need' => false,
            'm_top' => 50,
            'm_left' => 100,
        ];

        $researchType = new ResearchType($data);

        $this->assertEquals(100, $researchType->id);
        $this->assertEquals('Test Research', $researchType->title);
        $this->assertEquals(200, $researchType->cost);
        $this->assertEquals(2, $researchType->age);
        $this->assertFalse($researchType->age_need);
        $this->assertEquals(50, $researchType->m_top);
        $this->assertEquals(100, $researchType->m_left);
        $this->assertIsArray($researchType->requirements);
        // В данном случае requirements еще не загружены из БД (пустой список)
        $this->assertEmpty($researchType->requirements);

        // Проверяем, что объект добавлен в кэш
        $this->assertSame($researchType, ResearchType::get(100));
    }

    /**
     * Тест конструктора без id
     * @small
     */
    public function testConstructorWithoutId(): void
    {
        $data = [
            'title' => 'No ID Research',
            'cost' => 150,
        ];

        $researchType = new ResearchType($data);

        $this->assertNull($researchType->id);
        $this->assertEquals('No ID Research', $researchType->title);
        $this->assertEquals(150, $researchType->cost);
    }

    /**
     * Тест метода get_title
     * @small
     */
    public function testGetTitle(): void
    {
        // Создаем тестовый research_type
        $researchType = new ResearchType([
            'title' => 'Test Title Research',
            'cost' => 100,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $researchType->save();

        $this->assertEquals('Test Title Research', $researchType->get_title());

        // Очищаем
        $researchType->delete();
    }

    /**
     * Тест метода get_turn_count
     * @small
     */
    public function testGetTurnCount(): void
    {
        // Создаем тестовый research_type
        $researchType = new ResearchType([
            'title' => 'Test Turn Research',
            'cost' => 50,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $researchType->save();

        // Нормальный случай
        $turns = $researchType->get_turn_count(12); // 50 / 12 = 4.166, ceil(4.166) = 5
        $this->assertEquals(5, $turns);

        // Слишком мало - минимум 4
        $turns = $researchType->get_turn_count(25); // 50 / 25 = 2, но минимум 4
        $this->assertEquals(4, $turns);

        // Слишком много - максимум 50
        $turns = $researchType->get_turn_count(1); // 50 / 1 = 50, максимум 50
        $this->assertEquals(50, $turns);

        // Нулевой amount
        $turns = $researchType->get_turn_count(0);
        $this->assertFalse($turns);

        // Очищаем
        $researchType->delete();
    }

    /**
     * Тест метода get_need_age_ids
     * @small
     */
    public function testGetNeedAgeIds(): void
    {
        // Создаем тестовые research_types
        $rt1 = new ResearchType([
            'title' => 'Test Age 1 Need',
            'cost' => 50,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $rt1->save();

        $rt2 = new ResearchType([
            'title' => 'Test Age 1 No Need',
            'cost' => 50,
            'age' => 1,
            'age_need' => false,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $rt2->save();

        $rt3 = new ResearchType([
            'title' => 'Test Age 2 Need',
            'cost' => 50,
            'age' => 2,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $rt3->save();

        $age1Ids = ResearchType::get_need_age_ids(1);
        $this->assertIsArray($age1Ids);
        $this->assertContains($rt1->id, $age1Ids);
        $this->assertNotContains($rt2->id, $age1Ids);

        $age2Ids = ResearchType::get_need_age_ids(2);
        $this->assertIsArray($age2Ids);
        $this->assertContains($rt3->id, $age2Ids);

        $age3Ids = ResearchType::get_need_age_ids(3);
        $this->assertIsArray($age3Ids);
        $this->assertEmpty($age3Ids);

        // Очищаем
        $rt1->delete();
        $rt2->delete();
        $rt3->delete();
    }

    /**
     * Тест зависимостей исследований
     * @small
     */
    public function testResearchRequirements(): void
    {
        // Создаем тестовые research_types с requirements
        $rt1 = new ResearchType([
            'title' => 'Test Req 1',
            'cost' => 50,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $rt1->save();

        $rt2 = new ResearchType([
            'title' => 'Test Req 2',
            'cost' => 50,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $rt2->requirements = [$rt1];
        $rt2->save();

        // Проверяем, что у исследований есть массив requirements
        $research1 = ResearchType::get($rt1->id);
        $this->assertIsArray($research1->requirements);
        $this->assertEmpty($research1->requirements);

        $research2 = ResearchType::get($rt2->id);
        $this->assertIsArray($research2->requirements);
        $this->assertCount(1, $research2->requirements);
        $this->assertEquals($rt1->id, $research2->requirements[0]->id);

        // Очищаем
        $rt2->delete();
        $rt1->delete();
    }

    /**
     * Тест всех предопределенных типов исследований
     * @small
     */
    public function testAllPredefinedResearchTypes(): void
    {
        $this->initializeGameTypes();

        // Проверяем, что все исследования загружены
        $all = ResearchType::getAllCached();
        $this->assertGreaterThan(10, count($all));

        // Проверяем, что все имеют правильные свойства
        foreach ($all as $research) {
            $this->assertIsInt($research->id);
            $this->assertIsString($research->title);
            $this->assertIsInt($research->cost);
            $this->assertIsInt($research->age);
            $this->assertIsBool($research->age_need);
        }
    }

    /**
     * Тест метода getAll
     * @small
     */
    public function testGetAll(): void
    {
        $this->initializeGameTypes();

        // Проверяем статический кэш
        $this->assertGreaterThan(10, count(ResearchType::getAllCached()));

        // Проверяем, что все элементы являются экземплярами ResearchType
        foreach (ResearchType::getAllCached() as $researchType) {
            $this->assertInstanceOf(ResearchType::class, $researchType);
            $this->assertIsInt($researchType->id);
            $this->assertIsString($researchType->title);
            $this->assertIsInt($researchType->cost);
        }

        // Проверяем метод getAll (должен возвращать данные из БД, но для статических данных может быть пустым)
        $allResearchTypes = ResearchType::getAll();
        $this->assertIsArray($allResearchTypes);
        // Не проверяем count, так как статические данные могут не быть в БД
    }

    /**
     * Тест метода save для нового объекта
     * @small
     */
    public function testSaveNewResearchType(): void
    {
        $this->initializeGameTypes();

        $researchType = new ResearchType([]);
        $researchType->title = 'Unit Test Research';
        $researchType->cost = 300;
        $researchType->requirements = [];
        $researchType->m_top = 150;
        $researchType->m_left = 250;
        $researchType->age = 1;
        $researchType->age_need = true;

        $researchType->save();

        // Проверяем, что ID был присвоен
        $this->assertIsInt($researchType->id);
        $this->assertGreaterThan(0, $researchType->id);

        // Проверяем, что объект сохранен в БД
        $this->assertDatabaseHas('research_type', [
            'id' => $researchType->id,
            'title' => 'Unit Test Research',
            'cost' => 300,
            'age' => 1,
            'age_need' => 1,
        ]);

        // Проверяем, что объект в кэше
        $this->assertSame($researchType, ResearchType::get($researchType->id));

        // Очищаем
        $researchType->delete();
    }

    /**
     * Тест метода save для обновления существующего объекта
     * @small
     */
    public function testSaveExistingResearchType(): void
    {
        $this->initializeGameTypes();

        // Создаем новый объект
        $researchType = new ResearchType([]);
        $researchType->title = 'Update Test Research';
        $researchType->cost = 200;
        $researchType->save();

        $originalId = $researchType->id;

        // Обновляем
        $researchType->title = 'Updated Research';
        $researchType->cost = 250;
        $researchType->age = 2;
        $researchType->save();

        // Проверяем, что ID не изменился
        $this->assertEquals($originalId, $researchType->id);

        // Проверяем обновление в БД
        $this->assertDatabaseHas('research_type', [
            'id' => $originalId,
            'title' => 'Updated Research',
            'cost' => 250,
            'age' => 2,
        ]);

        // Очищаем
        $researchType->delete();
    }

    /**
     * Тест метода delete
     * @small
     */
    public function testDeleteResearchType(): void
    {
        $this->initializeGameTypes();

        // Создаем объект
        $researchType = new ResearchType([]);
        $researchType->title = 'Delete Test Research';
        $researchType->cost = 100;
        $researchType->save();

        $id = $researchType->id;

        // Проверяем, что объект существует
        $this->assertDatabaseHas('research_type', ['id' => $id]);

        // Удаляем
        $researchType->delete();

        // Проверяем, что объект удален из БД
        $this->assertDatabaseMissing('research_type', ['id' => $id]);

        // Проверяем, что объект удален из кэша
        $this->assertFalse(ResearchType::get($id));
    }

    /**
     * Тест обработки требований исследований
     * @small
     */
    public function testResearchRequirementsObjects(): void
    {
        // Создаем тестовые research_types с requirements
        $rt1 = new ResearchType([
            'title' => 'Test Req Base',
            'cost' => 50,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $rt1->save();

        $rt2 = new ResearchType([
            'title' => 'Test Req 2',
            'cost' => 50,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $rt2->addRequirement($rt1);
        $rt2->save();

        $rt3 = new ResearchType([
            'title' => 'Test Req 3',
            'cost' => 50,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ]);
        $rt3->addRequirement($rt1);
        $rt3->addRequirement($rt2);
        $rt3->save();

        // Проверяем requirements
        $research1 = ResearchType::get($rt1->id);
        $this->assertIsArray($research1->requirements);
        $this->assertEmpty($research1->requirements);

        $research2 = ResearchType::get($rt2->id);
        $this->assertIsArray($research2->requirements);
        $this->assertCount(1, $research2->requirements);
        $this->assertEquals($rt1->id, $research2->requirements[0]->id);

        $research3 = ResearchType::get($rt3->id);
        $this->assertIsArray($research3->requirements);
        $this->assertCount(2, $research3->requirements);
        $reqIds = array_map(fn ($r) => $r->id, $research3->requirements);
        $this->assertContains($rt1->id, $reqIds);
        $this->assertContains($rt2->id, $reqIds);

        // Очищаем
        $rt3->delete();
        $rt2->delete();
        $rt1->delete();
    }
}
