<?php

/**
 * Тесты для проверки конфигурации базы данных в тестах
 */
class DatabaseConfigTest extends TestBase
{
    /**
     * Тест проверки тестовых настроек БД
     */
    public function testTestDatabaseConfiguration(): void
    {
        // Проверяем, что MyDB настроен корректно для тестов
        $this->assertEquals(
            "db",
            MyDB::$dbhost,
            "Тестовая БД должна использовать 'db' для Docker",
        );
        $this->assertEquals(
            "civ_test",
            MyDB::$dbuser,
            "Тестовая БД должна использовать тестового пользователя",
        );
        $this->assertEquals(
            "civ_test",
            MyDB::$dbpass,
            "Тестовая БД должна использовать тестовый пароль",
        );
        // Проверяем имя базы данных
        $testToken = getenv('TEST_TOKEN');
        $paraTestFlag = getenv('PARATEST');
        $isParaTest = !empty($testToken) || $paraTestFlag === '1';

        if ($isParaTest) {
            // В ParaTest имя базы данных содержит токен
            $this->assertStringStartsWith(
                "civ_for_tests",
                MyDB::$dbname,
                "Тестовая БД в ParaTest должна начинаться с 'civ_for_tests'",
            );
            $this->assertStringContainsString(
                $testToken ?: getmypid(),
                MyDB::$dbname,
                "Тестовая БД в ParaTest должна содержать TEST_TOKEN или PID",
            );
        } else {
            // В обычных тестах имя базы данных фиксированное
            $this->assertEquals(
                "civ_for_tests",
                MyDB::$dbname,
                "Тестовая БД должна использовать тестовую базу данных",
            );
        }
        $this->assertEquals(
            "3306",
            MyDB::$dbport,
            "Тестовая БД должна использовать порт MySQL",
        );
    }

    // /**
    //  * Тест разделения настроек основного проекта и тестов
    //  */
    // public function testDatabaseConfigurationSeparation(): void
    // {
    //     // Проверяем, что тестовые настройки отличаются от основных
    //     $this->assertNotEquals(
    //         DB_HOST,
    //         MyDB::$dbhost,
    //         "Хост тестовой БД должен отличаться от основной",
    //     );
    //
    //     $this->assertNotEquals(
    //         DB_NAME,
    //         MyDB::$dbname,
    //         "Имя тестовой БД должно отличаться от основной",
    //     );
    //
    //     // Проверяем, что используется тестовая конфигурация
    //     $this->assertStringContainsString(
    //         "test",
    //         MyDB::$dbname,
    //         "Имя БД должно содержать 'test'",
    //     );
    // }

    /**
     * Тест создания тестовых таблиц
     */
    public function testTestTablesCreation(): void
    {
        // Проверяем основные таблицы
        $expectedTables = ["game", "user", "cell", "unit", "city"];

        $actualTables = MyDB::query("SHOW TABLES", [], "column");

        foreach ($expectedTables as $table) {
            $this->assertContains(
                $table,
                $actualTables,
                "Таблица {$table} должна существовать",
            );
        }
    }

    /**
     * Тест работы с тестовыми данными
     */
    public function testTestDataOperations(): void
    {
        // Создаем тестовую игру
        $gameData = [
            "name" => "Test Database Game",
            "map_w" => 100,
            "map_h" => 100,
            "turn_type" => "byturn",
            "turn_num" => 1,
        ];

        $gameId = MyDB::insert("game", $gameData);
        $this->assertGreaterThan(0, $gameId, "ID игры должен быть больше 0");

        // Проверяем, что игра создана
        $game = MyDB::query(
            "SELECT * FROM game WHERE id = :id",
            ["id" => $gameId],
            "row",
        );
        $this->assertNotNull($game, "Игра должна быть найдена в БД");
        $this->assertEquals(
            "Test Database Game",
            $game["name"],
            "Название игры должно совпадать",
        );

        // Обновляем игру
        $updateData = ["name" => "Updated Test Game"];
        $result = MyDB::update("game", $updateData, $gameId);
        $this->assertTrue($result, "Обновление должно быть успешным");

        // Проверяем обновление
        $updatedGame = MyDB::query(
            "SELECT * FROM game WHERE id = :id",
            ["id" => $gameId],
            "row",
        );
        $this->assertEquals(
            "Updated Test Game",
            $updatedGame["name"],
            "Название должно быть обновлено",
        );
    }

