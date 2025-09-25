<?php
class BuildingType
{
    public $id;
    public $title;
    public $cost;
    /**
     * Требуемые исследования
     * @var array
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
    public $need_research = [];
    public $culture_bonus = 0;
    public $research_bonus = 0;
    public $money_bonus = 0;
    public $description = "";

    public static $all = [];

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
                return new BuildingType($data);
            } else {
                return false;
            }
        }
    }

    public static function getAll()
    {
        $data = MyDB::query("SELECT * FROM building_type ORDER BY id");
        $result = [];
        foreach ($data as $row) {
            $result[] = new BuildingType($row);
        }
        return $result;
    }

    public function save()
    {
        $data = [
            'title' => $this->title,
            'cost' => $this->cost,
            'req_research' => json_encode($this->req_research),
            'req_resources' => json_encode($this->req_resources),
            'need_coastal' => (int)$this->need_coastal,
            'culture' => $this->culture,
            'upkeep' => $this->upkeep,
            'need_research' => json_encode($this->need_research),
            'culture_bonus' => $this->culture_bonus,
            'research_bonus' => $this->research_bonus,
            'money_bonus' => $this->money_bonus,
            'description' => $this->description,
        ];
        if (isset($this->id)) {
            MyDB::update('building_type', $data, $this->id);
        } else {
            $this->id = MyDB::insert('building_type', $data);
            BuildingType::$all[$this->id] = $this;
        }
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

        // Обрабатываем JSON поля
        $jsonFields = [
            "req_research",
            "req_resources",
            "need_research",
        ];

        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                if (is_string($data[$field])) {
                    $this->$field = json_decode($data[$field], true);
                } else {
                    $this->$field = $data[$field];
                }
            }
        }

        if (isset($data["id"])) {
            BuildingType::$all[$data["id"]] = $this;
        }
    }

    function get_title()
    {
        return $this->title;
    }

    /**
     * Применяет эффект этой постройки к городу
     * @param $city City
     */
    public function city_effect($city)
    {
        switch ($this->id) {
            case 2: //Амбар
                $city->eat_up = (int) (BASE_EAT_UP / 2);
                break;
            case 3: //Храм
                $city->people_norm -= 1;
                $city->people_happy += 1;
                if ($city->people_norm < 0) {
                    $city->people_happy += $city->people_norm;
                    $city->people_norm = 0;
                }
                break;
            case 4: //Библиотека
                $city->presearch = $city->presearch * 1.5;
                break;
            case 6: //Рынок
                $city->pmoney = $city->pmoney * 1.5;
                break;
            case 10: //Колизей
                $city->people_dis -= 2;
                $city->people_norm += 2;
                if ($city->people_dis < 0) {
                    $city->people_happy += $city->people_dis;
                    $city->people_dis = 0;
                }
                break;
        }
    }
}
