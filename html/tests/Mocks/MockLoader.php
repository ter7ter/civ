<?php

/**
 * Загрузчик необходимых моков для тестового окружения
 * Подключает только моки для БД и внешних зависимостей
 */

// Подключаем DatabaseTestAdapter для работы с тестовой БД
require_once __DIR__ . "/DatabaseTestAdapter.php";

//Подключаем вспомогательные функции для тестов
require_once __DIR__ . "/TestHelpers.php";

/**
 * Функция для инициализации тестового окружения
 */
function initializeTestEnvironment()
{
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
