<?php

/**
 * Загрузчик необходимых моков для тестового окружения
 * Подключает только моки для БД и внешних зависимостей
 */

// 1. Подключаем DatabaseTestAdapter для работы с тестовой БД
require_once __DIR__ . "/DatabaseTestAdapter.php";

// 2. Подключаем MyDBTestWrapper для перехвата запросов к БД
require_once __DIR__ . "/MyDBTestWrapper.php";

// 3. Подключаем вспомогательные функции для тестов
require_once __DIR__ . "/TestHelpers.php";

/**
 * Функция для инициализации тестового окружения
 */
function initializeTestEnvironment()
{
    // Настройки тестовой базы данных
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

    // Настройка переменных окружения для тестов
    $_SERVER["REQUEST_METHOD"] = $_SERVER["REQUEST_METHOD"] ?? "GET";
    $_SERVER["HTTP_HOST"] = $_SERVER["HTTP_HOST"] ?? "test-host";
    $_SERVER["REQUEST_URI"] = $_SERVER["REQUEST_URI"] ?? "/test";

    // Инициализация сессии для тестов
    if (!isset($_SESSION)) {
        $_SESSION = [];
    }

    return true;
}

/**
 * Функция для очистки тестового окружения
 */
function cleanupTestEnvironment()
{
    // Очищаем глобальные переменные
    $_GET = [];
    $_POST = [];
    $_REQUEST = [];
    $_FILES = [];
    $_COOKIE = [];
    $_SESSION = [];

    // Очищаем заголовки
    if (function_exists("clearTestHeaders")) {
        clearTestHeaders();
    }

    return true;
}

// Автоматически инициализируем тестовое окружение при подключении файла
initializeTestEnvironment();
