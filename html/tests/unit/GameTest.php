<?php

/**
 * Тесты для класса Game
 */
class GameTest extends TestBase
{
    /**
     * Тест получения существующей игры
     */
    public function testGetExistingGame(): void
    {
        $gameData = $this->createTestGame([
            'name' => 'Test Game',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        $game = Game::get($gameData['id']);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals($gameData['id'], $game->id);
        $this->assertEquals('Test Game', $game->name);
        $this->assertEquals(100, $game->map_w);
        $this->assertEquals(100, $game->map_h);
        $this->assertEquals('byturn', $game->turn_type);
    }

    /**
     * Тест получения несуществующей игры
     */
    public function testGetNonExistingGame(): void
    {
        $game = Game::get(999);

        $this->assertNull($game);
    }

    /**
     * Тест конструктора
     */
    public function testConstruct(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Construct Test Game',
            'map_w' => 50,
            'map_h' => 50,
            'turn_type' => 'concurrently',
            'turn_num' => 5
        ];

        $game = new Game($data);

        $this->assertEquals(1, $game->id);
        $this->assertEquals('Construct Test Game', $game->name);
        $this->assertEquals(50, $game->map_w);
        $this->assertEquals(50, $game->map_h);
        $this->assertEquals('concurrently', $game->turn_type);
        $this->assertEquals(5, $game->turn_num);
        $this->assertEquals(50, Cell::$map_width);
        $this->assertEquals(50, Cell::$map_height);
    }

    /**
     * Тест сохранения новой игры
     */
    public function testSaveNew(): void
    {
        $data = [
            'name' => 'Save New Game',
            'map_w' => 80,
            'map_h' => 80,
            'turn_type' => 'onewindow',
            'turn_num' => 1
        ];

        $game = new Game($data);
        $game->save();

        $this->assertNotNull($game->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query("SELECT * FROM game WHERE id = :id", ['id' => $game->id], 'row');
        $this->assertEquals('Save New Game', $savedData['name']);
        $this->assertEquals(80, $savedData['map_w']);
        $this->assertEquals(80, $savedData['map_h']);
        $this->assertEquals('onewindow', $savedData['turn_type']);
        $this->assertEquals(1, $savedData['turn_num']);
    }

    /**
     * Тест обновления существующей игры
     */
    public function testSaveUpdate(): void
    {
        $gameData = $this->createTestGame([
            'name' => 'Original Name',
            'turn_num' => 1
        ]);

        $game = Game::get($gameData['id']);
        $game->name = 'Updated Name';
        $game->turn_num = 2;
        $game->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query("SELECT * FROM game WHERE id = :id", ['id' => $game->id], 'row');
        $this->assertEquals('Updated Name', $updatedData['name']);
        $this->assertEquals(2, $updatedData['turn_num']);
    }

    /**
     * Тест метода game_list
     */
    public function testGameList(): void
    {
        // Создаем игры и пользователей
        $game1 = $this->createTestGame(['name' => 'Game 1']);
        $this->createTestUser(['game' => $game1['id']]);

        $game2 = $this->createTestGame(['name' => 'Game 2']);
        $this->createTestUser(['game' => $game2['id']]);
        $this->createTestUser(['game' => $game2['id']]);

        $games = Game::game_list();

        $this->assertCount(2, $games);
        // Проверяем, что игры отсортированы по id DESC
        $this->assertEquals('Game 2', $games[0]['name']);
        $this->assertEquals(2, $games[0]['ucount']);
        $this->assertEquals('Game 1', $games[1]['name']);
        $this->assertEquals(1, $games[1]['ucount']);
    }

    /**
     * Тест метода all_system_message
     */
    public function testAllSystemMessage(): void
    {
        $gameData = $this->createTestGame();
        $user1 = $this->createTestUser(['game' => $gameData['id'], 'login' => 'User1']);
        $user2 = $this->createTestUser(['game' => $gameData['id'], 'login' => 'User2']);

        // Создаем новый объект Game, чтобы избежать кэширования
        $game = new Game($gameData);
        // Заполняем пользователей
        $users = MyDB::query("SELECT id FROM user WHERE game = :gameid", ["gameid" => $game->id]);
        $game->users = [];
        foreach ($users as $user) {
            $game->users[$user["id"]] = User::get($user["id"]);
        }

        $this->assertCount(2, $game->users, "Игра должна иметь 2 пользователей");

        foreach ($game->users as $user) {
            $this->assertNotNull($user->id, "Пользователь должен иметь id");
        }

        $game->all_system_message('Test system message');

        // Проверяем, что сообщения созданы
        $allMessages = MyDB::query("SELECT * FROM message");
        $this->assertCount(2, $allMessages, "Должно быть создано 2 сообщения");

        $messages1 = MyDB::query("SELECT * FROM message WHERE to_id = :uid", ['uid' => $user1['id']]);
        $messages2 = MyDB::query("SELECT * FROM message WHERE to_id = :uid", ['uid' => $user2['id']]);

        $this->assertCount(1, $messages1);
        $this->assertCount(1, $messages2);
        $this->assertEquals('Test system message', $messages1[0]['text']);
        $this->assertEquals('system', $messages1[0]['type']);
        $this->assertEquals('Test system message', $messages2[0]['text']);
        $this->assertEquals('system', $messages2[0]['type']);
    }

    /**
     * Тест метода getActivePlayer для игры byturn
     */
    public function testGetActivePlayerByTurn(): void
    {
        $gameData = $this->createTestGame(['turn_type' => 'byturn']);
        $user1 = $this->createTestUser(['game' => $gameData['id'], 'turn_order' => 1, 'turn_status' => 'play']);
        $user2 = $this->createTestUser(['game' => $gameData['id'], 'turn_order' => 2, 'turn_status' => 'wait']);

        $game = Game::get($gameData['id']);
        $activePlayer = $game->getActivePlayer();

        $this->assertEquals($user1['id'], $activePlayer);
    }

    /**
     * Тест метода getActivePlayer для игры concurrently
     */
    public function testGetActivePlayerConcurrently(): void
    {
        $gameData = $this->createTestGame(['turn_type' => 'concurrently']);
        $user1 = $this->createTestUser(['game' => $gameData['id'], 'turn_status' => 'play']);
        $user2 = $this->createTestUser(['game' => $gameData['id'], 'turn_status' => 'play']);

        $game = Game::get($gameData['id']);
        $activePlayer = $game->getActivePlayer();

        // Для concurrently должен быть активный игрок
        $this->assertNotFalse($activePlayer, "Должен быть активный игрок");
        $this->assertTrue(in_array($activePlayer, [$user1['id'], $user2['id']]), "Активный игрок должен быть одним из созданных");
    }

    /**
     * Тест метода getActivePlayer без активных игроков
     */
    public function testGetActivePlayerNoActive(): void
    {
        $gameData = $this->createTestGame();
        $this->createTestUser(['game' => $gameData['id'], 'turn_status' => 'wait']);
        $this->createTestUser(['game' => $gameData['id'], 'turn_status' => 'end']);

        $game = Game::get($gameData['id']);
        $activePlayer = $game->getActivePlayer();

        $this->assertNull($activePlayer);
    }

    /**
     * Тест метода get_first_planet
     */
    public function testGetFirstPlanet(): void
    {
        $gameData = $this->createTestGame(['name' => 'Planet Game']);

        $planetId = $this->createTestPlanet(['game_id' => $gameData['id'], 'name' => 'First Planet']);

        $game = Game::get($gameData['id']);
        $planet = $game->get_first_planet();

        $this->assertInstanceOf(Planet::class, $planet);
        $this->assertEquals($planetId, $planet->id);
        $this->assertEquals('First Planet', $planet->name);
        $this->assertEquals($gameData['id'], $planet->game_id);
    }

    /**
     * Тест метода get_first_planet без планет
     */
    public function testGetFirstPlanetNoPlanets(): void
    {
        $gameData = $this->createTestGame(['name' => 'Empty Game']);

        $game = Game::get($gameData['id']);
        $planet = $game->get_first_planet();

        $this->assertNull($planet);
    }
}
