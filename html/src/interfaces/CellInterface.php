<?php

namespace App\Interfaces;

/**
 * Интерфейс для клеток: информация и ресурсы
 */
interface CellInterface
{
    public function getTitle(): string;
    public function get_work(?City $city = null): int;
    public function get_eat(?City $city = null): int;
    public function get_money(?City $city = null): int;
}
