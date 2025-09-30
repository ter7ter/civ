<?php

namespace App\Tests;

use App\Planet;
use App\Game;
use App\MyDB;
use Exception;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;

/**
 * Тесты для класса Planet
 */
class PlanetTest extends CommonTestBase
{
    /**
     * Тест получения существующей планеты
     */
    public function testGetExistingPlanet(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id, 'name' => 'Test Planet']);
        $planetId = $planet->id;

        $planet = Planet::get($planetId);

        $this->assertInstanceOf(Planet::class, $planet);
        $this->assertEquals($planetId, $planet->id);
        $this->assertEquals('Test Planet', $planet->name);
        $this->assertEquals($game->id, $planet->game_id);
    }

    /**
     * Тест получения несуществующей планеты
     */
    public function testGetNonExistingPlanet(): void
    {
        $planet = Planet::get(999);

        $this->assertNull($planet);
    }

    /**
     * Тест конструктора Planet
     */
    public function testConstructor(): void
    {
        $game = TestDataFactory::createTestGame();

        $data = [
            'id' => 1,
            'name' => 'Constructor Planet',
            'game_id' => $game->id,
        ];

        $planet = new Planet($data);

        $this->assertEquals(1, $planet->id);
        $this->assertEquals('Constructor Planet', $planet->name);
        $this->assertEquals($game->id, $planet->game_id);

        // Проверяем, что объект добавлен в кэш
        $this->assertSame($planet, Planet::get(1));
    }

    /**
     * Тест конструктора без id
     */
    public function testConstructorWithoutId(): void
    {
        $game = TestDataFactory::createTestGame();

        $data = [
            'name' => 'No ID Planet',
            'game_id' => $game->id,
        ];

        $planet = new Planet($data);

        $this->assertNull($planet->id);
        $this->assertEquals('No ID Planet', $planet->name);
        $this->assertEquals($game->id, $planet->game_id);
    }

    /**
     * Тест конструктора с некорректными данными
     */
    public function testConstructorWithInvalidData(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid planet data provided to Planet constructor');

        new Planet(null);
    }

    /**
     * Тест сохранения новой планеты
     */
    public function testSaveNew(): void
    {
        $game = TestDataFactory::createTestGame();

        $data = [
            'name' => 'Save New Planet',
            'game_id' => $game->id,
        ];

        $planet = new Planet($data);
        $planet->save();

        $this->assertNotNull($planet->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM planet WHERE id = :id",
            ["id" => $planet->id],
            "row"
        );
        $this->assertNotNull($savedData);
        $this->assertEquals('Save New Planet', $savedData['name']);
        $this->assertEquals($game->id, $savedData['game_id']);
    }

    /**
     * Тест обновления существующей планеты
     */
    public function testSaveUpdate(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id, 'name' => 'Original Name']);
        $planetId = $planet->id;

        Planet::clearCache();
        $planet = Planet::get($planetId);
        $planet->name = 'Updated Name';
        $planet->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM planet WHERE id = :id",
            ["id" => $planet->id],
            "row"
        );
        $this->assertEquals('Updated Name', $updatedData['name']);
        $this->assertEquals($game->id, $updatedData['game_id']);
    }

    /**
     * Тест метода get_game
     */
    public function testGetGame(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;

        Planet::clearCache();
        $planet = Planet::get($planetId);

        $this->assertInstanceOf(Game::class, $game);
    }

    /**
     * Тест очистки кэша
     */
    public function testClearCache(): void
    {
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;

        // Получаем планету, чтобы она попала в кэш
        $planet1 = Planet::get($planetId);
        $this->assertInstanceOf(Planet::class, $planet1);

        Planet::clearCache();

        // После очистки кэша, планета должна быть загружена заново
        $planet2 = Planet::get($planetId);
        $this->assertInstanceOf(Planet::class, $planet2);
        $this->assertNotSame($planet1, $planet2); // Должны быть разные объекты
    }
}
