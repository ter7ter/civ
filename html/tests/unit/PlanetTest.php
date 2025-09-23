<?php

require_once __DIR__ . "/../TestBase.php";

/**
 * Тесты для класса Planet
 */
class PlanetTest extends TestBase
{
    /**
     * Тест получения существующей планеты
     */
    public function testGetExistingPlanet(): void
    {
        $gameData = $this->createTestGame();
        $planetData = $this->createTestPlanet(['game_id' => $gameData['id'], 'name' => 'Test Planet']);

        $planet = Planet::get($planetData['id']);

        $this->assertInstanceOf(Planet::class, $planet);
        $this->assertEquals($planetData['id'], $planet->id);
        $this->assertEquals('Test Planet', $planet->name);
        $this->assertEquals($gameData['id'], $planet->game_id);
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
     * Тест конструктора
     */
    public function testConstruct(): void
    {
        $gameData = $this->createTestGame();
        $data = [
            'id' => 1,
            'name' => 'Construct Test Planet',
            'game_id' => $gameData['id']
        ];

        $planet = new Planet($data);

        $this->assertEquals(1, $planet->id);
        $this->assertEquals('Construct Test Planet', $planet->name);
        $this->assertEquals($gameData['id'], $planet->game_id);
    }

    /**
     * Тест сохранения новой планеты
     */
    public function testSaveNew(): void
    {
        $gameData = $this->createTestGame();
        $data = [
            'name' => 'Save New Planet',
            'game_id' => $gameData['id']
        ];

        $planet = new Planet($data);
        $planet->save();

        $this->assertNotNull($planet->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query("SELECT * FROM planet WHERE id = :id", ['id' => $planet->id], 'row');
        $this->assertEquals('Save New Planet', $savedData['name']);
        $this->assertEquals($gameData['id'], $savedData['game_id']);
    }

    /**
     * Тест обновления существующей планеты
     */
    public function testSaveUpdate(): void
    {
        $gameData = $this->createTestGame();
        $planetData = $this->createTestPlanet(['game_id' => $gameData['id'], 'name' => 'Original Name']);

        $planet = Planet::get($planetData['id']);
        $planet->name = 'Updated Name';
        $planet->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query("SELECT * FROM planet WHERE id = :id", ['id' => $planet->id], 'row');
        $this->assertEquals('Updated Name', $updatedData['name']);
    }

    /**
     * Тест метода get_game
     */
    public function testGetGame(): void
    {
        $gameData = $this->createTestGame(['name' => 'Planet Game']);
        $planetData = $this->createTestPlanet(['game_id' => $gameData['id']]);

        $planet = Planet::get($planetData['id']);
        $game = $planet->get_game();

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals($gameData['id'], $game->id);
        $this->assertEquals('Planet Game', $game->name);
    }

    /**
     * Тест очистки кэша
     */
    public function testClearCache(): void
    {
        $gameData = $this->createTestGame();
        $planetData = $this->createTestPlanet(['game_id' => $gameData['id']]);

        // Получаем планету, она должна кэшироваться
        $planet1 = Planet::get($planetData['id']);
        $this->assertInstanceOf(Planet::class, $planet1);

        // Очищаем кэш
        Planet::clearCache();

        // Получаем снова, должна быть загружена из БД
        $planet2 = Planet::get($planetData['id']);
        $this->assertInstanceOf(Planet::class, $planet2);
        $this->assertEquals($planet1->id, $planet2->id);
        $this->assertEquals($planet1->name, $planet2->name);
    }

    /**
     * Тест метода get_planet в классе Cell
     */
    public function testCellGetPlanet(): void
    {
        $gameData = $this->createTestGame();
        $planetData = $this->createTestPlanet(['game_id' => $gameData['id'], 'name' => 'Cell Planet']);

        // Создаем клетку с планетой
        $cellData = $this->createTestCell(['planet' => $planetData['id'], 'x' => 10, 'y' => 10]);
        $cell = Cell::get($cellData['x'], $cellData['y'], $cellData['planet']);

        $planet = $cell->get_planet();

        $this->assertInstanceOf(Planet::class, $planet);
        $this->assertEquals($planetData['id'], $planet->id);
        $this->assertEquals('Cell Planet', $planet->name);
        $this->assertEquals($gameData['id'], $planet->game_id);
    }
}
