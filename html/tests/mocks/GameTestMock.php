<?php

/**
 * Мок класса Game для тестов
 */
class GameTestMock
{
    public $id;
    public $name;
    public $map_w;
    public $map_h;
    public $turn_type;
    public $turn_num;
    public $users = [];

    public static $_all = [];

    public function __construct($data)
    {
        foreach (
            ["name", "map_w", "map_h", "turn_type", "turn_num"]
            as $field
        ) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        $this->users = [];
        if (isset($data["id"])) {
            $this->id = $data["id"];
            self::$_all[$this->id] = $this;

            $users = DatabaseTestAdapter::query(
                "SELECT id FROM user WHERE game = :gameid",
                ["gameid" => $this->id],
            );
            foreach ($users as $user) {
                $this->users[$user["id"]] = User::get($user["id"]);
            }
        }
    }

    public static function get($id)
    {
        if (isset(self::$_all[$id])) {
            return self::$_all[$id];
        }

        $data = DatabaseTestAdapter::query(
            "SELECT * FROM game WHERE id = :id",
            ["id" => $id],
            "row",
        );
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public function save()
    {
        $values = [];
        foreach (
            ["name", "map_w", "map_h", "turn_type", "turn_num"]
            as $field
        ) {
            if (isset($this->$field)) {
                $values[$field] = $this->$field;
            }
        }

        if ($this->id) {
            DatabaseTestAdapter::update("game", $values, $this->id);
        } else {
            $this->id = DatabaseTestAdapter::insert("game", $values);
            self::$_all[$this->id] = $this;
        }
    }

    public function create_new_game()
    {
        // Упрощенная версия для тестов - просто создаем базовые юниты
        $users = DatabaseTestAdapter::query(
            "SELECT id FROM user WHERE game = :gameid ORDER BY turn_order",
            ["gameid" => $this->id],
        );

        foreach ($users as $user) {
            $unitData = [
                "x" => rand(0, $this->map_w - 1),
                "y" => rand(0, $this->map_h - 1),
                "planet" => $this->id,
                "user_id" => $user["id"],
                "type" => 1,
                "health" => 3,
                "points" => 2,
            ];
            DatabaseTestAdapter::insert("unit", $unitData);
        }

        return true;
    }

    public static function game_list()
    {
        return DatabaseTestAdapter::query("SELECT game.*, count(user.id) as ucount FROM game
                               INNER JOIN user ON user.game = game.id
                               GROUP BY user.game ORDER BY id DESC");
    }

    public function calculate()
    {
        // Mock implementation for tests
        return true;
    }

    public function all_system_message($text)
    {
        // Mock implementation for tests
        return true;
    }
}
