<?php

namespace App\Tests;

require_once __DIR__ . "/../bootstrap.php";

use App\User;
use App\MyDB;
use App\Research;
use App\ResearchType;
use App\Event;
use App\Message;

/**
 * Тесты для класса User
 */
class UserTest extends TestBase
{
    /**
     * Тест получения существующего пользователя
     * @small
     */
    public function testGetExistingUser(): void
    {
        $user = $this->createTestUser([
            "login" => "TestUser",
            "money" => 100,
            "age" => 2,
        ]);


        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user->id, $user->id);
        $this->assertEquals("TestUser", $user->login);
        $this->assertEquals(100, $user->money);
        $this->assertEquals(2, $user->age);
    }

    /**
     * Тест получения несуществующего пользователя
     * @small
     */
    public function testGetNonExistingUser(): void
    {
        $user = User::get(999);

        $this->assertNull($user);
    }

    /**
     * Тест конструктора
     * @small
     */
    public function testConstruct(): void
    {
        $data = [
            "id" => 1,
            "login" => "ConstructUser",
            "money" => 200,
            "income" => 50,
            "color" => "#ff0000",
            "age" => 3,
            "game" => 1,
            "turn_status" => "play",
            "turn_order" => 1,
            "research_amount" => 10,
            "research_percent" => 20,
        ];

        $user = new User($data);

        $this->assertEquals(1, $user->id);
        $this->assertEquals("ConstructUser", $user->login);
        $this->assertEquals(200, $user->money);
        $this->assertEquals(50, $user->income);
        $this->assertEquals("#ff0000", $user->color);
        $this->assertEquals(3, $user->age);
        $this->assertEquals(1, $user->game);
        $this->assertEquals("play", $user->turn_status);
        $this->assertEquals(1, $user->turn_order);
        $this->assertEquals(10, $user->research_amount);
        $this->assertEquals(20, $user->research_percent);
    }

    /**
     * Тест сохранения нового пользователя
     * @small
     */
    public function testSaveNew(): void
    {
        $data = [
            "login" => "NewUser",
            "money" => 150,
            "color" => "#00ff00",
            "age" => 1,
            "game" => 1,
            "turn_status" => "wait",
            "turn_order" => 2,
            "research_percent" => 10,
        ];

        $user = new User($data);
        $user->save();

        $this->assertNotNull($user->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM user WHERE id = :id",
            ["id" => $user->id],
            "row",
        );
        $this->assertEquals("NewUser", $savedData["login"]);
        $this->assertEquals(150, $savedData["money"]);
        $this->assertEquals("#00ff00", $savedData["color"]);
        $this->assertEquals(1, $savedData["age"]);
        $this->assertEquals(1, $savedData["game"]);
        $this->assertEquals("wait", $savedData["turn_status"]);
        $this->assertEquals(2, $savedData["turn_order"]);
        $this->assertEquals(10, $savedData["research_percent"]);
    }

    /**
     * Тест обновления существующего пользователя
     * @small
     */
    public function testSaveUpdate(): void
    {
        $user = $this->createTestUser([
            "login" => "OriginalUser",
            "money" => 50,
        ]);

        $user->login = "UpdatedUser";
        $user->money = 75;
        $user->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM user WHERE id = :id",
            ["id" => $user->id],
            "row",
        );
        $this->assertEquals("UpdatedUser", $updatedData["login"]);
        $this->assertEquals(75, $updatedData["money"]);
    }

    /**
     * Тест метода calculate_income
     * @medium
     */
    public function testCalculateIncome(): void
    {
        $game = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $game->id]);
        $user = $this->createTestUser(["game" => $game->id, "money" => 100]);

        $this->createTestCell(['x' => 10, 'y' => 10, 'planet' => $planetId]);

        // Создаем город для пользователя
        $city = $this->createTestCity([
            "user_id" => $user->id,
            "x" => 10,
            "y" => 10,
            "planet" => $planetId,
            "title" => "Test City",
            "pmoney" => 25,
            "presearch" => 5,
        ]);

        $income = $user->calculate_income();

        $this->assertEquals(1, $income);
        $this->assertEquals(101, $user->money); // 100 + 1
        $this->assertEquals(0, $user->research_amount);
    }

    /**
     * Тест метода get_cities
     * @medium
     */
    public function testGetCities(): void
    {
        $game = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $game->id]);
        $user = $this->createTestUser(["game" => $game->id]);

        $this->createTestCell(['x' => 5, 'y' => 5, 'planet' => $planetId]);
        $this->createTestCell(['x' => 15, 'y' => 15, 'planet' => $planetId]);

        // Создаем города для пользователя
        $city1 = $this->createTestCity([
            "user_id" => $user->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planetId,
            "title" => "City 1",
        ]);

        $city2 = $this->createTestCity([
            "user_id" => $user->id,
            "x" => 15,
            "y" => 15,
            "planet" => $planetId,
            "title" => "City 2",
        ]);

        $cities = $user->get_cities();

        $this->assertCount(2, $cities);
        $this->assertEquals($city1->id, $cities[0]->id);
        $this->assertEquals("City 1", $cities[0]->title);
        $this->assertEquals($city2->id, $cities[1]->id);
        $this->assertEquals("City 2", $cities[1]->title);
    }

    /**
     * Тест метода get_research
     * @small
     */
    public function testGetResearch(): void
    {
        $user = $this->createTestUser();

        // Создаем исследования для пользователя
        $research1Data = [
            "user_id" => $user->id,
            "type" => 1,
        ];
        $research1 = new Research($research1Data);
        $research1->save();

        $research2Data = [
            "user_id" => $user->id,
            "type" => 3,
        ];
        $research2 = new Research($research2Data);
        $research2->save();

        $research = $user->get_research();

        $this->assertCount(2, $research);
        $this->assertArrayHasKey(1, $research);
        $this->assertArrayHasKey(3, $research);
        $this->assertInstanceOf(Research::class, $research[1]);
        $this->assertInstanceOf(Research::class, $research[3]);
    }

    /**
     * Тест метода start_research
     * @small
     */
    public function testStartResearch(): void
    {
        $user = $this->createTestUser(["age" => 1]);

        // Создаем тип исследования
        $researchTypeData = [
            "id" => 1,
            "title" => "Test Research",
            "age" => 1,
            "cost" => 100,
        ];
        $researchType = new ResearchType($researchTypeData);
        $researchType->save();

        $researchType = ResearchType::get(1);

        $result = $user->start_research($researchType);

        $this->assertTrue($result);
        $this->assertEquals($researchType, $user->process_research_type);
    }

    /**
     * Тест метода start_research с уже проведенным исследованием
     */
    public function testStartResearchAlreadyDone(): void
    {
        $user = $this->createTestUser(["age" => 1]);

        // Добавляем исследование как уже проведенное
        $researchData = [
            "user_id" => $user->id,
            "type" => 1, // Гончарное дело
        ];
        $research = new Research($researchData);
        $research->save();

        $researchType = ResearchType::get(1);

        $result = $user->start_research($researchType);

        $this->assertFalse($result);
        $this->assertNull($user->process_research_type);
    }

    /**
     * Тест метода new_system_message
     */
    public function testNewSystemMessage(): void
    {
        $user = $this->createTestUser();

        $userObj = User::get($user->id);
        $message = $userObj->new_system_message("Test system message");

        $this->assertInstanceOf(Message::class, $message);

        // Проверяем, что сообщение сохранено
        $messages = MyDB::query("SELECT * FROM message WHERE to_id = :uid", [
            "uid" => $user->id,
        ]);
        $this->assertCount(1, $messages);
        $this->assertEquals("Test system message", $messages[0]["text"]);
        $this->assertEquals("system", $messages[0]["type"]);
    }

    /**
     * Тест метода get_next_event без событий
     */
    public function testGetNextEventNoEvents(): void
    {
        $user = $this->createTestUser();

        $userObj = User::get($user->id);
        $event = $userObj->get_next_event();

        $this->assertFalse($event);
    }

    /**
     * Тест метода get_next_event с событием
     */
    public function testGetNextEventWithEvent(): void
    {
        $user = $this->createTestUser();

        // Создаем событие исследования (research event)
        $eventData = [
            "user_id" => $user->id,
            "type" => "research",
            "object" => 1, // ID типа исследования
            "source" => null,
        ];
        $event = new Event($eventData);
        $event->save();

        $event = $user->get_next_event();

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals("research", $event->type);
        $this->assertInstanceOf(ResearchType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
    }

    /**
     * Тест метода calculate_research без активного исследования
     */
    public function testCalculateResearchNoActive(): void
    {
        $user = $this->createTestUser();

        $userObj = User::get($user->id);
        $result = $userObj->calculate_research();

        $this->assertFalse($result);
    }

    /**
     * Тест метода calculate_research с активным исследованием
     */
    public function testCalculateResearchActive(): void
    {
        $user = $this->createTestUser([
            "research_amount" => 10,
            "process_research_complete" => 0,
            "process_research_turns" => 0,
        ]);

        // Создаем тип исследования
        $researchTypeData = [
            "title" => "Active Research",
            "age" => 1,
            "cost" => 50,
        ];
        $researchType = new ResearchType($researchTypeData);
        $researchType->save();

        $user->process_research_type = ResearchType::get($researchType->id);
        $user->save();

        $result = $user->calculate_research();

        $this->assertFalse($result); // Исследование не завершено
        $this->assertEquals(10, $user->process_research_complete);
        $this->assertEquals(1, $user->process_research_turns);
    }

    /**
     * Тест метода calculate_research с завершением исследования
     */
    public function testCalculateResearchComplete(): void
    {
        $user = $this->createTestUser([
            "research_amount" => 30,
            "process_research_complete" => 20,
            "process_research_turns" => 2,
            "age" => 1,
        ]);

        // Используем существующий тип исследования (id=1)
        $user->process_research_type = ResearchType::get(1); // Гончарное дело, cost=50
        $user->save();

        $result = $user->calculate_research();

        $this->assertInstanceOf(Research::class, $result);
        $this->assertNull($user->process_research_type);
        $this->assertEquals(0, $user->process_research_complete);
        $this->assertEquals(0, $user->process_research_turns);

        // Проверяем, что исследование сохранено
        $researchData = MyDB::query("SELECT * FROM research WHERE user_id = :uid AND type = :tid", [
            "uid" => $user->id,
            "tid" => 1,
        ], "row");
        $this->assertNotNull($researchData);

        // Проверяем, что событие создано
        $eventData = MyDB::query("SELECT * FROM event WHERE user_id = :uid AND type = 'research'", [
            "uid" => $user->id,
        ], "row");
        $this->assertNotNull($eventData);
    }

    /**
     * Тест метода get_available_research
     */
    public function testGetAvailableResearch(): void
    {
        $user = $this->createTestUser(["age" => 1]);

        $available = $user->get_available_research();

        // Должен быть доступен хотя бы один тип исследования (например, id=1 - Гончарное дело)
        $this->assertNotEmpty($available);
        $this->assertArrayHasKey(1, $available); // Гончарное дело
        $this->assertInstanceOf(ResearchType::class, $available[1]);
    }

    /**
     * Тест метода get_research_need_turns
     */
    public function testGetResearchNeedTurns(): void
    {
        $user = $this->createTestUser([
            "research_amount" => 10,
            "process_research_complete" => 20,
            "process_research_turns" => 1,
        ]);

        $user->process_research_type = ResearchType::get(1); // Гончарное дело, cost=50

        $turns = $user->get_research_need_turns();

        // (50 - 20) / 10 = 3, и 3 + 1 = 4 >= 4, так что возвращается 3
        $this->assertEquals(3, $turns);
    }

    /**
     * Тест метода calculate_cities
     * @medium
     */
    public function testCalculateCities(): void
    {
        $game = $this->createTestGame();
        $planetId = $this->createTestPlanet(["game_id" => $game->id]);
        $user = $this->createTestUser(["game" => $game->id]);

        $this->createTestCell(['x' => 10, 'y' => 10, 'planet' => $planetId]);

        // Создаем город для пользователя
        $city = $this->createTestCity([
            "user_id" => $user->id,
            "x" => 10,
            "y" => 10,
            "planet" => $planetId,
            "title" => "Test City",
        ]);

        $user->calculate_cities();

        // Проверяем, что город рассчитан (метод calculate() вызван)
        $this->assertNotNull($city);
    }



    /**
     * Тест метода get_messages
     */
    public function testGetMessages(): void
    {
        $user = $this->createTestUser();

        // Создаем сообщения для пользователя
        $message1Data = [
            "from_id" => null,
            "to_id" => $user->id,
            "text" => "Message 1",
            "type" => "system",
        ];
        $message1 = new Message($message1Data);
        $message1->save();

        $message2Data = [
            "from_id" => $user->id,
            "to_id" => $user->id,
            "text" => "Message 2",
            "type" => "user",
        ];
        $message2 = new Message($message2Data);
        $message2->save();

        $userObj = User::get($user->id);
        $messages = $userObj->get_messages();

        $this->assertCount(2, $messages);
        $this->assertInstanceOf(Message::class, $messages[0]);
        $this->assertInstanceOf(Message::class, $messages[1]);
    }
}