    /**
     * Тест очистки тестовых данных
     */
    public function testTestDataCleanup(): void
    {
        // Добавляем тестовые данные
        $gameId = MyDB::insert("game", [
            "name" => "Cleanup Test Game",
            "map_w" => 50,
            "map_h" => 50,
            "turn_type" => "byturn",
        ]);

        $userId = MyDB::insert("user", [
            "login" => "TestUser",
            "color" => "#ff0000",
            "game" => $gameId,
            "money" => 100,
        ]);

        // Проверяем, что данные есть
        $gameCount = MyDB::query("SELECT COUNT(*) FROM game", [], "elem");
        $userCount = MyDB::query("SELECT COUNT(*) FROM user", [], "elem");

        $this->assertGreaterThan(0, $gameCount, "Должна быть минимум 1 игра");
        $this->assertGreaterThan(
            0,
            $userCount,
            "Должен быть минимум 1 пользователь",
        );

        // Очищаем данные
        DatabaseTestAdapter::resetTestDatabase();

        // Проверяем, что данные очищены
        $gameCountAfter = MyDB::query("SELECT COUNT(*) FROM game", [], "elem");
        $userCountAfter = MyDB::query("SELECT COUNT(*) FROM user", [], "elem");

        $this->assertEquals(0, $gameCountAfter, "Игры должны быть удалены");
        $this->assertEquals(
            0,
            $userCountAfter,
            "Пользователи должны быть удалены",
        );
    }

    /**
     * Тест работы транзакций
     */
    public function testTransactionHandling(): void
    {
        // Начинаем транзакцию
        MyDB::start_transaction();

        // Добавляем данные
        $gameId = MyDB::insert("game", [
            "name" => "Transaction Test",
            "map_w" => 100,
            "map_h" => 100,
        ]);

        $this->assertGreaterThan(0, $gameId, "Игра должна быть создана");

        // Завершаем транзакцию
        MyDB::end_transaction();

        // Проверяем, что данные сохранены
        $game = MyDB::query(
            "SELECT * FROM game WHERE id = :id",
            ["id" => $gameId],
            "row",
        );
        $this->assertNotNull($game, "Игра должна быть сохранена после коммита");
    }

    /**
     * Тест работы моков классов Game и User
     */
    public function testGameAndUserMocks(): void
    {
        // Проверяем, что моки доступны
        $this->assertTrue(
            class_exists("Game"),
            "Класс Game должен быть доступен",
        );
        $this->assertTrue(
            class_exists("User"),
            "Класс User должен быть доступен",
        );

        // Создаем игру через мок
        $game = new Game([
            "name" => "Mock Test Game",
            "map_w" => 200,
            "map_h" => 200,
            "turn_type" => "concurrently",
            "turn_num" => 1,
        ]);

        $game->save();
        $this->assertGreaterThan(
            0,
            $game->id,
            "ID игры должен быть установлен после сохранения",
        );

        // Создаем пользователя через мок
        $user = new User([
            "login" => "MockUser",
            "color" => "#00ff00",
            "game" => $game->id,
            "money" => 150,
        ]);

        $user->save();
        $this->assertGreaterThan(
            0,
            $user->id,
            "ID пользователя должен быть установлен после сохранения",
        );

        // Проверяем связь через static методы
        $retrievedGame = Game::get($game->id);
        $this->assertInstanceOf(
            Game::class,
            $retrievedGame,
            "Game::get должен возвращать объект Game",
        );
        $this->assertEquals(
            $game->name,
            $retrievedGame->name,
            "Данные игры должны совпадать",
        );

        $retrievedUser = User::get($user->id);
        $this->assertInstanceOf(
            User::class,
            $retrievedUser,
            "User::get должен возвращать объект User",
        );
        $this->assertEquals(
            $user->login,
            $retrievedUser->login,
            "Данные пользователя должны совпадать",
        );
    }
}
