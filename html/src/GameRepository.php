<?php

namespace App;

/**
 * Репозиторий для доступа к играм
 */
class GameRepository
{
    /**
     * Kэш всех загруженных игр
     * @var Game[]
     */
    protected static $_all = [];

    /**
     * Очистка кэша для тестов
     */
    public static function clearCache()
    {
        self::$_all = [];
    }

    /**
     * Получить игру по идентификатору
     * @param int $id Идентификатор игры
     * @return Game|null Игра или null, если не найдена
     */
    public static function get($id)
    {
        if (isset(self::$_all[$id])) {
            return self::$_all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM game WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if (!$data || !isset($data["id"])) {
                return null;
            }
            return new Game($data);
        }
    }

    /**
     * Получить список всех игр
     * @return array Список игр с количеством пользователей
     */
    public static function game_list()
    {
        $games = MyDB::query("SELECT game.*, count(user.id) as ucount FROM game
                                INNER JOIN user ON user.game = game.id
                                GROUP BY user.game ORDER BY id DESC");
        return $games;
    }
}
