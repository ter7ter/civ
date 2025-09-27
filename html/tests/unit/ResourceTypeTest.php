<?php

namespace App\Tests;

use App\ResourceType;
use App\User;

/**
 * Тесты для класса ResourceType
 */
class ResourceTypeTest extends TestBase
{
    /**
     * Тест получения существующего типа ресурса
     */
    public function testGetExistingResourceType(): void
    {
        $this->initializeGameTypes();

        $resourceType = ResourceType::get('iron');

        $this->assertInstanceOf(ResourceType::class, $resourceType);
        $this->assertEquals('iron', $resourceType->id);
        $this->assertEquals('железо', $resourceType->title);
        $this->assertEquals('mineral', $resourceType->type);
        $this->assertEquals(2, $resourceType->work);
        $this->assertEquals(1, $resourceType->money);
        $this->assertEquals(0.015, $resourceType->chance);
    }

    /**
     * Тест получения несуществующего типа ресурса
     */
    public function testGetNonExistingResourceType(): void
    {
        $this->initializeGameTypes();

        $resourceType = ResourceType::get('nonexistent');

        $this->assertFalse($resourceType);
    }

    /**
     * Тест конструктора ResourceType
     */
    public function testConstructor(): void
    {
        $data = [
            'id' => 'test_resource',
            'title' => 'Test Resource',
            'type' => 'luxury',
            'work' => 1,
            'eat' => 2,
            'money' => 3,
            'req_research' => [],
            'cell_types' => [],
            'chance' => 0.05,
            'min_amount' => 10,
            'max_amount' => 100,
        ];

        $resourceType = new ResourceType($data);

        $this->assertEquals('test_resource', $resourceType->id);
        $this->assertEquals('Test Resource', $resourceType->title);
        $this->assertEquals('luxury', $resourceType->type);
        $this->assertEquals(1, $resourceType->work);
        $this->assertEquals(2, $resourceType->eat);
        $this->assertEquals(3, $resourceType->money);
        $this->assertEquals([], $resourceType->req_research);
        $this->assertEquals([], $resourceType->cell_types);
        $this->assertEquals(0.05, $resourceType->chance);
        $this->assertEquals(10, $resourceType->min_amount);
        $this->assertEquals(100, $resourceType->max_amount);

        // Проверяем, что объект добавлен в кэш
        $this->assertSame($resourceType, ResourceType::get('test_resource'));
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $this->initializeGameTypes();

        $resourceType = ResourceType::get('coal');

        $this->assertEquals('уголь', $resourceType->get_title());
    }

    /**
     * Тест метода can_use без требуемых исследований
     */
    public function testCanUseWithoutRequiredResearch(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        $user = User::get($userData['id']);

        $resourceType = ResourceType::get('coal'); // Уголь не требует исследований

        $this->assertTrue($resourceType->can_use($user));
    }

    /**
     * Тест метода can_use с требуемыми исследованиями (доступно)
     */
    public function testCanUseWithRequiredResearchAvailable(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        $user = User::get($userData['id']);

        // Добавляем исследование пользователю
        MyDB::insert('research', [
            'type' => 4, // Верховая езда
            'user_id' => $user->id,
        ]);

        User::clearCache();
        $user = User::get($userData['id']);

        $resourceType = ResourceType::get('horse'); // Лошади требуют верховой езды

        $this->assertTrue($resourceType->can_use($user));
    }

    /**
     * Тест метода can_use с требуемыми исследованиями (недоступно)
     */
    public function testCanUseWithRequiredResearchUnavailable(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        $user = User::get($userData['id']);

        $resourceType = ResourceType::get('horse'); // Лошади требуют верховой езды

        $this->assertFalse($resourceType->can_use($user));
    }

