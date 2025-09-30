<?php

namespace App\Tests\Factory;

use App\Cell;
use App\CellType;
use App\City;
use App\Game;
use App\MyDB;
use App\Planet;
use App\Research;
use App\ResourceType;
use App\Unit;
use App\User;
use App\Tests\Base\TestGameDataInitializer;

/**
 * Фабрика для создания тестовых данных
 * Содержит статические методы для создания объектов игры в тестах
 */
class TestDataFactory
{
    /**
     * Создает тестовую игру
     */
    public static function createTestGame($data = []): Game
    {
        $defaultData = [
            "name" => "Test Game",
            "map_w" => 20,
            "map_h" => 20,
            "turn_type" => "byturn",
            "turn_num" => 1,
        ];

        $gameData = array_merge($defaultData, $data);

        $game = new Game($gameData);
        $game->save();

        return $game;
    }

    /**
     * Создает тестового пользователя
     */
    public static function createTestUser($data = []): User
    {
        // Создаем игру, если её нет
        if (!isset($data["game"])) {
            $game = self::createTestGame();
            $gameId = $game->id;
        } else {
            $gameId = $data["game"];
        }

        $defaultData = [
            "login" => "TestUser" . rand(1000, 9999),
            "color" => "#ff0000",
            "game" => $gameId,
            "turn_order" => 1,
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];

        $userData = array_merge($defaultData, $data);

        $user = new User($userData);
        $user->save();

        return $user;
    }

    /**
     * Создает тестовую клетку
     */
    public static function createTestCell($data = []): Cell
    {
        if (!isset($data["planet"])) {
            throw new \Exception("Planet ID is required to create a test cell");
        }

        // Ensure cell types are initialized
        if (empty(CellType::$all)) {
            TestGameDataInitializer::initializeCellTypes();
        }

        $defaultData = [
            "x" => 0,
            "y" => 0,
            "type" => "plains",
        ];

        $cellData = array_merge($defaultData, $data);

        $cell = new Cell($cellData);
        $cell->save();

        return $cell;
    }

    /**
     * Создает тестовый город
     */
    public static function createTestCity($data = []): City
    {
        if (!isset($data["planet"])) {
            throw new \Exception("Planet ID is required to create a test city");
        }

        // Ensure cell types are initialized
        if (empty(CellType::$all)) {
            TestGameDataInitializer::initializeCellTypes();
        }

        $defaultData = [
            "user_id" => 1,
            "x" => 10,
            "y" => 10,
            "title" => "Test City",
            "population" => 1,
            "pmoney" => 0,
            "presearch" => 0,
        ];

        $cityData = array_merge($defaultData, $data);

        // Создаем клетку, если она не существует
        self::createTestCell([
            "x" => $cityData["x"],
            "y" => $cityData["y"],
            "planet" => $cityData["planet"],
            "type" => "plains",
        ]);

        $user = User::get($cityData["user_id"]);
        $city = City::new_city($user, $cityData["x"], $cityData["y"], $cityData["title"], $cityData["planet"]);

        return $city;
    }


    /**
     * Создает тестовый юнит
     */
    public static function createTestUnit($data = []): Unit
    {
        if (!isset($data["planet"])) {
            throw new \Exception("Planet ID is required to create a test unit");
        }

        // Ensure at least one unit type exists
        if (!class_exists('App\\UnitType') || !method_exists('App\\UnitType','get') || !\App\UnitType::get(1)) {
            self::createTestUnitType();
        }

        $defaultData = [
            "x" => 5,
            "y" => 5,
            "user_id" => 1,
            "type" => 1,
            "health" => 3,
            "points" => 2,
        ];

        $unitData = array_merge($defaultData, $data);

        $unit = new Unit($unitData);
        $unit->save();

        return $unit;
    }

    /**
     * Создает тестовую планету
     */
    public static function createTestPlanet($data = []): Planet
    {
        // Создаем игру, если её нет
        if (!isset($data["game_id"])) {
            $game = self::createTestGame();
            $gameId = $game->id;
        } else {
            $gameId = $data["game_id"];
        }

        $defaultData = [
            "name" => "Test Planet",
            "game_id" => $gameId,
        ];

        $planetData = array_merge($defaultData, $data);

        $planet = new Planet($planetData);
        $planet->save();
        return $planet;
    }

    /**
     * Создает тестовую игру с планетой
     */
    public static function createTestGameWithPlanet($gameData = [], $planetData = []): array
    {
        $game = self::createTestGame($gameData);
        $planet = self::createTestPlanet(array_merge(['game_id' => $game->id], $planetData));

        return [
            'game' => $game,
            'planet' => $planet,
        ];
    }

    /**
     * Создает тестовую игру с планетой и пользователем
     */
    public static function createTestGameWithPlanetAndUser($gameData = [], $planetData = [], $userData = []): array
    {
        $result = self::createTestGameWithPlanet($gameData, $planetData);
        $user = self::createTestUser(array_merge(['game' => $result['game']->id], $userData));

        return array_merge($result, [
            'user' => $user,
        ]);
    }

    /**
     * Создает тестовую игру с планетой, пользователем и городом
     */
    public static function createTestGameWithPlanetUserAndCity($gameData = [], $planetData = [], $userData = [], $cityData = []): array
    {
        $result = self::createTestGameWithPlanetAndUser($gameData, $planetData, $userData);
        $city = self::createTestCity(array_merge([
            'user_id' => $result['user']->id,
            'planet' => $result['planet']->id
        ], $cityData));

        return array_merge($result, [
            'city' => $city,
        ]);
    }

