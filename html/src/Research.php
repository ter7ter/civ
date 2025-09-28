<?php

namespace App;

class Research
{
    //int
    public $id;
    //ResearchType
    public $type;
    //User
    public $user;

    public static $all = [];

    public static function get($id)
    {
        $data = MyDB::query(
            "SELECT * FROM research WHERE id = :id",
            ["id" => $id],
            "row",
        );
        return new Research($data);
    }

    public function __construct($data)
    {
        if (isset($data["id"])) {
            $this->id = $data["id"];
        }
        $this->type = ResearchType::get($data["type"]);
        $this->user = User::get($data["user_id"]);
    }

    public function save()
    {
        $data = [];
        $data["type"] = $this->type->id;
        $data["user_id"] = $this->user->id;
        if ($this->id !== null) {
            MyDB::update("research", $data, $this->id);
        } else {
            $this->id = MyDB::insert("research", $data);
        }
    }
}
