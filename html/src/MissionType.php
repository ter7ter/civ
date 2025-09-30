<?php

namespace App;

class MissionType implements MissionInterface
{
    //int
    public $id;
    //string
    public $title;
    //array
    public $cell_types = [];

    public $unit_lost = false;
    //Сколько требуется очков для полного выполнения задания, в зависимости от типа местности
    public $need_points = [];
    // Стратегия для завершения миссии
    public $completeHandler;

    public static $all = [];

    /**
     * @param $id
     * @return bool|MissionType
     */
    public static function get($id)
    {
        return (isset(MissionType::$all[$id])) ? MissionType::$all[$id] : false;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function check_cell(int $x, int $y, int $planet_id): bool
    {
        $cell = Cell::get($x, $y, $planet_id);
        if (!in_array($cell->type->id, $this->cell_types)) {
            return false;
        }
        if ($this->id == 'build_city' && $cell->city) {
            return false;
        }
        if ($this->id == 'build_road') {
            if ($cell->city) {
                return false;
            }
            if ($cell->road) {
                return false;
            }
        }
        if ($this->id == 'mine' || $this->id == 'irrigation') {
            if ($cell->city) {
                return false;
            }
            if ($cell->improvement && $cell->improvement == $this->id) {
                return false;
            }
        }

        return true;
    }

    public function __construct($data)
    {
        foreach (['id', 'title', 'unit_lost', 'cell_types', 'need_points'] as $field) {
            $this->$field = $data[$field];
        }

        MissionType::$all[$data['id']] = $this;
    }

    //Завершение выполнения миссии
    public function complete(Unit $unit, string|false $title = false): bool
    {
        if ($this->completeHandler) {
            return $this->completeHandler->complete($unit, $title);
        }
        return false;
    }
}
new MissionType([   'id' => 'build_city',
                    'title' => 'Основать город',
                    'unit_lost' => true,
                    'cell_types' => ['plains', 'plains2', 'forest', 'hills', 'desert'],
                    'need_points' => []]);
MissionType::$all['build_city']->completeHandler = new BuildCityMission();

new MissionType([   'id' => 'build_road',
                    'title' => 'Строить дорогу',
                    'unit_lost' => false,
                    'cell_types' => ['plains', 'plains2', 'forest', 'hills', 'desert', 'mountains'],
                    'need_points' => [
                        'plains' => 4,
                        'plains2' => 4,
                        'forest' => 6,
                        'hills' => 6,
                        'desert' => 4,
                        'mountains' => 8
                    ]]);
MissionType::$all['build_road']->completeHandler = new BuildRoadMission();

new MissionType([   'id' => 'mine',
                    'title' => 'Построить рудник',
                    'unit_lost' => false,
                    'cell_types' => ['plains', 'plains2', 'hills', 'mountains'],
                    'need_points' => [
                        'plains' => 8,
                        'plains2' => 8,
                        'hills' => 10,
                        'mountains' => 10
                    ]]);
MissionType::$all['mine']->completeHandler = new BuildMineAndIrrigationMission('mine');

new MissionType([   'id' => 'irrigation',
                    'title' => 'Орошать',
                    'unit_lost' => false,
                    'cell_types' => ['plains', 'plains2', 'desert'],
                    'need_points' => [
                        'plains' => 10,
                        'plains2' => 10,
                        'desert' => 10
                    ]]);
MissionType::$all['irrigation']->completeHandler = new BuildMineAndIrrigationMission('irrigation');

new MissionType([   'id' => 'move_to',
                    'title' => 'Идти к',
                    'unit_lost' => false,
                    'cell_types' => ['plains', 'plains2', 'forest', 'hills', 'mountains', 'water1', 'water2', 'water3', 'desert'],
                    'need_points' => []]);
MissionType::$all['move_to']->completeHandler = null; // move_to doesn't complete instantly, it's for movement
