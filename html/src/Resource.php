<?php

namespace App;

use Exception;

class Resource
{
    /**
     * @var mixed string|null
     */
    public mixed $id = null;
    /**
     * @var int
     */
    public int $x;
    /**
     * @var int
     */
    public int $y;
    /**
     * @var int id планеты
     */
    public int $planet = 0;
    /**
     * @var ResourceType
     */
    public mixed $type;
    /**
     * @var int
     */
    public int $amount;

    public static function get($x, $y, $planet): bool|Resource
    {
        $data = MyDB::query(
            "SELECT * FROM resource WHERE x = :x AND y = :y AND planet = :planet",
            ["x" => $x, "y" => $y, "planet" => $planet],
            "row",
        );
        if ($data) {
            return new Resource($data);
        } else {
            return false;
        }
    }

    public function __construct($data)
    {
        if (isset($data["id"])) {
            $this->id = $data["id"];
        }
        foreach (["x", "y", "planet", "amount"] as $field) {
            $this->$field = $data[$field];
        }
        $this->type = ResourceType::get($data["type"]);
    }

    public function save(): void
    {
        if (!$this->type) {
            throw new Exception("Resource type is not set");
        }
        $data = [];
        foreach (["x", "y", "planet", "amount"] as $field) {
            $data[$field] = $this->$field;
        }
        $data["type"] = $this->type->id;
        if ($this->id !== null) {
            MyDB::update("resource", $data, $this->id);
        } else {
            $this->id = MyDB::insert("resource", $data);
        }
    }

    public function getTitle(): string
    {
        return $this->type ? $this->type->title : "";
    }
}
