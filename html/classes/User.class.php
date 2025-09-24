<?php
/**
 * Класс, представляющий пользователя в игре Civilization.
 * Управляет ресурсами, исследованиями, городами и юнитами игрока.
 */
class User
{
    /**
     * Идентификатор пользователя
     * @var int|null
     */
    public $id = null;

    /**
     * Логин пользователя
     * @var string
     */
    public $login;
    /**
     * Цвет игрока
     * @var string
     */
    public $color;

    /**
     * Пароль пользователя
     * @var string
     */
    public $pass = "";
    /**
     * Текущее кол-во денег
     * @var int
     */
    public $money = 0;
    /**
     * Прибыль денег за ход(последняя расчитанная)
     * @var int
     */
    public $income = 0;
    /**
     * Затраты на исследования за ход
     * @var int
     */
    public $research_amount = 0;
    /**
     * Выставленный % расходов на исследования, в десятках %
     * @var int
     */
    public $research_percent = 0;
    /**
     * Текущее проводимое исследование
     * @var ResearchType|null
     */
    public $process_research_type = null;
    /**
     * На сколько едениц уже произведено текущее исследование
     * @var int
     */
    public $process_research_complete = 0;
    /**
     * Сколько ходов уже идёт текущее исследование
     * @var int
     */
    public $process_research_turns = 0;

    /**
     * Текущая эра
     * @var int
     */
    public $age = 1;

    /**
     * Видимая карта пользователя
     * @var array
     */
    public $see_map = [];

    /**
     * Игра, в которой участвует пользователь
     * @var Game
     */
    public $game;

    /**
     * Текущий статус хода (play, wait, end)
     * @var string
     */
    public $turn_status;

    /**
     * Порядок хода в игре, если ходы по очереди
     * @var int
     */
    public $turn_order;

    /**
     * Уровень пользователя
     * @var int
     */
    public $lvl = 0;

    /**
     * Кэш всех загруженных пользователей
     * @var array
     */
    protected static $_all = [];

    /**
     * Очистка кэша для тестов
     */
    public static function clearCache()
    {
        self::$_all = [];
    }

    /**
     * Получить пользователя по идентификатору
     * @param int $id Идентификатор пользователя
     * @return User|null Пользователь или null, если не найден
     */
    public static function get($id)
    {
        if (isset(User::$_all[$id])) {
            return User::$_all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM user WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if (!$data || !isset($data["id"])) {
                return null;
            }
            return new User($data);
        }
    }

