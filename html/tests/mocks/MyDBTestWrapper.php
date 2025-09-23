<?php

/**
 * Мок-обертка для замены MyDB в тестах
 * Теперь делегирует вызовы непосредственно к MyDB, который работает с SQLite в тестах
 */
class MyDBTestWrapper
{
    public static $dbhost = "";
    public static $dbuser = "";
    public static $dbpass = "";
    public static $dbname = "";
    public static $dbport = "";

    public static function resetTestDatabase()
    {
        DatabaseTestAdapter::resetTestDatabase();
    }

    public static function clearQueries()
    {
        DatabaseTestAdapter::clearQueries();
    }

    public static function getQueries()
    {
        return DatabaseTestAdapter::getQueries();
    }
}
