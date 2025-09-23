<?php

require_once __DIR__ . "/../TestBase.php";

/**
 * Тесты для класса Research
 */
class ResearchTest extends TestBase
{
    /**
     * Тест получения существующего исследования
     */
    public function testGetExistingResearch(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем исследование
        $researchData = [
            "user_id" => $user->id,
            "type" => 1,
        ];
        $researchId = MyDB::insert("research", $researchData);

        $research = Research::get($researchId);

        $this->assertInstanceOf(Research::class, $research);
        $this->assertEquals($researchId, $research->id);
        $this->assertInstanceOf(User::class, $research->user);
        $this->assertEquals($user->id, $research->user->id);
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertEquals(1, $research->type->id);
    }

    /**
     * Тест конструктора исследования
     */
    public function testConstructor(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $researchData = [
            "id" => 1,
            "user_id" => $user->id,
            "type" => 2,
        ];

        $research = new Research($researchData);

        $this->assertEquals(1, $research->id);
        $this->assertInstanceOf(User::class, $research->user);
        $this->assertEquals($user->id, $research->user->id);
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertEquals(2, $research->type->id);
    }

    /**
     * Тест конструктора без ID (новое исследование)
     */
    public function testConstructorWithoutId(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $researchData = [
            "user_id" => $user->id,
            "type" => 5,
        ];

        $research = new Research($researchData);

        $this->assertNull($research->id);
        $this->assertInstanceOf(User::class, $research->user);
        $this->assertEquals($user->id, $research->user->id);
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertEquals(5, $research->type->id);
    }

    /**
     * Тест сохранения нового исследования
     */
    public function testSaveNew(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $researchData = [
            "user_id" => $user->id,
            "type" => 5,
        ];

        $research = new Research($researchData);
        $research->save();

        $this->assertNotNull($research->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM research WHERE id = :id",
            ["id" => $research->id],
            "row",
        );
        $this->assertNotNull($savedData);
        $this->assertEquals($user->id, $savedData["user_id"]);
        $this->assertEquals(5, $savedData["type"]);
    }

    /**
     * Тест обновления существующего исследования
     */
    public function testSaveUpdate(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        // Создаем исследование
        $researchData = [
            "user_id" => $user->id,
            "type" => 1,
        ];
        $researchId = MyDB::insert("research", $researchData);
        $research = Research::get($researchId);
        $originalId = $research->id;

        // Обновляем тип исследования
        $research->type = ResearchType::get(2);
        $research->save();

        $this->assertEquals($originalId, $research->id);

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM research WHERE id = :id",
            ["id" => $research->id],
            "row",
        );
        $this->assertEquals(2, $updatedData["type"]);
        $this->assertEquals($user->id, $updatedData["user_id"]);
    }

    /**
     * Тест связи исследования с пользователем
     */
    public function testResearchUserRelation(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $researchData = [
            "user_id" => $user->id,
            "type" => 6,
        ];

        $research = new Research($researchData);

        // Проверяем что исследование правильно связано с пользователем
        $this->assertInstanceOf(User::class, $research->user);
        $this->assertEquals($user->id, $research->user->id);
        $this->assertEquals($user->login, $research->user->login);
        $this->assertEquals($user->game, $research->user->game);
    }

    /**
     * Тест связи исследования с типом исследования
     */
    public function testResearchTypeRelation(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $researchData = [
            "user_id" => $user->id,
            "type" => 6,
        ];

        $research = new Research($researchData);

        // Проверяем что исследование правильно связано с типом
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertEquals(6, $research->type->id);

        // Проверяем что можем получить название типа
        $this->assertIsString($research->type->get_title());
        $this->assertNotEmpty($research->type->get_title());
    }

    /**
     * Тест создания исследований с различными типами
     */
    public function testDifferentResearchTypes(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $researchTypes = [1, 2, 5, 3, 4];

        foreach ($researchTypes as $type) {
            $researchData = [
                "user_id" => $user->id,
                "type" => $type,
            ];

            $research = new Research($researchData);
            $research->save();

            $this->assertNotNull($research->id);
            $this->assertEquals($type, $research->type->id);
            $this->assertIsString($research->type->get_title());
            $this->assertNotEmpty($research->type->get_title());
        }
    }

    /**
     * Тест создания нескольких исследований для одного пользователя
     */
    public function testMultipleResearchesForUser(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $research1Data = [
            "user_id" => $user->id,
            "type" => 1,
        ];
        $research1 = new Research($research1Data);
        $research1->save();

        $research2Data = [
            "user_id" => $user->id,
            "type" => 2,
        ];
        $research2 = new Research($research2Data);
        $research2->save();

        $this->assertNotNull($research1->id);
        $this->assertNotNull($research2->id);
        $this->assertNotEquals($research1->id, $research2->id);

        // Проверяем что оба исследования принадлежат одному пользователю
        $this->assertEquals($user->id, $research1->user->id);
        $this->assertEquals($user->id, $research2->user->id);

        // Проверяем что типы исследований разные
        $this->assertNotEquals($research1->type->id, $research2->type->id);
    }

    /**
     * Тест получения исследований для разных пользователей
     */
    public function testResearchesForDifferentUsers(): void
    {
        $this->initializeGameTypes();

        // Создаем двух пользователей
        $userData1 = $this->createTestUser();
        $user1 = User::get($userData1["id"]);

        $userData2 = $this->createTestUser();
        $user2 = User::get($userData2["id"]);

        // Создаем исследования для каждого пользователя
        $research1Data = [
            "user_id" => $user1->id,
            "type" => 1,
        ];
        $research1 = new Research($research1Data);
        $research1->save();

        $research2Data = [
            "user_id" => $user2->id,
            "type" => 5,
        ];
        $research2 = new Research($research2Data);
        $research2->save();

        $this->assertNotEquals($research1->user->id, $research2->user->id);
        $this->assertEquals($user1->id, $research1->user->id);
        $this->assertEquals($user2->id, $research2->user->id);
    }

    /**
     * Тест валидации обязательных полей
     */
    public function testRequiredFields(): void
    {
        $this->initializeGameTypes();
        $userData = $this->createTestUser();
        $user = User::get($userData["id"]);

        $researchData = [
            "user_id" => $user->id,
            "type" => 6,
        ];

        $research = new Research($researchData);

        // Проверяем что все обязательные поля установлены
        $this->assertNotNull($research->type);
        $this->assertNotNull($research->user);
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertInstanceOf(User::class, $research->user);
    }
}
