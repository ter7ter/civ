<?php

namespace App\Tests;

use App\Tests\Base\TestGameDataInitializer;
use App\Unit;
use App\UnitStats;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;

/**
 * Тесты для UnitStats
 */
class UnitStatsTest extends CommonTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        TestGameDataInitializer::initializeCellTypes();
    }
    public function testGetHealth()
    {
        $result = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $planet = $result['planet'];
        $user = $result['user'];
        $unit = TestDataFactory::createTestUnit(['health' => 5, 'planet' => $planet->id, 'user_id' => $user->id, 'x' => 10, 'y' => 10]);
        $stats = new UnitStats($unit);

        $this->assertEquals(5, $stats->getHealth());
    }

    public function testSetHealth()
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $planet = $result['planet'];
        $user = $result['user'];
        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit(['health' => 5, 'health_max' => 10, 'planet' => $planet->id, 'user_id' => $user->id]);
        $stats = new UnitStats($unit);

        $stats->setHealth(3);
        $this->assertEquals(3, $unit->health);

        $stats->setHealth(15); // > max
        $this->assertEquals(10, $unit->health);
    }

    public function testSetPoints()
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $planet = $result['planet'];
        $user = $result['user'];
        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit(['points' => 5, 'planet' => $planet->id, 'user_id' => $user->id]);
        $stats = new UnitStats($unit);

        $stats->setPoints(3);
        $this->assertEquals(3, $unit->points);

        $stats->setPoints(-1); // < 0
        $this->assertEquals(0, $unit->points);
    }

    public function testGetLevel()
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $planet = $result['planet'];
        $user = $result['user'];
        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit(['lvl' => 2, 'planet' => $planet->id, 'user_id' => $user->id]);
        $stats = new UnitStats($unit);

        $this->assertEquals(2, $stats->getLevel());
    }

    public function testLevelUp()
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $planet = $result['planet'];
        $user = $result['user'];
        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit(['lvl' => 1, 'planet' => $planet->id, 'user_id' => $user->id]);
        $stats = new UnitStats($unit);

        $stats->levelUp();
        $this->assertEquals(2, $unit->lvl);
    }

    public function testIsAlive()
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $planet = $result['planet'];
        $user = $result['user'];
        TestDataFactory::createTestCell(['x' => 5, 'y' => 5, 'planet' => $planet->id]);
        $unit = TestDataFactory::createTestUnit(['health' => 1, 'planet' => $planet->id, 'user_id' => $user->id]);
        $stats = new UnitStats($unit);

        $this->assertTrue($stats->isAlive());

        $unit->health = 0;
        $this->assertFalse($stats->isAlive());
    }
}
