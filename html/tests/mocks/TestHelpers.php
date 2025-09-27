<?php

/**
 * Вспомогательные функции для тестов
 * Обеспечивают совместимость и упрощают создание тестовых объектов
 */

/**
 * Создает тестовый экземпляр игры с использованием реального класса Game
 */
function createTestGame($data = [])
{
    $defaultData = [
        "name" => "Test Game",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "turn_num" => 1,
    ];

    $gameData = array_merge($defaultData, $data);
    return new Game($gameData);
}

/**
 * Создает тестовый экземпляр пользователя с использованием реального класса User
 */
function createTestUser($data = [])
{
    $defaultData = [
        "login" => "TestUser",
        "color" => "#ff0000",
        "game" => 1,
        "turn_order" => 1,
        "turn_status" => "wait",
        "money" => 50,
        "age" => 1,
    ];

    $userData = array_merge($defaultData, $data);
    return new User($userData);
}

/**
 * Создает тестовый экземпляр клетки с использованием реального класса Cell
 */
function createTestCell($data = [])
{
    $defaultData = [
        "x" => 0,
        "y" => 0,
        "planet" => 1,
        "type" => "plains",
    ];

    $cellData = array_merge($defaultData, $data);
    return new Cell($cellData);
}

/**
 * Создает тестовый экземпляр города
 */
function createTestCity($data = [])
{
    $defaultData = [
        "user_id" => 1,
        "x" => 10,
        "y" => 10,
        "planet" => 1,
        "title" => "Test City",
        "people" => 1,
    ];

    $cityData = array_merge($defaultData, $data);
    return new City($cityData);
}

/**
 * Создает тестовый экземпляр юнита
 */
function createTestUnit($data = [])
{
    $defaultData = [
        "x" => 5,
        "y" => 5,
        "planet" => 1,
        "user_id" => 1,
        "type" => 1,
        "health" => 3,
        "points" => 2,
    ];

    $unitData = array_merge($defaultData, $data);
    return new Unit($unitData);
}

/**
 * Создает тестовое сообщение
 */
function createTestMessage($data = [])
{
    $defaultData = [
        "form_id" => null,
        "to_id" => 1,
        "text" => "Test message",
        "type" => "system",
    ];

    $messageData = array_merge($defaultData, $data);
    return new Message($messageData);
}

/**
 * Создает тестовое исследование
 */
function createTestResearch($data = [])
{
    $defaultData = [
        "user_id" => 1,
        "type" => 1,
    ];

    $researchData = array_merge($defaultData, $data);
    return new Research($researchData);
}

/**
 * Создает тестовый ресурс
 */
function createTestResource($data = [])
{
    $defaultData = [
        "x" => 0,
        "y" => 0,
        "planet" => 1,
        "type" => 1,
        "amount" => 10,
    ];

    $resourceData = array_merge($defaultData, $data);
    return new Resource($resourceData);
}

/**
 * Мок для функции header() чтобы отслеживать редиректы в тестах
 */
if (!function_exists("test_header")) {
    function test_header($header, $replace = true, $response_code = null)
    {
        global $test_headers;
        if (!isset($test_headers)) {
            $test_headers = [];
        }
        $test_headers[] = $header;
        return true;
    }
}

/**
 * Мок для exit/die в тестах
 */
if (!function_exists("test_exit")) {
    function test_exit($message = "")
    {
        throw new TestExitException("Exit called: " . $message);
    }
}

/**
 * Исключение для обработки exit() в тестах
 */
class TestExitException extends Exception {}

/**
 * Функция для установки заголовков в тестах
 */
function send_header($location)
{
    global $test_headers;
    if (!isset($test_headers)) {
        $test_headers = [];
    }
    $test_headers[] = $location;
}

/**
 * Функция для завершения скрипта в тестах
 */
function terminate_script()
{
    throw new TestExitException();
}

/**
 * Функция для получения заголовков, установленных в тестах
 */
function getTestHeaders()
{
    global $test_headers;
    return isset($test_headers) ? $test_headers : [];
}

/**
 * Функция для очистки тестовых заголовков
 */
