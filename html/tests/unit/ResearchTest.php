<?php

namespace App\Tests;

use App\Research;
use App\User;
use App\ResearchType;
use App\MyDB;

/**
 * Тесты для класса Research
 */
class ResearchTest extends TestBase
{
    /**
     * Тест получения исследования по ID
     */
    public function testGet(): void
    {
        $result = $this->createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        // Создаем исследование через БД
        $researchId = MyDB::insert('research', [
            'user_id' => $user->id,
            'type' => 1, // Гончарное дело
        ]);

        $research = Research::get($researchId);

        $this->assertInstanceOf(Research::class, $research);
        $this->assertEquals($researchId, $research->id);
        $this->assertInstanceOf(User::class, $research->user);
        $this->assertEquals($user->id, $research->user->id);
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertEquals(1, $research->type->id);
    }

    /**
     * Тест конструктора без ID
     */
    public function testConstructorWithoutId(): void
    {
        $result = $this->createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $data = [
            'user_id' => $user->id,
            'type' => 2, // Бронзовое дело
        ];

        $research = new Research($data);

        $this->assertNull($research->id);
        $this->assertInstanceOf(User::class, $research->user);
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertEquals(2, $research->type->id);
    }

    /**
     * Тест сохранения нового исследования
     */
    public function testSaveNew(): void
    {
        $result = $this->createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $data = [
            'user_id' => $user->id,
            'type' => 1, // Гончарное дело
        ];

        $research = new Research($data);
        $research->save();

        $this->assertNotNull($research->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM research WHERE id = :id",
            ["id" => $research->id],
            "row"
        );
        $this->assertNotNull($savedData);
        $this->assertEquals($user->id, $savedData['user_id']);
        $this->assertEquals(1, $savedData['type']);
    }

    /**
     * Тест обновления существующего исследования
     */
    public function testSaveUpdate(): void
    {
        $result = $this->createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        // Создаем исследование через БД
        $researchId = MyDB::insert('research', [
            'user_id' => $user->id,
            'type' => 1, // Гончарное дело
        ]);

        $data = [
            'id' => $researchId,
            'user_id' => $user->id,
            'type' => 1,
        ];

        $research = new Research($data);
        $research->type = ResearchType::get(2); // Меняем тип на Бронзовое дело
        $research->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM research WHERE id = :id",
            ["id" => $research->id],
            "row"
        );
        $this->assertEquals(2, $updatedData['type']);
    }

    /**
     * Тест создания исследований разных типов
     */
    public function testDifferentResearchTypes(): void
    {
        $result = $this->createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $researchTypes = [1, 2, 3]; // Гончарное дело, Бронзовое дело, Письменность

        foreach ($researchTypes as $typeId) {
            $data = [
                'user_id' => $user->id,
                'type' => $typeId,
            ];

            $research = new Research($data);

            $this->assertInstanceOf(ResearchType::class, $research->type);
            $this->assertEquals($typeId, $research->type->id);
            $this->assertInstanceOf(User::class, $research->user);
            $this->assertEquals($user->id, $research->user->id);
        }
    }

    /**
     * Тест связи исследования с пользователем
     */
    public function testResearchUserRelationship(): void
    {
        $result = $this->createTestGameWithPlanetAndUser();
        $user = $result['user'];

        $researchTypeData = [
            "title" => "Test Research Type 1",
            "age" => 1,
            "cost" => 50,
        ];
        $researchType = new ResearchType($researchTypeData);
        $researchType->save();

        $research = new Research([
            'user_id' => $user->id,
            'type' => $researchType->id,
        ]);

        // Проверяем, что исследование связано с правильным пользователем
        $this->assertEquals($user->id, $research->user->id);
        $this->assertEquals($user->login, $research->user->login);

        // Проверяем, что тип исследования правильный
        $this->assertEquals($researchType->id, $research->type->id);
        $this->assertEquals('Test Research Type 1', $research->type->title);
    }
}
