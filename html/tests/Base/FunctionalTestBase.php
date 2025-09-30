<?php

namespace App\Tests\Base;

require_once __DIR__ . "/TestBase.php";

/**
 * Базовый класс для функциональных тестов
 * Обеспечивает тестирование страниц и функций без использования моков
 */
class FunctionalTestBase extends TestBase
{
    /**
     * Выполняет PHP-файл страницы и возвращает результат
     */
    protected function executePage(
        $pagePath,
        $requestData = [],
        $sessionData = [],
    ) {
        // Подготавливаем окружение
        $this->clearRequest();
        $this->clearSession();

        // Устанавливаем данные запроса
        foreach ($requestData as $key => $value) {
            $_REQUEST[$key] = $value;
            $_POST[$key] = $value;
        }

        // Устанавливаем сессию
        foreach ($sessionData as $key => $value) {
            $_SESSION[$key] = $value;
        }

        // Переменные для сбора результатов
        $output = "";
        $variables = [];
        $error = false;

        // Перехватываем вывод и исключения
        ob_start();
        try {
            // Устанавливаем переменные перед включением
            $beforeVars = get_defined_vars();

            // Включаем файл страницы
            include $pagePath;

            // Собираем переменные после выполнения
            $afterVars = get_defined_vars();
            foreach ($afterVars as $key => $value) {
                if (
                    !array_key_exists($key, $beforeVars) ||
                    $beforeVars[$key] !== $value
                ) {
                    $variables[$key] = $value;
                }
            }
        } catch (TestExitException $e) {
            // Нормальное завершение через terminate_script()
        } catch (Exception $e) {
            $error = $e->getMessage();
        } finally {
            $output = ob_get_clean();
        }

        return [
            "output" => $output,
            "variables" => $variables,
            "error" => $error,
            "headers" => $this->getTestHeaders(),
        ];
    }

    /**
     * Создает игру через страницу создания игры
     */
    protected function createGameViaPage($gameData)
    {
        $defaultData = [
            "name" => "Test Game",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "users" => ["Player1", "Player2"],
        ];

        $requestData = array_merge($defaultData, $gameData);

        $result = $this->executePage(
            PROJECT_ROOT . "/pages/creategame.php",
            $requestData,
        );

        return $result;
    }

    /**
     * Редактирует игру через страницу редактирования
     */
    protected function editGameViaPage($gameId, $editData)
    {
        $requestData = array_merge(["game_id" => $gameId], $editData);

        $result = $this->executePage(
            PROJECT_ROOT . "/pages/editgame.php",
            $requestData,
        );

        return $result;
    }

    /**
     * Открывает игру через страницу открытия игры
     */
    protected function openGameViaPage($gameId)
    {
        $result = $this->executePage(PROJECT_ROOT . "/pages/opengame.php", [
            "game_id" => $gameId,
        ]);

        return $result;
    }

    /**
     * Проверяет наличие ошибки в результате выполнения страницы
     */
    protected function assertPageHasError($result, $expectedError = null)
    {
        $this->assertTrue(
            isset($result["variables"]["error"]) &&
                $result["variables"]["error"],
            "Страница должна содержать ошибку",
        );

        if ($expectedError !== null) {
            $this->assertStringContainsString(
                $expectedError,
                $result["variables"]["error"],
                "Ошибка должна содержать ожидаемый текст",
            );
        }
    }

    /**
     * Проверяет отсутствие ошибки в результате выполнения страницы
     */
    protected function assertPageHasNoError($result)
    {
        $hasError =
            isset($result["variables"]["error"]) &&
            (bool)$result["variables"]["error"];
        $this->assertFalse(
            $hasError,
            "Страница не должна содержать ошибок: " .
                ($hasError ? $result["variables"]["error"] : ""),
        );
    }

    /**
     * Проверяет наличие редиректа
     */
    protected function assertPageRedirects($result, $expectedLocation = null)
    {
        $headers = $result["headers"];
        $hasRedirect = false;

        foreach ($headers as $header) {
            if (stripos($header, "Location:") === 0) {
                $hasRedirect = true;
                if ($expectedLocation !== null) {
                    $this->assertStringContainsString(
                        $expectedLocation,
                        $header,
                        "Редирект должен вести на ожидаемую страницу",
                    );
                }
                break;
            }
        }

        $this->assertTrue($hasRedirect, "Страница должна выполнить редирект");
    }

    /**
     * Проверяет, что данные сохранены на странице (для повторного заполнения формы)
     */
    protected function assertPagePreservesData($result, $expectedData)
    {
        $this->assertArrayHasKey(
            "data",
            $result["variables"],
            "Страница должна содержать переменную data с сохраненными данными",
        );

        $data = $result["variables"]["data"];

        foreach ($expectedData as $key => $expectedValue) {
            $this->assertArrayHasKey(
                $key,
                $data,
                "Данные должны содержать поле $key",
            );

            if (is_array($expectedValue)) {
                $this->assertEquals(
                    $expectedValue,
                    $data[$key],
                    "Поле $key должно содержать ожидаемый массив данных",
                );
            } else {
                $this->assertEquals(
                    $expectedValue,
                    $data[$key],
                    "Поле $key должно содержать ожидаемое значение",
                );
            }
        }
    }

    /**
     * Возвращает тестовые заголовки, установленные во время выполнения страницы
     */
    protected function getTestHeaders()
    {
        global $test_headers;
        return isset($test_headers) ? $test_headers : [];
    }

    /**
     * Очищает тестовые заголовки
     */
    protected function clearTestHeaders()
    {
        global $test_headers;
        $test_headers = [];
    }

    /**
     * Устанавливает мок для функции header
     */
    protected function setupHeaderMock()
    {
        if (!function_exists("header")) {
            function header($header, $replace = true, $response_code = null)
            {
                global $test_headers;
                if (!isset($test_headers)) {
                    $test_headers = [];
                }
                $test_headers[] = $header;
                return true;
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRequest();
        $this->clearSession();
        $this->clearTestHeaders();
        $this->setupHeaderMock();
    }

    protected function tearDown(): void
    {
        $this->clearRequest();
        $this->clearSession();
        $this->clearTestHeaders();
        parent::tearDown();
    }
}
