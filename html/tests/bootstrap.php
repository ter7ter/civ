<?php

/**
 * Bootstrap файл для PHPUnit тестов
 * Инициализирует тестовое окружение
 */

// Устанавливаем временную зону по умолчанию
date_default_timezone_set("Europe/Moscow");

// Пути к файлам проекта
define("PROJECT_ROOT", dirname(__DIR__));
define("TESTS_ROOT", __DIR__);

// Загружаем основные файлы проекта, но сначала MyDB для тестов
require_once PROJECT_ROOT . "/classes/MyDB.class.php";
require_once PROJECT_ROOT . "/includes.php";

// Настройки тестовой базы данных
// В тестах всегда используем sqlite::memory:, независимо от phpunit.xml
if (!defined("TEST_DB_HOST")) {
    define("TEST_DB_HOST", "sqlite::memory:");
}
if (!defined("TEST_DB_USER")) {
    define("TEST_DB_USER", $_ENV["TEST_DB_USER"] ?? "test_user");
}
if (!defined("TEST_DB_PASS")) {
    define("TEST_DB_PASS", $_ENV["TEST_DB_PASS"] ?? "test_pass");
}
if (!defined("TEST_DB_NAME")) {
    define("TEST_DB_NAME", $_ENV["TEST_DB_NAME"] ?? "test_db");
}
if (!defined("TEST_DB_PORT")) {
    define("TEST_DB_PORT", $_ENV["TEST_DB_PORT"] ?? 3306);
}

// Принудительно устанавливаем sqlite::memory: для тестов
MyDB::setDBConfig(
    "sqlite::memory:",
    TEST_DB_USER,
    TEST_DB_PASS,
    TEST_DB_PORT,
    TEST_DB_NAME,
);

// Подключаем тестовые классы ДО загрузки основных файлов проекта
require_once __DIR__ . "/mocks/MockLoader.php";

require_once __DIR__ . "/test_bootstrap_classes.php";

DatabaseTestAdapter::createTestTablesStatic();

// Настройка обработки ошибок для тестов
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// Функция для создания тестовых данных
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

// Устанавливаем обработчик ошибок для тестов
set_error_handler(function ($severity, $message, $file, $line) {
    // Игнорируем ошибки header в тестах
    if (strpos($message, "Cannot modify header information") !== false) {
        return true;
    }
    // Преобразуем ошибки PHP в исключения для лучшего тестирования
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

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
