<?php

use App\MyDB;
use App\Tests\Base\TestGameDataInitializer;
use App\Tests\Mocks\DatabaseTestAdapter;

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
define("USER_TRANSACTION_MODE", false);

// Загружаем автозагрузчик Composer
require_once PROJECT_ROOT . "/vendor/autoload.php";

// Ручная автозагрузка для App namespace, если composer autoload не работает
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0 && strpos($class, 'App\\Tests\\') === false) {
        $relativeClass = substr($class, 4);
        $file = PROJECT_ROOT . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Автозагрузка для App\\Tests namespace
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\Tests\\') === 0) {
        $relativeClass = substr($class, 10);
        $file = PROJECT_ROOT . '/tests/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Загружаем MyDB для настройки БД
require_once PROJECT_ROOT . "/src/MyDB.php";

if (getenv('PARATEST')) {
    $testToken = getmypid();
    $dbName = 'civ_for_tests_' . $testToken;
} else {
    $dbName = 'civ_for_tests';
}
//$dbName = 'civ_for_tests';

// Сначала подключаемся без базы данных, чтобы создать её
try {
    $pdo = new PDO("mysql:host=" . TEST_DB_HOST . ";port=" . TEST_DB_PORT, TEST_DB_USER, TEST_DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8 COLLATE utf8_general_ci");
} catch (PDOException $e) {
    error_log("Failed to create test database: " . $e->getMessage());
    throw $e;
}

MyDB::setDBConfig(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB_PORT, $dbName);
// Подключаемся к MySQL серверу, не указывая базу данных
MyDB::startTransaction();

// Затем загружаем моки для БД
require_once TESTS_ROOT . "/Mocks/DatabaseTestAdapter.php";
require_once TESTS_ROOT . "/Mocks/MockLoader.php";
require_once TESTS_ROOT . "/Mocks/TestHelpers.php";

// Устанавливаем схему базы данных
TestGameDataInitializer::setupDatabaseSchema();

if (class_exists('App\\CellType')) {
    TestGameDataInitializer::initializeCellTypes();
}

// Initialize default resource types for tests
if (class_exists('App\\ResourceType')) {
    $defaultResourceTypes = [
        [
            'id' => 'coal',
            'title' => 'уголь',
            'type' => 'mineral',
            'work' => 1,
            'eat' => 0,
            'money' => 1,
            'chance' => 0.02,
            'min_amount' => 50,
            'max_amount' => 200,
        ],
        [
            'id' => 'fish',
            'title' => 'рыба',
            'type' => 'food',
            'work' => 0,
            'eat' => 2,
            'money' => 0,
            'chance' => 0.02,
            'min_amount' => 30,
            'max_amount' => 150,
        ],
        [
            'id' => 'furs',
            'title' => 'меха',
            'type' => 'luxury',
            'work' => 0,
            'eat' => 0,
            'money' => 2,
            'chance' => 0.01,
            'min_amount' => 20,
            'max_amount' => 100,
        ],
        [
            'id' => 'horse',
            'title' => 'Лошади',
            'type' => 'strategic',
            'work' => 0,
            'eat' => 0,
            'money' => 0,
            'chance' => 0.005,
            'min_amount' => 10,
            'max_amount' => 50,
        ],
    ];
    foreach ($defaultResourceTypes as $rt) {
        if (!\App\ResourceType::get($rt['id'])) {
            $res = new \App\ResourceType($rt);
            $res->save();
        }
    }
}

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
    if (php_sapi_name() === "cli" && !defined('RUNNING_TESTS')) {
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
