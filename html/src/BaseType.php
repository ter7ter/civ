<?php

namespace App;

/**
 * Базовый класс для типов сущностей.
 * Реализует LSP через наследование.
 */
class BaseType implements TypeInterface
{
    /**
     * @var mixed Идентификатор типа
     */
    public $id;

    /**
     * @var string Название типа
     */
    public $title;
    /**
     * Имя файла картинки
     * @var string
     */
    public $image_file = "";

    /**
     * Получить идентификатор типа.
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Получить название типа.
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
