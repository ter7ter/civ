<?php

namespace App;

class BuildingType extends BaseType
{
    public $cost;
    /**
     * Требуемые исследования
     * @var ResearchType[]
     */
    public $req_research = [];
    /**
     * Требуемые ресурсы
     * @var ResourceType[]
     */
    public $req_resources = [];
    /**
     * Требуется что бы город был прибрежным
     * @var bool
     */
    public $need_coastal = false;
    /**
     * Сколько даёт кульуры за ход
     * @var int
     */
    public $culture = 0;
    /**
     * Затраты по содержанию за ход
     * @var int
     */
    public $upkeep = 0;

    /**
     * Дополнительные динамические свойства
     */
    public $culture_bonus = 0;
    public $research_bonus = 0;
    public $money_bonus = 0;
    public $description = "";

    /**
     * @var array эффекты постройки
     * [
     *  eat_up_multiplier => 1,
     *  people_norm => 0
     *  people_dis => 0
     *  people_happy => 0
     *  research_multiplier => 1
     *  money_multiplier => 1
     * ]
     */
    public $city_effects = [];

    protected static $all = [];

    public static function get($id)
    {
        $id = (int)$id;
        if (isset(BuildingType::$all[$id])) {
            return BuildingType::$all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM building_type WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if ($data) {
                $data['city_effects'] = json_decode($data['city_effects'], true);
                return new BuildingType($data);
            } else {
                return false;
            }
        }
    }


    public static function clearAll(): void
    {
        BuildingType::$all = [];
    }

    /**
     * Возвращает все типы зданий
     * @return array
     */
    public static function getAll()
    {
        if (count(BuildingType::$all) == 0) {
            $data = MyDB::query("SELECT * FROM building_type ORDER BY id");
            $result = [];
            foreach ($data as $row) {
                $result[] = new BuildingType($row);
            }
        }
        return BuildingType::$all;
    }

    public function save()
    {
        $data = [
            'title' => $this->title,
            'cost' => $this->cost,
            'need_coastal' => (int)$this->need_coastal,
            'culture' => $this->culture,
            'upkeep' => $this->upkeep,
            'culture_bonus' => $this->culture_bonus,
            'research_bonus' => $this->research_bonus,
            'money_bonus' => $this->money_bonus,
            'description' => $this->description,
            'city_effects' => json_encode($this->city_effects),
        ];
        if (isset($this->id)) {
            MyDB::update('building_type', $data, $this->id);
        } else {
            $this->id = MyDB::insert('building_type', $data);
        }
        // Update requirements in join table
        MyDB::query("DELETE FROM building_requirements_research WHERE building_type_id = :id", ["id" => $this->id]);
        foreach ($this->req_research as $req) {
            if ($req && isset($req->id)) {
                MyDB::insert("building_requirements_research", ["building_type_id" => $this->id, "required_research_type_id" => $req->id]);
            }
        }
        MyDB::query("DELETE FROM building_requirements_resources WHERE building_type_id = :id", ["id" => $this->id]);
        foreach ($this->req_resources as $req) {
            if ($req && isset($req->id)) {
                MyDB::insert("building_requirements_resources", ["building_type_id" => $this->id, "required_resource_type_id" => $req->id]);
            }
        }
        BuildingType::$all[$this->id] = $this;
    }

    public function delete()
    {
        if (isset($this->id)) {
            MyDB::query("DELETE FROM building_type WHERE id = :id", ["id" => $this->id]);
            unset(BuildingType::$all[$this->id]);
        }
    }

    public function __construct($data)
    {
        // Устанавливаем значения по умолчанию
        $this->cost = 0;
        $this->need_coastal = false;
        $this->culture = 0;
        $this->upkeep = 0;
        $this->culture_bonus = 0;
        $this->research_bonus = 0;
        $this->money_bonus = 0;
        $this->description = "";
        $this->req_research = [];
        $this->req_resources = [];
        $this->city_effects = [
            'eat_up_multiplier' => 1,
            'people_norm' => 0,
            'people_dis' => 0,
            'people_happy' => 0,
            'research_multiplier' => 1,
            'money_multiplier' => 1
        ];

        // Список разрешенных свойств
        $allowedProperties = [
            "id",
            "title",
            "cost",
            "need_coastal",
            "culture",
            "upkeep",
            "culture_bonus",
            "research_bonus",
            "money_bonus",
            "description",
        ];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedProperties)) {
                if ($field === 'id') {
                    $this->$field = (int)$value;
                } else {
                    $this->$field = $value;
                }
            }
        }

        $allowedEffects = [
            "eat_up_multiplier",
            "people_norm",
            "people_dis",
            "people_happy",
            "research_multiplier",
            "money_multiplier"
        ];

        foreach ($allowedEffects as $field) {
            if (isset($data['city_effects'][$field])) {
                $this->city_effects[$field] = $data['city_effects'][$field];
            }
        }

        if (isset($data["id"])) {
            BuildingType::$all[$data["id"]] = $this;
            $this->loadReqResearch();
            $this->loadReqResources();
        }
    }

    public function loadReqResearch()
    {
        $this->req_research = [];

        $data = MyDB::query("SELECT required_research_type_id FROM building_requirements_research WHERE building_type_id = :id", ["id" => $this->id]);
        foreach ($data as $row) {
            $this->req_research[] = ResearchType::get($row['required_research_type_id']);
        }
    }

    public function loadReqResources()
    {
        $this->req_resources = [];

        $data = MyDB::query("SELECT required_resource_type_id FROM building_requirements_resources WHERE building_type_id = :id", ["id" => $this->id]);
        foreach ($data as $row) {
            $this->req_resources[] = ResourceType::get($row['required_resource_type_id']);
        }
    }

    public function addReqResearch($req)
    {
        if (!in_array($req, $this->req_research, true)) {
            $this->req_research[] = $req;
        }
    }

    public function addReqResources($req)
    {
        if (!in_array($req, $this->req_resources, true)) {
            $this->req_resources[] = $req;
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Применяет эффект этой постройки к городу
     * @param $city City
     */
    public function city_effect($city)
    {
        $city->eat_up = (int)($city->eat_up * $this->city_effects['eat_up_multiplier']);

        if ($this->city_effects['people_happy']) {
            $city->people_norm -= $this->city_effects['people_happy'];
            $city->people_happy += $this->city_effects['people_happy'];
            if ($city->people_norm < 0) {
                $city->people_happy += $city->people_norm;
                $city->people_norm = 0;
            }
        }
        if ($this->city_effects['people_dis']) {
            $city->people_dis += $this->city_effects['people_dis'];
            $city->people_norm -= $this->city_effects['people_dis'];
            if ($city->people_norm < 0) {
                $city->people_happy += $city->people_norm;
                $city->people_norm = 0;
            }
            if ($city->people_happy < 0) {
                $city->people_dis += $city->people_happy;
                $city->people_happy = 0;
            }
        }
        if ($this->city_effects['people_norm']) {
            $city->people_dis -= $this->city_effects['people_norm'];
            $city->people_norm += $this->city_effects['people_norm'];
            if ($city->people_dis < 0) {
                $city->people_norm += $city->people_dis;
                $city->people_dis = 0;
            }
        }
        $city->presearch = $city->presearch * $this->city_effects['research_multiplier'];
        $city->pmoney = $city->pmoney * $this->city_effects['money_multiplier'];
    }
}
