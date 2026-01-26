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
    public float $points = 0;

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
                "lvl",
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
                "lvl",
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

        $unitType = UnitType::get($data["type"]);
        if ($unitType === null || $unitType === false) {
            throw new Exception("Invalid unit type provided: " . $data["type"]);
        }
        $this->type = $unitType;

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
    public function getMissionTypes($x = null, $y = null): array
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
    public function startMission($mtype, &$result_mission = null, $title = ""): bool|string
    {
        return UnitMissionHandler::startMission($this, $mtype, $result_mission, $title);
    }

    /**
     * Сколько ходов осталось до завершения текущей миссии
     * @return int
     */
    public function getCurrentMissionNeedTurns(): int
    {
        if ($this->mission) {
            return UnitMissionHandler::getNeedTurns($this->x, $this->y, $this->planet, $this->mission);
        } else {
            return 0;
        }
    }

    /**
     * Сколько ходов займёт выполнение миссии
     * @param MissionType $missionType
     * @return int|bool
     */
    public function getMissionNeedTurns(MissionType $missionType): int|bool
    {
        return UnitMissionHandler::unitGetNeedTurns($this, $missionType);
    }

    /**
     * Может ли юнит переместится на данную клетку
     * @param Cell $cell
     * @return bool
     */
    public function canMove($cell): bool
    {
        return UnitMovement::canMove($this, $cell);
    }

    /**
     * Осуществляет перемещение с текущей клетки на заданную
     * @param Cell $cell
     * @return bool
     */
    public function moveTo($cell): bool
    {
        return UnitMovement::moveTo($this, $cell);
    }

    /**
     * Осуществляет перемещение по заданному пути
     * @param $path
     */
    public function movePath($path)
    {
        UnitMovement::movePath($this, $path, "move");
    }

    /**
     * Строит дорогу по заданному пути
     * @param $path
     * @return void
     */
    public function roadPath($path)
    {
        UnitMovement::movePath($this, $path, "road");
    }

    public function addOrder(
        $mission,
        $target_x = "NULL",
        $target_y = "NULL",
        $number = false,
    ): bool|int {
        return UnitOrderHandler::addOrder($this, $mission, $target_x, $target_y, $number);
    }

    public function calculate()
    {
        if ($this->points == 0) {
            return;
        }
        UnitOrderHandler::processOrders($this);
        UnitMissionHandler::processMissions($this);
        UnitAutoHandler::processAuto($this);
    }

    /**
     * Отменяет текущую миссию юнита
     * @return bool
     */
    public function cancelMission(): bool
    {
        if (!$this->mission) {
            return false;
        }
        $this->mission = false;
        $this->mission_points = 0;
        $this->save();
        return true;
    }

    /**
     * Расчитывает путь между двумя клетками
     * @param Cell $cell1
     * @param Cell $cell2
     * @param int $max_path
     * @return array|bool
     */
    public function calculatePath($cell1, $cell2, $max_path = 200)
    {
        return UnitMovement::calculatePath($this, $cell1, $cell2, $max_path);
    }
}
