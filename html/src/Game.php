<?php

namespace App;

use App\Interfaces\IModel;

/**
 * Класс, представляющий игру в Civilization.
 * Управляет пользователями, картой, ходами и общими механиками игры.
 */
class Game implements IModel
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
     * Менеджер логики игры
     * @var GameManager
     */
    private GameManager $manager;

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
        $this->manager = new GameManager($this);
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
        $this->manager->createNewGame();
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
        $this->manager->calculateTurn();
    }

    /**
     * Отправить системное сообщение всем пользователям в игре
     * @param string $text Текст сообщения
     */
    public function all_system_message($text)
    {
        $this->manager->sendSystemMessageToAll($text);
    }

    /**
     * Получить активного игрока в игре
     * @return int|null Идентификатор активного игрока или null
     */
    public function getActivePlayer()
    {
        return $this->manager->getActivePlayer();
    }

    /**
     * Получить первую планету в игре
     * @return Planet|null Первая планета или null, если не найдена
     */
    public function get_first_planet()
    {
        return $this->manager->getFirstPlanet();
    }
}
