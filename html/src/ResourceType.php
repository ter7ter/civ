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
            return false;
        }
    }

    public static function getAll()
    {
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
        ResourceType::$all[$this->id] = $this;
    }

    public function get_title()
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
}
