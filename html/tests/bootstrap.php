<?php

/**
 * Bootstrap файл для PHPUnit тестов
 * Инициализирует тестовое окружение
 */

// Устанавливаем временную зону по умолчанию
date_default_timezone_set("Europe/Moscow");

// Определяем константы для тестового окружения
if (!defined("TESTING")) {
    define("TESTING", true);
}
if (!defined("TEST_MODE")) {
    define("TEST_MODE", true);
}

// Пути к файлам проекта
define("PROJECT_ROOT", dirname(__DIR__));
define("TESTS_ROOT", __DIR__);

// Настройки тестовой базы данных
if (!defined("TEST_DB_HOST")) {
    define("TEST_DB_HOST", $_ENV["TEST_DB_HOST"] ?? "localhost");
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

// Подключаем тестовые классы ДО загрузки основных файлов проекта
require_once __DIR__ . "/test_bootstrap_classes.php";

// Подавляем вывод заголовков в тестах
if (!function_exists("header")) {
    function header($header, $replace = true, $http_response_code = null)
    {
        // Заглушка для функции header в тестах
        global $test_headers;
        if (!isset($test_headers)) {
            $test_headers = [];
        }
        $test_headers[] = $header;
        return true;
    }
}

// Мок для функции exit/die в тестах
if (!function_exists("test_exit")) {
    function test_exit($message = "")
    {
        throw new Exception("Exit called: " . $message);
    }
}

// Автозагрузчик для классов тестов
spl_autoload_register(function ($className) {
    $testFile = TESTS_ROOT . "/" . str_replace("\\", "/", $className) . ".php";
    if (file_exists($testFile)) {
        require_once $testFile;
        return true;
    }
    return false;
});



// Инициализация сессии для тестов
if (!isset($_SESSION)) {
    $_SESSION = [];
}

// Очистка глобальных переменных для чистого состояния тестов
$_GET = [];
$_POST = [];
$_REQUEST = [];
$_COOKIE = [];
$_FILES = [];
$_SERVER = array_merge($_SERVER, [
    "REQUEST_METHOD" => "GET",
    "HTTP_HOST" => "localhost",
    "REQUEST_URI" => "/test",
    "SCRIPT_NAME" => "/test.php",
    "SERVER_NAME" => "localhost",
    "SERVER_PORT" => 80,
    "HTTPS" => false,
]);

// Подключаем тестовые моки
require_once TESTS_ROOT . "/DatabaseMocks.php";

// Инициализируем тестовое окружение
if (defined("TESTING") && TESTING) {
    initializeTestEnvironment();
}

// В тестах НЕ подключаем основные файлы проекта, чтобы избежать переопределения моков
// Подключаем только config.php для констант
if (file_exists(PROJECT_ROOT . "/config.php")) {
    try {
        require_once PROJECT_ROOT . "/config.php";
    } catch (Exception $e) {
        error_log("Warning: Could not include config.php: " . $e->getMessage());
    }
}

// Подключаем functions.php для вспомогательных функций
if (file_exists(PROJECT_ROOT . "/functions.php")) {
    try {
        require_once PROJECT_ROOT . "/functions.php";
    } catch (Exception $e) {
        error_log("Warning: Could not include functions.php: " . $e->getMessage());
    }
}

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
