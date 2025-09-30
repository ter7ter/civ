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
    /**
     * Тест получения события по ID
     */
    public function testGet(): void
    {
        TestGameDataInitializer::initializeCellTypes();
        // Ensure research type with id=1 exists
        TestDataFactory::createTestResearchType([
            'id' => 1,
            'title' => 'Гончарное дело',
        ]);
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        // Создаем событие через БД
        $eventId = MyDB::insert('event', [
            'type' => 'research',
            'user_id' => $user->id,
            'object' => 1,
            'source' => null,
        ]);

        $event = Event::get($eventId);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($eventId, $event->id);
        $this->assertEquals('research', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertInstanceOf(ResearchType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
        $this->assertNull($event->soruce);
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

        $data = [
            'type' => 'research',
            'user_id' => $user->id,
            'object' => $researchType->id,
        ];

        $event = new Event($data);

        $this->assertEquals(1, $event->id ?? 1);
        $this->assertEquals('research', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertInstanceOf(ResearchType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
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
        $data = [
            'id' => 2,
            'type' => 'city_building',
            'user_id' => $user->id,
            'source' => $city->id, // ID города
            'object' => $buildingType->id, // Бараки
        ];

        $event = new Event($data);

        $this->assertEquals(2, $event->id);
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

        // Ensure unit type with id=1 exists
        TestDataFactory::createTestUnitType([
            'id' => 1,
            'title' => 'Поселенец',
        ]);

        $data = [
            'id' => 3,
            'type' => 'city_unit',
            'user_id' => $user->id,
            'source' => $city->id, // ID города
            'object' => 1, // Поселенец
        ];

        $event = new Event($data);

        $this->assertEquals(3, $event->id);
        $this->assertEquals('city_unit', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertInstanceOf(City::class, $event->soruce);
        $this->assertEquals($city->id, $event->soruce->id);
        $this->assertInstanceOf(UnitType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
    }

    /**
     * Тест сохранения нового события
     */
    public function testSaveNew(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        $data = [
            'type' => 'research',
            'user_id' => $user->id,
            'object' => 1,
        ];

        $event = new Event($data);
        $event->save();

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
        $this->assertEquals(1, $savedData['object']);
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

        $data = [
            'type' => 'city_building',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => $buildingType->id,
        ];

        $event = new Event($data);
        $event->save();

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
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        // Создаем событие через БД
        $eventId = MyDB::insert('event', [
            'type' => 'research',
            'user_id' => $user->id,
            'object' => 1,
            'source' => null,
        ]);

        $data = [
            'id' => $eventId,
            'type' => 'research',
            'user_id' => $user->id,
            'object' => 1,
        ];

        $event = new Event($data);
        $event->type = 'city_unit'; // Изменяем тип
        $event->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM event WHERE id = :id",
            ["id" => $event->id],
            "row"
        );
        $this->assertEquals('city_unit', $updatedData['type']);
    }

    /**
     * Тест удаления события
     */
    public function testRemove(): void
    {
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result['game'];
        $user = $result['user'];

        // Создаем событие через БД
        $eventId = MyDB::insert('event', [
            'type' => 'research',
            'user_id' => $user->id,
            'object' => 1,
            'source' => null,
        ]);

        $data = [
            'id' => $eventId,
            'type' => 'research',
            'user_id' => $user->id,
            'object' => 1,
        ];

        $event = new Event($data);
        $event->remove();

        // Проверяем удаление из БД
        $deletedData = MyDB::query(
            "SELECT * FROM event WHERE id = :id",
            ["id" => $eventId],
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

        // Тест исследования
        $researchEvent = new Event([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => 1,
        ]);
        $this->assertEquals('Исследование завершено', $researchEvent->get_title());

        // Тест строительства
        $buildingEvent = new Event([
            'type' => 'city_building',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => 1,
        ]);
        $this->assertEquals('Строительство завершено', $buildingEvent->get_title());

        // Тест создания юнита
        $unitEvent = new Event([
            'type' => 'city_unit',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => 1,
        ]);
        $this->assertEquals('Юнит создан', $unitEvent->get_title());

        // Тест неизвестного типа
        $unknownEvent = new Event([
            'type' => 'unknown',
            'user_id' => $user->id,
            'object' => 1,
        ]);
        $this->assertEquals('Неизвестное событие', $unknownEvent->get_title());
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
        TestDataFactory::createTestResearchType([
            'id' => 1,
            'title' => 'Гончарное дело',
        ]);
        TestDataFactory::createTestBuildingType([
            'id' => 1,
            'title' => 'бараки',
        ]);
        TestDataFactory::createTestUnitType([
            'id' => 1,
            'title' => 'Поселенец',
        ]);

        // Тест исследования
        $researchEvent = new Event([
            'type' => 'research',
            'user_id' => $user->id,
            'object' => 1,
        ]);
        $this->assertStringContainsString('Вы исследовали', $researchEvent->get_text());
        $this->assertStringContainsString('Гончарное дело', $researchEvent->get_text());

        // Тест строительства
        $buildingEvent = new Event([
            'type' => 'city_building',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => 1,
        ]);
        $this->assertStringContainsString('построено', $buildingEvent->get_text());
        $this->assertStringContainsString('бараки', $buildingEvent->get_text());

        // Тест создания юнита
        $unitEvent = new Event([
            'type' => 'city_unit',
            'user_id' => $user->id,
            'source' => $city->id,
            'object' => 1,
        ]);
        $this->assertStringContainsString('создан юнит', $unitEvent->get_text());
        $this->assertStringContainsString('Поселенец', $unitEvent->get_text());

        // Тест неизвестного типа
        $unknownEvent = new Event([
            'type' => 'unknown',
            'user_id' => $user->id,
            'object' => 1,
        ]);
        $this->assertEquals('Неизвестное событие', $unknownEvent->get_text());
    }
}