    /**
     * Тест всех предопределенных типов ресурсов
     */
    public function testAllPredefinedResourceTypes(): void
    {
        $this->initializeGameTypes();

        $expectedTypes = [
            'iron' => ['title' => 'железо', 'type' => 'mineral', 'work' => 2, 'money' => 1],
            'horse' => ['title' => 'лошади', 'type' => 'mineral', 'work' => 1, 'money' => 1],
            'coal' => ['title' => 'уголь', 'type' => 'mineral', 'work' => 2, 'money' => 1],
            'oil' => ['title' => 'нефть', 'type' => 'mineral', 'work' => 2, 'money' => 2],
            'saltpetre' => ['title' => 'селитра', 'type' => 'mineral', 'work' => 2, 'money' => 1],
            'rubber' => ['title' => 'резина', 'type' => 'mineral', 'work' => 1, 'money' => 2],
            'uranium' => ['title' => 'уран', 'type' => 'mineral', 'work' => 1, 'money' => 1],
            'vine' => ['title' => 'виноград', 'type' => 'luxury', 'eat' => 1, 'money' => 2],
            'ivory' => ['title' => 'слоновая кость', 'type' => 'luxury', 'work' => 1, 'money' => 2],
            'silk' => ['title' => 'шёлк', 'type' => 'luxury', 'work' => 2, 'money' => 1],
            'furs' => ['title' => 'меха', 'type' => 'luxury', 'work' => 1, 'eat' => 1, 'money' => 1],
            'fish' => ['title' => 'рыба', 'type' => 'bonuce', 'eat' => 2],
            'whale' => ['title' => 'киты', 'type' => 'bonuce', 'eat' => 1, 'money' => 1],
        ];

        foreach ($expectedTypes as $id => $expected) {
            $resourceType = ResourceType::get($id);
            $this->assertInstanceOf(ResourceType::class, $resourceType, "Resource type {$id} should exist");
            $this->assertEquals($expected['title'], $resourceType->title, "Title for resource type {$id}");
            $this->assertEquals($expected['type'], $resourceType->type, "Type for resource type {$id}");

            if (isset($expected['work'])) {
                $this->assertEquals($expected['work'], $resourceType->work, "Work for resource type {$id}");
            }

            if (isset($expected['eat'])) {
                $this->assertEquals($expected['eat'], $resourceType->eat, "Eat for resource type {$id}");
            }

            if (isset($expected['money'])) {
                $this->assertEquals($expected['money'], $resourceType->money, "Money for resource type {$id}");
            }
        }
    }

    /**
     * Тест свойства req_research и cell_types
     */
    public function testComplexProperties(): void
    {
        $this->initializeGameTypes();

        $horse = ResourceType::get('horse');
        $this->assertIsArray($horse->req_research);
        $this->assertIsArray($horse->cell_types);
        $this->assertNotEmpty($horse->req_research);
        $this->assertNotEmpty($horse->cell_types);

        $coal = ResourceType::get('coal');
        $this->assertIsArray($coal->req_research);
        $this->assertIsArray($coal->cell_types);
        $this->assertEmpty($coal->req_research); // Уголь не требует исследований
        $this->assertNotEmpty($coal->cell_types);
    }

    /**
     * Тест метода getAll
     */
    public function testGetAll(): void
    {
        $this->initializeGameTypes();

        $allResourceTypes = ResourceType::getAll();

        $this->assertIsArray($allResourceTypes);
        $this->assertNotEmpty($allResourceTypes);

        // Проверяем, что все предопределенные типы присутствуют
        $expectedIds = [
            'iron', 'horse', 'coal', 'oil', 'saltpetre', 'rubber', 'uranium',
            'vine', 'ivory', 'silk', 'furs', 'fish', 'whale'
        ];

        foreach ($expectedIds as $id) {
            $this->assertArrayHasKey($id, $allResourceTypes);
            $this->assertInstanceOf(ResourceType::class, $allResourceTypes[$id]);
            $this->assertEquals($id, $allResourceTypes[$id]->id);
        }

        // Проверяем, что количество соответствует ожидаемому (может быть больше из-за тестов)
        $this->assertGreaterThanOrEqual(count($expectedIds), count($allResourceTypes));
    }
}