    /**
     * Создает несколько тестовых пользователей
     */
    public static function createTestUsers(array $usersData): array
    {
        $result = [];
        foreach ($usersData as $userData) {
            $result[] = self::createTestUser($userData);
        }
        return $result;
    }

    /**
     * Создает тестовый тип здания
     */
    public static function createTestBuildingType($data = []): \App\BuildingType
    {
        $defaultData = [
            "title" => "амбар",
            "cost" => 30,
            "upkeep" => 1,
            "req_research" => [],
            "req_resources" => [],
            "need_coastal" => false,
            "culture" => 0,
            "culture_bonus" => 0,
            "research_bonus" => 0,
            "money_bonus" => 0,
            "description" => "Базовое здание",
            "city_effects" => [
                "eat_up_multiplier" => 0.5,
                "people_norm" => 0,
                "people_dis" => 0,
                "people_happy" => 0,
                "research_multiplier" => 1,
                "money_multiplier" => 1,
            ],
        ];

        $buildingData = array_merge($defaultData, $data);

        $buildingType = new \App\BuildingType($buildingData);
        $buildingType->save();

        return $buildingType;
    }

    /**
     * Создает тестовый тип юнита
     */
    public static function createTestUnitType($data = []): \App\UnitType
    {
        $defaultData = [
            "title" => "Воин",
            "cost" => 30,
            "upkeep" => 1,
            "attack" => 2,
            "defence" => 1,
            "health" => 1,
            "movement" => 1,
            "can_found_city" => false,
            "need_research" => [],
            "description" => "Базовый боевой юнит",
            "missions" => ["move_to"],
            "can_move" => ["plains" => 1, "plains2" => 1, "forest" => 1, "hills" => 1, "mountains" => 2, "desert" => 1, "city" => 1],
        ];

        $unitData = array_merge($defaultData, $data);

        $unitType = new \App\UnitType($unitData);
        $unitType->save();

        return $unitType;
    }

    /**
     * Создает тестовый тип исследования
     */
    public static function createTestResearchType($data = []): \App\ResearchType
    {
        $defaultData = [
            "title" => "Бронзовое дело",
            "age" => 1,
            "cost" => 80,
            "m_top" => 130,
            "m_left" => 30,
            "age_need" => true,
        ];

        $researchData = array_merge($defaultData, $data);

        $researchType = new \App\ResearchType($researchData);
        $researchType->save();

        return $researchType;
    }

    /**
     * Создает полную тестовую игру с пользователями и планетой
     */
    public static function createCompleteTestGame($gameData = [], $userNames = ["Игрок1", "Игрок2"]): array
    {
        $defaultGameData = [
            "name" => "Тестовая игра",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
        ];
        $gameData = array_merge($defaultGameData, $gameData);

        $game = self::createTestGame($gameData);
        $planet = self::createTestPlanet(["game_id" => $game->id]);

        $users = [];
        foreach ($userNames as $index => $name) {
            $user = self::createTestUser([
                "game" => $game->id,
                "login" => $name,
                "turn_order" => $index + 1,
                "turn_status" => $index === 0 ? "play" : "wait",
            ]);
            $users[] = $user;
        }

        return [
            "game" => $game,
            "planet" => $planet->id,
            "users" => $users,
        ];
    }

    /**
     * Создает тестовый тип миссии
     */
    public static function createTestMissionType($data = []): \App\MissionType
    {
        $defaultData = [
            "id" => "build_city",
            "title" => "Основать город",
            "unit_lost" => true,
            "cell_types" => ["plains"],
            "need_points" => [],
        ];

        $missionData = array_merge($defaultData, $data);

        $missionType = new \App\MissionType($missionData);
        return $missionType;
    }

    public static function createTestCellType(array $data): CellType
    {
        // Если тип уже существует, просто возвращаем его, не изменяя поля
        if (isset($data['id'])) {
            $existing = CellType::get($data['id']);
            if ($existing) {
                return $existing;
            }
        }

        $defaultData = [
            'id' => $data['id'] ?? 'plains',
            'title' => $data['title'] ?? 'равнина',
            'base_chance' => $data['base_chance'] ?? 10,
            'chance_inc1' => $data['chance_inc1'] ?? 5,
            'chance_inc2' => $data['chance_inc2'] ?? 3,
            'work' => $data['work'] ?? 1,
            'eat' => $data['eat'] ?? 1,
            'money' => $data['money'] ?? 1,
            'chance_inc_other' => $data['chance_inc_other'] ?? [],
            'border_no' => $data['border_no'] ?? [],
        ];

        $cellType = new CellType($defaultData);
        return $cellType;
    }

    public static function createTestResearch(array $data): Research
    {
        $defaultData = [
            'title' => 'Test Research',
            'cost' => 100,
            'age' => 1,
            'age_need' => true,
            'm_top' => 30,
            'm_left' => 30,
        ];
        $researchData = array_merge($defaultData, $data);
        $research = new Research($researchData);
        $research->save();
        return $research;
    }

    public static function createTestResourceType(array $data): ResourceType
    {
        $defaultData = [
            'title' => 'Test Resource',
            'type' => 'mineral',
            'work' => 1,
            'eat' => 1,
            'money' => 1,
            'chance' => 0.01,
            'min_amount' => 50,
            'max_amount' => 500,
        ];
        $resourceTypeData = array_merge($defaultData, $data);
        $resourceType = new ResourceType($resourceTypeData);
        $resourceType->save();

        return $resourceType;

    }

    // Backward compatible alias used by some tests
    public static function createResourceType(array $data): ResourceType
    {
        return self::createTestResourceType($data);
    }
}
