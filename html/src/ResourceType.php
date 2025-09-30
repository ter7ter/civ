<?php

namespace App;

class ResourceType
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $title;
    /**
     * Тип ресурса
     * bonuce - дающий + к добыче клетки
     * luxury - роскошь
     * mineral - полезное ископаемое
     * @var string
     */
    public $type = 'bonuce';
    /**
     * Бонус к еде
     * @var int
     */
    public $eat = 0;
    /**
     * Бонус к производству
     * @var int
     */
    public $work = 0;
    /**
     * Бонус к деньгам
     * @var int
     */
    public $money = 0;
    /**
     * Требуемые исследования
     * @var array
     */
    public $req_research = [];
    /**
     * На каких типах местности может распологаться
     * @var array
     */
    public $cell_types = [];

    /**
     * Шанс генерации ресурса на клетках соответсвующих типов
     * @var float
     */
    public $chance = 0.01;
    /**
     * Сколько минимум появляется ресурса(на сколько ходов)
     * @var int
     */
    public $min_amount = 50;
    /**
     * На сколько максимумм появляется ресурса(на сколько ходов)
     * @var int
     */
    public $max_amount = 500;

    protected static $all;

    public static function get($id)
    {
        if (isset(ResourceType::$all[$id])) {
            return ResourceType::$all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM resource_type WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if ($data) {
                $rt = new ResourceType($data);
                ResourceType::$all[$id] = $rt;
                return $rt;
            } else {
                return false;
            }
        }
    }

    public static function getAll()
    {
        if (empty(ResourceType::$all)) {
            self::loadAll();
        }
        return ResourceType::$all;
    }

    public static function loadAll()
    {
        $data = MyDB::query("SELECT * FROM resource_type ORDER BY id");
        foreach ($data as $row) {
            new ResourceType($row);
        }
        return ResourceType::$all;
    }

    public function save()
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'work' => $this->work,
            'eat' => $this->eat,
            'money' => $this->money,
            'req_research' => json_encode(array_map(function($req) { return $req ? $req->id : null; }, $this->req_research)),
            'cell_types' => json_encode(array_map(function($ct) { return $ct ? $ct->id : null; }, $this->cell_types)),
            'chance' => $this->chance,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
        ];
        MyDB::replace('resource_type', $data);
    }

    public static function clearAll()
    {
        ResourceType::$all = [];
    }

    public function __construct($data)
    {
        foreach ($data as $field => $value) {
            $this->$field = $value;
        }

        if (isset($data['cell_types']) && is_string($data['cell_types'])) {
            $this->cell_types = json_decode($data['cell_types'], true);
        }

        if (isset($data['req_research']) && is_string($data['req_research'])) {
            $ids = json_decode($data['req_research'], true);
            $this->req_research = [];
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    $rt = ResearchType::get($id);
                    if ($rt) {
                        $this->req_research[] = $rt;
                    }
                }
            }
        }

        ResourceType::$all[$this->id] = $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Проверяет может ли данный игрок видеть и использовать такой ресурс
     * @param User $user
     * @return bool
     */
    public function canUse($user)
    {
        if (count($this->req_research) == 0) {
            return true;
        }
        $uresearch = $user->get_research();
        foreach ($this->req_research as $research) {
            if (!isset($uresearch[$research->id])) {
                return false;
            }
        }
        return true;
    }

    public function addReqResearch(ResearchType $researchType): void
    {
        $this->req_research[] = $researchType;
    }

    public function delete()
    {
        if (isset($this->id)) {
            MyDB::query("DELETE FROM resource_type WHERE id = :id", ["id" => $this->id]);
            unset(ResourceType::$all[$this->id]);
        }
    }
}
