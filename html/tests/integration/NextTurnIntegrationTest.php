<?php

namespace App\Tests\Integration;

use App\MyDB;
use App\User;
use App\Game;
use App\Tests\Base\CommonTestBase;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\TestGameDataInitializer;

/**
 * Интеграционные тесты для кнопки "следующий ход" - завершения хода игрока.
 */
class NextTurnIntegrationTest extends CommonTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpIntegrationTest();
        TestGameDataInitializer::initializeCellTypes();
        $unitSettlerType = TestDataFactory::createTestUnitType([
            "title" => "Поселенец",
            "cost" => 40,
            "upkeep" => 1,
            "attack" => 0,
            "defence" => 1,
            "health" => 1,
            "movement" => 1,
            "can_found_city" => true,
            "need_research" => [],
            "description" => "Основывает новые города",
            "missions" => ["move_to", "build_city"],
            "can_move" => ["plains" => 1, "plains2" => 1, "forest" => 1, "hills" => 1, "mountains" => 2, "desert" => 1, "city" => 1],
        ]);
        \App\GameConfig::$START_UNIT_SETTLER_TYPE = $unitSettlerType->id;
    }

    /**
     * Тест завершения хода в режиме "по очереди"
     * @medium
     */
    public function testNextTurnByTurnMode(): void
    {
        // Создаем тестовую игру с двумя игроками
        $gameData = TestDataFactory::createCompleteTestGame([
            'name' => 'Тест следующий ход',
            'map_w' => 50,
            'map_h' => 50,
            'turn_type' => 'byturn'
        ], ["Игрок1", "Игрок2"]);

        // Устанавливаем первого игрока в статус 'play'
        $firstUser = $gameData['users'][0];
        $nextUser = $gameData['users'][1];
        $firstUser->turn_status = 'play';
        $firstUser->save();
        $nextUser->turn_status = 'wait';
        $nextUser->save();

        $result = mockIncludeFile(__DIR__ . "/../../pages/calculate.php", [
            "user" => $firstUser,
            "game" => $gameData['game'],
        ]);

        // Проверяем, что статус первого игрока изменился на 'end'
        $this->assertEquals('end', $firstUser->turn_status);

        // Проверяем, что следующий игрок перешел в статус 'play'
        $this->assertEquals('play', $nextUser->turn_status);

        // Проверяем системные сообщения
        $messages = MyDB::query("SELECT * FROM message WHERE to_id = :uid AND type = 'system'", ['uid' => $nextUser->id]);
        $this->assertGreaterThan(0, count($messages));

        $gameMessages = MyDB::query("SELECT * FROM message WHERE to_id = :uid AND type = 'system'", [
            'uid' => $firstUser->id,
        ]); // broadcast messages
        $this->assertGreaterThan(0, count($gameMessages));

        // Не должен быть новый ход
        $this->assertEquals('byturn', $gameData['game']->turn_type);
    }

    /**
     * Тест завершения хода в режиме "одновременно"
     * @medium
     */
    public function testNextTurnConcurrentlyNotEndingTurn(): void
    {
        $gameData = TestDataFactory::createCompleteTestGame([
            'name' => 'Тест одновременный ход',
            'map_w' => 50,
            'map_h' => 50,
            'turn_type' => 'concurrently'
        ], ["Игрок1", "Игрок2"]);

        $firstUser = $gameData['users'][0];
        $secondUser = $gameData['users'][1];

        // В concurrently все игроки в 'play'
        $firstUser->turn_status = 'play';
        $firstUser->save();
        $secondUser->turn_status = 'play';
        $secondUser->save();

        $result = mockIncludeFile(__DIR__ . "/../../pages/calculate.php", [
            "user" => $firstUser,
            "game" => $gameData['game'],
        ]);
        print_r($result['output']);

        // Первый должен быть 'end'
        $this->assertEquals('end', $firstUser->turn_status);

        // Второй все еще 'play'
        $this->assertEquals('play', $secondUser->turn_status);
    }

    /**
     * Тест начала нового хода после того, как все игроки завершили ход
     * @medium
     */
    public function testAllPlayersEndTurnStartNewTurn(): void
    {
        $gameData = TestDataFactory::createCompleteTestGame([
            'name' => 'Тест новый ход',
            'map_w' => 50,
            'map_h' => 50,
            'turn_type' => 'byturn'
        ], ["Игрок1", "Игрок2"]);

        $firstUser = $gameData['users'][0];
        $secondUser = $gameData['users'][1];

        // Устанавливаем статусы: первый 'play', второй уже 'end'
        $firstUser->turn_status = 'play';
        $firstUser->save();
        $secondUser->turn_status = 'end';
        $secondUser->save();

        $result = mockIncludeFile(__DIR__ . "/../../pages/calculate.php", [
            "user" => $firstUser,
            "game" => $gameData['game'],
        ]);

        // Перезагружаем объект игры для получения обновленных данных
        $gameData['game'] = Game::get($gameData['game']->id);

        $this->assertEquals(2, $gameData['game']->turn_num); // Предполагаем, что начальный turn был 1

        User::clearCache();
        // Для нового хода: первый 'play', второй 'wait'
        $firstUser = User::get($firstUser->id); // Обновляем данные
        $this->assertEquals('play', $firstUser->turn_status);
        $secondUser = User::get($secondUser->id);
        $this->assertEquals('wait', $secondUser->turn_status);

        // Проверяем сообщение о новом ходе
        $gameMessages = MyDB::query("SELECT * FROM message WHERE type = 'system' ORDER BY id DESC LIMIT 1");
        $this->assertStringContainsString('Начало нового хода', $gameMessages[0]['text']);
    }

    /**
     * Тест режима 'onewindow': переключение сессии на следующего игрока
     * @medium
     */
    public function testOneWindowModeSessionSwitch(): void
    {
        $gameData = TestDataFactory::createCompleteTestGame([
            'name' => 'Тест onewindow',
            'map_w' => 50,
            'map_h' => 50,
            'turn_type' => 'onewindow'
        ], ["Игрок1 onewindow", "Игрок2 onewindow"]);

        $firstUser = $gameData['users'][0];
        $secondUser = $gameData['users'][1];

        // Первый в 'play', второй 'wait'
        $firstUser->turn_status = 'play';
        $firstUser->save();
        $secondUser->turn_status = 'wait';
        $secondUser->save();

        Game::clearCache();
        $result = mockIncludeFile(__DIR__ . "/../../pages/calculate.php", [
            "user" => $firstUser,
            "game" => $gameData['game'],
        ]);
        error_log(var_export($gameData['game'], true));
        error_log($result['output']);

        // Проверяем переключение сессии
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('1', $result['data']['reload']);
        $this->assertEquals($secondUser->id, $_SESSION['user_id']);
    }
}
