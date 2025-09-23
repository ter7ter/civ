<?php

/**
 * Основной загрузчик моков для тестового окружения
 * Подключает все необходимые файлы с моками в правильном порядке
 */

// Подключаем моки в правильном порядке зависимостей

// 1. Сначала подключаем DatabaseTestAdapter, так как от него зависят другие классы
require_once __DIR__ . '/DatabaseTestAdapter.php';

// 2. Подключаем MyDBTestWrapper
require_once __DIR__ . '/MyDBTestWrapper.php';

// 3. Подключаем моки основных классов
require_once __DIR__ . '/GameTestMock.php';
require_once __DIR__ . '/UserTestMock.php';

// 4. Подключаем вспомогательные функции
require_once __DIR__ . '/TestHelpers.php';

/**
 * Функция для автоматической инициализации всех моков
 */
function initializeAllMocks()
{
    // Инициализируем тестовое окружение
    initializeTestEnvironment();

    // Можно добавить дополнительную логику инициализации здесь

    return true;
}

// Автоматически инициализируем моки при подключении файла
initializeAllMocks();
