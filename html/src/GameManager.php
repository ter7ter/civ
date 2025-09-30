<?php

namespace App;

/**
 * Менеджер игры, отвечающий за логику управления игрой.
 * Реализует SRP, разделяя ответственность от модели данных Game.
 */
class GameManager
{
    /**
     * Создание новой игры.
     * @param Game $game
     */
    public static function createNewGame(Game $game)
    {
        $game->create_new_game();
    }

    /**
     * Расчет хода для игры.
     * @param Game $game
     */
    public static function calculateTurn(Game $game)
    {
        $game->calculate();
    }

    /**
     * Получить активного игрока.
     * @param Game $game
     * @return int|null
     */
    public static function getActivePlayer(Game $game)
    {
        return $game->getActivePlayer();
    }

    /**
     * Отправить системное сообщение всем пользователям игры.
     * @param Game $game
     * @param string $message
     */
    public static function sendSystemMessageToAll(Game $game, string $message)
    {
        $game->all_system_message($message);
    }
}
