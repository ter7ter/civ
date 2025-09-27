<?php

namespace App;

class Unit
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var UnitType
     */
    public $type;
    /**
     * @var User
     */
    public $user;
    public $x, $y, $planet;
    /**
     * Текущие HP у юнита
     * @var int
     */
    public $health = 3;
    /**
     * Максимум HP у этого юнита
     * @var int
     */
    public $health_max = 3;
    /**
     * @var MissionType
     */
    public $mission = false;
    public $points = 0;
    /**
     * Сколько уже вложено очков в выполнение текущей миссии
     * @var int
     */
    public $mission_points = 0;
    /**
     * Включена ли автоматизация действий юнита
     * @var string
     */
    public $auto = "none";

    public $mission_x = null;
    public $mission_y = null;

    public $lvl = 1;

    protected static $_all = [];

    /**
     * Очистка кэша для тестов
     */
    public static function clearCache()
    {
        self::$_all = [];
    }

    /**
     * @param $id
     * @return Unit
     * @throws Exception
     */
    public static function get($id)
    {
        if (isset(Unit::$_all[$id])) {
            return Unit::$_all[$id];
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

    public static function get_all()
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
            ]
            as $field
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
    }

    public function remove()
    {
        MyDB::query("DELETE FROM unit WHERE id = :id", ["id" => $this->id]);
        unset(Unit::$_all[$this->id]);
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
            ]
            as $field
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
        if ($this->type === false || $this->type === null) {
            throw new Exception("Invalid unit type provided: " . $data["type"]);
        }
        if (isset($data["mission"])) {
            $this->mission = MissionType::get($data["mission"]);
        }

        if (isset($data["id"])) {
            Unit::$_all[$this->id] = $this;
        }
    }

    public function get_title()
    {
        return $this->type->get_title();
    }

    /**
     * Какие миссии этот юнит может выполнить в данной точке
     * @param $x int
     * @param $y int
     * @return array
     */
    public function get_mission_types($x = null, $y = null)
    {
        if (is_null($x)) {
            $x = $this->x;
        }
        if (is_null($y)) {
            $y = $this->y;
        }
        $result = [];
        foreach ($this->type->missions as $mission_id) {
            $mtype = MissionType::get($mission_id);
            if ($mtype->check_cell($x, $y, $this->planet)) {
                $result[$mtype->id] = $mtype;
            }
        }
        return $result;
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
        if ($this->mission) {
            $this->mission = false;
        }
        if ($this->points == 0) {
            $this->save();
            return false;
        }
        $cell = Cell::get($this->x, $this->y, $this->planet);
        if (!$mtype->check_cell($this->x, $this->y)) {
            return false;
        }
        if (!in_array($cell->type->id, $mtype->cell_types)) {
            return false;
        }
        $need_points = $cell->get_mission_need_points($mtype);
        if ($this->points >= $need_points) {
            //Можем сделать сразу
            if (!$mtype->complete($this, $title)) {
                return false;
            }
            if ($mtype->unit_lost) {
                $this->remove();
                return "unit_lost";
            }
            $this->points -= $need_points;
            return true;
        } else {
            $this->mission = $mtype;
            $this->mission_points = (int) $this->points;
            $this->points = 0;
            $this->save();
            return true;
        }
    }

    /**
     * Может ли юнит переместится на данную клетку
     * @param Cell $cell
     * @return bool
     */
    public function can_move($cell)
    {
        if ($cell->planet != $this->planet) {
            return false;
        }
        if ($cell->city && $cell->city->user->id == $this->user->id) {
            return true;
        }
        if ($this->points == 0) {
            return false;
        }
        if ($this->mission) {
            return false;
        }
        if (!isset($this->type->can_move[$cell->type->id])) {
            return false;
        }
        if (abs($this->x - $cell->x) > 1 && abs($this->x - $cell->x) != 99) {
            return false;
        }
        if (abs($this->y - $cell->y) > 1 && abs($this->y - $cell->y) != 99) {
            return false;
        }
        return true;
    }

    /**
     * Осуществляет перемещение с текущей клетки на заданную
     * @param Cell $cell
     * @return bool
     */
    public function move_to($cell)
    {
        if (!$this->can_move($cell)) {
            return false;
        }
        $cell_from = Cell::get($this->x, $this->y, $this->planet);
        if (
            ($cell->road || $cell->city) &&
            ($cell_from->road || $cell_from->city)
        ) {
            $this->points -= GameConfig::$ROAD_MOVE_POINTS;
        } else {
            if ($cell->city && $cell->city->user->id == $this->user->id) {
                $this->points -= $this->type->can_move["city"];
            } else {
                $this->points -= $this->type->can_move[$cell->type->id];
            }
        }
        $this->x = $cell->x;
        $this->y = $cell->y;
        if ($this->points < 0) {
            $this->points = 0;
        }
        $cell->units = $this;
        $this->save();
        return true;
    }

    /**
     * Осуществляет перемещение по заданному пути
     * @param $path
     */
    public function move_path($path)
    {
        MyDB::query("DELETE FROM mission_order WHERE unit_id = :uid", [
            "uid" => $this->id,
        ]);
        $number = 1;
        foreach ($path as $cell) {
            $this->add_order("move", $cell["x"], $cell["y"], $number);
            $number++;
        }
    }

    public function add_order(
        $mission,
        $target_x = "NULL",
        $target_y = "NULL",
        $number = false,
    ) {
        if (!$number) {
            $number = MyDB::query(
                "SELECT max(number) FROM mission_order WHERE unit_id = :uid",
                ["uid" => $this->id],
                "el",
            );
            if (!$number) {
                $number = 0;
            }
            $number++;
        }
        MyDB::insert("mission_order", [
            "unit_id" => $this->id,
            "number" => $number,
            "type" => $mission,
            "target_x" => $target_x,
            "target_y" => $target_y,
        ]);
        return $number;
    }

    public function calculate()
    {
        if ($this->points == 0) {
            return;
        }
        if ($this->mission) {
            $cell = Cell::get($this->x, $this->y, $this->planet);
            $need_points = $cell->get_mission_need_points($this->mission);
            if ($need_points <= $this->points) {
                //Можем закончить
                $units = MyDB::query(
                    "SELECT id FROM unit WHERE x = :x AND y = :y AND planet = :planet AND mission = :mission AND id != :uid",
                    [
                        "x" => $this->x,
                        "y" => $this->y,
                        "planet" => $this->planet,
                        "mission" => $this->mission->id,
                        "uid" => $this->id,
                    ],
                );
                $this->points -= $need_points;
                $this->mission->complete($this);
                $this->mission = false;
                $this->mission_points = 0;
                foreach ($units as $row) {
                    $unit = Unit::get($row["id"]);
                    $unit->mission = false;
                    $unit->mission_points = 0;
                    $unit->save();
                }
            } else {
                $this->mission_points += $this->points;
                $this->points = 0;
            }
        } else {
            $order = MyDB::query(
                "SELECT * FROM mission_order WHERE unit_id = :uid
                        ORDER BY `number` ASC LIMIT 1",
                ["uid" => $this->id],
                "row",
            );
            while ($order && $this->points > 0) {
                if ($order["type"] == "move") {
                    $cell = Cell::get($order["target_x"], $order["target_y"], $this->planet);
                    if (!$cell) {
                        //Несуществующая клетка в задаче, отменяем
                        MyDB::query(
                            "DELETE FROM mission_order WHERE `unit_id` = :uid",
                            ["uid" => $this->id],
                        );
                        break;
                    }
                    if ($this->can_move($cell)) {
                        $this->move_to($cell);
                        MyDB::query(
                            "DELETE FROM mission_order WHERE `unit_id` = :uid AND `number` = :number",
                            ["uid" => $this->id, "number" => $order["number"]],
                        );
                    } else {
                        //Если не можем туда идти отменяем все дальнейшие задачи
                        MyDB::query(
                            "DELETE FROM mission_order WHERE `unit_id` = :uid",
                            ["uid" => $this->id],
                        );
                        break;
                    }
                } else {
                    if (
                        $this->start_mission(MissionType::get($order["type"]))
                    ) {
                        //Смогли запустить миссию
                        MyDB::query(
                            "DELETE FROM mission_order WHERE `unit_id` = :uid AND `number` = :number",
                            ["uid" => $this->id, "number" => $order["number"]],
                        );
                    } else {
                        //Если не можем то отменяем все дальнейшие задачи
                        MyDB::query(
                            "DELETE FROM mission_order WHERE `unit_id` = :uid",
                            ["uid" => $this->id],
                        );
                    }
                }
                $order = MyDB::query(
                    "SELECT * FROM mission_order WHERE unit_id = :uid
                        ORDER BY `number` ASC LIMIT 1",
                    ["uid" => $this->id],
                    "row",
                );
            }
            //Автоматизация действий рабочих. Ещё не протестировано
            if (!$order && $this->auto == "work") {
                $ux = $this->x;
                $uy = $this->y;
                $number = 0;
                $auto_ok = false;
                if (
                    MyDB::query(
                        "SELECT count(unit_id) FROM mission_order WHERE unit_id = :uid",
                        ["uid" => $this->id],
                        "el",
                    ) == 0
                ) {
                    //Выполняемых задач нет, нужно выдать следующую
                    $cities = $this->user->get_cities();
                    $paths = [];
                    foreach ($cities as $city) {
                        //Проверяем расстояния до своих городов
                        $path = $this->calculate_path(
                            Cell::get($this->x, $this->y, $this->planet),
                            Cell::get($city->x, $city->y, $city->planet),
                        );
                        if ($path) {
                            $paths[$city->id] = $path;
                        }
                    }
                    usort($paths, function ($a, $b) {
                        if ($a["dist"] > $b["dist"]) {
                            return 1;
                        }
                        if ($a["dist"] < $b["dist"]) {
                            return -1;
                        }
                        return 0;
                    });
                    while (count($paths) > 0 && !$auto_ok) {
                        //Пробуем все города от ближайшего
                        $path = array_shift($paths);
                        $paths_to = [];
                        foreach ($cities as $city_to) {
                            if ($city == $city_to) {
                                continue;
                            }
                            $path_to = $this->calculate_path(
                                Cell::get($city->x, $city->y, $city->planet),
                                Cell::get($city_to->x, $city_to->y, $city_to->planet),
                            );
                            if ($path_to) {
                                $paths_to[$city_to->id] = $path_to;
                            }
                            usort($paths_to, function ($a, $b) {
                                if ($a["dist"] > $b["dist"]) {
                                    return 1;
                                }
                                if ($a["dist"] < $b["dist"]) {
                                    return -1;
                                }
                                return 0;
                            });
                            while (count($paths_to) > 0) {
                                // Перебираем дороги ко всем городам от него
                                $path_to = array_shift($paths_to);
                                foreach ($path_to["path"] as $cell) {
                                    if (
                                        !$cell->city &&
                                        !$cell->road &&
                                        ($cell->owner == $this->user ||
                                            !$cell->owner)
                                    ) {
                                        $need_road = true; //Строим тут дорогу
                                        $move_path = $this->calculate_path(
                                            Cell::get($ux, $uy, $this->planet),
                                            $cell,
                                        );
                                        array_shift($move_path);
                                        foreach ($move_path["path"] as $move) {
                                            $number++;
                                            $this->add_order(
                                                "move",
                                                $move->x,
                                                $move->y,
                                            );
                                        }
                                        $number++;
                                        $this->add_order(
                                            "build_road",
                                            "NULL",
                                            "NULL",
                                            $number,
                                        );
                                        $ux = $cell->x;
                                        $uy = $cell->y;
                                    }
                                }
                                if ($need_road) {
                                    $auto_ok = true;
                                    break;
                                }
                            }
                        }
                    }
                    if (!$auto_ok) {
                        //Не нашли где строить дороги между городами
                        $max_path = GameConfig::$WORK_DIST1 + 1;
                        $path = false;
                        $peoples = MyDB::query(
                            "SELECT `p`.`x` as `x`, `p`.`y` as `y` FROM `people_cells` as `p`
                                        INNER JOIN `cell` ON cell.x = p.x AND cell.y = p.y AND cell.planet = p.planet
                                        WHERE cell.road = 'none' AND cell.owner = :userid",
                            ["userid" => $this->user->id],
                        );
                        foreach ($peoples as $cell) {
                            $npath = $this->calculate_path(
                                Cell::get($this->x, $this->y, $this->planet),
                                Cell::get($cell["x"], $cell["y"], $this->planet),
                            );
                            if ($npath && $npath["dist"] < $max_path) {
                                $max_path = $npath["dist"];
                                $path = $npath;
                            }
                        }
                        if ($path) {
                            $auto_ok = true;
                            foreach ($path["path"] as $cell) {
                                $number++;
                                $this->add_order(
                                    "move",
                                    $cell->x,
                                    $cell->y,
                                    $number,
                                );
                            }
                            $number++;
                            $this->add_order(
                                "build_road",
                                "NULL",
                                "NULL",
                                $number,
                            );
                        }
                    }
                    if (!$auto_ok) {
                        //Не нашли где строить дороги около городов
                        $max_path = GameConfig::$WORK_DIST1 + 1;
                        $path = false;
                        $mine = MissionType::get("mine");
                        $irrigation = MissionType::get("irrigation");
                        $cell_types = array_merge(
                            $mine->cell_types,
                            $irrigation->cell_types,
                        );
                        $cell_types = array_unique($cell_types);
                        //TODO: выбор типа улучшения с учётом технологий и приоритетов
                        $peoples = MyDB::query(
                            "SELECT `p`.`x` as `x`, `p`.`y` as `y` FROM `people_cells` as `p`
                                        INNER JOIN `cell` ON cell.x = p.x AND cell.y = p.y AND cell.planet = p.planet
                                        WHERE cell.improvement = 'none' AND cell.owner = :userid
                                        AND `cell`.`type` IN ('" .
                                join($cell_types, "', '") .
                                "')",
                            ["userid" => $this->user->id],
                        );
                        foreach ($peoples as $cell) {
                            $npath = $this->calculate_path(
                                Cell::get($this->x, $this->y, $this->planet),
                                Cell::get($cell["x"], $cell["y"], $this->planet),
                            );
                            if ($npath && $npath["dist"] < $max_path) {
                                $max_path = $npath["dist"];
                                $path = $npath;
                            }
                        }
                        if ($path) {
                            $auto_ok = true;
                            foreach ($path["path"] as $cell) {
                                $number++;
                                $this->add_order(
                                    "move",
                                    $cell->x,
                                    $cell->y,
                                    $number,
                                );
                            }
                            $number++;
                            $cell = array_pop($path["path"]);
                            if (
                                in_array(
                                    $irrigation->cell_types,
                                    $cell->type->id,
                                )
                            ) {
                                $this->add_order(
                                    "irrigation",
                                    "NULL",
                                    "NULL",
                                    $number,
                                );
                            }
                            if (in_array($mine->cell_types, $cell->type->id)) {
                                $this->add_order(
                                    "mine",
                                    "NULL",
                                    "NULL",
                                    $number,
                                );
                            }
                        }
                    }
                }
            }
        }
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
        if ($cell1->planet != $cell2->planet) {
            return false;
        }
        $cells_next = [["cell" => $cell1, "prev" => false, "dist" => 0]];
        $cells_path = [];
        $dist = $max_path + 1;
        while (count($cells_next) > 0) {
            $next = array_shift($cells_next);
            if (
                isset($cells_path[$next["cell"]->x . "_" . $next["cell"]->y]) &&
                $cells_path[$next["cell"]->x . "_" . $next["cell"]->y][
                    "dist"
                ] <= $next["dist"]
            ) {
                continue; //Сюда уже нашли путь не хуже
            }
            if ($next["dist"] >= $dist) {
                continue;
            } //Слишком длинный путь
            if ($next["cell"] == $cell2) {
                $dist = $next["dist"]; //Нашли возможный путь
            }
            $cells_path[$next["cell"]->x . "_" . $next["cell"]->y] = $next;
            $cells_around = Cell::get_cells_around(
                $next["cell"]->x,
                $next["cell"]->y,
                3,
                3,
                $next["cell"]->planet,
            );
            foreach ($cells_around as $row) {
                foreach ($row as $map_near) {
                    if ($map_near == $next["cell"]) {
                        continue;
                    }
                    if (
                        !isset($this->type->can_move[$map_near->type->id]) &&
                        !$map_near->city
                    ) {
                        continue;
                    } //Сюда пути нет
                    if (
                        ($next["cell"]->road || $next["cell"]->city) &&
                        ($map_near->road || $map_near->city)
                    ) {
                        $dist_near = GameConfig::$ROAD_MOVE_POINTS; //Есть дорога
                    } else {
                        if ($map_near->city) {
                            $dist_near = $this->type->can_move["city"];
                        } else {
                            $dist_near =
                                $this->type->can_move[$map_near->type->id];
                        }
                    }
                    $cells_next[] = [
                        "cell" => $map_near,
                        "prev" => $next["cell"],
                        "dist" => $next["dist"] + $dist_near,
                    ];
                }
            }
        }
        if ($dist <= $max_path) {
            //Нашли путь
            $x = $cell2->x;
            $y = $cell2->y;
            $path = [$cell2];
            $i = 0;
            while (!($x == $cell1->x && $y == $cell1->y)) {
                $i++;
                if ($i > $max_path) {
                    return false;
                }
                if ($cells_path[$x . "_" . $y]["prev"]) {
                    $path[] = $cells_path[$x . "_" . $y]["prev"];
                    $x = $path[count($path) - 1]->x;
                    $y = $path[count($path) - 1]->y;
                } else {
                    break;
                }
            }
            $path = array_reverse($path);
            return ["dist" => $dist, "path" => $path];
        }
        return false;
    }
}
?>
