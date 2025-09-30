<?php

namespace App\Tests;

use App\Game;
use App\User;
use App\MyDB;
use App\Planet;
use App\Cell;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;
use App\Tests\Mocks\DatabaseTestAdapter;

/**
 * Тесты для класса Game
 */
class GameTest extends CommonTestBase
{
    protected function setUp(): void
    {
        DatabaseTestAdapter::resetTestDatabase();
        parent::setUp();
    }

    /**
     * Тест получения существующей игры
     */
    public function testGetExistingGame(): void
    {
        $game = TestDataFactory::createTestGame([
            'name' => 'Test Game',
            'map_w' => 100,
            'map_h' => 100,
            'turn_type' => 'byturn'
        ]);

        $game = Game::get($game->id);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals($game->id, $game->id);
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
        $game = TestDataFactory::createTestGame([
            'name' => 'Original Name',
            'turn_num' => 1
        ]);

        $game = Game::get($game->id);
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
        $game1 = TestDataFactory::createTestGame(['name' => 'Game 1']);
        TestDataFactory::createTestUser(['game' => $game1->id]);

        $game2 = TestDataFactory::createTestGame(['name' => 'Game 2']);
        TestDataFactory::createTestUser(['game' => $game2->id]);
        TestDataFactory::createTestUser(['game' => $game2->id]);

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
        $game = TestDataFactory::createTestGame();
        $user1 = TestDataFactory::createTestUser(['game' => $game->id, 'login' => 'User1']);
        $user2 = TestDataFactory::createTestUser(['game' => $game->id, 'login' => 'User2']);

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

        $messages1 = MyDB::query("SELECT * FROM message WHERE to_id = :uid", ['uid' => $user1->id]);
        $messages2 = MyDB::query("SELECT * FROM message WHERE to_id = :uid", ['uid' => $user2->id]);

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
        $game = TestDataFactory::createTestGame(['turn_type' => 'byturn']);
        $user1 = TestDataFactory::createTestUser(['game' => $game->id, 'turn_order' => 1, 'turn_status' => 'play']);
        $user2 = TestDataFactory::createTestUser(['game' => $game->id, 'turn_order' => 2, 'turn_status' => 'wait']);

        $game = Game::get($game->id);
        $activePlayer = $game->getActivePlayer();

        $this->assertEquals($user1->id, $activePlayer);
    }

    /**
     * Тест метода getActivePlayer для игры concurrently
     */
    public function testGetActivePlayerConcurrently(): void
    {
        $game = TestDataFactory::createTestGame(['turn_type' => 'concurrently']);
        $user1 = TestDataFactory::createTestUser(['game' => $game->id, 'turn_status' => 'play']);
        $user2 = TestDataFactory::createTestUser(['game' => $game->id, 'turn_status' => 'play']);

        $game = Game::get($game->id);
        $activePlayer = $game->getActivePlayer();

        // Для concurrently должен быть активный игрок
        $this->assertNotFalse($activePlayer, "Должен быть активный игрок");
        $this->assertTrue(in_array($activePlayer, [$user1->id, $user2->id]), "Активный игрок должен быть одним из созданных");
    }

    /**
     * Тест метода getActivePlayer без активных игроков
     */
    public function testGetActivePlayerNoActive(): void
    {
        $game = TestDataFactory::createTestGame();
        TestDataFactory::createTestUser(['game' => $game->id, 'turn_status' => 'wait']);
        TestDataFactory::createTestUser(['game' => $game->id, 'turn_status' => 'end']);

        $game = Game::get($game->id);
        $activePlayer = $game->getActivePlayer();

        $this->assertNull($activePlayer);
    }

    /**
     * Тест метода get_first_planet
     */
    public function testGetFirstPlanet(): void
    {
        $result = TestDataFactory::createTestGameWithPlanet(['name' => 'Planet Game'], ['name' => 'First Planet']);
        $game = $result['game'];
        $planet = $result['planet'];

        $this->assertNotNull($game, 'Game should be found');
        $planetFirst = $game->get_first_planet();

        $this->assertInstanceOf(Planet::class, $planetFirst);
        $this->assertEquals($planet->id, $planetFirst->id);
        $this->assertEquals('First Planet', $planetFirst->name);
        $this->assertEquals($game->id, $planetFirst->game_id);
    }

