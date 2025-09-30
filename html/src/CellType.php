<?php

namespace App;

class CellType
{
    public $id;
    public $base_chance = 0;
    public $chance_inc1 = 0;
    public $chance_inc2 = 0;
    public $title;
    public $chance_inc_other = [];
    public $border_no = [];
    //int
    public $work = 0;
    public $eat = 0;
    public $money = 0;

    public static $all = [];

    public function __construct($data)
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
        foreach (['base_chance', 'chance_inc1', 'chance_inc2', 'title', 'work', 'eat', 'money'] as $field) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        if (isset($data['chance_inc_other'])) {
            if (is_string($data['chance_inc_other'])) {
                $this->chance_inc_other = json_decode($data['chance_inc_other'], true);
            } else {
                $this->chance_inc_other = $data['chance_inc_other'];
            }
        }

        if (isset($data['border_no'])) {
            if (is_string($data['border_no'])) {
                $this->border_no = json_decode($data['border_no'], true);
            } else {
                $this->border_no = $data['border_no'];
            }
        }

        if (isset($data['id'])) {
            CellType::$all[$this->id] = $this;
        }
    }

    public static function get($id)
    {
        return (isset(CellType::$all[$id])) ? CellType::$all[$id] : false;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public static function initData()
    {
        new CellType(['id' => 'plains',
            'title' => 'равнина',
            'base_chance' => 15,
            'chance_inc1' => 8,
            'chance_inc2' => 6,
            'eat' => 2,
            'work' => 1,
            'money' => 1,
            'chance_inc_other' => ['mountains' => [15, 8]],
            'border_no' => ['water2', 'water3']]);
        new CellType(['id' => 'plains2',
            'title' => 'равнина',
            'base_chance' => 15,
            'chance_inc1' => 8,
            'chance_inc2' => 6,
            'eat' => 2,
            'work' => 0,
            'money' => 1,
            'chance_inc_other' => ['plains2' => [15, 8]],
            'border_no' => ['water2', 'water3']]);
        new CellType(['id' => 'forest',
            'title' => 'лес',
            'base_chance' => 15,
            'chance_inc1' => 10,
            'chance_inc2' => 6,
            'eat' => 1,
            'work' => 2,
            'money' => 1,
            'border_no' => ['water2', 'water3']]);
        new CellType(['id' => 'hills',
            'title' => 'холмы',
            'base_chance' => 10,
            'chance_inc1' => 5,
            'chance_inc2' => 3,
            'eat' => 1,
            'work' => 2,
            'money' => 0,
            'chance_inc_other' => ['mountains' => [3, 2]],
            'border_no' => ['water2', 'water3']]);
        new CellType(['id' => 'mountains',
            'title' => 'горы',
            'base_chance' => 4,
            'chance_inc1' => 5,
            'chance_inc2' => 2,
            'eat' => 0,
            'work' => 1,
            'money' => 1,
            'chance_inc_other' => ['hills' => [3, 2]],
            'border_no' => ['water2', 'water3']]);
        new CellType(['id' => 'desert',
            'title' => 'пустыня',
            'base_chance' => 7,
            'chance_inc1' => 6,
            'chance_inc2' => 4,
            'eat' => 0,
            'work' => 1,
            'money' => 2,
            'border_no' => ['water2', 'water3']]);
        new CellType(['id' => 'water1',
            'title' => 'вода',
            'base_chance' => 5,
            'chance_inc1' => 20,
            'chance_inc2' => 15,
            'eat' => 2,
            'work' => 0,
            'money' => 1,
            'chance_inc_other' => ['water2' => [25, 11]],
            'border_no' => ['water3']]);
        new CellType(['id' => 'water2',
            'title' => 'море',
            'base_chance' => 0,
            'chance_inc1' => 35,
            'chance_inc2' => 16,
            'eat' => 1,
            'work' => 0,
            'money' => 0,
            'chance_inc_other' => ['water1' => [14, 8], 'water3' => [20, 10]],
            'border_no' => ['plains', 'plains2', 'forest', 'hills', 'mountains']]);
        new CellType(['id' => 'water3',
            'title' => 'океан',
            'base_chance' => 0,
            'chance_inc1' => 35,
            'chance_inc2' => 17,
            'eat' => 1,
            'work' => 0,
            'money' => 0,
            'chance_inc_other' => ['water2' => [10, 6]],
            'border_no' => ['plains', 'plains2', 'forest', 'hills', 'mountains', 'water1']]);
    }

    public static function getAll()
    {
        if (empty(CellType::$all)) {
            self::loadAll();
        }
        return CellType::$all;
    }

    public static function loadAll()
    {
        $data = MyDB::query("SELECT * FROM cell_type ORDER BY id");
        foreach ($data as $row) {
            new CellType($row);
        }
        return CellType::$all;
    }

    public function save()
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'base_chance' => $this->base_chance,
            'chance_inc1' => $this->chance_inc1,
            'chance_inc2' => $this->chance_inc2,
            'work' => $this->work,
            'eat' => $this->eat,
            'money' => $this->money,
            'chance_inc_other' => json_encode($this->chance_inc_other),
            'border_no' => json_encode($this->border_no),
        ];
        MyDB::replace('cell_type', $data);
    }
}
