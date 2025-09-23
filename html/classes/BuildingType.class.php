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
        return isset(BuildingType::$all[$id]) ? BuildingType::$all[$id] : false;
    }

    public function __construct($data)
    {
        // Список разрешенных свойств
        $allowedProperties = [
            "id",
            "title",
            "cost",
            "req_research",
            "req_resources",
            "need_coastal",
            "culture",
            "upkeep",
            "need_research",
            "culture_bonus",
            "research_bonus",
            "money_bonus",
            "description",
        ];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedProperties)) {
                $this->$field = $value;
            }
        }

        BuildingType::$all[$data["id"]] = $this;
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

new BuildingType(["id" => 1, "title" => "бараки", "upkeep" => 1, "cost" => 30]);
new BuildingType([
    "id" => 2,
    "title" => "амбар",
    "cost" => 30,
    "upkeep" => 1,
    "req_research" => [
        ResearchType::get(5), //Гончарное дело
    ],
]);
new BuildingType([
    "id" => 3,
    "title" => "храм",
    "cost" => 30,
    "culture" => 2,
    "upkeep" => 1,
    "req_research" => [
        ResearchType::get(6), //Мистицизм
    ],
]);
new BuildingType([
    "id" => 4,
    "title" => "библиотека",
    "cost" => 50,
    "culture" => 3,
    "upkeep" => 1,
    "req_research" => [
        ResearchType::get(15), //Литература
    ],
]);
new BuildingType([
    "id" => 5,
    "title" => "стены",
    "cost" => 30,
    "req_research" => [
        ResearchType::get(12), //Строительство
    ],
]);
new BuildingType([
    "id" => 6,
    "title" => "рынок",
    "cost" => 50,
    "req_research" => [
        ResearchType::get(19), //Деньги
    ],
]);
new BuildingType([
    "id" => 7,
    "title" => "суд",
    "cost" => 60,
    "req_research" => [
        ResearchType::get(13), //Свод законов
    ],
]);
new BuildingType([
    "id" => 8,
    "title" => "гавань",
    "cost" => 60,
    "upkeep" => 1,
    "req_research" => [
        ResearchType::get(16), //Создание карт
    ],
]);
new BuildingType([
    "id" => 9,
    "title" => "акведук",
    "cost" => 80,
    "upkeep" => 1,
    "req_research" => [
        ResearchType::get(18), //Конструкции
    ],
]);
new BuildingType([
    "id" => 10,
    "title" => "колизей",
    "cost" => 80,
    "upkeep" => 2,
    "req_research" => [
        ResearchType::get(18), //Конструкции
    ],
]);
