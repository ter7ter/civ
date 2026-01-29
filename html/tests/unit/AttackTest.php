<?php

namespace App\Tests;

use App\Game;
use App\City;
use App\Cell;
use App\MyDB;
use App\UnitType;
use App\User;
use App\CellType;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\FunctionalTestBase;
use App\Tests\Base\TestGameDataInitializer;

/**
 * Тестирование боевой системы
 */
class AttackTest extends FunctionalTestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Подключаем классы проекта
        require_once PROJECT_ROOT . "/includes.php";

        // Инициализируем типы клеток для тестов
        TestGameDataInitializer::initializeCellTypes();
    }

    /**
     * Тестируем атаку юнита другого игрока
     */
    public function testAttackEnemyUnit()
    {
        // Создаем тестовую игру с двумя игроками
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result["game"];
        $user1 = $result["user"];
        
        // Создаем второго пользователя в той же игре
        $user2 = TestDataFactory::createTestUser(["game" => $game->id]);

        // Перезагружаем игру, чтобы получить актуальный список пользователей
        $game = \App\Game::get($game->id);

        // Создаем планету для игры
        $planet = $result["planet"]; // планета уже создана в createTestGameWithPlanetAndUser

        // Создаем боевой юнит типа для первого игрока (воин)
        $warriorType = TestDataFactory::createTestUnitType([
            "title" => "Воин",
            "attack" => 2,
            "defence" => 1,
            "health" => 3,
            "points" => 1,
            "missions" => ["move_to", "attack"]
        ]);
        
        // Создаем юниты для двух игроков на одной клетке
        $unit1 = TestDataFactory::createTestUnit([
            "user_id" => $user1->id,
            "type" => $warriorType->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planet->id,
            "health" => 3,
            "health_max" => 3,
            "points" => 1
        ]);

        $unit2 = TestDataFactory::createTestUnit([
            "user_id" => $user2->id,
            "type" => $warriorType->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planet->id,
            "health" => 3,
            "health_max" => 3,
            "points" => 1
        ]);

        // Проверяем, что юниты созданы
        $this->assertNotNull($unit1);
        $this->assertNotNull($unit2);
        $this->assertNotEquals($unit1->user->id, $unit2->user->id);

        // Проверяем, что оба юнита находятся на одной клетке
        $this->assertEquals($unit1->x, $unit2->x);
        $this->assertEquals($unit1->y, $unit2->y);
        $this->assertEquals($unit1->planet, $unit2->planet);

        // Проверяем начальные характеристики
        $this->assertEquals(3, $unit1->health);
        $this->assertEquals(3, $unit2->health);

        // Проверяем, что атакующий юнит может выполнить миссию атаки
        $possibleMissions = $unit1->getMissionTypes($unit1->x, $unit1->y);
        $this->assertArrayHasKey('attack', $possibleMissions);

        // Запускаем атаку
        $result_message = null;
        $result = $unit1->startMission($possibleMissions['attack'], $result_message);
        $this->assertTrue($result);

        // Перезагружаем юниты, чтобы получить обновленные данные
        $unit1 = Unit::get($unit1->id);
        $unit2 = Unit::get($unit2->id);

        // После атаки с равными параметрами, в новой системе побеждает случайный юнит
        // Проверим, что только один юнит остался жив
        $unit1_alive = ($unit1 && $unit1->health > 0);
        $unit2_alive = ($unit2 && $unit2->health > 0);
        
        // Только один юнит должен быть жив
        $this->assertTrue($unit1_alive xor $unit2_alive, "Only one unit should survive the battle");
    }

    /**
     * Тестируем, что мирный юнит не может атаковать
     */
    public function testPeacefulUnitCannotAttack()
    {
        // Создаем тестовую игру с двумя игроками
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result["game"];
        $user1 = $result["user"];
        
        // Создаем второго пользователя в той же игре
        $user2 = TestDataFactory::createTestUser(["game" => $game->id]);

        // Перезагружаем игру, чтобы получить актуальный список пользователей
        $game = \App\Game::get($game->id);

        // Создаем планету для игры
        $planet = $result["planet"];

        // Создаем мирный юнит (поселенец) для первого игрока
        $settlerType = TestDataFactory::createTestUnitType([
            "title" => "Поселенец",
            "attack" => 0,
            "defence" => 1,
            "health" => 1,
            "points" => 1,
            "can_found_city" => true,
            "missions" => ["move_to", "build_city"]
        ]);
        
        $settler = TestDataFactory::createTestUnit([
            "user_id" => $user1->id,
            "type" => $settlerType->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planet->id
        ]);

        // Создаем боевой юнит для второго игрока на той же клетке
        $warriorType = TestDataFactory::createTestUnitType([
            "title" => "Воин",
            "attack" => 1,
            "defence" => 1,
            "health" => 3,
            "points" => 1,
            "missions" => ["move_to", "attack"]
        ]);
        
        $warrior = TestDataFactory::createTestUnit([
            "user_id" => $user2->id,
            "type" => $warriorType->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planet->id
        ]);

        // Проверяем, что поселенец не может атаковать
        $possibleMissions = $settler->getMissionTypes($settler->x, $settler->y);
        $this->assertArrayNotHasKey('attack', $possibleMissions);
    }

    /**
     * Тестируем, что юнит не может атаковать самого себя
     */
    public function testUnitCannotAttackSelf()
    {
        // Создаем тестовую игру с одним игроком
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result["game"];
        $user = $result["user"];
        $planet = $result["planet"];

        // Перезагружаем игру, чтобы получить актуальный список пользователей
        $game = \App\Game::get($game->id);

        // Создаем боевой юнит для игрока
        $warriorType = TestDataFactory::createTestUnitType([
            "title" => "Воин",
            "attack" => 1,
            "defence" => 1,
            "health" => 3,
            "points" => 1,
            "missions" => ["move_to", "attack"]
        ]);
        
        $warrior = TestDataFactory::createTestUnit([
            "user_id" => $user->id,
            "type" => $warriorType->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planet->id
        ]);

        // Проверяем, что юнит не может атаковать, когда на клетке нет вражеских юнитов
        $possibleMissions = $warrior->getMissionTypes($warrior->x, $warrior->y);
        $this->assertArrayNotHasKey('attack', $possibleMissions);
    }

    /**
     * Тестируем атаку, в результате которой один юнит уничтожается
     */
    public function testAttackThatDestroysEnemyUnit()
    {
        // Создаем тестовую игру с двумя игроками
        $result = TestDataFactory::createTestGameWithPlanetAndUser();
        $game = $result["game"];
        $user1 = $result["user"];
        
        // Создаем второго пользователя в той же игре
        $user2 = TestDataFactory::createTestUser(["game" => $game->id]);

        // Перезагружаем игру, чтобы получить актуальный список пользователей
        $game = \App\Game::get($game->id);

        // Создаем планету для игры
        $planet = $result["planet"];

        // Создаем сильного юнита для первого игрока (рыцарь)
        $knightType = TestDataFactory::createTestUnitType([
            "title" => "Рыцарь",
            "attack" => 4,
            "defence" => 2,
            "health" => 3,
            "points" => 2,
            "missions" => ["move_to", "attack"]
        ]);
        
        $knight = TestDataFactory::createTestUnit([
            "user_id" => $user1->id,
            "type" => $knightType->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planet->id,
            "health" => 3,
            "health_max" => 3,
            "points" => 2
        ]);

        // Создаем слабого юнита для второго игрока на той же клетке (воин)
        $warriorType = TestDataFactory::createTestUnitType([
            "title" => "Воин",
            "attack" => 1,
            "defence" => 1,
            "health" => 1, // Мало здоровья
            "points" => 1,
            "missions" => ["move_to", "attack"]
        ]);
        
        $warrior = TestDataFactory::createTestUnit([
            "user_id" => $user2->id,
            "type" => $warriorType->id,
            "x" => 5,
            "y" => 5,
            "planet" => $planet->id,
            "health" => 1, // Мало здоровья
            "health_max" => 1,
            "points" => 1
        ]);

        // Проверяем, что оба юнита находятся на одной клетке
        $this->assertEquals($knight->x, $warrior->x);
        $this->assertEquals($knight->y, $warrior->y);
        $this->assertEquals($knight->planet, $warrior->planet);

        // Проверяем, что атакующий юнит может выполнить миссию атаки
        $possibleMissions = $knight->getMissionTypes($knight->x, $knight->y);
        $this->assertArrayHasKey('attack', $possibleMissions);

        // Запускаем атаку
        $result_message = null;
        $result = $knight->startMission($possibleMissions['attack'], $result_message);
        $this->assertTrue($result);

        // Проверяем, что слабый юнит уничтожен
        $updatedKnight = Unit::get($knight->id);
        $updatedWarrior = Unit::get($warrior->id);

        // Рыцарь должен выжить (его атака 4, защита воина 1, урон = 4-1=3, но у воина только 1 здоровье)
        $this->assertNotNull($updatedKnight, "Attacking knight should survive");
        
        // Воин должен быть уничтожен (его атака 1, защита рыцаря 2, урон = 0, но он получит 3 урона от рыцаря)
        $this->assertNull($updatedWarrior, "Enemy warrior should be destroyed");
    }
}
