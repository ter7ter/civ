<?php

/**
 * Тесты для класса ResearchType
 */
class ResearchTypeTest extends TestBase
{
    /**
     * Тест получения существующего типа исследования
     */
    public function testGetExistingResearchType(): void
    {
        $this->initializeGameTypes();

        $researchType = ResearchType::get(1);

        $this->assertInstanceOf(ResearchType::class, $researchType);
        $this->assertEquals(1, $researchType->id);
        $this->assertEquals('Гончарное дело', $researchType->title);
        $this->assertEquals(50, $researchType->cost);
        $this->assertEquals(1, $researchType->age);
        $this->assertTrue($researchType->age_need);
    }

    /**
     * Тест получения несуществующего типа исследования
     */
    public function testGetNonExistingResearchType(): void
    {
        $this->initializeGameTypes();

        $researchType = ResearchType::get(999);

        $this->assertFalse($researchType);
    }

    /**
     * Тест конструктора ResearchType
     */
    public function testConstructor(): void
    {
        $data = [
            'id' => 100,
            'title' => 'Test Research',
            'cost' => 200,
            'age' => 2,
            'age_need' => false,
            'requirements' => [1, 2],
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
        $this->assertCount(2, $researchType->requirements);
        // Проверяем, что requirements содержит объекты ResearchType
        foreach ($researchType->requirements as $req) {
            $this->assertInstanceOf(ResearchType::class, $req);
        }

        // Проверяем, что объект добавлен в кэш
        $this->assertSame($researchType, ResearchType::get(100));
    }

    /**
     * Тест конструктора без id
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
     */
    public function testGetTitle(): void
    {
        $this->initializeGameTypes();

        $researchType = ResearchType::get(2);

        $this->assertEquals('Бронзовое дело', $researchType->get_title());
    }

    /**
     * Тест метода get_turn_count
     */
    public function testGetTurnCount(): void
    {
        $this->initializeGameTypes();

        $researchType = ResearchType::get(1); // cost = 50

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
    }

    /**
     * Тест метода get_need_age_ids
     */
    public function testGetNeedAgeIds(): void
    {
        $this->initializeGameTypes();

        $age1Ids = ResearchType::get_need_age_ids(1);
        $this->assertIsArray($age1Ids);
        $this->assertContains(1, $age1Ids); // Гончарное дело
        $this->assertContains(2, $age1Ids); // Бронзовое дело
        $this->assertNotContains(20, $age1Ids); // Республика имеет age_need = false

        $age2Ids = ResearchType::get_need_age_ids(2);
        $this->assertIsArray($age2Ids);
        // В TestGameDataInitializer нет исследований для эпохи 2

        $age3Ids = ResearchType::get_need_age_ids(3);
        $this->assertIsArray($age3Ids);
        $this->assertEmpty($age3Ids); // Нет исследований для 3 эпохи
    }

    /**
     * Тест зависимостей исследований
     */
    public function testResearchRequirements(): void
    {
        $this->initializeGameTypes();

        // Проверяем, что у исследований есть массив requirements
        $research1 = ResearchType::get(1);
        $this->assertIsArray($research1->requirements);

        $research2 = ResearchType::get(2);
        $this->assertIsArray($research2->requirements);
    }

    /**
     * Тест всех предопределенных типов исследований
     */
    public function testAllPredefinedResearchTypes(): void
    {
        $this->initializeGameTypes();

        // Проверяем, что все исследования загружены
        $this->assertGreaterThan(10, count(ResearchType::$all));

        // Проверяем некоторые конкретные
        $research1 = ResearchType::get(1);
        $this->assertEquals('Гончарное дело', $research1->title);
        $this->assertEquals(50, $research1->cost);
        $this->assertEquals(1, $research1->age);

        $research2 = ResearchType::get(2);
        $this->assertEquals('Бронзовое дело', $research2->title);
        $this->assertEquals(80, $research2->cost);
        $this->assertEquals(1, $research2->age);
    }

    /**
     * Тест метода getAll
     */
    public function testGetAll(): void
    {
        $this->initializeGameTypes();

        // Проверяем статический кэш
        $this->assertGreaterThan(10, count(ResearchType::$all));

        // Проверяем, что все элементы являются экземплярами ResearchType
        foreach (ResearchType::$all as $researchType) {
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
     */
    public function testSaveNewResearchType(): void
    {
        $this->initializeGameTypes();

        $researchType = new ResearchType([]);
        $researchType->title = 'Unit Test Research';
        $researchType->cost = 300;
        $researchType->requirements = [1, 2];
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
     * Тест обработки JSON полей в конструкторе
     */
    public function testJsonFieldsHandling(): void
    {
        $this->initializeGameTypes();

        // Создаем запись в БД с JSON полем
        $id = MyDB::insert('research_type', [
            'title' => 'JSON Test Research',
            'cost' => 150,
            'requirements' => '[1, 2, 3]',
            'age' => 1,
        ]);

        $researchType = ResearchType::get($id);

        // Проверяем, что JSON правильно распарсился
        $this->assertIsArray($researchType->requirements);
        // Проверяем, что requirements содержит 3 объекта (JSON распарсился)
        $this->assertCount(3, $researchType->requirements);
        // Проверяем, что значения - объекты ResearchType
        foreach ($researchType->requirements as $req) {
            $this->assertInstanceOf(ResearchType::class, $req);
        }

        // Очищаем
        $researchType->delete();
    }

    /**
     * Тест обработки некорректного JSON в конструкторе
     */
    public function testInvalidJsonHandling(): void
    {
        // Создаем запись с некорректным JSON
        $id = MyDB::insert('research_type', [
            'title' => 'Invalid JSON Test',
            'cost' => 100,
            'requirements' => 'invalid json',
        ]);

        $researchType = ResearchType::get($id);

        // Должен корректно обработать - некорректный JSON должен стать пустым массивом
        $this->assertIsArray($researchType->requirements);
        $this->assertEquals([], $researchType->requirements);

        // Очищаем
        $researchType->delete();
    }
}
