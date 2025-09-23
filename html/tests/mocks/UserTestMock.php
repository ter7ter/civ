<?php

/**
 * Мок класса User для тестов
 */
class UserTestMock
{
    public $id;
    public $login;
    public $color;
    public $game;
    public $turn_order;
    public $turn_status;
    public $money;
    public $age;
    public $income = 0;
    public $research_amount = 0;
    public $research_percent = 0;
    public $process_research_complete = 0;
    public $process_research_turns = 0;
    public $process_research_type = 0;

    public static $_all = [];

    public function __construct($data)
    {
        foreach (
            [
                "login",
                "color",
                "game",
                "turn_order",
                "turn_status",
                "money",
                "age",
                "income",
                "research_amount",
                "research_percent",
                "process_research_complete",
                "process_research_turns",
                "process_research_type",
            ]
            as $field
        ) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        if (isset($data["id"])) {
            $this->id = $data["id"];
            self::$_all[$this->id] = $this;
        }
    }

    public static function get($id)
    {
        if (isset(self::$_all[$id])) {
            return self::$_all[$id];
        }

        $data = DatabaseTestAdapter::query(
            "SELECT * FROM user WHERE id = :id",
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
            [
                "login",
                "color",
                "game",
                "turn_order",
                "turn_status",
                "money",
                "age",
                "income",
                "research_amount",
                "research_percent",
                "process_research_complete",
                "process_research_turns",
                "process_research_type",
            ]
            as $field
        ) {
            if (isset($this->$field)) {
                $values[$field] = $this->$field;
            }
        }

        if ($this->id) {
            DatabaseTestAdapter::update("user", $values, $this->id);
        } else {
            $this->id = DatabaseTestAdapter::insert("user", $values);
            self::$_all[$this->id] = $this;
        }
    }
}