    /**
     * Конструктор пользователя
     * @param array $data Данные пользователя
     * @throws Exception
     */
    public function __construct($data)
    {
        if (!$data || !is_array($data)) {
            throw new Exception(
                "Invalid user data provided to User constructor",
            );
        }

        foreach (
            [
                "login",
                "money",
                "income",
                "color",
                "age",
                "game",
                "turn_status",
                "turn_order",
                "research_amount",
                "research_percent",
                "process_research_complete",
                "process_research_turns",
            ]
            as $field
        ) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }
        if (isset($data["id"])) {
            $this->id = $data["id"];
            User::$_all[$this->id] = $this;
        }
        if (
            isset($data["process_research_type"]) &&
            $data["process_research_type"]
        ) {
            $this->process_research_type = ResearchType::get(
                $data["process_research_type"],
            );
        }
    }

    /**
     * Сохранить пользователя в базу данных
     */
    public function save()
    {
        $values = [];
        foreach (
            [
                "login",
                "pass",
                "money",
                "income",
                "color",
                "age",
                "game",
                "turn_status",
                "turn_order",
                "research_amount",
                "research_percent",
                "process_research_complete",
                "process_research_turns",
            ]
            as $field
        ) {
            $values[$field] = $this->$field;
        }
        if ($this->process_research_type) {
            $values["process_research_type"] = $this->process_research_type->id;
        } else {
            $values["process_research_type"] = 0;
        }
        if ($this->id !== null) {
            MyDB::update("user", $values, $this->id);
        } else {
            $this->id = MyDB::insert("user", $values);
            User::$_all[$this->id] = $this;
        }
    }

    /**
     * Получить видимую карту пользователя
     * @return array Массив клеток
     */
    public function get_map()
    {
        if (count($this->buildings) == 0) {
            $this->load_buildings();
        }
        $coordsk = [];
        foreach ($this->buildings as $building) {
            for ($i = -2; $i < 3; $i++) {
                for ($j = -2; $j < 3; $j++) {
                    $coordsk[$building->x + $i][$building->y + $j] = 1;
                }
            }
        }
        $coords = [];
        foreach ($coordsk as $x => $coordsx) {
            foreach ($coordsx as $y => $v) {
                $coords[] = ["x" => $x, "y" => $y];
            }
        }
        $game = Game::get($this->game);
        $planetId = $game->get_first_planet()->id;
        $cells = Cell::load_cells($coords, $planetId);
        foreach ($cells as $cell) {
            $this->see_map[$cell->x][$cell->y] = $cell;
        }
        return $cells;
    }

    /**
     * Рассчитать города пользователя
     */
    public function calculate_cities()
    {
        $cities = $this->get_cities();
        foreach ($cities as $city) {
            $city->calculate();
        }
        $this->caclulate_culture();
    }

    /**
     * Рассчитать культурное влияние городов пользователя
     */
    public function caclulate_culture()
    {
        $cities = $this->get_cities();
        $culture_cells = [];
        foreach ($cities as $city) {
            $cells = $city->get_culture_cells();
            foreach ($cells as $cell) {
                if (!isset($culture_cells[$cell["x"]][$cell["y"]])) {
                    $culture_cells[$cell["x"]][$cell["y"]] = 0;
                }
                $culture_cells[$cell["x"]][$cell["y"]] += $cell["culture"];
            }
        }
        //Пересчёт границ и культурного влияния
        foreach ($culture_cells as $x => $cells) {
            foreach ($cells as $y => $culture) {
                $game = Game::get($this->game);
                $planetId = $game->get_first_planet()->id;
                $cell = Cell::get($x, $y, $planetId);
                if (!$cell) {
                    continue;
                }
                if (!$cell->owner) {
                    $cell->owner = $this;
                    $cell->owner_culture = (int) $culture;
                    $cell->save();
                } elseif ($cell->owner->id == $this->id) {
                    $cell->owner_culture = (int) $culture;
                    $cell->save();
                } elseif (
                    $cell->owner->id != $this->id &&
                    $cell->owner_culture + 10 < $culture
                ) {
                    if (!$cell->city) {
                        $cell->owner = $this;
                        $cell->owner_culture = (int) $culture;
                        $cell->save();
                    }
                }
            }
        }
    }

    /**
     * Рассчитать юниты пользователя
     */
    public function calculate_units()
    {
        $rows = MyDB::query("SELECT * FROM unit WHERE user_id = :id", [
            "id" => $this->id,
        ]);
        foreach ($rows as $row) {
            $unit = new Unit($row);
            $unit->calculate();
            $unit->points = $unit->type->points;
            $unit->save();
        }
    }

    /**
     * Рассчитать доход пользователя
     * @return int Доход
     */
    public function calculate_income()
    {
        $this->income = 0;
        $this->research_amount = 0;
        $cities = $this->get_cities();
        foreach ($cities as $city) {
            $this->income += $city->pmoney;
            $this->research_amount += $city->presearch;
        }
        $this->money += $this->income;
        return $this->income;
    }

    /**
     * Рассчитать исследования пользователя
     * @return Research|false Завершенное исследование или false
     */
    public function calculate_research()
    {
        if (!$this->process_research_type || !$this->research_amount) {
            return false;
        }
        $this->process_research_complete += $this->research_amount;
        $this->process_research_turns++;
        if (
            $this->process_research_complete >=
            $this->process_research_type->cost
        ) {
            //Исследование завершено
            $research = new Research([
                "type" => $this->process_research_type->id,
                "user_id" => $this->id,
            ]);
            $research->save();
            $event = new Event([
                "type" => "research",
                "user_id" => $this->id,
                "object" => $this->process_research_type->id,
            ]);
            $event->save();
            $this->process_research_type = null;
            $this->process_research_complete = 0;
            $this->process_research_turns = 0;
            $r_need_age = ResearchType::get_need_age_ids($this->age);
            if (empty($r_need_age)) {
                $r_count = 0;
            } else {
                $r_count = MyDB::query(
                    "SELECT count(id) FROM research WHERE user_id = :uid AND
                `type` IN (" .
                        join(",", $r_need_age) .
                        ")",
                    ["uid" => $this->id],
                    "elem",
                );
            }
            if ($r_count == count($r_need_age)) {
                //Переходим в следующую эру
                $this->age++;
                $this->save();
            }
            return $research;
        }
        return false;
    }

    /**
     * @return City[]
     * @throws Exception
     */
    public function get_cities()
    {
        $result = [];
        $rows = MyDB::query("SELECT id FROM city WHERE user_id = :id", [
            "id" => $this->id,
        ]);
        foreach ($rows as $row) {
            $result[] = City::get($row["id"]);
        }
        return $result;
    }

    /**
     * Возвращает все уже проведённые исследования юзера
     * @return Research[]
     * @throws Exception
     */
    public function get_research()
    {
        $rows = MyDB::query("SELECT * FROM research WHERE user_id = :id", [
            "id" => $this->id,
        ]);
        $result = [];
        foreach ($rows as $row) {
            $result[$row["type"]] = new Research($row);
        }
        return $result;
    }

    /**
     * Список исследований, которые пользователь сейчас может начать
     * @return ResearchType[]
     */
    public function get_available_research()
    {
        $result = [];
        $research = $this->get_research();
        foreach (ResearchType::$all as $res) {
            if (isset($research[$res->id])) {
                continue;
            }
            if ($res->age > $this->age) {
                continue;
            }
            $ok = true;
            foreach ($res->requirements as $req) {
                if (!isset($research[$req->id])) {
                    $ok = false;
                }
            }
            if ($ok) {
                $result[$res->id] = $res;
            }
        }
        return $result;
    }

    /**
     * Начало нового исследования
     * @param ResearchType $type Тип исследования
     * @return bool Успешно ли начато исследование
     */
    public function start_research($type)
    {
        $available = $this->get_available_research();
        if (!isset($available[$type->id])) {
            return false;
        }
        $this->process_research_type = $type;
        return true;
    }

    /**
     * Возвращает сколько осталось ходов исследовать
     * @param ResearchType|bool $research Тип исследования или false для текущего
     * @return int|bool Количество ходов или false
     */
    public function get_research_need_turns($research = false)
    {
        if (!$this->process_research_type && !$research) {
            return false;
        }
        if ($this->research_amount == 0) {
            return 0;
        }
        if ($research) {
            $cost = $research->cost;
        } else {
            $cost = $this->process_research_type->cost;
        }
        $turns = Ceil(
            ($cost - $this->process_research_complete) / $this->research_amount,
        );
        if ($turns + $this->process_research_turns < 4) {
            $turns = 4 - $this->process_research_turns;
        }
        if ($turns + $this->process_research_turns > 50) {
            $turns = 50 - $this->process_research_turns;
        }
        return $turns;
    }

    /**
     * Рассчитывает группы доступности ресурсов по городам
     * @throws Exception
     */
    public function calculate_resource()
    {
        $cities = $this->get_cities();
        $groups = [];
        $group_id = 0;
        $cities_in_group = [];
        $cells_in_group = [];
        while (count($cities) > 0) {
            $city = array_shift($cities);
            if (in_array($city->id, $cities_in_group)) {
                continue;
            }
            $group_id++;
            $groups[$group_id] = [
                "cities" => [$city->id => $city],
                "resources" => [],
            ];
            $cities_in_group[] = $city->id;
            $game = Game::get($this->game);
            $planetId = $game->get_first_planet()->id;
            $next_cells = [Cell::get($city->x, $city->y, $planetId)];
            while (count($next_cells) > 0) {
                $cell = array_shift($next_cells);
                if (in_array($cell->x . "_" . $cell->y, $cells_in_group)) {
                    continue;
                }
                $cells_in_group[] = $cell->x . "_" . $cell->y;
                if (
                    $cell->resource &&
                    $cell->resource->type &&
                    $cell->resource->type->type != "bonuce"
                ) {
                    $groups[$group_id]["resources"][] = $cell->resource;
                }
                $cells_around = Cell::get_cells_around(
                    $cell->x,
                    $cell->y,
                    3,
                    3,
                    $planetId,
                );
                foreach ($cells_around as $row) {
                    foreach ($row as $acell) {
                        if ($acell == $cell) {
                            continue;
                        }
                        if ($acell->owner && $acell->owner != $this) {
                            continue;
                        } //Через чужую территорию ничего не идёт
                        if ($acell->city && $acell->owner == $this) {
                            //Наш город, добавим его к группе
                            $groups[$group_id]["cities"][$acell->city->id] =
                                $acell->city;
                            $cities_in_group[] = $acell->city->id;
                            $next_cells[] = $acell;
                            continue;
                        }
                        if ($acell->road) {
                            //Дорога по своей или нейтральной территории
                            $next_cells[] = $acell;
                            continue;
                        }
                        if (
                            $cell->type->id == "water1" &&
                            $acell->type->id == "water1"
                        ) {
                            //Водный путь, прибрежный
                            $next_cells[] = $acell;
                            continue;
                        }
                        if (
                            $cell->type->id == "water2" &&
                            ($acell->type->id == "water2" ||
                                $acell->type->id == "water1")
                        ) {
                            //Водный путь, морской
                            $next_cells[] = $acell;
                            continue;
                        }
                        if (
                            $cell->type->id == "water3" &&
                            ($acell->type->id == "water3" ||
                                $acell->type->id == "water2")
                        ) {
                            //Водный путь, по океану
                            $next_cells[] = $acell;
                            continue;
                        }
                        if (
                            $acell->type->id == "water1" &&
                            $cell->city &&
                            isset($cell->city->buildings[8])
                        ) {
                            //Переход с города с гаванью на воду
                            $next_cells[] = $acell;
                            continue;
                        }
                        //TODO: ещё добавить переход на море и океан, в зависимости от исследований
                    }
                }
            }
        }
        MyDB::query("DELETE FROM resource_group WHERE user_id = :uid", [
            "uid" => $this->id,
        ]);
        foreach ($groups as $group_id => $group) {
            foreach ($group["resources"] as $resource) {
                MyDB::query(
                    "INSERT INTO resource_group(`group_id`, `user_id`, `resource_id`) VALUES (:gid, :uid, :rid)",
                    [
                        "gid" => $group_id,
                        "uid" => $this->id,
                        "rid" => $resource->id,
                    ],
                );
            }
            foreach ($group["cities"] as $city) {
                $city->resources = [];
                if (count($group["resources"]) > 0) {
                    MyDB::update(
                        "city",
                        ["resource_group" => $group_id],
                        $city->id,
                    );
                    foreach ($group["resources"] as $resource) {
                        if (isset($city->resources[$resource->type->id])) {
                            $city->resources[$resource->type->id]["count"]++;
                        } else {
                            $city->resources[$resource->type->id] = [
                                "type" => $resource->type,
                                "count" => 1,
                            ];
                        }
                    }
                } else {
                    MyDB::update(
                        "city",
                        ["resource_group" => null],
                        $city->id,
                    );
                }
                $city->save();
            }
        }
    }

    /**
     * Получить сообщения пользователя
     * @param int $last Идентификатор последнего сообщения
     * @return Message[] Массив сообщений
     * @throws Exception
     */
    public function get_messages($last = 0)
    {
        $result = [];
        $messages = MyDB::query(
            "SELECT * FROM message WHERE (from_id = :uid OR to_id = :uid) AND id > :last ORDER BY DATE LIMIT 50",
            ["uid" => $this->id, "last" => $last],
        );
        foreach ($messages as $message) {
            $result[] = new Message($message);
        }
        return $result;
    }

    /**
     * Создать новое системное сообщение
     * @param string $text Текст сообщения
     * @return Message Созданное сообщение
     */
    public function new_system_message($text)
    {
        $message = new Message([
            "form_id" => false,
            "to_id" => $this->id,
            "text" => $text,
            "type" => "system",
        ]);
        $message->save();
        return $message;
    }

    /**
     * Получить следующее событие пользователя
     * @return Event|false Событие или false, если нет
     */
    public function get_next_event()
    {
        $data = MyDB::query(
            "SELECT * FROM event WHERE user_id = :uid ORDER BY id LIMIT 1",
            ["uid" => $this->id],
            "row",
        );
        if ($data) {
            $event = new Event($data);
            return $event;
        } else {
            return false;
        }
    }
}
