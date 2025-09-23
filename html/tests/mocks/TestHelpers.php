<?php

/**
 * Вспомогательные функции для тестового окружения
 */

/**
 * Функция для инициализации тестового окружения
 */
function initializeTestEnvironment()
{
    // Устанавливаем глобальные переменные для использования тестовых классов
    global $TESTING_MODE;
    $TESTING_MODE = true;
}

/**
 * Функция для получения тестового экземпляра Game
 */
function getTestGameClass($data = [])
{
    return new GameTestMock($data);
}

/**
 * Функция для получения тестового экземпляра User
 */
function getTestUserClass($data = [])
{
    return new UserTestMock($data);
}
