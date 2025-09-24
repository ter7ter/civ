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
}
