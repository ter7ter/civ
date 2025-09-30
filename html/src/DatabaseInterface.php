<?php

namespace App;

/**
 * Интерфейс для базы данных.
 * Абстрагирует конкретную реализацию MyDB.
 */
interface DatabaseInterface
{
    /**
     * Выполнить запрос к базе данных.
     * @param string $query Запрос SQL
     * @param array $vars Параметры
     * @param string $output Тип вывода
     */
    public static function query(string $query, array $vars = [], string $output = "assoc");

    /**
     * Вставить данные в таблицу.
     * @param string $table Имя таблицы
     * @param array $values Значения для вставки
     */
    public static function insert(string $table, array $values);

    /**
     * Обновить данные в таблице.
     * @param string $table Имя таблицы
     * @param array $values Значения для обновления
     * @param mixed $where Условие WHERE
     */
    public static function update(string $table, array $values, $where);

    /**
     * Получить соединение с БД.
     * @return \PDO
     */
    public static function get();

    /**
     * Начать транзакцию.
     */
    public static function startTransaction();

    /**
     * Завершить транзакцию.
     */
    public static function endTransaction();

    /**
     * Откатить транзакцию.
     */
    public static function rollbackTransaction();

    // Другие методы как replace могут быть добавлены позже
}
