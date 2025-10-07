<?php

namespace App\Tests;

use App\BuildingType;
use App\City;
use App\Event;
use App\MyDB;
use App\ResearchType;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\CommonTestBase;
use App\Tests\Base\TestGameDataInitializer;
use App\UnitType;
use App\User;

/**
 * Тесты для класса Event
 */
class EventTest extends CommonTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        TestGameDataInitializer::initializeCellTypes();
    }

    /**
     * Тест получения события по ID
     */
    public function testGet(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        // Ensure research type with id=1 exists
        $researchType = TestDataFactory::createTestResearchType([
            'title' => 'Гончарное дело',
        ]);
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $event = TestDataFactory::createTestEvent([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => $researchType->id,
            'source' => null,
        ]);

        $eventGet = Event::get($event->id);

        $this->assertInstanceOf(Event::class, $eventGet);
        $this->assertEquals($event->id, $eventGet->id);
        $this->assertEquals('research', $eventGet->type);
        $this->assertInstanceOf(User::class, $eventGet->user);
        $this->assertEquals($user->id, $eventGet->user->id);
        $this->assertInstanceOf(ResearchType::class, $eventGet->object);
        $this->assertEquals($researchType->id, $eventGet->object->id);
        $this->assertNull($eventGet->soruce);
    }

    /**
     * Тест конструктора Event для исследования
     */
    public function testConstructorResearch(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $researchType = TestDataFactory::createTestResearchType([
            'id' => 1,
            "title" => "Гончарное дело",
            "age" => 1,
            "cost" => 50,
            "m_top" => 30,
            "m_left" => 30,
            "age_need" => true
        ]);

        $event = TestDataFactory::createTestEvent([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => $researchType->id,
        ]);

        $this->assertEquals('research', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertInstanceOf(ResearchType::class, $event->object);
        $this->assertEquals($researchType->id, $event->object->id);
        $this->assertNull($event->soruce);
    }

    /**
     * Тест конструктора Event для строительства здания
     */
    public function testConstructorCityBuilding(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity(['user_id' => $user->id, 'planet' => $planetId]);

        // Create building type for test
        $buildingType = TestDataFactory::createTestBuildingType([
            'title' => 'бараки',
            'cost' => 30,
            'upkeep' => 1,
        ]);

        $event = TestDataFactory::createTestEvent([
            'type' => 'city_building',
            'user_id' => $user->id,
            'source' => $city->id, // ID города
            'object' => $buildingType->id, // Бараки
        ]);

        $this->assertEquals('city_building', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertInstanceOf(City::class, $event->soruce);
        $this->assertEquals($city->id, $event->soruce->id);
        $this->assertInstanceOf(BuildingType::class, $event->object);
        $this->assertEquals($buildingType->id, $event->object->id);
    }

    /**
     * Тест конструктора Event для создания юнита
     */
    public function testConstructorCityUnit(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity(['user_id' => $user->id, 'planet' => $planetId]);

        $unitType = TestDataFactory::createTestUnitType([
            'title' => 'Поселенец',
        ]);

        $event = TestDataFactory::createTestEvent([
            'type' => 'city_unit',
            'user_id' => $user->id,
            'source' => $city->id, // ID города
            'object' => $unitType->id, // Поселенец
        ]);

        $this->assertEquals('city_unit', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertInstanceOf(City::class, $event->soruce);
        $this->assertEquals($city->id, $event->soruce->id);
        $this->assertInstanceOf(UnitType::class, $event->object);
        $this->assertEquals($unitType->id, $event->object->id);
    }

    /**
     * Тест сохранения нового события
     */
    public function testSaveNew(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $researchType = TestDataFactory::createTestResearchType();

        $event = TestDataFactory::createTestEvent([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => $researchType->id,
        ]);

        $this->assertNotNull($event->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM event WHERE id = :id",
            ["id" => $event->id],
            "row"
        );
        $this->assertNotNull($savedData);
        $this->assertEquals('research', $savedData['type']);
        $this->assertEquals($user->id, $savedData['user_id']);
        $this->assertEquals($researchType->id, $savedData['object']);
        $this->assertNull($savedData['source']);
    }

    /**
     * Тест сохранения события с источником
     */
    public function testSaveWithSource(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        $game = TestDataFactory::createTestGame();
        $planet = TestDataFactory::createTestPlanet(['game_id' => $game->id]);
        $planetId = $planet->id;
        $user = TestDataFactory::createTestUser(['game' => $game->id]);
        $city = TestDataFactory::createTestCity(['user_id' => $user->id, 'planet' => $planetId]);
        $buildingType = TestDataFactory::createTestBuildingType(['title' => 'бараки']);

        $event = TestDataFactory::createTestEvent([
            'type' => 'city_building',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => $buildingType->id,
        ]);

        $this->assertNotNull($event->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM event WHERE id = :id",
            ["id" => $event->id],
            "row"
        );
        $this->assertNotNull($savedData);
        $this->assertEquals('city_building', $savedData['type']);
        $this->assertEquals($user->id, $savedData['user_id']);
        $this->assertEquals($city->id, $savedData['source']);
        $this->assertEquals($buildingType->id, $savedData['object']);
    }

    /**
     * Тест обновления существующего события
     */
    public function testSaveUpdate(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $game = $result['game'];
        $user = $result['user'];

        $researchType = TestDataFactory::createTestResearchType();

        $event = TestDataFactory::createTestEvent([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => $researchType->id,
            'source' => null,
        ]);

        $unitType = TestDataFactory::createTestUnitType();

        $event->type = 'city_unit'; // Изменяем тип
        $event->object = $unitType; // Изменяем объект
        $event->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM event WHERE id = :id",
            ["id" => $event->id],
            "row"
        );
        $this->assertEquals('city_unit', $updatedData['type']);
        $this->assertEquals($unitType->id, $updatedData['object']);
    }

    /**
     * Тест удаления события
     */
    public function testRemove(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $game = $result['game'];
        $user = $result['user'];

        $researchType = TestDataFactory::createTestResearchType();

        $event = TestDataFactory::createTestEvent([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => $researchType->id,
            'source' => null,
        ]);
        $event->remove();

        // Проверяем удаление из БД
        $deletedData = MyDB::query(
            "SELECT * FROM event WHERE id = :id",
            ["id" => $event->id],
            "row"
        );
        $this->assertFalse($deletedData);
    }

    /**
     * Тест метода get_title для разных типов событий
     */
    public function testGetTitle(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $game = $result['game'];
        $planetId = $result['planet'];
        $user = $result['user'];
        $city = $result['city'];

        $researchType = TestDataFactory::createTestResearchType();
        $researchEvent = TestDataFactory::createTestEvent([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => $researchType->id,
        ]);

        $this->assertEquals('Исследование завершено', $researchEvent->getTitle());

        // Тест строительства
        $buildingType = TestDataFactory::createTestBuildingType();
        $buildingEvent = TestDataFactory::createTestEvent([
            'type' => 'city_building',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => $buildingType->id,
        ]);
        $this->assertEquals('Строительство завершено', $buildingEvent->getTitle());

        // Тест создания юнита
        $unitType = TestDataFactory::createTestUnitType();
        $unitEvent = TestDataFactory::createTestEvent([
            'type' => 'city_unit',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => $unitType->id,
        ]);
        $this->assertEquals('Юнит создан', $unitEvent->getTitle());
    }

    /**
     * Тест метода get_text для разных типов событий
     */
    public function testGetText(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $game = $result['game'];
        $planetId = $result['planet'];
        $user = $result['user'];
        $city = $result['city'];

        // Ensure base types exist for text lookups
        $researchType = TestDataFactory::createTestResearchType([
            'title' => 'Гончарное дело',
        ]);
        $buildingType = TestDataFactory::createTestBuildingType([
            'title' => 'бараки',
        ]);
        $unitType = TestDataFactory::createTestUnitType([
            'title' => 'Поселенец',
        ]);

        // Тест исследования
        $researchEvent = TestDataFactory::createTestEvent([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => $researchType->id,
        ]);
        $this->assertStringContainsString('Вы исследовали', $researchEvent->get_text());
        $this->assertStringContainsString('Гончарное дело', $researchEvent->get_text());

        // Тест строительства
        $buildingEvent = TestDataFactory::createTestEvent([
            'type' => 'city_building',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => $buildingType->id,
        ]);
        $this->assertStringContainsString('построено', $buildingEvent->get_text());
        $this->assertStringContainsString('бараки', $buildingEvent->get_text());

        // Тест создания юнита
        $unitEvent = TestDataFactory::createTestEvent([
            'type' => 'city_unit',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => $buildingType->id,
        ]);
        $this->assertStringContainsString('создан юнит', $unitEvent->get_text());
        $this->assertStringContainsString('Поселенец', $unitEvent->get_text());
    }
}
