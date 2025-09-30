<?php

namespace App\Tests;

use App\CellType;
use App\MyDB;
use App\Tests\Base\CommonTestBase;
use App\Tests\Base\TestGameDataInitializer;
use App\Tests\Factory\TestDataFactory;

/**
 * Тесты для класса CellType
 */
class CellTypeTest extends CommonTestBase
{
    /**
     * Тест получения существующего типа клетки
     */
    public function testGetExistingCellType(): void
    {
        TestGameDataInitializer::initializeCellTypes();

        TestDataFactory::createTestCellType([
            'id' => 'plains',
            'title' => 'равнина',
            'base_chance' => 15,
            'chance_inc1' => 8,
            'chance_inc2' => 6,
            'work' => 2,
            'eat' => 1,
            'money' => 1,
            'chance_inc_other' => ['other' => [1, 2]],
            'border_no' => ['border1', 'border2'],
        ]);

        $cellTypeGet = CellType::get('plains');

        $this->assertInstanceOf(CellType::class, $cellTypeGet);
        $this->assertEquals('plains', $cellTypeGet->id);
        $this->assertEquals('равнина', $cellTypeGet->title);
        $this->assertEquals(15, $cellTypeGet->base_chance);
        $this->assertEquals(8, $cellTypeGet->chance_inc1);
        $this->assertEquals(6, $cellTypeGet->chance_inc2);
        $this->assertEquals(2, $cellTypeGet->eat);
        $this->assertEquals(1, $cellTypeGet->work);
        $this->assertEquals(1, $cellTypeGet->money);
    }

    /**
     * Тест получения несуществующего типа клетки
     */
    public function testGetNonExistingCellType(): void
    {
        TestGameDataInitializer::initializeCellTypes();

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

        $cellType = TestDataFactory::createTestCellType($data);

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
        TestGameDataInitializer::initializeCellTypes();

        $cellType = TestDataFactory::createTestCellType([
            'id' => 'forest',
            'title' => 'лес']);

        $this->assertEquals('лес', $cellType->get_title());
    }

    /**
     * Тест всех предопределенных типов клеток
     */
    public function testAllPredefinedCellTypes(): void
    {
        TestGameDataInitializer::initializeCellTypes();

        $expectedTypes = [
            'plains' => ['title' => 'равнина', 'work' => 1, 'eat' => 2, 'money' => 1],
            'plains2' => ['title' => 'равнина', 'work' => 0, 'eat' => 2, 'money' => 1],
            'forest' => ['title' => 'лес', 'work' => 2, 'eat' => 1, 'money' => 1],
            'hills' => ['title' => 'холмы', 'work' => 2, 'eat' => 1, 'money' => 0],
            'mountains' => ['title' => 'горы', 'work' => 1, 'eat' => 0, 'money' => 1],
            'desert' => ['title' => 'пустыня', 'work' => 1, 'eat' => 0, 'money' => 2],
            'water1' => ['title' => 'вода', 'work' => 0, 'eat' => 2, 'money' => 1],
        ];

        foreach ($expectedTypes as $id => $expected) {
            $cellType = TestDataFactory::createTestCellType([
                'id' => $id,
                'title' => $expected['title'],
                'work' => $expected['work'],
                'eat' => $expected['eat'],
                'money' => $expected['money'],
            ]);
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
        TestGameDataInitializer::initializeCellTypes();

        $plains = CellType::get('plains');
        $this->assertIsArray($plains->chance_inc_other);
        $this->assertIsArray($plains->border_no);
        $this->assertContains('water2', $plains->border_no);
        $this->assertContains('water3', $plains->border_no);

        // Проверяем, что массивы не пустые для типов, которые их имеют
        $this->assertNotEmpty($plains->chance_inc_other);
    }
}
