<?php

namespace App\Interfaces;

/**
 * Базовый интерфейс для всех моделей
 */
interface IModel
{
    public static function get($id);
    public function save();
}
