<?php

namespace App\Tests;

require_once __DIR__ . "/../bootstrap.php";

use App\Tests\Base\TestGameDataInitializer;
use App\Tests\Factory\TestDataFactory;
use App\UnitType;
use App\Tests\base\CommonTestBase;

/**
 * Тесты для класса UnitType
 */
class UnitTypeTest extends CommonTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        TestGameDataInitializer::initializeCellTypes();
    }
    /**
     * Тест получения существующего типа юнита
     */
    public function testGetExistingUnitType(): void
    {
        // Ensure default unit types exist
        $unitSettlerType = TestDataFactory::createTestUnitType([
            'title' => 'Поселенец',
            'cost' => 40,
            'attack' => 0,
            'defence' => 1,
            'health' => 1,
            'movement' => 1,
            'can_found_city' => true,
            'missions' => ['move_to'],
        ]);
        $unitWarriorType = TestDataFactory::createTestUnitType([
            'title' => 'Воин',
        ]);

        $unitTypeGet = UnitType::get($unitWarriorType->id);

        $this->assertInstanceOf(UnitType::class, $unitTypeGet);
        $this->assertEquals($unitSettlerType->id, $unitTypeGet->id);
        $this->assertEquals("Поселенец", $unitTypeGet->title);
        $this->assertEquals(40, $unitTypeGet->cost);
        $this->assertEquals(0, $unitTypeGet->attack);
        $this->assertEquals(1, $unitTypeGet->defence);
        $this->assertTrue($unitTypeGet->can_found_city);
    }

    /**
     * Тест получения несуществующего типа юнита
     */
    public function testGetNonExistingUnitType(): void
    {
        $unitType = UnitType::get(999);

        $this->assertFalse($unitType);
    }

    /**
     * Тест конструктора с базовыми данными
     */
    public function testConstructBasic(): void
    {
        $data = [
            "title" => "Test Unit",
            "cost" => 50,
            "attack" => 2,
            "defence" => 3,
            "type" => "land",
            "points" => 2,
        ];

        $unitType = new UnitType($data);

        $this->assertEquals("Test Unit", $unitType->title);
        $this->assertEquals(50, $unitType->cost);
        $this->assertEquals(2, $unitType->attack);
        $this->assertEquals(3, $unitType->defence);
        $this->assertEquals("land", $unitType->type);
        $this->assertEquals(2, $unitType->points);
    }

    /**
     * Тест конструктора с массивом can_move
     */
    public function testConstructWithCanMove(): void
    {
        $data = [
            "title" => "Water Unit",
            "can_move" => [
                "water1" => 1,
                "water2" => 1,
                "city" => 1,
            ],
        ];

        $unitType = new UnitType($data);

        $this->assertEquals("Water Unit", $unitType->title);
        $this->assertIsArray($unitType->can_move);
        $this->assertEquals(1, $unitType->can_move["water1"]);
        $this->assertEquals(1, $unitType->can_move["water2"]);
        $this->assertEquals(1, $unitType->can_move["city"]);
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $unitType = TestDataFactory::createTestUnitType([
            'title' => 'Воин',
        ]);

        $this->assertEquals("Воин", $unitType->getTitle());
    }

    /**
     * Тест кеширования в статическом массиве $all
     */
    public function testCaching(): void
    {
        $unitType = TestDataFactory::createTestUnitType();
        // Получить юнит из кеша
        $unitType1 = UnitType::get($unitType->id);
        $unitType2 = UnitType::get($unitType->id);

        // Должен быть один и тот же объект
        $this->assertSame($unitType1, $unitType2);
    }

    /**
     * Тест свойств по умолчанию
     */
    public function testDefaultProperties(): void
    {
        $data = [
            "title" => "Minimal Unit",
        ];

        $unitType = new UnitType($data);

        $this->assertEquals("Minimal Unit", $unitType->title);
        $this->assertEquals(0, $unitType->attack);
        $this->assertEquals(0, $unitType->defence);
        $this->assertEquals(1, $unitType->health);
        $this->assertEquals(1, $unitType->movement);
        $this->assertEquals(0, $unitType->upkeep);
        $this->assertFalse($unitType->can_found_city);
        $this->assertFalse($unitType->can_build);
        $this->assertEquals("land", $unitType->type);
        $this->assertEquals(["move_to"], $unitType->missions);
    }
}
