<?php

namespace App\Tests;

use App\Unit;
use App\UnitStats;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;

/**
 * Тесты для UnitStats
 */
class UnitStatsTest extends CommonTestBase
{
    public function testGetHealth()
    {
        $unit = TestDataFactory::createTestUnit(['health' => 5]);
        $stats = new UnitStats($unit);

        $this->assertEquals(5, $stats->getHealth());
    }

    public function testSetHealth()
    {
        $unit = TestDataFactory::createTestUnit(['health' => 5, 'health_max' => 10]);
        $stats = new UnitStats($unit);

        $stats->setHealth(3);
        $this->assertEquals(3, $unit->health);

        $stats->setHealth(15); // > max
        $this->assertEquals(10, $unit->health);
    }

    public function testSetPoints()
    {
        $unit = TestDataFactory::createTestUnit(['points' => 5]);
        $stats = new UnitStats($unit);

        $stats->setPoints(3);
        $this->assertEquals(3, $unit->points);

        $stats->setPoints(-1); // < 0
        $this->assertEquals(0, $unit->points);
    }

    public function testGetLevel()
    {
        $unit = TestDataFactory::createTestUnit(['lvl' => 2]);
        $stats = new UnitStats($unit);

        $this->assertEquals(2, $stats->getLevel());
    }

    public function testLevelUp()
    {
        $unit = TestDataFactory::createTestUnit(['lvl' => 1]);
        $stats = new UnitStats($unit);

        $stats->levelUp();
        $this->assertEquals(2, $unit->lvl);
    }

    public function testIsAlive()
    {
        $unit = TestDataFactory::createTestUnit(['health' => 1]);
        $stats = new UnitStats($unit);

        $this->assertTrue($stats->isAlive());

        $unit->health = 0;
        $this->assertFalse($stats->isAlive());
    }
}
