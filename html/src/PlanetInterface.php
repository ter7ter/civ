<?php

namespace App;

/**
 * Интерфейс для планет.
 * Реализует DIP и возможности расширения.
 */
interface PlanetInterface
{
    /**
     * Получить игру, к которой относится планета.
     * @return Game|null
     */
    public function get_game(): ?Game;

    /**
     * Сохранить планету.
     */
    public function save();
}
