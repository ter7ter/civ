<?php

namespace App\Tests;

use App\CellType;
use App\ResourceType;
use App\Tests\Factory\TestDataFactory;
use App\User;
use App\MyDB;
use App\Tests\base\CommonTestBase;

/**
 * Тесты для класса ResourceType
 */
class ResourceTypeTest extends CommonTestBase
{
    /**
     * Тест получения существующего типа ресурса
     */
    public function testGetExistingResourceType(): void
    {
        $resourceType = TestDataFactory::createTestResourceType([
            'id' => 'iron',
            'title' => 'железо',
            'type' => 'mineral',
            'work' => 2,
            'money' => 1,
            'chance' => 0.015,
        ]);

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
     * Тест метода getTitle
     */
    public function testGetTitle(): void
    {
        $resourceType = TestDataFactory::createTestResourceType([
            'id' => 'coal',
            'title' => 'уголь',
        ]);

        $this->assertEquals('уголь', $resourceType->getTitle());
    }

    /**
     * Тест метода canUse без требуемых исследований
     */
    public function testCanUseWithoutRequiredResearch(): void
    {
        $game = TestDataFactory::createTestGame();
        $user = TestDataFactory::createTestUser(['game' => $game->id]);

        $resourceType = TestDataFactory::createTestResourceType([
            'id' => 'coal',
        ]);

        $this->assertTrue($resourceType->canUse($user));
    }

    /**
     * Тест метода canUse с требуемыми исследованиями (доступно)
     */
    public function testCanUseWithRequiredResearchAvailable(): void
    {
        $game = TestDataFactory::createTestGame();
        $user = TestDataFactory::createTestUser(['game' => $game->id]);

        $researchType = TestDataFactory::createTestResearchType(['title' => 'Верховая езда']);

        $resourceType = TestDataFactory::createTestResourceType([
            'id' => 'horse',
            'title' => 'Лошади',
            'req_research' => [],
        ]);
        $resourceType->addReqResearch($researchType); // Требуется верховая езд
        $resourceType->save();
        // Выдать пользователю требуемое исследование (однократно)
        TestDataFactory::createTestResearch([
            'type' => $researchType->id,
            'user_id' => $user->id,
            'title' => 'Верховая езда',
        ]);
        $this->assertTrue($resourceType->canUse($user));
    }

    /**
     * Тест метода canUse с требуемыми исследованиями (недоступно)
     */
    public function testCanUseWithRequiredResearchUnavailable(): void
    {
        $game = TestDataFactory::createTestGame();
        $user = TestDataFactory::createTestUser(['game' => $game->id]);

        $researchType = TestDataFactory::createTestResearchType(['title' => 'Верховая езда']);

        $resourceType = TestDataFactory::createTestResourceType([
            'id' => 'horse',
            'title' => 'Лошади',
            'req_research' => [],
        ]);
        $resourceType->addReqResearch($researchType); // Требуется объект исследования
        $resourceType->save();

        $this->assertFalse($resourceType->canUse($user));
    }

    /**
     * Тест свойства req_research и cell_types
     */
    public function testComplexProperties(): void
    {
        $horse = TestDataFactory::createTestResourceType([
            'id' => 'horse',
            'title' => 'Лошади',
            'cell_types' => [
                CellType::get("plains"),
                CellType::get("plains2")
            ],
        ]);
        $researchType = TestDataFactory::createTestResearchType(['title' => 'Верховая езда']);
        $horse->addReqResearch($researchType);
        $this->assertIsArray($horse->req_research);
        $this->assertIsArray($horse->cell_types);
        $this->assertNotEmpty($horse->req_research);
        $this->assertNotEmpty($horse->cell_types);

        $coal = TestDataFactory::createTestResourceType([
            'id' => 'coal',
            'title' => 'уголь',
            'cell_types' => [
                CellType::get("hills"),
                CellType::get("mountains")
            ],
        ]);
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
        $allResourceTypes = ResourceType::getAll();

        $this->assertIsArray($allResourceTypes);
    }
}
