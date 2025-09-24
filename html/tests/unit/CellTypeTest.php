<?php

require_once __DIR__ . "/../bootstrap.php";

/**
 * Тесты для класса CellType
 */
class CellTypeTest extends TestBase
{
    /**
     * Тест получения существующего типа клетки
     */
    public function testGetExistingCellType(): void
    {
        $this->initializeGameTypes();

        $cellType = CellType::get('plains');

        $this->assertInstanceOf(CellType::class, $cellType);
        $this->assertEquals('plains', $cellType->id);
        $this->assertEquals('Равнины', $cellType->title);
        $this->assertEquals(15, $cellType->base_chance);
        $this->assertEquals(8, $cellType->chance_inc1);
        $this->assertEquals(6, $cellType->chance_inc2);
        $this->assertEquals(2, $cellType->eat);
        $this->assertEquals(1, $cellType->work);
        $this->assertEquals(0, $cellType->money);
    }

    /**
     * Тест получения несуществующего типа клетки
     */
    public function testGetNonExistingCellType(): void
    {
        $this->initializeGameTypes();

        $cellType = CellType::get('nonexistent');

        $this->assertFalse($cellType);
    }

    /**
     * Тест конструктора CellType
     */
    public function testConstructor(): void
    {
        $data = [
            'id' => 'test_type',
            'title' => 'Test Type',
            'base_chance' => 10,
            'chance_inc1' => 5,
            'chance_inc2' => 3,
            'work' => 2,
            'eat' => 1,
            'money' => 3,
            'chance_inc_other' => ['other' => [1, 2]],
            'border_no' => ['border1', 'border2'],
        ];

        $cellType = new CellType($data);

        $this->assertEquals('test_type', $cellType->id);
        $this->assertEquals('Test Type', $cellType->title);
        $this->assertEquals(10, $cellType->base_chance);
        $this->assertEquals(5, $cellType->chance_inc1);
        $this->assertEquals(3, $cellType->chance_inc2);
        $this->assertEquals(2, $cellType->work);
        $this->assertEquals(1, $cellType->eat);
        $this->assertEquals(3, $cellType->money);
        $this->assertEquals(['other' => [1, 2]], $cellType->chance_inc_other);
        $this->assertEquals(['border1', 'border2'], $cellType->border_no);

        // Проверяем, что объект добавлен в кэш
        $this->assertSame($cellType, CellType::get('test_type'));
    }

    /**
     * Тест конструктора без id
     */
    public function testConstructorWithoutId(): void
    {
        $data = [
            'title' => 'No ID Type',
            'base_chance' => 5,
        ];

        $cellType = new CellType($data);

        $this->assertNull($cellType->id);
        $this->assertEquals('No ID Type', $cellType->title);
        $this->assertEquals(5, $cellType->base_chance);
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $this->initializeGameTypes();

        $cellType = CellType::get('forest');

        $this->assertEquals('Лес', $cellType->get_title());
    }

    /**
     * Тест всех предопределенных типов клеток
     */
    public function testAllPredefinedCellTypes(): void
    {
        $this->initializeGameTypes();

        $expectedTypes = [
            'plains' => ['title' => 'Равнины', 'work' => 1, 'eat' => 2, 'money' => 0],
            'plains2' => ['title' => 'Равнины 2', 'work' => 2, 'eat' => 2, 'money' => 0],
            'forest' => ['title' => 'Лес', 'work' => 2, 'eat' => 1, 'money' => 0],
            'hills' => ['title' => 'Холмы', 'work' => 1, 'eat' => 1, 'money' => 0],
            'mountains' => ['title' => 'Горы', 'work' => 1, 'eat' => 0, 'money' => 0],
            'desert' => ['title' => 'Пустыня', 'work' => 0, 'eat' => 0, 'money' => 1],
            'water' => ['title' => 'Вода', 'work' => 0, 'eat' => 2, 'money' => 2],
            'water1' => ['title' => 'Прибрежная вода', 'work' => 0, 'eat' => 3, 'money' => 2],
            'tundra' => ['title' => 'Тундра', 'work' => 1, 'eat' => 1, 'money' => 0],
        ];

        foreach ($expectedTypes as $id => $expected) {
            $cellType = CellType::get($id);
            $this->assertInstanceOf(CellType::class, $cellType, "Cell type {$id} should exist");
            $this->assertEquals($expected['title'], $cellType->title, "Title for cell type {$id}");
            $this->assertEquals($expected['work'], $cellType->work, "Work for cell type {$id}");
            $this->assertEquals($expected['eat'], $cellType->eat, "Eat for cell type {$id}");
            $this->assertEquals($expected['money'], $cellType->money, "Money for cell type {$id}");
        }
    }

    /**
     * Тест свойств chance_inc_other и border_no
     */
    public function testComplexProperties(): void
    {
        $this->initializeGameTypes();

        $plains = CellType::get('plains');
        $this->assertIsArray($plains->chance_inc_other);
        $this->assertIsArray($plains->border_no);
        $this->assertContains('water2', $plains->border_no);
        $this->assertContains('water3', $plains->border_no);

        // Проверяем, что массивы не пустые для типов, которые их имеют
        $this->assertNotEmpty($plains->chance_inc_other);
    }
}
