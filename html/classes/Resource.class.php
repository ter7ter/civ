<?php
class Resource
{
    public $id = null;
    //int
    public $x;
    //int
    public $y;
    //int
    public $planet = 0;
    /**
     * @var ResourceType
     */
    public $type;
    public $amount;

    public static function get($x, $y)
    {
        $data = MyDB::query(
            "SELECT * FROM resource WHERE x = :x AND y = :y AND planet = :planet",
            ["x" => $x, "y" => $y, "planet" => Cell::$map_planet],
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

    public function save()
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

    function get_title()
    {
        return $this->type ? $this->type->title : "";
    }
}
?>
