<?php

namespace App;

class ResearchType
{
    public $id;
    //Название
    public $title;
    //Стоимость исследования
    public $cost;

    /**
     * Требуемые исследования
     * @var ResearchType[]
     */
    public $requirements = [];

    //Визуальное расположение на карте исследований
    public $m_top = 30;
    public $m_left = 0;
    /**
     * Эпоха
     * @var int
     */
    public $age = 1;
    /**
     * Требуется ли для перехода в следующий век
     * @var bool
     */
    public $age_need = true;
    /**
     * @var ResearchType[]
     */
    protected static $all = [];

    public static function get($id)
    {
        if (isset(ResearchType::$all[$id])) {
            return ResearchType::$all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM research_type WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if ($data) {
                $rt = new ResearchType($data);
                ResearchType::$all[$id] = $rt;
                return $rt;
            } else {
                return false;
            }
        }
    }

    public static function getByTitle($title)
    {
        foreach (ResearchType::$all as $rt) {
            if ($rt->title === $title) {
                return $rt;
            }
        }
        $data = MyDB::query(
            "SELECT * FROM research_type WHERE title = :title",
            ["title" => $title],
            "row",
        );
        if ($data) {
            $rt = new ResearchType($data);
            ResearchType::$all[$rt->id] = $rt;
            return $rt;
        } else {
            return false;
        }
    }

    public static function loadAll()
    {
        $data = MyDB::query("SELECT * FROM research_type ORDER BY id");
        foreach ($data as $row) {
            new ResearchType($row);
        }
        return ResearchType::$all;
    }

    public static function getAll()
    {
        if (empty(ResearchType::$all)) {
            self::loadAll();
        }
        return ResearchType::$all;
    }

    public function __construct($data)
    {
        if (isset($data['id'])) {
            $this->id = (int)$data['id'];
        }

        // Устанавливаем значения по умолчанию
        $this->title = '';
        $this->cost = 0;
        $this->requirements = [];
        $this->m_top = 30;
        $this->m_left = 0;
        $this->age = 1;
        $this->age_need = true;

        // Явно устанавливаем известные свойства
        $knownFields = [
            "title",
            "cost",
            "m_top",
            "m_left",
            "age",
            "age_need",
        ];

        foreach ($knownFields as $field) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        $this->loadRequirements();

        if (isset($data['id'])) {
            ResearchType::$all[$this->id] = $this;
        }
    }

    public function getTitle()
    {
        return $this->title;
    }

    //Расчитывает сколько будет исследоваться данная наука при заданных затратах на ход
    public function get_turn_count($amount)
    {
        if ($amount <= 0) {
            return false;
        }
        $result = Ceil($this->cost / $amount);
        if ($result > 50) {
            $result = 50;
        }
        if ($result < 4) {
            $result = 4;
        }
        return $result;
    }

    /**
     * Возвращает список id исследований, необходимых для прохода данной эры
     * @param $age int
     * @return array
     */
    public static function get_need_age_ids($age)
    {
        $result = [];
        foreach (ResearchType::getAllCached() as $research) {
            if ($research->age == $age && $research->age_need) {
                $result[] = $research->id;
            }
        }
        return $result;
    }

    public function loadRequirements()
    {
        $this->requirements = [];

        $data = MyDB::query("SELECT required_research_type_id FROM research_requirements WHERE research_type_id = :id", ["id" => $this->id]);
        foreach ($data as $row) {
            $this->requirements[] = ResearchType::get($row['required_research_type_id']);
        }
    }

    public function addRequirement($req)
    {
        if (!in_array($req, $this->requirements, true)) {
            $this->requirements[] = $req;
        }
    }

    public function save()
    {
        $data = [
            'title' => $this->title,
            'cost' => $this->cost,
            'm_top' => $this->m_top,
            'm_left' => $this->m_left,
            'age' => $this->age,
            'age_need' => (int)$this->age_need,
        ];
        if (isset($this->id)) {
            MyDB::update('research_type', $data, $this->id);
        } else {
            $this->id = (int)MyDB::insert('research_type', $data);
        }
        // Update requirements in join table
        MyDB::query("DELETE FROM research_requirements WHERE research_type_id = :id", ["id" => $this->id]);
        foreach ($this->requirements as $req) {
            MyDB::insert("research_requirements", ["research_type_id" => $this->id, "required_research_type_id" => $req->id]);
        }
        ResearchType::$all[$this->id] = $this;
    }

    public function delete()
    {
        if (isset($this->id)) {
            MyDB::query("DELETE FROM research_type WHERE id = :id", ["id" => $this->id]);
            unset(ResearchType::$all[$this->id]);
        }
    }

    public static function clearAll()
    {
        ResearchType::$all = [];
    }

    public static function getAllCached()
    {
        return ResearchType::$all;
    }
}
