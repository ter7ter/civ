<?php

/**
 * Файл для условной замены классов в тестовом окружении
 * Подключается только в тестах для замены основных классов на моки
 */

// Проверяем, что мы в тестовом режиме
if (!defined('TESTING') || !TESTING) {
    return;
}

// Подключаем моки базы данных
require_once __DIR__ . '/DatabaseMocks.php';

/**
 * Условная замена класса MyDB на тестовую версию
 */
if (!class_exists('MyDB', false)) {
    // Если MyDB еще не загружен, создаем его как алиас тестового класса
    class_alias('MyDBTestWrapper', 'MyDB');
}

/**
 * Условная замена класса Game на тестовую версию
 */
if (!class_exists('Game', false)) {
    class Game extends GameTestMock {}
} else {
    // Если класс уже существует, переопределяем его методы через наследование
    class TestableGame extends GameTestMock {
        public static function createFromOriginal($originalGame) {
            $data = [];
            if (isset($originalGame->id)) $data['id'] = $originalGame->id;
            if (isset($originalGame->name)) $data['name'] = $originalGame->name;
            if (isset($originalGame->map_w)) $data['map_w'] = $originalGame->map_w;
            if (isset($originalGame->map_h)) $data['map_h'] = $originalGame->map_h;
            if (isset($originalGame->turn_type)) $data['turn_type'] = $originalGame->turn_type;
            if (isset($originalGame->turn_num)) $data['turn_num'] = $originalGame->turn_num;

            return new self($data);
        }
    }
}

/**
 * Условная замена класса User на тестовую версию
 */
if (!class_exists('User', false)) {
    class User extends UserTestMock {}
} else {
    // Если класс уже существует, создаем тестируемую версию
    class TestableUser extends UserTestMock {
        public static function createFromOriginal($originalUser) {
            $data = [];
            $fields = ['id', 'login', 'color', 'game', 'turn_order', 'turn_status',
                      'money', 'age', 'income', 'research_amount', 'research_percent',
                      'process_research_complete', 'process_research_turns', 'process_research_type'];

            foreach ($fields as $field) {
                if (isset($originalUser->$field)) {
                    $data[$field] = $originalUser->$field;
                }
            }

            return new self($data);
        }
    }
}

/**
 * Вспомогательные функции для тестов
 */

/**
 * Создает тестовый экземпляр игры
 */
function createTestGame($data = []) {
    if (class_exists('TestableGame')) {
        return new TestableGame($data);
    } else {
        return new Game($data);
    }
}

/**
 * Создает тестовый экземпляр пользователя
 */
function createTestUser($data = []) {
    if (class_exists('TestableUser')) {
        return new TestableUser($data);
    } else {
        return new User($data);
    }
}

/**
 * Переопределяем глобальные функции для тестов (если нужно)
 */

// Мок для header() функции
if (!function_exists('test_header')) {
    function test_header($header, $replace = true, $response_code = null) {
        global $test_headers;
        if (!isset($test_headers)) {
            $test_headers = [];
        }
        $test_headers[] = $header;
        return true;
    }
}

// Мок для exit/die
if (!function_exists('test_exit')) {
    function test_exit($message = '') {
        throw new TestExitException("Exit called: " . $message);
    }
}

/**
 * Исключение для обработки exit() в тестах
 */
class TestExitException extends Exception {}

function send_header($location) {
    global $test_headers;
    if (!isset($test_headers)) {
        $test_headers = [];
    }
    $test_headers[] = $location;
}

function terminate_script() {
    throw new TestExitException();
}


/**
 * Функция для получения заголовков, установленных в тестах
 */
function getTestHeaders() {
    global $test_headers;
    return isset($test_headers) ? $test_headers : [];
}

/**
 * Функция для очистки тестовых заголовков
 */
function clearTestHeaders() {
    global $test_headers;
    $test_headers = [];
}

/**
 * Вспомогательная функция для подмены include/require в тестах
 * Предотвращает подключение includes.php в тестах
 */
function mockIncludeFile($filename, $varsToExtract = []) {
    $scope = function() use ($filename, $varsToExtract) {
        extract($varsToExtract, EXTR_SKIP);
        ob_start();
        try {
            // Предотвращаем подключение includes.php в тестах
            if (basename($filename) === 'includes.php') {
                return [];
            }
            include $filename;
        } catch (TestExitException $e) {
            // ignore
        }
        ob_end_clean();
        return get_defined_vars();
    };
    return $scope();
}

/**
 * Инициализация тестового окружения для подмены классов
 */
function initializeTestClassEnvironment() {
    // Устанавливаем обработчики ошибок для тестов
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    // Настраиваем переменные окружения
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_HOST'] = 'test-host';
    $_SERVER['REQUEST_URI'] = '/test';

    // Очищаем глобальные переменные
    $_GET = [];
    $_POST = [];
    $_REQUEST = [];
    $_FILES = [];
    $_COOKIE = [];

    if (!isset($_SESSION)) {
        $_SESSION = [];
    }

    // Инициализируем тестовую БД
    initializeTestEnvironment();
}

// Автоматическая инициализация при подключении файла
initializeTestClassEnvironment();
