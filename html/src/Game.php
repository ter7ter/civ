<?php

namespace App;

/**
 * Класс, представляющий игру в Civilization.
 * Управляет пользователями, картой, ходами и общими механиками игры.
 */
class Game
{
    /**
     * Кэш всех загруженных игр
     * @var Game[]
     */
    protected static $_all = [];

    /**
     * Очистка кэша для тестов
     */
    public static function clearCache()
    {
        GameRepository::clearCache();
        self::$_all = [];
    }

    /**
     * Идентификатор игры
     * @var int|null
     */
    public $id = null;

    /**
     * Название игры
     * @var string
     */
    public $name;

    /**
     * Пользователи в игре
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
    public $turn_num = 1;

    /**
     * Получить игру по идентификатору
     * @param int $id Идентификатор игры
     * @return Game|null Игра или null, если не найдена
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

    /**
     * Конструктор игры
     * @param array $data Данные игры
     * @throws Exception
     */
    public function __construct($data)
    {
        if (!$data || !is_array($data)) {
            throw new \Exception(
                "Invalid game data provided to Game constructor",
            );
        }

        foreach (
            ["name", "map_w", "map_h", "turn_type", "turn_num"] as $field
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

    /**
     * Сохранить игру в базу данных
     */
    public function save()
    {

        $values = [];
        foreach (
            ["name", "map_w", "map_h", "turn_type", "turn_num"] as $field
        ) {
            $values[$field] = $this->$field;
        }
        if ($this->id !== null) {
            MyDB::update("game", $values, $this->id);
        } else {
            $this->id = MyDB::insert("game", $values);
        }
    }

    /**
     * Создать новую игру с генерацией карты и размещением игроков
     * @throws Exception
     */
    public function create_new_game()
    {
        MapGenerator::generateNewGame($this);
    }

    /**
     * Получить список всех игр
     * @return array Список игр с количеством пользователей
     */
    public static function game_list()
    {
        return GameRepository::game_list();
    }

    /**
     * Рассчитать новый ход для всех пользователей в игре
     */
    public function calculate()
    {
        TurnCalculator::calculateTurn($this);
    }

    /**
     * Отправить системное сообщение всем пользователям в игре
     * @param string $text Текст сообщения
     */
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

    /**
     * Получить активного игрока в игре
     * @return int|null Идентификатор активного игрока или null
     */
    public function getActivePlayer()
    {
        return TurnCalculator::getActivePlayer($this);
    }

    /**
     * Получить первую планету в игре
     * @return Planet|null Первая планета или null, если не найдена
     */
    public function get_first_planet()
    {
        $planet_id = MyDB::query(
            "SELECT id FROM planet WHERE game_id = :game_id ORDER BY id LIMIT 1",
            ["game_id" => $this->id],
            "elem",
        );
        if ($planet_id) {
            return Planet::get($planet_id);
        }
        return null;
    }
}
