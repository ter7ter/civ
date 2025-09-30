<?php

namespace App;

/**
 * Интерфейс для стратегии завершения миссии.
 * Обеспечивает OCP для добавления новых миссий без изменения кода.
 */
interface MissionCompleteInterface
{
    /**
     * Завершить выполнение миссии.
     * @param Unit $unit
     * @param string|null $title
     * @return bool
     */
    public function complete(Unit $unit, string|false $title = false): bool;
}
