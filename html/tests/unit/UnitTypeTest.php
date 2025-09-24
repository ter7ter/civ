<?php

require_once __DIR__ . "/../bootstrap.php";

/**
 * Тесты для класса UnitType
 */
class UnitTypeTest extends TestBase
{
    /**
     * Тест получения существующего типа юнита
     */
    public function testGetExistingUnitType(): void
    {
        $unitType = UnitType::get(1);

        $this->assertInstanceOf(UnitType::class, $unitType);
        $this->assertEquals(1, $unitType->id);
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
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $unitType = UnitType::get(2); // Воин

        $this->assertEquals("Воин", $unitType->get_title());
    }

    /**
     * Тест кеширования в статическом массиве $all
     */
    public function testCaching(): void
    {
        // Получить юнит из кеша
        $unitType1 = UnitType::get(1);
        $unitType2 = UnitType::get(1);

        // Должен быть один и тот же объект
        $this->assertSame($unitType1, $unitType2);
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
