<?php

namespace App\Tests;

use App\MyDB;

/**
 * Тестовый адаптер для базы данных
 */
class DatabaseTestAdapter
{
    /**
     * Очистка всех таблиц с использованием TRUNCATE для производительности
     */
    public static function clearAllTables()
    {
        $pdo = MyDB::get();

        // Порядок важен: сначала таблицы, которые ссылаются на другие (дочерние), потом родительские
        $tables = [
            "event",
            "resource",
            "resource_type",
            "message",
            "research",
            "research_type",
            "resource_group",
            "city_people",
            "building", // Сначала здания
            "city", // Потом города
            "building_requirements_research",
            "building_type",
            "mission_order", // Сначала миссии
            "unit", // Потом юниты
            "unit_type",
            "mission_type",
            "cell", // Клетки могут ссылаться на пользователей
            "user", // Пользователи могут быть в разных таблицах
            "planet", // Планеты ссылаются на игры
            "game", // Игры в конце
        ];

        // Сначала отключаем foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($tables as $table) {
            try {
                //echo "\nTruncating table: {$table}\n";
                $pdo->exec("TRUNCATE TABLE `{$table}`");
            } catch (Exception $e) {
                // Если TRUNCATE не работает, используем DELETE
                try {
                    echo "\nDeleting from table: {$table}\n";
                    $pdo->exec("DELETE FROM `{$table}`");
                } catch (Exception $e2) {
                    // Игнорируем ошибки при очистке
                }
            }
        }

        // Включаем foreign key checks обратно
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }


    /**
     * Полная очистка тестовой БД
     */
    public static function resetTestDatabase()
    {
        self::clearAllTables();

        // Очистка кэшей классов
        if (class_exists("City") && method_exists("City", "clearCache")) {
            City::clearCache();
        }
        if (class_exists("User") && method_exists("User", "clearCache")) {
            User::clearCache();
        }
        if (class_exists("Game") && method_exists("Game", "clearCache")) {
            Game::clearCache();
        }
        if (class_exists("Unit") && method_exists("Unit", "clearCache")) {
            Unit::clearCache();
        }
        if (class_exists("UnitType")) {
            UnitType::clearCache();
        }
        if (class_exists("ResearchType")) {
            ResearchType::clearAll();
        }
        if (class_exists("BuildingType")) {
            BuildingType::clearAll();
        }
        if (class_exists("ResourceType")) {
            ResourceType::clearAll();
        }
        if (class_exists("CellType")) {
            CellType::$all = [];
        }
    }
}
