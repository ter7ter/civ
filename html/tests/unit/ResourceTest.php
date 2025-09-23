<?php

require_once __DIR__ . "/../TestBase.php";

/**
 * Тесты для класса Resource
 */
class ResourceTest extends TestBase
{
    /**
     * Тест получения существующего ресурса
     */
    public function testGetExistingResource(): void
    {
        // Создаем тестовый тип ресурса в памяти
        $resourceType = new ResourceType([
            'id' => 1,
            'title' => 'Test Resource',
            'type' => 'luxury',
            'work' => 1,
            'money' => 1,
        ]);

        // Создаем тестовый ресурс
        $resourceData = [
            'x' => 10,
            'y' => 20,
            'planet' => Cell::$map_planet,
            'type' => 1,
            'amount' => 5
        ];
        $resourceId = MyDB::insert('resource', $resourceData);

        $resource = Resource::get(10, 20);

        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals($resourceId, $resource->id);
        $this->assertEquals(10, $resource->x);
        $this->assertEquals(20, $resource->y);
        $this->assertEquals(5, $resource->amount);
        $this->assertEquals('Test Resource', $resource->get_title());
    }

    /**
     * Тест получения несуществующего ресурса
     */
    public function testGetNonExistingResource(): void
    {
        $resource = Resource::get(999, 999);

        $this->assertFalse($resource);
    }

    /**
     * Тест конструктора
     */
    public function testConstruct(): void
    {
        // Создаем тестовый тип ресурса в памяти
        $resourceType = new ResourceType([
            'id' => 2,
            'title' => 'Construct Test Resource',
            'type' => 'strategic',
            'work' => 1,
            'money' => 1,
        ]);

        $data = [
            'id' => 1,
            'x' => 5,
            'y' => 15,
            'planet' => Cell::$map_planet,
            'type' => 2,
            'amount' => 10
        ];

        $resource = new Resource($data);

        $this->assertEquals(1, $resource->id);
        $this->assertEquals(5, $resource->x);
        $this->assertEquals(15, $resource->y);
        $this->assertEquals(10, $resource->amount);
        $this->assertInstanceOf(ResourceType::class, $resource->type);
        $this->assertEquals('Construct Test Resource', $resource->type->title);
    }

    /**
     * Тест сохранения нового ресурса
     */
    public function testSaveNew(): void
    {
        // Создаем тестовый тип ресурса в памяти (не в БД)
        $resourceType = new ResourceType([
            'id' => 3,
            'title' => 'Save Test Resource',
            'type' => 'bonus',
            'work' => 1,
            'money' => 1,
        ]);

        $data = [
            'x' => 1,
            'y' => 2,
            'planet' => Cell::$map_planet,
            'type' => 3,
            'amount' => 7
        ];

        $resource = new Resource($data);
        $resource->save();

        $this->assertNotNull($resource->id);

        // Проверяем, что ресурс сохранен в БД
        $savedData = MyDB::query("SELECT * FROM resource WHERE id = :id", ['id' => $resource->id], 'row');
        $this->assertEquals(1, $savedData['x']);
        $this->assertEquals(2, $savedData['y']);
        $this->assertEquals(7, $savedData['amount']);
        $this->assertEquals(3, $savedData['type']);
    }

    /**
     * Тест обновления существующего ресурса
     */
    public function testSaveUpdate(): void
    {
        // Создаем тестовый тип ресурса в памяти
        $resourceType = new ResourceType([
            'id' => 4,
            'title' => 'Update Test Resource',
            'type' => 'luxury',
            'work' => 1,
            'money' => 1,
        ]);

        // Создаем ресурс
        $data = [
            'x' => 3,
            'y' => 4,
            'planet' => Cell::$map_planet,
            'type' => 4,
            'amount' => 8
        ];
        $resource = new Resource($data);
        $resource->save();
        $originalId = $resource->id;

        // Обновляем
        $resource->amount = 15;
        $resource->save();

        $this->assertEquals($originalId, $resource->id);

        // Проверяем обновление в БД
        $updatedData = MyDB::query("SELECT * FROM resource WHERE id = :id", ['id' => $resource->id], 'row');
        $this->assertEquals(15, $updatedData['amount']);
    }

    /**
     * Тест метода get_title
     */
    public function testGetTitle(): void
    {
        // Создаем тестовый тип ресурса в памяти
        $resourceType = new ResourceType([
            'id' => 5,
            'title' => 'Title Test Resource',
            'type' => 'luxury',
            'work' => 1,
            'money' => 1,
        ]);

        $data = [
            'x' => 6,
            'y' => 7,
            'planet' => Cell::$map_planet,
            'type' => 5,
            'amount' => 1
        ];

        $resource = new Resource($data);

        $this->assertEquals('Title Test Resource', $resource->get_title());
    }
}
