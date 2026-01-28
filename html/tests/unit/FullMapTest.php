<?php

namespace App\Tests;

use App\Game;
use App\City;
use App\Cell;
use App\MyDB;
use App\Tests\Factory\TestDataFactory;
use App\Tests\Base\FunctionalTestBase;
use App\Tests\Base\TestGameDataInitializer;

/**
 * @coversNothing
 */
class FullMapTest extends FunctionalTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Подключаем классы проекта
        require_once PROJECT_ROOT . "/includes.php";

        // Инициализируем типы клеток для тестов
        TestGameDataInitializer::initializeCellTypes();
    }

    /**
     * Тестируем функционал получения данных для полной карты через выполнение страницы
     */
    public function testFullMapDataRetrieval()
    {
        // Создаем тестовую игру с планетой, пользователем и городом
        $result = TestDataFactory::createTestGameWithPlanetUserAndCity();
        $game = $result["game"];
        $user = $result["user"];
        $planet = $result["planet"];
        $city = $result["city"];

        // Перезагружаем игру, чтобы получить актуальный список пользователей
        $game = \App\Game::get($game->id);

        // Создаем несколько клеток на карте
        $this->createTestMapCells(5, 5, 10, 10, $planet->id);

        // Выполняем страницу fullmap.php с установленной сессией
        $result = $this->executePage(
            PROJECT_ROOT . "/pages/fullmap.php",
            [], // нет данных запроса
            ["game_id" => $game->id] // сессия с ID игры
        );

        // Проверяем, что нет ошибок
        $this->assertPageHasNoError($result);

        // Проверяем, что переменная $data была создана
        $this->assertArrayHasKey(
            "data", 
            $result["variables"], 
            "Страница должна создать переменную \$data"
        );

        $mapData = $result["variables"]["data"];
        
        // Проверяем, что $data является массивом
        $this->assertIsArray($mapData, "Переменная \$data должна быть массивом");

        // Проверяем, что в данных карты есть хотя бы одна клетка
        $hasCells = false;
        foreach ($mapData as $x_row) {
            if (count($x_row) > 0) {
                $hasCells = true;
                break;
            }
        }
        $this->assertTrue($hasCells, "Должны быть клетки в данных карты");

        // Проверяем структуру данных клетки
        $foundCell = false;
        foreach ($mapData as $x => $x_row) {
            foreach ($x_row as $y => $cell) {
                $this->assertArrayHasKey("x", $cell, "Клетка должна содержать координату x");
                $this->assertArrayHasKey("y", $cell, "Клетка должна содержать координату y");
                $this->assertArrayHasKey("type", $cell, "Клетка должна содержать тип");
                $this->assertArrayHasKey("owner", $cell, "Клетка должна содержать владельца");
                $this->assertArrayHasKey("city", $cell, "Клетка должна содержать информацию о городе");
                
                $this->assertEquals($x, $cell["x"], "Координата x должна совпадать с индексом");
                $this->assertEquals($y, $cell["y"], "Координата y должна совпадать с индексом");
                
                $foundCell = true;
                break 2; // выходим из обоих циклов
            }
        }
        $this->assertTrue($foundCell, "Должна быть найдена хотя бы одна клетка для проверки структуры");
    }

    /**
     * Тестируем, что при отсутствии game_id в сессии возникает ошибка
     */
    public function testFullMapWithoutGameIdShowsError()
    {
        // Выполняем страницу fullmap.php без game_id в сессии
        $result = $this->executePage(
            PROJECT_ROOT . "/pages/fullmap.php",
            [],
            [] // пустая сессия
        );

        // Проверяем, что переменная $error установлена
        $this->assertArrayHasKey(
            "error", 
            $result["variables"], 
            "Должна быть установлена переменная \$error при отсутствии game_id"
        );

        $this->assertNotEmpty(
            $result["variables"]["error"],
            "Переменная \$error должна содержать сообщение об ошибке"
        );
    }

    /**
     * Тестируем, что при неверном game_id в сессии возникает ошибка
     */
    public function testFullMapWithInvalidGameIdShowsError()
    {
        // Выполняем страницу fullmap.php с неверным game_id
        $result = $this->executePage(
            PROJECT_ROOT . "/pages/fullmap.php",
            [],
            ["game_id" => 99999] // заведомо несуществующий ID
        );

        // Проверяем, что переменная $error установлена
        $this->assertArrayHasKey(
            "error", 
            $result["variables"], 
            "Должна быть установлена переменная \$error при неверном game_id"
        );

        $this->assertNotEmpty(
            $result["variables"]["error"],
            "Переменная \$error должна содержать сообщение об ошибке"
        );
    }
}
