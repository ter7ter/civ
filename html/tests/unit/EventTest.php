<?php

namespace App\Tests;

use App\Event;
use App\User;
use App\ResearchType;
use App\City;
use App\BuildingType;
use App\UnitType;
use App\MyDB;

/**
 * Тесты для класса Event
 */
class EventTest extends TestBase
{
    /**
     * Тест получения события по ID
     */
    public function testGet(): void
    {
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        // Создаем событие через БД
        $eventId = MyDB::insert('event', [
            'type' => 'research',
            'user_id' => $userData['id'],
            'object' => 1,
            'source' => null,
        ]);

        $event = Event::get($eventId);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($eventId, $event->id);
        $this->assertEquals('research', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($userData['id'], $event->user->id);
        $this->assertInstanceOf(ResearchType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
        $this->assertNull($event->soruce);
    }

    /**
     * Тест конструктора Event для исследования
     */
    public function testConstructorResearch(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        $data = [
            'id' => 1,
            'type' => 'research',
            'user_id' => $userData['id'],
            'object' => 1, // Гончарное дело
        ];

        $event = new Event($data);

        $this->assertEquals(1, $event->id);
        $this->assertEquals('research', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($userData['id'], $event->user->id);
        $this->assertInstanceOf(ResearchType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
        $this->assertNull($event->soruce);
    }

    /**
     * Тест конструктора Event для строительства здания
     */
    public function testConstructorCityBuilding(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity(['user_id' => $userData['id'], 'planet' => $planetId]);

        $data = [
            'id' => 2,
            'type' => 'city_building',
            'user_id' => $userData['id'],
            'source' => $cityData['id'], // ID города
            'object' => 1, // Бараки
        ];

        $event = new Event($data);

        $this->assertEquals(2, $event->id);
        $this->assertEquals('city_building', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($userData['id'], $event->user->id);
        $this->assertInstanceOf(City::class, $event->soruce);
        $this->assertEquals($cityData['id'], $event->soruce->id);
        $this->assertInstanceOf(BuildingType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
    }

    /**
     * Тест конструктора Event для создания юнита
     */
    public function testConstructorCityUnit(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity(['user_id' => $userData['id'], 'planet' => $planetId]);

        $data = [
            'id' => 3,
            'type' => 'city_unit',
            'user_id' => $userData['id'],
            'source' => $cityData['id'], // ID города
            'object' => 1, // Поселенец
        ];

        $event = new Event($data);

        $this->assertEquals(3, $event->id);
        $this->assertEquals('city_unit', $event->type);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($userData['id'], $event->user->id);
        $this->assertInstanceOf(City::class, $event->soruce);
        $this->assertEquals($cityData['id'], $event->soruce->id);
        $this->assertInstanceOf(UnitType::class, $event->object);
        $this->assertEquals(1, $event->object->id);
    }

    /**
     * Тест сохранения нового события
     */
    public function testSaveNew(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        $data = [
            'type' => 'research',
            'user_id' => $userData['id'],
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
        $this->assertEquals($userData['id'], $savedData['user_id']);
        $this->assertEquals(1, $savedData['object']);
        $this->assertNull($savedData['source']);
    }

    /**
     * Тест сохранения события с источником
     */
    public function testSaveWithSource(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity(['user_id' => $userData['id'], 'planet' => $planetId]);

        $data = [
            'type' => 'city_building',
            'user_id' => $userData['id'],
            'source' => $cityData['id'],
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
        $this->assertEquals('city_building', $savedData['type']);
        $this->assertEquals($userData['id'], $savedData['user_id']);
        $this->assertEquals($cityData['id'], $savedData['source']);
        $this->assertEquals(1, $savedData['object']);
    }

    /**
     * Тест обновления существующего события
     */
    public function testSaveUpdate(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        // Создаем событие через БД
        $eventId = MyDB::insert('event', [
            'type' => 'research',
            'user_id' => $userData['id'],
            'object' => 1,
            'source' => null,
        ]);

        $data = [
            'id' => $eventId,
            'type' => 'research',
            'user_id' => $userData['id'],
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
        $gameData = $this->createTestGame();
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        // Создаем событие через БД
        $eventId = MyDB::insert('event', [
            'type' => 'research',
            'user_id' => $userData['id'],
            'object' => 1,
            'source' => null,
        ]);

        $data = [
            'id' => $eventId,
            'type' => 'research',
            'user_id' => $userData['id'],
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
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);

        // Тест исследования
        $researchEvent = new Event([
            'type' => 'research',
            'user_id' => $userData['id'],
            'object' => 1,
        ]);
        $this->assertEquals('Исследование завершено', $researchEvent->get_title());

        // Тест строительства
        $cityData = $this->createTestCity(['user_id' => $userData['id'], 'planet' => $planetId]);
        $buildingEvent = new Event([
            'type' => 'city_building',
            'user_id' => $userData['id'],
            'source' => $cityData['id'],
            'object' => 1,
        ]);
        $this->assertEquals('Строительство завершено', $buildingEvent->get_title());

        // Тест создания юнита
        $unitEvent = new Event([
            'type' => 'city_unit',
            'user_id' => $userData['id'],
            'source' => $cityData['id'],
            'object' => 1,
        ]);
        $this->assertEquals('Юнит создан', $unitEvent->get_title());

        // Тест неизвестного типа
        $unknownEvent = new Event([
            'type' => 'unknown',
            'user_id' => $userData['id'],
            'object' => 1,
        ]);
        $this->assertEquals('Неизвестное событие', $unknownEvent->get_title());
    }

    /**
     * Тест метода get_text для разных типов событий
     */
    public function testGetText(): void
    {
        $this->initializeGameTypes();
        $gameData = $this->createTestGame();
        $planetId = $this->createTestPlanet(['game_id' => $gameData['id']]);
        $userData = $this->createTestUser(['game' => $gameData['id']]);
        $cityData = $this->createTestCity(['user_id' => $userData['id'], 'planet' => $planetId]);

        // Тест исследования
        $researchEvent = new Event([
            'type' => 'research',
            'user_id' => $userData['id'],
            'object' => 1,
        ]);
        $this->assertStringContainsString('Вы исследовали', $researchEvent->get_text());
        $this->assertStringContainsString('Гончарное дело', $researchEvent->get_text());

        // Тест строительства
        $buildingEvent = new Event([
            'type' => 'city_building',
            'user_id' => $userData['id'],
            'source' => $cityData['id'],
            'object' => 1,
        ]);
        $this->assertStringContainsString('построено', $buildingEvent->get_text());
        $this->assertStringContainsString('бараки', $buildingEvent->get_text());

        // Тест создания юнита
        $unitEvent = new Event([
            'type' => 'city_unit',
            'user_id' => $userData['id'],
            'source' => $cityData['id'],
            'object' => 1,
        ]);
        $this->assertStringContainsString('создан юнит', $unitEvent->get_text());
        $this->assertStringContainsString('Поселенец', $unitEvent->get_text());

        // Тест неизвестного типа
        $unknownEvent = new Event([
            'type' => 'unknown',
            'user_id' => $userData['id'],
            'object' => 1,
        ]);
        $this->assertEquals('Неизвестное событие', $unknownEvent->get_text());
    }
}