function clearTestHeaders()
{
    global $test_headers;
    $test_headers = [];
}

/**
 * Вспомогательная функция для безопасной очистки кэшей классов
 */
function clearAllClassCaches()
{
    if (class_exists("Game") && method_exists("Game", "clearCache")) {
        Game::clearCache();
    }
    if (class_exists("User") && method_exists("User", "clearCache")) {
        User::clearCache();
    }
    if (class_exists("Cell") && method_exists("Cell", "clearCache")) {
        Cell::clearCache();
    }
}

/**
 * Функция для генерации случайных тестовых данных
 */
function generateRandomTestData($type, $count = 1)
{
    $results = [];

    for ($i = 0; $i < $count; $i++) {
        switch ($type) {
            case "game":
                $results[] = [
                    "name" => "Test Game " . ($i + 1),
                    "map_w" => rand(50, 200),
                    "map_h" => rand(50, 200),
                    "turn_type" => ["byturn", "concurrently", "onewindow"][
                        rand(0, 2)
                    ],
                    "turn_num" => rand(1, 10),
                ];
                break;

            case "user":
                $results[] = [
                    "login" => "TestUser" . ($i + 1),
                    "color" =>
                        "#" .
                        str_pad(
                            dechex(rand(0, 0xffffff)),
                            6,
                            "0",
                            STR_PAD_LEFT,
                        ),
                    "money" => rand(0, 1000),
                    "age" => rand(1, 4),
                    "turn_order" => $i + 1,
                ];
                break;

            case "cell":
                $types = ["plains", "forest", "hills", "water", "desert"];
                $results[] = [
                    "x" => rand(0, 99),
                    "y" => rand(0, 99),
                    "planet" => 1,
                    "type" => $types[rand(0, count($types) - 1)],
                ];
                break;

            default:
                $results[] = [];
        }
    }

    return $count === 1 ? $results[0] : $results;
}

/**
 * Функция для проверки доступности методов в классе
 */
function assertClassHasMethod($className, $methodName, $message = "")
{
    if (!class_exists($className)) {
        throw new Exception("Class {$className} does not exist");
    }

    if (!method_exists($className, $methodName)) {
        $message =
            $message ?:
            "Method {$methodName} does not exist in class {$className}";
        throw new Exception($message);
    }

    return true;
}

/**
 * Функция для безопасного вызова методов с проверкой их существования
 */
function safeMethodCall($object, $methodName, $args = [])
{
    if (!is_object($object)) {
        throw new InvalidArgumentException("First argument must be an object");
    }

    $className = get_class($object);

    if (!method_exists($object, $methodName)) {
        throw new Exception(
            "Method {$methodName} does not exist in class {$className}",
        );
    }

    return call_user_func_array([$object, $methodName], $args);
}

/**
 * Вспомогательная функция для подмены include/require в тестах
 * Предотвращает подключение includes.php в тестах
 */
function mockIncludeFile($filename, $varsToExtract = [])
{
    // Предотвращаем подключение includes.php в тестах
    if (basename($filename) === "includes.php") {
        return [];
    }

    // Извлекаем переменные в глобальную область видимости
    extract($varsToExtract, EXTR_SKIP);

    // Сохраняем текущее состояние переменных
    $beforeVars = get_defined_vars();

    ob_start();
    try {
        // Подключаем MyDB в глобальное пространство имен для тестов
        if (!class_exists('MyDB', false)) {
            require_once PROJECT_ROOT . "/src/MyDB.php";
        }
        include $filename;
    } catch (TestExitException $e) {
        // Игнорируем выход из скрипта
    } finally {
        // Гарантированно закрываем буфер вывода
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    // Получаем переменные после выполнения
    $afterVars = get_defined_vars();

    // Возвращаем все переменные, которые были определены или изменены в файле
    $resultVars = [];
    foreach ($afterVars as $key => $value) {
        if (
            !array_key_exists($key, $beforeVars) ||
            $beforeVars[$key] !== $value
        ) {
            $resultVars[$key] = $value;
        }
    }

    return $resultVars;
}
