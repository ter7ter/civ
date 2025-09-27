<?php

namespace App\Tests;

use App\Resource;
use App\ResourceType;
use App\MyDB;

/**
 * Тесты для класса Resource
 */
class ResourceTest extends TestBase
{
    /**
     * Тест получения ресурса по координатам
     */
    public function testGet(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        // Создаем ресурс через БД
        $resourceId = MyDB::insert('resource', [
            'x' => 5,
            'y' => 5,
            'planet' => $planetId,
            'type' => 'coal', // Уголь
            'amount' => 100,
        ]);

        // Resource::clearCache(); // Нет такого метода
        $resource = Resource::get(5, 5, $planetId);

        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertIsInt($resource->id);
        $this->assertGreaterThan(0, $resource->id);
        $this->assertEquals(5, $resource->x);
        $this->assertEquals(5, $resource->y);
        $this->assertEquals($planetId, $resource->planet);
        $this->assertInstanceOf(ResourceType::class, $resource->type);
        $this->assertEquals('coal', $resource->type->id);
        $this->assertEquals(100, $resource->amount);
    }

    /**
     * Тест получения несуществующего ресурса
     */
    public function testGetNonExisting(): void
    {
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        $resource = Resource::get(999, 999, $planetId);

        $this->assertFalse($resource);
    }

    /**
     * Тест конструктора Resource
     */
    public function testConstructor(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        $data = [
            'id' => 1,
            'x' => 10,
            'y' => 10,
            'planet' => $planetId,
            'type' => 'coal', // Уголь
            'amount' => 50,
        ];

        $resource = new Resource($data);

        $this->assertEquals(1, $resource->id);
        $this->assertEquals(10, $resource->x);
        $this->assertEquals(10, $resource->y);
        $this->assertEquals($planetId, $resource->planet);
        $this->assertInstanceOf(ResourceType::class, $resource->type);
        $this->assertEquals('coal', $resource->type->id);
        $this->assertEquals(50, $resource->amount);
    }

    /**
     * Тест конструктора без ID
     */
    public function testConstructorWithoutId(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        $data = [
            'x' => 15,
            'y' => 15,
            'planet' => $planetId,
            'type' => 'fish', // Рыба
            'amount' => 75,
        ];

        $resource = new Resource($data);

        $this->assertNull($resource->id);
        $this->assertEquals(15, $resource->x);
        $this->assertEquals(15, $resource->y);
        $this->assertEquals($planetId, $resource->planet);
        $this->assertInstanceOf(ResourceType::class, $resource->type);
        $this->assertEquals('fish', $resource->type->id);
        $this->assertEquals(75, $resource->amount);
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        $data = [
            'x' => 20,
            'y' => 20,
            'planet' => $planetId,
            'type' => 'coal', // Уголь
            'amount' => 25,
        ];

        $resource = new Resource($data);

        $this->assertEquals('уголь', $resource->get_title());
    }

    /**
     * Тест метода get_title без типа
     */
    public function testGetTitleWithoutType(): void
    {
        $resource = new Resource([
            'x' => 1,
            'y' => 1,
            'planet' => 1,
            'type' => 999, // Несуществующий тип
            'amount' => 10,
        ]);

        $this->assertEquals('', $resource->get_title());
    }

    /**
     * Тест сохранения нового ресурса
     */
    public function testSaveNew(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        $data = [
            'x' => 25,
            'y' => 25,
            'planet' => $planetId,
            'type' => 'coal', // Уголь
            'amount' => 200,
        ];

        $resource = new Resource($data);
        $resource->save();

        $this->assertNotNull($resource->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM resource WHERE id = :id",
            ["id" => $resource->id],
            "row"
        );
        $this->assertNotNull($savedData);
        $this->assertEquals(25, $savedData['x']);
        $this->assertEquals(25, $savedData['y']);
        $this->assertEquals($planetId, $savedData['planet']);
        $this->assertEquals('coal', $savedData['type']);
        $this->assertEquals(200, $savedData['amount']);
    }

    /**
     * Тест обновления существующего ресурса
     */
    public function testSaveUpdate(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        // Создаем ресурс через БД
        $resourceId = MyDB::insert('resource', [
            'x' => 30,
            'y' => 30,
            'planet' => $planetId,
            'type' => 'coal',
            'amount' => 50,
        ]);

        $data = [
            'id' => $resourceId,
            'x' => 30,
            'y' => 30,
            'planet' => $planetId,
            'type' => 'coal',
            'amount' => 50,
        ];

        $resource = new Resource($data);
        $resource->amount = 150; // Увеличиваем количество
        $resource->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM resource WHERE id = :id",
            ["id" => $resource->id],
            "row"
        );
        $this->assertEquals(150, $updatedData['amount']);
    }

    /**
     * Тест сохранения без типа ресурса
     */
    public function testSaveWithoutType(): void
    {
        $resource = new Resource([
            'x' => 1,
            'y' => 1,
            'planet' => 1,
            'type' => null,
            'amount' => 10,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Resource type is not set');
        $resource->save();
    }

    /**
     * Тест создания ресурсов разных типов
     */
    public function testDifferentResourceTypes(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        $resourceTypes = ['coal', 'fish', 'furs']; // Уголь, Рыба, Меха

        foreach ($resourceTypes as $typeId) {
            $data = [
                'x' => rand(1, 100),
                'y' => rand(1, 100),
                'planet' => $planetId,
                'type' => $typeId,
                'amount' => rand(10, 100),
            ];

            $resource = new Resource($data);

            $this->assertInstanceOf(ResourceType::class, $resource->type);
            $this->assertEquals($typeId, $resource->type->id);
            $this->assertIsString($resource->get_title());
            $this->assertNotEmpty($resource->get_title());
        }
    }

    /**
     * Тест уникальности ресурсов по координатам
     */
    public function testResourceCoordinatesUniqueness(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);

        // Создаем первый ресурс
        MyDB::insert('resource', [
            'x' => 40,
            'y' => 40,
            'planet' => $planetId,
            'type' => 'coal',
            'amount' => 100,
        ]);

        $resource1 = Resource::get(40, 40, $planetId);
        $this->assertInstanceOf(Resource::class, $resource1);

        // Создаем второй ресурс на других координатах
        MyDB::insert('resource', [
            'x' => 41,
            'y' => 41,
            'planet' => $planetId,
            'type' => 'fish',
            'amount' => 200,
        ]);

        $resource2 = Resource::get(41, 41, $planetId);
        $this->assertInstanceOf(Resource::class, $resource2);

        // Проверяем, что ресурсы разные
        $this->assertNotEquals($resource1->id, $resource2->id);
        $this->assertEquals('coal', $resource1->type->id);
        $this->assertEquals('fish', $resource2->type->id);
    }
}
