<?php

namespace App;

class UnitType
{
    /**
     * @var int
     */
    public $id;
    /**
     * Название
     * @var string
     */
    public $title;
    /**
     * Максимальные очки движений
     * @var int
     */
    public $points;
    /**
     * Cтоимость постройки, производство
     * @var int
     */
    public $cost;
    /**
     * Стоимость постройки населения
     * @var int
     */
    public $population_cost = 0;
    /**
     * Тип юнита - land, water, air
     * @var string
     */
    public $type = "land";
    /**
     * @var int
     */
    public $attack = 0;
    /**
     * @var int
     */
    public $defence = 0;
    /**
     * По каким типам местности может ходить и с какими затратами перемещения
     * @var array
     */
    public $can_move = [
        "plains" => 1,
        "plains2" => 1,
        "forest" => 1,
        "hills" => 1,
        "mountains" => 2,
        "desert" => 1,
        "city" => 1,
    ];

    // Дополнительные свойства для устранения динамических свойств
    public $health = 1;
    public $movement = 1;
    public $upkeep = 0;
    public $can_found_city = false;
    public $can_build = false;
    public $need_research = [];
    public $description = "";
    public $mission_points = [];
    public $age = 1;
    /**
     * Доступные типы миссий
     * @var array
     */
    public $missions = ["move_to"];
    /**
     * Требуемые исследования
     * @var ResourceType[]
     */
    public $req_research = [];
    /**
     * Требуемые ресурсы
     * @var ResourceType[]
     */
    public $req_resources = [];

    /**
     * Кеш
     * @var array UnitType[]
     */
    protected static $all = [];

    public static function get($id)
    {
        if (isset(UnitType::$all[$id])) {
            return UnitType::$all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM unit_type WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if ($data) {
                return new UnitType($data);
            } else {
                return false;
            }
        }
    }

    public static function loadAll()
    {

    }

    public static function clearAll()
    {
        UnitType::$all = [];
    }

    /**
     * Возвращает кэш всех загруженных типов юнитов
     * @return array
     */
    public static function getAll()
    {
        if (count(UnitType::$all) == 0) {
            $data = MyDB::query("SELECT * FROM unit_type ORDER BY id");
            $result = [];
            foreach ($data as $row) {
                $result[] = new UnitType($row);
            }
        }

        return UnitType::$all;
    }

    public function save()
    {
        $data = [
            'title' => $this->title,
            'points' => $this->points,
            'cost' => $this->cost,
            'population_cost' => $this->population_cost,
            'type' => $this->type,
            'attack' => $this->attack,
            'defence' => $this->defence,
            'health' => $this->health,
            'movement' => $this->movement,
            'upkeep' => $this->upkeep,
            'can_found_city' => (int)$this->can_found_city,
            'can_build' => (int)$this->can_build,
            'need_research' => json_encode($this->need_research),
            'description' => $this->description,
            'mission_points' => json_encode($this->mission_points),
            'age' => $this->age,
            'missions' => json_encode($this->missions),
            'req_research' => json_encode($this->req_research),
            'req_resources' => json_encode($this->req_resources),
            'can_move' => json_encode($this->can_move),
        ];
        if (isset($this->id)) {
            MyDB::update('unit_type', $data, $this->id);
        } else {
            $this->id = MyDB::insert('unit_type', $data);
        }
        UnitType::$all[$this->id] = $this;
    }

    public function delete()
    {
        if (isset($this->id)) {
            MyDB::query("DELETE FROM unit_type WHERE id = :id", ["id" => $this->id]);
            unset(UnitType::$all[$this->id]);
        }
    }

    public function __construct($data)
    {
        if (isset($data["id"])) {
            $this->id = $data["id"];
        }

        // Устанавливаем значения по умолчанию
        $this->points = 1;
        $this->cost = 0;
        $this->population_cost = 0;
        $this->type = "land";
        $this->attack = 0;
        $this->defence = 0;
        $this->health = 1;
        $this->movement = 1;
        $this->upkeep = 0;
        $this->can_found_city = false;
        $this->can_build = false;
        $this->description = "";
        $this->age = 1;
        $this->missions = ["move_to"];
        $this->can_move = [
            "plains" => 1,
            "plains2" => 1,
            "forest" => 1,
            "hills" => 1,
            "mountains" => 2,
            "desert" => 1,
            "city" => 1,
        ];
        $this->need_research = [];
        $this->mission_points = [];
        $this->req_research = [];
        $this->req_resources = [];

        // Явно устанавливаем известные свойства
        $knownFields = [
            "title",
            "points",
            "cost",
            "population_cost",
            "type",
            "attack",
            "defence",
            "health",
            "movement",
            "upkeep",
            "can_found_city",
            "can_build",
            "description",
            "age",
        ];

        foreach ($knownFields as $field) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        // Обрабатываем JSON поля
        $jsonFields = [
            "need_research",
            "mission_points",
            "missions",
            "req_research",
            "req_resources",
            "can_move",
        ];

        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                if (is_string($data[$field])) {
                    $decoded = json_decode($data[$field], true);
                    $this->$field = $decoded !== null ? $decoded : [];
                } else {
                    $this->$field = $data[$field];
                }
            }
        }

        if (isset($data["id"])) {
            UnitType::$all[$this->id] = $this;
        }
    }

    public function get_title()
    {
        return $this->title;
    }

    public static function clearCache()
    {
        self::$all = [];
    }
}


?>
