<?php

namespace App;

/**
 * Интерфейс для производства в городе.
 * Обеспечивает OCP для добавления новых типов производства без изменения кода.
 */
interface ProductionInterface
{
    /**
     * Завершить производство.
     * @param City $city
     */
    public function complete(City $city);
}
