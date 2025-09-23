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

// Сначала загружаем MyDB.class.php
require_once PROJECT_ROOT . "/classes/MyDB.class.php";

// Затем загружаем моки для БД
require_once TESTS_ROOT . "/mocks/DatabaseTestAdapter.php";
require_once TESTS_ROOT . "/mocks/MyDBTestWrapper.php";
require_once TESTS_ROOT . "/mocks/MockLoader.php";

// Подключаем инициализатор игровых данных
require_once TESTS_ROOT . "/TestGameDataInitializer.php";

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

// Очищаем данные, созданные в оригинальных классах, и инициализируем тестовые данные
TestGameDataInitializer::clearAll();
TestGameDataInitializer::initializeAll();

// Создаем тестовые таблицы БД
DatabaseTestAdapter::createTestTables();

// Настройка обработки ошибок для тестов
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// Устанавливаем обработчик ошибок для тестов
set_error_handler(function ($severity, $message, $file, $line) {
    // Игнорируем ошибки header в тестах
    if (strpos($message, "Cannot modify header information") !== false) {
        return true;
    }
    // Игнорируем deprecated warnings о ${var} синтаксисе в PHP 8.4
    if (strpos($message, "Using \${var} in strings is deprecated") !== false) {
        return true;
    }
    // Игнорируем deprecated warnings о var в строках
    if (strpos($message, "Using ${var} in strings is deprecated") !== false) {
        return true;
    }
    // Игнорируем deprecated warnings о динамических свойствах
    if (strpos($message, "Creation of dynamic property") !== false) {
        return true;
    }
    // Игнорируем все E_DEPRECATED warnings
    if ($severity === E_DEPRECATED) {
        return true;
    }
    // Преобразуем только серьезные ошибки в исключения
    if ($severity >= E_ERROR) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    // Остальные ошибки просто логируем
    if (error_reporting() & $severity) {
        error_log("PHP Warning in tests: $message in $file on line $line");
    }
    return true;
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
ini_set("max_execution_time", "300"); // 5 минут для тестов

// Настройка для вывода детальной информации в случае ошибок
ini_set("log_errors", 1);
ini_set("error_log", TESTS_ROOT . "/results/php_errors.log");
