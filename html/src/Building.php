<?php

namespace App;

class Building
{
    /**
     * @var int
     */
    public $id = null;
    /**
     * @var City
     */
    public $city;
    /**
     * @var BuildingType
     */
    public $type;

    private static $_all = [];

    /**
     * Очищает кэш зданий
     */
    public static function clearCache(): void
    {
        Building::$_all = [];
    }

    /**
     * @param $id
     * @return Building
     * @throws Exception
     */
    public static function get($id)
    {
        $id = (int)$id;
        if (isset(Building::$_all[$id])) {
            return Building::$_all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM building WHERE id =:id",
                ["id" => $id],
                "row",
            );
            return new Building($data);
        }
    }

    public function __construct($data)
    {
        $this->id = isset($data["id"]) ? (int)$data["id"] : null;
        $this->type = BuildingType::get($data["type"]);
        $this->city = City::get($data["city_id"]);
        if ($this->id) {
            Building::$_all[$this->id] = $this;
        }
    }

    public function getTitle()
    {
        return $this->type->getTitle();
    }

    public function save()
    {
        $values = ["city_id" => $this->city->id, "type" => $this->type->id];
        if ($this->id !== null) {
            MyDB::update("building", $values, $this->id);
        } else {
            $this->id = MyDB::insert("building", $values);
        }
    }
}
