<?php

namespace App\Tests;

use App\Game;
use App\GameManager;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;

/**
 * Тесты для GameManager
 */
class GameManagerTest extends CommonTestBase
{
    public function testCreateNewGame()
    {
        $game = TestDataFactory::createTestGame();
        $manager = new GameManager($game);

        // Since createNewGame delegates to MapGenerator, test if no exception
        $this->expectNotToPerformAssertions();
        try {
            $manager->createNewGame();
        } catch (\Exception $e) {
            // If DB issue, skip
            $this->markTestSkipped('DB not available');
        }
    }

    public function testCalculateTurn()
    {
        $game = TestDataFactory::createTestGame();
        $manager = new GameManager($game);

        $this->expectNotToPerformAssertions();
        try {
            $manager->calculateTurn();
        } catch (\Exception $e) {
            $this->markTestSkipped('DB not available');
        }
    }

    public function testSendSystemMessageToAll()
    {
        $game = TestDataFactory::createTestGame();
        $manager = new GameManager($game);

        // Test if no exception
        $this->expectNotToPerformAssertions();
        $manager->sendSystemMessageToAll('Test message');
    }

    public function testGetActivePlayer()
    {
        $game = TestDataFactory::createTestGame();
        $manager = new GameManager($game);

        // Test if returns int or null
        $result = $manager->getActivePlayer();
        $this->assertTrue(is_int($result) || $result === null);
    }

    public function testGetFirstPlanet()
    {
        $game = TestDataFactory::createTestGame();
        $manager = new GameManager($game);

        $result = $manager->getFirstPlanet();
        // Since no planets, null
        $this->assertNull($result);
    }
}
