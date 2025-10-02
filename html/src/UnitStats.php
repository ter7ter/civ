<?php

namespace App;

/**
 * Класс для управления статистикой юнита (здоровье, очки, уровень)
 */
class UnitStats
{
    private Unit $unit;

    public function __construct(Unit $unit)
    {
        $this->unit = $unit;
    }

    /**
     * Получить текущие HP
     */
    public function getHealth(): int
    {
        return $this->unit->health;
    }

    /**
     * Установить HP
     */
    public function setHealth(int $health): void
    {
        if ($health < 0) {
            $health = 0;
        }
        if ($health > $this->unit->health_max) {
            $health = $this->unit->health_max;
        }
        $this->unit->health = $health;
    }

    /**
     * Получить максимум HP
     */
    public function getMaxHealth(): int
    {
        return $this->unit->health_max;
    }

    /**
     * Получить очки движения
     */
    public function getPoints(): int
    {
        return $this->unit->points;
    }

    /**
     * Установить очки движения
     */
    public function setPoints(int $points): void
    {
        $this->unit->points = max(0, $points);
    }

    /**
     * Получить уровень
     */
    public function getLevel(): int
    {
        return $this->unit->lvl;
    }

    /**
     * Повысить уровень
     */
    public function levelUp(): void
    {
        $this->unit->lvl += 1;
    }

    /**
     * Проверить, жив ли юнит
     */
    public function isAlive(): bool
    {
        return $this->unit->health > 0;
    }
}
