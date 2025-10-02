<?php

namespace App;

/**
 * Класс для управления логикой игры (расчет ходов, генерация, сообщения)
 */
class GameManager
{
    private Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    /**
     * Создать новую игру
     */
    public function createNewGame(): void
    {
        MapGenerator::generateNewGame($this->game);
    }

    /**
     * Рассчитать ход для игры
     */
    public function calculateTurn(): void
    {
        TurnCalculator::calculateTurn($this->game);
    }

    /**
     * Отправить системное сообщение всем пользователям
     */
    public function sendSystemMessageToAll(string $text): void
    {
        $messageService = new MessageService();
        $messageService->send_system_to_all($this->game, $text);
    }

    /**
     * Получить активного игрока
     */
    public function getActivePlayer(): ?int
    {
        return TurnCalculator::getActivePlayer($this->game);
    }

    /**
     * Получить первую планету
     */
    public function getFirstPlanet(): ?Planet
    {
        $planet_id = MyDB::query(
            "SELECT id FROM planet WHERE game_id = :game_id ORDER BY id LIMIT 1",
            ["game_id" => $this->game->id],
            "elem",
        );
        if ($planet_id) {
            return Planet::get($planet_id);
        }
        return null;
    }
}
