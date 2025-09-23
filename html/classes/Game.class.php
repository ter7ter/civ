<?php
class Game
{
    /**
     * @var Game[]
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
     * @var int
     */
    public $id = null;
    /**
     * @var string
     */
    public $name;
    /**
     * @var User[]
     */
    public $users = [];
    /**
     * Ширина карты
     * @var int
     */
    public $map_w;
    /**
     * Высота карты
     * @var int
     */
    public $map_h;

    /**
     * Порядок ходов ('concurrently','byturn', 'onewindow')
     * @var string
     */
    public $turn_type;

    /**
     * Номер текущего хода
     * @var int
     */
    public $turn_num;

    /**
     * @param $id
     * @return Game
     */
    public static function get($id)
    {
        if (isset(Game::$_all[$id])) {
            return Game::$_all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM game WHERE id = :id",
                ["id" => $id],
                "row",
            );
            if (!$data || !isset($data["id"])) {
                return null;
            }
            return new Game($data);
        }
    }

    public function __construct($data)
    {
        if (!$data || !is_array($data)) {
            throw new Exception(
                "Invalid game data provided to Game constructor",
            );
        }

        foreach (
            ["name", "map_w", "map_h", "turn_type", "turn_num"]
            as $field
        ) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }
        Cell::$map_width = $this->map_w;
        Cell::$map_height = $this->map_h;

        $this->users = [];
        if (isset($data["id"])) {
            $this->id = $data["id"];
            Game::$_all[$this->id] = $this;

            $users = MyDB::query("SELECT id FROM user WHERE game = :gameid", [
                "gameid" => $this->id,
            ]);
            foreach ($users as $user) {
                $userObj = User::get($user["id"]);
                if ($userObj !== null) {
                    $this->users[$user["id"]] = $userObj;
                }
            }
        }
    }

    public function save()
    {
        $values = [];
        foreach (
            ["name", "map_w", "map_h", "turn_type", "turn_num"]
            as $field
        ) {
            $values[$field] = $this->$field;
        }
        if ($this->id !== null) {
            MyDB::update("game", $values, $this->id);
        } else {
            $this->id = MyDB::insert("game", $values);
        }
    }

    public function create_new_game()
    {
        // Убеждаемся, что типы юнитов инициализированы перед созданием юнитов
        if (empty(UnitType::$all)) {
            if (class_exists("TestGameDataInitializer")) {
                TestGameDataInitializer::initializeUnitTypes();
            }
        }

        Cell::generate_map($this->id);
        $users = MyDB::query(
            "SELECT id FROM user WHERE game = :gameid ORDER BY turn_order",
            ["gameid" => $this->id],
        );
        foreach ($users as $user) {
            $userObj = User::get($user["id"]);
            if ($userObj !== null) {
                $this->users[$user["id"]] = $userObj;
            }
        }
        $positions = [];
        $i = 0;
        while (count($positions) < count($this->users)) {
            $i++;
            $pos_x = mt_rand(0, $this->map_w - 1);
            $pos_y = mt_rand(0, $this->map_h - 1);
            $cell = Cell::get($pos_x, $pos_y);
            if ($i > 1000) {
                throw new Exception("Too many iterations");
            }
            if (
                !in_array($cell->type->id, [
                    "plains",
                    "plains2",
                    "forest",
                    "hills",
                ])
            ) {
                //Эта клетка не подходит для заселения
                continue;
            }
            $around_ok = 0;
            $cells = Cell::get_cells_around($pos_x, $pos_y, 3, 3);
            foreach ($cells as $row) {
                foreach ($row as $item) {
                    if (
                        in_array($cell->type->id, [
                            "plains",
                            "plains2",
                            "forest",
                            "hills",
                        ])
                    ) {
                        $around_ok++;
                    }
                }
            }
            if ($around_ok < 3) {
                //Мало подходящих соседних клеток
                continue;
            }
            //Проверяем наличие соседей поблизости
            $users_around = false;
            foreach ($positions as $pos) {
                if (Cell::calc_distance($pos[0], $pos[1], $pos_x, $pos_y) < 8) {
                    $users_around = true;
                }
            }
            if ($users_around) {
                continue;
            }
            $positions[] = [$pos_x, $pos_y];
        }
        foreach ($this->users as $user) {
            $position = array_shift($positions);
            $citizen = new Unit([
                "x" => $position[0],
                "y" => $position[1],
                "planet" => Cell::$map_planet,
                "health" => 3,
                "points" => 2,
                "user_id" => $user->id,
                "type" => 1,
            ]);
            $citizen->save();
        }
    }

    public static function game_list()
    {
        $games = MyDB::query("SELECT game.*, count(user.id) as ucount FROM game
                                INNER JOIN user ON user.game = game.id
                                GROUP BY user.game ORDER BY id DESC");
        return $games;
    }

    public function calculate()
    {
        $first = true;
        foreach ($this->users as $user) {
            $user->calculate_research(); //Начало нового
            $user->calculate_resource();
            $user->calculate_cities();
            $user->calculate_income();
            if (
                $this->turn_type == "byturn" ||
                $this->turn_type == "onewindow"
            ) {
                if ($first) {
                    $user->turn_status = "play";
                    $first = false;
                } else {
                    $user->turn_status = "wait";
                }
            } else {
                $user->turn_status = "play";
            }
            $user->save();
        }
        $this->turn_num++;
        $this->save();
    }

    public function all_system_message($text)
    {
        foreach ($this->users as $user) {
            $message = new Message([
                "form_id" => false,
                "to_id" => $user->id,
                "text" => $text,
                "type" => "system",
            ]);
            $message->save();
        }
    }

    public function getActivePlayer()
    {
        return MyDB::query(
            "SELECT id FROM user WHERE game = :gid AND turn_status = 'play' LIMIT 1",
            ["gid" => $this->id],
            "elem",
        );
    }
}