    /**
     * Тест метода get_first_planet без планет
     */
    public function testGetFirstPlanetNoPlanets(): void
    {
        $game = TestDataFactory::createTestGame(['name' => 'Empty Game']);

        $game = Game::get($game->id);
        $planet = $game->get_first_planet();

        $this->assertNull($planet);
    }

    /**
     * Тест метода calculate для игры byturn
     */
    public function testCalculateByTurn(): void
    {
        $game = TestDataFactory::createTestGame(['turn_type' => 'byturn', 'turn_num' => 1]);
        $user1 = TestDataFactory::createTestUser(['game' => $game->id, 'turn_order' => 1]);
        $user2 = TestDataFactory::createTestUser(['game' => $game->id, 'turn_order' => 2]);

        $game = Game::get($game->id);
        $game->calculate();

        // Проверяем, что turn_num увеличился
        $this->assertEquals(2, $game->turn_num);

        // Проверяем статусы пользователей
        $user1Updated = User::get($user1->id);
        $user2Updated = User::get($user2->id);

        $this->assertEquals('play', $user1Updated->turn_status);
        $this->assertEquals('wait', $user2Updated->turn_status);
    }

    /**
     * Тест метода calculate для игры concurrently
     */
    public function testCalculateConcurrently(): void
    {
        $game = TestDataFactory::createTestGame(['turn_type' => 'concurrently', 'turn_num' => 1]);
        $user1 = TestDataFactory::createTestUser(['game' => $game->id]);
        $user2 = TestDataFactory::createTestUser(['game' => $game->id]);

        $game = Game::get($game->id);
        $game->calculate();

        // Проверяем, что turn_num увеличился
        $this->assertEquals(2, $game->turn_num);

        // Проверяем статусы пользователей
        $user1Updated = User::get($user1->id);
        $user2Updated = User::get($user2->id);

        $this->assertEquals('play', $user1Updated->turn_status);
        $this->assertEquals('play', $user2Updated->turn_status);
    }

    /**
     * Тест метода create_new_game
     */
    public function testCreateNewGame(): void
    {
        // Initialize cell and unit types required for map/unit creation
        \App\Tests\Base\TestGameDataInitializer::initializeCellTypes();
        $unitSettlerType = \App\Tests\Factory\TestDataFactory::createTestUnitType(['title' => 'Поселенец']);
        \App\GameConfig::$START_UNIT_SETTLER_TYPE = $unitSettlerType->id;

        $game = TestDataFactory::createTestGame([
            'name' => 'New Game Test',
            'map_w' => 20,
            'map_h' => 20,
            'turn_type' => 'byturn'
        ]);

        // Добавляем пользователей
        $user1 = TestDataFactory::createTestUser(['game' => $game->id, 'turn_order' => 1]);
        $user2 = TestDataFactory::createTestUser(['game' => $game->id, 'turn_order' => 2]);

        $game = Game::get($game->id);

        // Метод create_new_game должен выполняться без ошибок
        $game->create_new_game();

        // Проверяем, что планета создана
        $planet = $game->get_first_planet();
        $this->assertNotNull($planet);
        $this->assertInstanceOf(Planet::class, $planet);

        // Проверяем, что пользователи загружены в игру
        $this->assertCount(2, $game->users);

        // Проверяем, что юниты созданы для пользователей
        $units1 = MyDB::query("SELECT COUNT(*) as count FROM unit WHERE user_id = :uid", ['uid' => $user1->id], 'elem');
        $units2 = MyDB::query("SELECT COUNT(*) as count FROM unit WHERE user_id = :uid", ['uid' => $user2->id], 'elem');

        $this->assertGreaterThan(0, $units1, "Должен быть создан хотя бы один юнит для первого пользователя");
        $this->assertGreaterThan(0, $units2, "Должен быть создан хотя бы один юнит для второго пользователя");
    }
}
