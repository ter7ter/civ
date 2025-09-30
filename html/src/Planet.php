<?php

namespace App;

class Planet implements PlanetInterface
{
    /**
     * @var Planet[]
     */
    protected static array $_all = [];

    /**
     * Очистка кэша для тестов
     */
    public static function clearCache()
    {
        self::$_all = [];
    }

    /**
     * @var int
     */
    public mixed $id = null;
    /**
     * @var string
     */
    public string $name;
    /**
     * ID игры
     * @var int
     */
    public int $game_id;

    /**
     * @param $id
     * @return Planet|null
     * @throws \Exception
     */
    public static function get($id): ?Planet
    {
        if (isset(Planet::$_all[$id])) {
            return Planet::$_all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM planet WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if (!$data || !isset($data["id"])) {
                return null;
            }
            return new Planet($data);
        }
    }

    public function __construct($data)
    {
        if (!$data || !is_array($data)) {
            throw new \Exception(
                "Invalid planet data provided to Planet constructor",
            );
        }

        foreach (["name", "game_id"] as $field) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        if (isset($data["id"])) {
            $this->id = $data["id"];
            Planet::$_all[$this->id] = $this;
        }
    }

    public function save(): void
    {
        $values = [];
        foreach (["name", "game_id"] as $field) {
            $values[$field] = $this->$field;
        }
        if ($this->id !== null) {
            MyDB::update("planet", $values, $this->id);
        } else {
            $this->id = MyDB::insert("planet", $values);
        }
    }

    /**
     * @return Game|null
     */
    public function get_game(): ?Game
    {
        return Game::get($this->game_id);
    }
}
