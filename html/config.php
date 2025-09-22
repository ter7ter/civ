<?php
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

class GameConfig {
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
        9 => 1000000
        ];
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