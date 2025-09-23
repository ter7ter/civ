<?php
class Building
{
    /**
     * @var int
     */
    public $id;
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
     * @param $id
     * @return Building
     * @throws Exception
     */
    public static function get($id)
    {
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
        $this->id = isset($data["id"]) ? $data["id"] : null;
        $this->type = BuildingType::get($data["type"]);
        $this->city = City::get($data["city_id"]);
        if ($this->id) {
            Building::$_all[$this->id] = $this;
        }
    }

    public function get_title()
    {
        return $this->type->get_title();
    }

    public function save()
    {
        $values = ["city_id" => $this->city->id, "type" => $this->type->id];
        if ($this->id) {
            MyDB::update("building", $values, $this->id);
        } else {
            $this->id = MyDB::insert("building", $values);
        }
    }
}
