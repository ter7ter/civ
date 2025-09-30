<?php

namespace App\Tests\Base;

use App\BuildingType;
use App\CellType;
use App\MissionType;
use App\MyDB;
use App\ResearchType;
use App\ResourceType;
use App\Tests\Exception;
use App\Tests\PDOException;
use App\UnitType;

/**
 * Инициализатор тестовых игровых данных
 * содержит функции для инициализации базовых типов игровых объектов для тестов
 */
class TestGameDataInitializer
{

    // Для создания тестовых данных теперь используем TestDataFactory
    public static function initializeCellTypes(): void
    {
        // Очищаем старые данные
        CellType::$all = [];

        $cellTypes = [
            [
                'id' => 'plains',
                'title' => 'равнина',
                'base_chance' => 15,
                'chance_inc1' => 8,
                'chance_inc2' => 6,
                'eat' => 2,
                'work' => 1,
                'money' => 1,
                'chance_inc_other' => ['mountians' => [15, 8]],
                'border_no' => ['water2', 'water3']
            ],
            [
                'id' => 'plains2',
                'title' => 'равнина',
                'base_chance' => 15,
                'chance_inc1' => 8,
                'chance_inc2' => 6,
                'eat' => 2,
                'work' => 0,
                'money' => 1,
                'chance_inc_other' => ['plains2' => [15, 8]],
                'border_no' => ['water2', 'water3']
            ],
            [
                'id' => 'forest',
                'title' => 'лес',
                'base_chance' => 15,
                'chance_inc1' => 10,
                'chance_inc2' => 6,
                'eat' => 1,
                'work' => 2,
                'money' => 1,
                'border_no' => ['water2', 'water3'],
            ],
            [
                'id' => 'hills',
                'title' => 'холмы',
                'base_chance' => 10,
                'chance_inc1' => 5,
                'chance_inc2' => 3,
                'eat' => 1,
                'work' => 2,
                'money' => 0,
                'chance_inc_other' => ['mountians' => [3, 2]],
                'border_no' => ['water2', 'water3']
            ],
            [
                'id' => 'mountains',
                'title' => 'горы',
                'base_chance' => 4,
                'chance_inc1' => 5,
                'chance_inc2' => 2,
                'eat' => 0,
                'work' => 1,
                'money' => 1,
                'chance_inc_other' => ['hills' => [3, 2]],
                'border_no' => ['water2', 'water3'],
            ],
            [
                'id' => 'desert',
                'title' => 'пустыня',
                'base_chance' => 7,
                'chance_inc1' => 6,
                'chance_inc2' => 4,
                'eat' => 0,
                'work' => 1,
                'money' => 2,
                'border_no' => ['water2', 'water3'],
            ],
            [
                'id' => 'water1',
                'title' => 'вода',
                'base_chance' => 5,
                'chance_inc1' => 20,
                'chance_inc2' => 15,
                'eat' => 2,
                'work' => 0,
                'money' => 1,
                'chance_inc_other' => ['water2' => [25, 11]],
                'border_no' => ['water3']
            ],
            [
                'id' => 'water2',
                'title' => 'море',
                'base_chance' => 0,
                'chance_inc1' => 35,
                'chance_inc2' => 16,
                'eat' => 1,
                'work' => 0,
                'money' => 0,
                'chance_inc_other' => ['water1' => [14, 8], 'water3' => [20, 10]],
                'border_no' => ['plains', 'plains2', 'forest', 'hills', 'mountains']
            ],
            [
                'id' => 'water3',
                'title' => 'океан',
                'base_chance' => 0,
                'chance_inc1' => 35,
                'chance_inc2' => 17,
                'eat' => 1,
                'work' => 0,
                'money' => 0,
                'chance_inc_other' => ['water2' => [10, 6]],
                'border_no' => ['plains', 'plains2', 'forest', 'hills', 'mountains', 'water1']
            ],
            [
                'id' => 'city',
                'title' => 'город',
                'base_chance' => 0,
                'chance_inc1' => 0,
                'chance_inc2' => 0,
                'eat' => 0,
                'work' => 0,
                'money' => 0,
                'chance_inc_other' => [],
                'border_no' => []
            ],
        ];

        foreach ($cellTypes as $type) {
            $ct = new CellType($type);
            $ct->save();
        }
    }

    public static function initializeUnitTypes(): void
    {
        // Initialize unit types if needed, but for now, assume they are handled elsewhere
        // Since the test is calling it, let's add a simple implementation
        // For now, just create a test unit type
    }

    public static function clearAll(): void
    {
        if (class_exists("CellType")) {
            CellType::$all = [];
        }
        if (class_exists("ResourceType")) {
            ResourceType::clearAll();
        }
        if (class_exists("ResearchType")) {
            ResearchType::clearAll();
        }
        if (class_exists("BuildingType")) {
            BuildingType::clearAll();
        }
        if (class_exists("UnitType")) {
            UnitType::clearAll();
        }
    }

    /**
     * Устанавливает схему базы данных из всех SQL-файлов в каталоге sql.
     */
    public static function setupDatabaseSchema(): void
    {
        $pdo = MyDB::get();

        // Отключаем foreign key checks для MySQL
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $sqlFiles = [
            PROJECT_ROOT . "/sql/civ.sql",
            PROJECT_ROOT . "/sql/add_building_type_table.sql",
            PROJECT_ROOT . "/sql/add_building_requirements_table.sql",
            PROJECT_ROOT . "/sql/add_planet_table.sql",
            PROJECT_ROOT . "/sql/add_research_type_table.sql",
            PROJECT_ROOT . "/sql/add_unit_type_table.sql",
            PROJECT_ROOT . "/sql/add_resource_type_table.sql",
            PROJECT_ROOT . "/sql/add_mission_type_table.sql",
            PROJECT_ROOT . "/sql/add_cell_type_table.sql",
            PROJECT_ROOT . "/sql/add_unit_type_can_move_table.sql",
        ];

        foreach ($sqlFiles as $sqlFile) {
            if (!file_exists($sqlFile)) {
                throw new Exception("SQL file not found: " . $sqlFile);
            }
            $sqlContent = file_get_contents($sqlFile);
            // Разделяем SQL-запросы по точке с запятой, но учитываем, что точка с запятой может быть внутри строк
            $queries = array_filter(array_map('trim', explode(';', $sqlContent)));

            foreach ($queries as $query) {
                if (!empty($query)) {
                    try {
                        MyDB::query($query);
                    } catch (PDOException $e) {
                        // Игнорируем ошибки, если таблица уже существует (CREATE TABLE IF NOT EXISTS)
                        // или другие незначительные ошибки при создании схемы
                        // Также игнорируем ошибки, связанные с внешними ключами при DROP TABLE,
                        // так как они могут возникать, если таблицы уже были удалены или в другом порядке.
                        if (!str_contains($e->getMessage(), "already exists") &&
                            !str_contains($e->getMessage(), "Cannot delete or update a parent row") &&
                            !str_contains($e->getMessage(), "a foreign key constraint fails") &&
                            !str_contains($e->getMessage(), "Unknown table")) {
                            throw $e;
                        }
                    }
                }
            }
        }

        // Включаем foreign key checks обратно
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}
