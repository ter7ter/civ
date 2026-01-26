<?php

namespace App;

/**
 * Настройки БД
 */
define("DB_HOST", "db");
define("DB_USER", "root");
define("DB_PASS", "rootpass");
define("DB_PORT", "3306");
define("DB_NAME", "civ");
/**
 * Настройки игры
 */
define("POPULATION_PEOPLE_DIS", 4); //С какого размера города появляются недовольные жители
define("BASE_EAT_UP", 20); //Требуется еды для роста города

if (!defined("USE_TRANSACTION_MODE")) {
    define("USE_TRANSACTION_MODE", true);
}

define("LOG_FILE", __DIR__ . "/log.txt");

// Показывать ошибки сервера пользователю
define("SHOW_SERVER_ERRORS", true);

class GameConfig
{
    /**
     * ID типа юнита поселенца
     * @var int
     */
    public static $START_UNIT_SETTLER_TYPE = 1;

    /**
     * Необходимое количество культуры для уровней
     * @var array
     */
    public static $CULTURE_LEVELS = [
        1 => 20,
        2 => 100,
        3 => 500,
        4 => 2500,
        5 => 10000,
        6 => 50000,
        7 => 100000,
        8 => 500000,
        9 => 1000000,
    ];
    //Уровень культуры для большей территории для жителей
    public static $CITIZEN_MEDIUM = 1;
    public static $POPULATION_MEDIUM = 6;

    public static $POPULATION_BIG = 12;

    public static $ROAD_MOVE_POINTS = 0.25;

    /**
     * Номер последнего века
     * @var int
     */
    public static $MAX_AGE = 2;

    public static $WORK_DIST1 = 6;
}
