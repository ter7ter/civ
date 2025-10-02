<?php

namespace App;

use App\Interfaces\DatabaseInterface;

/**
 * Простой DI контейнер
 */
class Container
{
    private static array $bindings = [];

    public static function bind(string $abstract, callable $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }

    public static function make(string $abstract)
    {
        if (!isset(self::$bindings[$abstract])) {
            throw new \Exception("No binding found for {$abstract}");
        }
        return self::$bindings[$abstract]();
    }
}
