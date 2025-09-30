<?php

namespace App\Tests;

require_once __DIR__ . "/../bootstrap.php";

use App\UnitType;
use App\TestDataFactory;
use App\Tests\base\CommonTestBase;

/**
 * Тесты для класса UnitType
 */
class UnitTypeTest extends CommonTestBase
{
    /**
     * Тест получения существующего типа юнита
     */
    public function testGetExistingUnitType(): void
    {
        // Ensure default unit types exist
        $settler = \App\Tests\Factory\TestDataFactory::createTestUnitType([
            'title' => 'Поселенец',
            'cost' => 40,
            'attack' => 0,
            'defence' => 1,
            'health' => 1,
            'movement' => 1,
            'can_found_city' => true,
            'missions' => ['move_to'],
        ]);
        $warrior = \App\Tests\Factory\TestDataFactory::createTestUnitType([
            'title' => 'Воин',
        ]);

        $unitType = UnitType::get($settler->id);

        $this->assertInstanceOf(UnitType::class, $unitType);
        $this->assertEquals("Поселенец", $unitType->title);
        $this->assertEquals(40, $unitType->cost);
        $this->assertEquals(0, $unitType->attack);
        $this->assertEquals(1, $unitType->defence);
        $this->assertTrue($unitType->can_found_city);
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
            "id" => 10,
            "title" => "Test Unit",
            "cost" => 50,
            "attack" => 2,
            "defence" => 3,
            "type" => "land",
            "points" => 2,
        ];

        $unitType = new UnitType($data);

        $this->assertEquals(10, $unitType->id);
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
            "id" => 11,
            "title" => "Water Unit",
            "can_move" => [
                "water1" => 1,
                "water2" => 1,
                "city" => 1,
            ],
        ];

        $unitType = new UnitType($data);

        $this->assertEquals(11, $unitType->id);
        $this->assertEquals("Water Unit", $unitType->title);
        $this->assertIsArray($unitType->can_move);
        $this->assertEquals(1, $unitType->can_move["water1"]);
        $this->assertEquals(1, $unitType->can_move["water2"]);
        $this->assertEquals(1, $unitType->can_move["city"]);
    }

    /**
     * Тест кеширования в статическом массиве $all
     */
    public function testCaching(): void
    {
        $unitType1 = \App\Tests\Factory\TestDataFactory::createTestUnitType([
            'title' => 'Cached Unit',
        ]);
        UnitType::clearAll(); // Clear cache
        $unitType2 = UnitType::get($unitType1->id);

        // Должен быть один и тот же объект после clear? No, get reloads.
        // Test caching: get twice
        $unitType3 = UnitType::get($unitType1->id);

        $this->assertSame($unitType2, $unitType3);
    }

    /**
     * Тест свойств по умолчанию
     */
    public function testDefaultProperties(): void
    {
        $data = [
            "id" => 12,
            "title" => "Minimal Unit",
        ];

        $unitType = new UnitType($data);

        $this->assertEquals(12, $unitType->id);
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
