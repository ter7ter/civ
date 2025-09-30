<?php

namespace App;

/**
 * Интерфейс для типов миссий.
 * Реализует ISP, позволяя разделять обязанности.
 */
interface MissionInterface
{
    /**
     * Проверить, подходит ли клетка для миссии.
     * @param int $x
     * @param int $y
     * @param int $planet_id
     * @return bool
     */
    public function check_cell(int $x, int $y, int $planet_id): bool;

    /**
     * Завершить выполнение миссии.
     * @param Unit $unit
     * @param string|null $title
     * @return bool
     */
    public function complete(Unit $unit, string|false $title = false): bool;

    /**
     * Получить заголовок миссии.
     * @return string
     */
    public function getTitle(): string;
}
