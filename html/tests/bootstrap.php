<?php

/**
 * Bootstrap файл для PHPUnit тестов
 * Инициализирует тестовое окружение с использованием реальных классов игры
 */

// Устанавливаем временную зону по умолчанию
date_default_timezone_set("Europe/Moscow");

// Пути к файлам проекта
define("PROJECT_ROOT", dirname(__DIR__));
define("TESTS_ROOT", __DIR__);

// Подключаем конфигурацию
require_once PROJECT_ROOT . "/config.php";

// Определяем тестовые константы БД
if (!defined("TEST_DB_HOST")) {
    define("TEST_DB_HOST", "db");
}
if (!defined("TEST_DB_USER")) {
    define("TEST_DB_USER", "civ_test");
}
if (!defined("TEST_DB_PASS")) {
    define("TEST_DB_PASS", "civ_test");
}
if (!defined("TEST_DB_PORT")) {
    define("TEST_DB_PORT", 3306);
}

// Сначала загружаем MyDB.class.php
require_once PROJECT_ROOT . "/classes/MyDB.class.php";


if (getenv('PARATEST')) {
    $testToken = getmypid();
    $dbName = 'civ_for_tests_' . $testToken;
} else {
    $dbName = 'civ_for_tests';
}
MyDB::setDBConfig(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB_PORT, $dbName);

MyDB::query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8 COLLATE utf8_general_ci");
    // Подключаемся к MySQL серверу, не указывая базу данных

// Затем загружаем моки для БД
require_once TESTS_ROOT . "/mocks/DatabaseTestAdapter.php";
require_once TESTS_ROOT . "/mocks/MockLoader.php";
require_once TESTS_ROOT . "/mocks/TestHelpers.php";

// Подключаем инициализатор игровых данных
require_once TESTS_ROOT . "/TestGameDataInitializer.php";

// Устанавливаем схему базы данных
TestGameDataInitializer::setupDatabaseSchema();

// Загружаем остальные реальные классы проекта в правильном порядке зависимостей
$classFiles = [
    "CellType.class.php",
    "ResearchType.class.php",
    "ResourceType.class.php",
    "BuildingType.class.php",
    "UnitType.class.php",
    "MissionType.class.php",
    "Planet.class.php",
    "Cell.class.php",
    "Resource.class.php",
    "Research.class.php",
    "Building.class.php",
    "Unit.class.php",
    "City.class.php",
    "Message.class.php",
    "Event.class.php",
    "User.class.php",
    "Game.class.php",
];

foreach ($classFiles as $classFile) {
    $filePath = PROJECT_ROOT . "/classes/" . $classFile;
    if (file_exists($filePath)) {
        require_once $filePath;
    }
}

// Загружаем тестовые базовые классы только если PHPUnit доступен
if (class_exists("PHPUnit\Framework\TestCase")) {
    require_once TESTS_ROOT . "/TestBase.php";
    require_once TESTS_ROOT . "/FunctionalTestBase.php";
}

// Очищаем данные, созданные в оригинальных классах, и инициализируем тестовые данные
TestGameDataInitializer::clearAll();
TestGameDataInitializer::initializeAll();

// Настройка обработки ошибок для тестов
error_reporting(E_ALL); // Максимальный уровень ошибок
ini_set("display_errors", 1); // Отображать ошибки
ini_set("display_startup_errors", 1); // Отображать ошибки запуска

// Устанавливаем обработчик ошибок для тестов
set_error_handler(function ($severity, $message, $file, $line) {
    // Игнорируем ошибки header в тестах
    if (strpos($message, "Cannot modify header information") !== false) {
        return true;
    }
});

// Функция для создания тестовых директорий
function createTestDirectory($path)
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Создание необходимых директорий для тестов
createTestDirectory(TESTS_ROOT . "/results");
createTestDirectory(TESTS_ROOT . "/coverage-html");
createTestDirectory(TESTS_ROOT . "/temp");

// Устанавливаем переменную среды для быстрых тестов
define("RUNNING_TESTS", true);
define("FAST_TEST_MODE", true);

// Функция очистки тестовых данных
function cleanupTestData()
{
    $tempDir = TESTS_ROOT . "/temp";
    if (is_dir($tempDir)) {
        $files = glob($tempDir . "/*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

// Регистрируем функцию очистки при завершении
register_shutdown_function("cleanupTestData");

// Мок для функций, которые могут вызывать проблемы в тестах
if (!function_exists("session_start")) {
    function session_start()
    {
        return true;
    }
}

// Проверка на наличие обязательных расширений PHP
$requiredExtensions = ["pdo", "json", "mbstring"];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (!empty($missingExtensions)) {
    $message =
        "Missing required PHP extensions: " . implode(", ", $missingExtensions);
    if (php_sapi_name() === "cli") {
        echo "Warning: " . $message . "\n";
    }
    error_log($message);
}

// Установка лимитов для тестов
ini_set("memory_limit", "256M");
ini_set("max_execution_time", "240");

// Настройка для вывода детальной информации в случае ошибок
ini_set("log_errors", 1);
ini_set("error_log", TESTS_ROOT . "/results/php_errors.log");
