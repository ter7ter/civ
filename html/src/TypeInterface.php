<?php

namespace App;

/**
 * Интерфейс для типов сущностей.
 * Реализует LSP - обеспечивает подстановку подтипов.
 */
interface TypeInterface
{
    /**
     * Получить идентификатор типа.
     * @return mixed
     */
    public function getId();

    /**
     * Получить название типа.
     * @return string
     */
    public function getTitle(): string;
}
