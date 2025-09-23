<?php

require_once __DIR__ . "/../TestBase.php";

/**
 * Тесты для класса User
 */
class UserTest extends TestBase
{
    /**
     * Тест получения существующего пользователя
     */
    public function testGetExistingUser(): void
    {
        $userData = $this->createTestUser([
            "login" => "TestUser",
            "money" => 100,
            "age" => 2,
        ]);

        $user = User::get($userData["id"]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData["id"], $user->id);
        $this->assertEquals("TestUser", $user->login);
        $this->assertEquals(100, $user->money);
        $this->assertEquals(2, $user->age);
    }

    /**
     * Тест получения несуществующего пользователя
     */
    public function testGetNonExistingUser(): void
    {
        $user = User::get(999);

        $this->assertNull($user);
    }

    /**
     * Тест конструктора
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
     */
    public function testSaveUpdate(): void
    {
        $userData = $this->createTestUser([
            "login" => "OriginalUser",
            "money" => 50,
        ]);

        $user = User::get($userData["id"]);
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
     */
    public function testCalculateIncome(): void
    {
        $userData = $this->createTestUser(["money" => 100]);

        // Создаем город для пользователя
        $cityData = [
            "user_id" => $userData["id"],
            "x" => 10,
            "y" => 10,
            "planet" => 0,
            "title" => "Test City",
            "pmoney" => 25,
            "presearch" => 5,
        ];
        MyDB::insert("city", $cityData);

        $user = User::get($userData["id"]);
        $income = $user->calculate_income();

        $this->assertEquals(25, $income);
        $this->assertEquals(125, $user->money); // 100 + 25
        $this->assertEquals(5, $user->research_amount);
    }

    /**
     * Тест метода get_cities
     */
    public function testGetCities(): void
    {
        $userData = $this->createTestUser();

        // Создаем города для пользователя
        $city1Data = [
            "user_id" => $userData["id"],
            "x" => 5,
            "y" => 5,
            "planet" => 0,
            "title" => "City 1",
        ];
        $city1Id = MyDB::insert("city", $city1Data);

        $city2Data = [
            "user_id" => $userData["id"],
            "x" => 15,
            "y" => 15,
            "planet" => 0,
            "title" => "City 2",
        ];
        $city2Id = MyDB::insert("city", $city2Data);

        $user = User::get($userData["id"]);
        $cities = $user->get_cities();

        $this->assertCount(2, $cities);
        $this->assertEquals($city1Id, $cities[0]->id);
        $this->assertEquals("City 1", $cities[0]->title);
        $this->assertEquals($city2Id, $cities[1]->id);
        $this->assertEquals("City 2", $cities[1]->title);
    }

    /**
     * Тест метода get_research
     */
    public function testGetResearch(): void
    {
        $userData = $this->createTestUser();

        // Создаем исследования для пользователя
        $research1Data = [
            "user_id" => $userData["id"],
            "type" => 1,
        ];
        MyDB::insert("research", $research1Data);

        $research2Data = [
            "user_id" => $userData["id"],
            "type" => 3,
        ];
        MyDB::insert("research", $research2Data);

        $user = User::get($userData["id"]);
        $research = $user->get_research();

        $this->assertCount(2, $research);
        $this->assertArrayHasKey(1, $research);
        $this->assertArrayHasKey(3, $research);
        $this->assertInstanceOf(Research::class, $research[1]);
        $this->assertInstanceOf(Research::class, $research[3]);
    }

    /**
     * Тест метода start_research
     */
    public function testStartResearch(): void
    {
        $userData = $this->createTestUser(["age" => 1]);

        // Создаем тип исследования
        $researchTypeData = [
            "id" => 1,
            "title" => "Test Research",
            "age" => 1,
            "cost" => 100,
        ];
        MyDB::insert("research_type", $researchTypeData);

        $user = User::get($userData["id"]);
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
        $userData = $this->createTestUser(["age" => 1]);

        // Создаем тип исследования
        $researchTypeData = [
            "id" => 2,
            "title" => "Already Done Research",
            "age" => 1,
            "cost" => 50,
        ];
        MyDB::insert("research_type", $researchTypeData);

        // Добавляем исследование как уже проведенное
        $researchData = [
            "user_id" => $userData["id"],
            "type" => 2,
        ];
        MyDB::insert("research", $researchData);

        $user = User::get($userData["id"]);
        $researchType = ResearchType::get(2);

        $result = $user->start_research($researchType);

        $this->assertFalse($result);
        $this->assertNull($user->process_research_type);
    }

    /**
     * Тест метода new_system_message
     */
    public function testNewSystemMessage(): void
    {
        $userData = $this->createTestUser();

        $user = User::get($userData["id"]);
        $message = $user->new_system_message("Test system message");

        $this->assertInstanceOf(Message::class, $message);

        // Проверяем, что сообщение сохранено
        $messages = MyDB::query("SELECT * FROM message WHERE to_id = :uid", [
            "uid" => $userData["id"],
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
        $userData = $this->createTestUser();

        $user = User::get($userData["id"]);
        $event = $user->get_next_event();

        $this->assertFalse($event);
    }

    /**
     * Тест метода get_next_event с событием
     */
    public function testGetNextEventWithEvent(): void
    {
        $userData = $this->createTestUser();

        // Создаем событие исследования (research event)
        $eventData = [
            "user_id" => $userData["id"],
            "type" => "research",
            "object" => 1, // ID типа исследования
            "source" => null,
        ];
        MyDB::insert("event", $eventData);

        $user = User::get($userData["id"]);
        $event = $user->get_next_event();

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals("research", $event->type);
        $this->assertInstanceOf(ResearchType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
    }
}
