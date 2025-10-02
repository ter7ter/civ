<?php

namespace App;

use Exception;
use App\Interfaces\IModel;
use App\Interfaces\UnitInterface;

class Unit implements IModel, UnitInterface
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var UnitType
     */
    public UnitType $type;
    /**
     * @var User
     */
    public User $user;
    public int $x;
    public int $y;
    public int $planet;
    /**
     * Текущие HP у юнита
     * @var int
     */
    public int $health = 3;
    /**
     * Максимум HP у этого юнита
     * @var int
     */
    public int $health_max = 3;
    /**
     * @var MissionType
     */
    public $mission = false;
    public int $points = 0;

    /**
     * Менеджер статистики юнита
     * @var UnitStats
     */
    private UnitStats $stats;
    /**
     * Сколько уже вложено очков в выполнение текущей миссии
     * @var int
     */
    public int $mission_points = 0;
    /**
     * Включена ли автоматизация действий юнита
     * @var string
     */
    public $auto = "none";

    public $mission_x = null;
    public $mission_y = null;

    public $lvl = 1;

    protected static $all = [];

    /**
     * Очистка кэша для тестов
     */
    public static function clearCache()
    {
        self::$all = [];
    }

    /**
     * @param $id
     * @return Unit
     * @throws Exception
     */
    public static function get($id)
    {
        if (isset(Unit::$all[$id])) {
            return Unit::$all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM unit WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if ($data && isset($data["id"])) {
                return new Unit($data);
            } else {
                return null;
            }
        }
    }

    public static function getAll()
    {
        $units = [];
        $data = MyDB::query("SELECT * FROM unit");
        foreach ($data as $row) {
            $units[] = new Unit($row);
        }
        return $units;
    }

    public function save()
    {
        $data = ["user_id" => $this->user->id, "type" => $this->type->id];
        foreach (
            [
                "x",
                "y",
                "planet",
                "health",
                "health_max",
                "points",
                "mission_points",
                "auto",
            ] as $field
        ) {
            $data[$field] = $this->$field;
        }
        if ($this->mission) {
            $data["mission"] = $this->mission->id;
        } else {
            $data["mission"] = "NULL";
        }
        if ($this->id !== null) {
            MyDB::update("unit", $data, $this->id);
        } else {
            $this->id = MyDB::insert("unit", $data);
        }
        self::$all[$this->id] = $this;
    }

    public function remove()
    {
        MyDB::query("DELETE FROM unit WHERE id = :id", ["id" => $this->id]);
        unset(Unit::$all[$this->id]);
    }

    public function __construct($data)
    {
        if (!$data || !is_array($data)) {
            throw new Exception(
                "Invalid unit data provided to Unit constructor",
            );
        }

        if (isset($data["id"])) {
            $this->id = $data["id"];
        }
        foreach (
            [
                "x",
                "y",
                "planet",
                "health",
                "health_max",
                "mission_x",
                "mission_y",
                "points",
                "mission_points",
                "auto",
            ] as $field
        ) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        if (!isset($data["user_id"])) {
            throw new Exception("user_id is required for Unit constructor");
        }
        if (!isset($data["type"])) {
            throw new Exception("type is required for Unit constructor");
        }

        $this->user = User::get($data["user_id"]);
        if ($this->user === null) {
            throw new Exception(
                "Invalid user_id provided: " . $data["user_id"],
            );
        }

        $this->type = UnitType::get($data["type"]);
        if ($this->type === null || $this->type === false) {
            throw new Exception("Invalid unit type provided: " . $data["type"]);
        }
        if (isset($data["mission"])) {
            $this->mission = MissionType::get($data["mission"]);
        }

        if (isset($data["id"])) {
            Unit::$all[$this->id] = $this;
        }
        $this->stats = new UnitStats($this);
    }

    public function getTitle()
    {
        return $this->type->getTitle();
    }

    /**
     * Какие миссии этот юнит может выполнить в данной точке
     * @param $x int
     * @param $y int
     * @return array
     */
    public function get_mission_types($x = null, $y = null)
    {
        return UnitMissionHandler::getMissionTypes($this, $x, $y);
    }

    /**
     * Выполнение юнитом задания
     * @param MissionType $mtype
     * @param string $title
     * @return bool|string
     * @throws Exception
     */
    public function start_mission($mtype, $title = "")
    {
        return UnitMissionHandler::startMission($this, $mtype, $title);
    }

    /**
     * Может ли юнит переместится на данную клетку
     * @param Cell $cell
     * @return bool
     */
    public function can_move($cell)
    {
        return UnitMovement::canMove($this, $cell);
    }

    /**
     * Осуществляет перемещение с текущей клетки на заданную
     * @param Cell $cell
     * @return bool
     */
    public function move_to($cell)
    {
        return UnitMovement::moveTo($this, $cell);
    }

    /**
     * Осуществляет перемещение по заданному пути
     * @param $path
     */
    public function move_path($path)
    {
        UnitMovement::movePath($this, $path);
    }

    public function add_order(
        $mission,
        $target_x = "NULL",
        $target_y = "NULL",
        $number = false,
    ) {
        return UnitOrderHandler::addOrder($this, $mission, $target_x, $target_y, $number);
    }

    public function calculate()
    {
        if ($this->points == 0) {
            return;
        }
        UnitMissionHandler::processMissions($this);
        UnitOrderHandler::processOrders($this);
        UnitAutoHandler::processAuto($this);
    }

    /**
     * Расчитывает путь между двумя клетками
     * @param Cell $cell1
     * @param Cell $cell2
     * @param int $max_path
     * @return array|bool
     */
    public function calculate_path($cell1, $cell2, $max_path = 200)
    {
        return UnitMovement::calculatePath($this, $cell1, $cell2, $max_path);
    }
}
