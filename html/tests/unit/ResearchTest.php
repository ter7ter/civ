<?php

namespace App\Tests;

use App\Research;
use App\User;
use App\ResearchType;
use App\MyDB;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;

/**
 * Тесты для класса Research
 */
class ResearchTest extends CommonTestBase
{
    /**
     * Тест получения исследования по ID
     */
    public function testGet(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $researchType = TestDataFactory::createTestResearchType([
            "title" => "Гончарное дело",
            "age" => 1,
            "cost" => 50,
            "m_top" => 30,
            "m_left" => 30,
            "age_need" => true
        ]);

        // Создаем исследование через БД
        $researchId = MyDB::insert('research', [
            'user_id' => $user->id,
            'type' => $researchType->id, // Гончарное дело
        ]);

        $research = Research::get($researchId);

        $this->assertInstanceOf(Research::class, $research);
        $this->assertEquals($researchId, $research->id);
        $this->assertInstanceOf(User::class, $research->user);
        $this->assertEquals($user->id, $research->user->id);
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertEquals($researchType->id, $research->type->id);
    }

    /**
     * Тест конструктора без ID
     */
    public function testConstructorWithoutId(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $researchType = TestDataFactory::createTestResearchType([
            "title" => "Бронзовое дело",
        ]);

        $data = [
            'user_id' => $user->id,
            'type' => $researchType->id, // Бронзовое дело
        ];

        $research = new Research($data);

        $this->assertNull($research->id);
        $this->assertInstanceOf(User::class, $research->user);
        $this->assertInstanceOf(ResearchType::class, $research->type);
        $this->assertEquals($researchType->id, $research->type->id);
    }

    /**
     * Тест сохранения нового исследования
     */
    public function testSaveNew(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $researchType = TestDataFactory::createTestResearchType([
            "title" => "Гончарное дело",
            "age" => 1,
            "cost" => 50,
            "m_top" => 30,
            "m_left" => 30,
            "age_need" => true
        ]);

        $data = [
            'user_id' => $user->id,
            'type' => $researchType->id, // Гончарное дело
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
        $this->assertEquals($researchType->id, $savedData['type']);
    }

    /**
     * Тест обновления существующего исследования
     */
    public function testSaveUpdate(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        // Создаем исследование через БД
        $researchType = TestDataFactory::createTestResearchType([
            "title" => "Гончарное дело",
            "age" => 1,
            "cost" => 50,
            "m_top" => 30,
            "m_left" => 30,
            "age_need" => true
        ]);
        $researchId = MyDB::insert('research', [
            'user_id' => $user->id,
            'type' => $researchType->id, // Гончарное дело
        ]);

        $data = [
            'id' => $researchId,
            'user_id' => $user->id,
            'type' => $researchType->id,
        ];

        $research = new Research($data);
        $researchTypeBronze = TestDataFactory::createTestResearchType([
            'title' => 'Бронзовое дело',
            'age' => 1,
            'cost' => 80,
            'm_top' => 130,
            'm_left' => 30,
            'age_need' => true
        ]);
        $research->type = $researchTypeBronze; // Меняем тип на Бронзовое дело
        $research->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM research WHERE id = :id",
            ["id" => $research->id],
            "row"
        );
        $this->assertEquals($researchTypeBronze->id, $updatedData['type']);
    }

    /**
     * Тест создания исследований разных типов
     */
    public function testDifferentResearchTypes(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $researchTypes = [
            TestDataFactory::createTestResearchType([
                "title" => "Гончарное дело",
                "age" => 1,
                "cost" => 50,
                "m_top" => 30,
                "m_left" => 30,
                "age_need" => true
            ]),
            TestDataFactory::createTestResearchType([
                'title' => 'Бронзовое дело',
                'age' => 1,
                'cost' => 80,
                'm_top' => 130,
                'm_left' => 30,
                'age_need' => true
            ]),
            TestDataFactory::createTestResearchType([
                "title" => "Письменность",
                "age" => 1,
                "cost" => 50,
                "m_top" => 30,
                "m_left" => 30,
                "age_need" => true
            ])
        ];

        foreach ($researchTypes as $researchType) {
            $data = [
                'user_id' => $user->id,
                'type' => $researchType->id,
            ];

            $research = new Research($data);
            $research->save();

            $this->assertInstanceOf(ResearchType::class, $research->type);
            $this->assertEquals($researchType->id, $research->type->id);
            $this->assertInstanceOf(User::class, $research->user);
            $this->assertEquals($user->id, $research->user->id);
        }
    }

    /**
     * Тест связи исследования с пользователем
     */
    public function testResearchUserRelationship(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
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
