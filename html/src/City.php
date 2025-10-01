<?php

namespace App;

/**
 * Класс, представляющий город в игре Civilization.
 * Город управляет населением, производством, ресурсами и зданиями.
 */
class City
{
    /**
     * Идентификатор города
     * @var int|null
     */
    public $id;

    /**
     * Координата X города на карте
     * @var int
     */
    public $x;

    /**
     * Координата Y города на карте
     * @var int
     */
    public $y;

    /**
     * Идентификатор планеты, на которой находится город
     * @var int
     */
    public $planet = 0;
    /**
     * Население города
     * @var int
     */
    public $population = 1;
    /**
     * Число недовольных жителей
     * @var int
     */
    public $people_dis = 0;
    /**
     * Число довольных жителей
     * @var int
     */
    public $people_norm = 1;
    /**
     * Число счастливых жителей
     * @var int
     */
    public $people_happy = 0;
    /**
     * Число специалистов - артистов
     * @var int
     */
    public $people_artist = 0;
    /**
     * Сколько еды накоплено в  городе для роста
     * @var int
     */
    public $eat = 0;
    /**
     * Сколько еды нужно для роста города
     * @var int
     */
    public $eat_up = BASE_EAT_UP;
    /**
     * Количество накопленных очков культуры
     * @var int
     */
    public $culture = 0;
    /**
     * Уровень культуры города
     * @var int
     */
    public $culture_level = 0;
    /**
     * Название города
     * @var string
     */
    public $title;
    /**
     * Что производится в городе
     * @var int
     */
    public $production = false;

    /**
     * Тип текущего производства (unit или buil)
     * @var string
     */
    public $production_type = "unit";

    /**
     * Прогресс текущего производства
     * @var int
     */
    public $production_complete = 0;

    /**
     * Производство за ход в городе
     * @var int
     */
    public $pwork = 1;

    /**
     * Добыча еды за ход в городе
     * @var int
     */
    public $peat = 2;

    /**
     * Добыча денег за ход в городе
     * @var int
     */
    public $pmoney = 1;

    /**
     * Добыча очков исследований за ход
     * @var int
     */
    public $presearch = 0;
    /**
     * В каких клетках размещены жители
     * @var array \
     */
    public $people_cells = [];
    /**
     * Постройки в этом городе
     * @var array Building
     */
    public $buildings = [];
    /**
     * Является ли городе прибрежным
     * @var bool
     */
    public $is_coastal = false;

    /**
     * Кэш всех загруженных городов
     * @var array
     */
    protected static $_all = [];

    /**
     * Идентификатор владельца города
     * @var int
     */
    public $user_id;

    /**
     * Владелец города
     * @var User
     */
    public $user;

    /**
     * Очистка кэша для тестов
     */
    public static function clearCache()
    {
        self::$_all = [];
    }

    /**
     * Ресурсы, доступные в городе
     * @var array
     */
    public $resources = [];

    /**
     * Группа ресурсов города
     * @var int|null
     */
    public $resource_group;

    /**
     * Получить город по идентификатору
     * @param int $id Идентификатор города
     * @return City|null Город или null, если не найден
     * @throws Exception
     */
    public static function get($id)
    {
        if (isset(City::$_all[$id])) {
            return City::$_all[$id];
        } else {
            $data = MyDB::query(
                "SELECT * FROM city WHERE id =:id",
                ["id" => $id],
                "row",
            );
            if (!$data || !isset($data["id"])) {
                return null;
            }
            return new City($data);
        }
    }

    /**
     * Возвращает город по координатам, если такой есть
     * @param int $x Координата X
     * @param int $y Координата Y
     * @param int $planet Идентификатор планеты
     * @return City|false Город или false, если не найден
     * @throws Exception
     */
    public static function by_coords($x, $y, $planet)
    {
        $data = MyDB::query(
            "SELECT * FROM city WHERE x = :x AND y = :y AND planet = :planet",
            ["x" => $x, "y" => $y, "planet" => $planet],
            "row",
        );
        if ($data) {
            return new City($data);
        } else {
            return false;
        }
    }

    /**
     * Создает новый город
     * @param User $user Владелец города
     * @param int $x Координата X
     * @param int $y Координата Y
     * @param string $title Название города
     * @param int $planetId Идентификатор планеты
     * @return City Новый город
     * @throws Exception
     */
    public static function new_city($user, $x, $y, $title, $planetId)
    {
        $city = new City([
            "user_id" => $user->id,
            "x" => $x,
            "y" => $y,
            "title" => $title,
            "planet" => $planetId,
            "population" => 1,
        ]);
        //Проверяем есть ли вода в соседних клетках
        $city->is_coastal = false;
        for ($ix = -1; $ix < 2; $ix++) {
            for ($iy = -1; $iy < 2; $iy++) {
                $cell = Cell::d_coord(
                    $city->x,
                    $city->y,
                    $ix,
                    $iy,
                    true,
                    $planetId,
                );
                if ($cell && $cell->type->id == "water1") {
                    $city->is_coastal = true;
                }
            }
        }
        $city->locate_people();
        $city->calculate_people();
        $city->save();
        $city->user->caclulate_culture();
        return $city;
    }

    /**
     * Конструктор города
     * @param array $data Данные города
     * @throws Exception
     */
    public function __construct($data)
    {
        if (!$data || !is_array($data)) {
            throw new Exception(
                "Invalid city data provided to City constructor",
            );
        }

        foreach ($data as $field => $val) {
            if ($field == "user_id") {
                continue;
            }
            $this->$field = $val;
        }

        if (!isset($data["user_id"])) {
            throw new Exception("user_id is required for City constructor");
        }

        $this->user_id = $data["user_id"];
        $this->user = User::get($data["user_id"]);
        if ($this->user === null) {
            throw new Exception(
                "Invalid user_id provided: " . $data["user_id"],
            );
        }

        if ($this->id !== null) {
            City::$_all[$this->id] = $this;
            $this->people_cells = [];
            $people_cells = MyDB::query(
                "SELECT * FROM city_people WHERE city_id =:id",
                ["id" => $this->id],
            );
            foreach ($people_cells as $pcell) {
                $this->people_cells[] = Cell::get($pcell["x"], $pcell["y"]);
            }
            $buildings = MyDB::query(
                "SELECT * FROM building WHERE city_id =:id ORDER BY `type`",
                ["id" => $this->id],
            );
            $this->buildings = [];
            foreach ($buildings as $building) {
                $this->buildings[$building["type"]] = new Building($building);
            }
            $this->resources = [];
            if ($data["resource_group"]) {
                $resources = MyDB::query(
                    "SELECT resource.* FROM resource_group
                    INNER JOIN resource ON resource.id = resource_group.resource_id
                    WHERE group_id =:gid AND user_id =:uid",
                    [
                        "gid" => $data["resource_group"],
                        "uid" => $this->user->id,
                    ],
                );
                foreach ($resources as $row) {
                    $resource = new Resource($row);
                    if (isset($this->resources[$resource->type->id])) {
                        $this->resources[$resource->type->id]["count"]++;
                    } else {
                        $this->resources[$resource->type->id] = [
                            "type" => $resource->type,
                            "count" => 1,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Возвращает название города
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Возвращает клетки, на которых могут жить жители города
     * @return array Массив клеток
     */
    public function get_city_cells()
    {
        $cells = [];
        $cells[] = Cell::d_coord(
            $this->x,
            $this->y,
            0,
            -1,
            true,
            $this->planet,
        );
        $cells[] = Cell::d_coord($this->x, $this->y, 0, 1, true, $this->planet);
        $cells[] = Cell::d_coord(
            $this->x,
            $this->y,
            -1,
            0,
            true,
            $this->planet,
        );
        $cells[] = Cell::d_coord($this->x, $this->y, 1, 0, true, $this->planet);
        $cells[] = Cell::d_coord(
            $this->x,
            $this->y,
            -1,
            -1,
            true,
            $this->planet,
        );
        $cells[] = Cell::d_coord(
            $this->x,
            $this->y,
            1,
            -1,
            true,
            $this->planet,
        );
        $cells[] = Cell::d_coord(
            $this->x,
            $this->y,
            -1,
            1,
            true,
            $this->planet,
        );
        $cells[] = Cell::d_coord($this->x, $this->y, 1, 1, true, $this->planet);
        if ($this->culture_level > 0) {
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                -1,
                -2,
                true,
                $this->planet,
            );
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                0,
                -2,
                true,
                $this->planet,
            );
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                1,
                -2,
                true,
                $this->planet,
            );

            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                -1,
                2,
                true,
                $this->planet,
            );
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                0,
                2,
                true,
                $this->planet,
            );
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                1,
                2,
                true,
                $this->planet,
            );

            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                -2,
                -1,
                true,
                $this->planet,
            );
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                -2,
                0,
                true,
                $this->planet,
            );
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                -2,
                1,
                true,
                $this->planet,
            );

            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                2,
                -1,
                true,
                $this->planet,
            );
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                2,
                0,
                true,
                $this->planet,
            );
            $cells[] = Cell::d_coord(
                $this->x,
                $this->y,
                2,
                1,
                true,
                $this->planet,
            );
        }
        $result = [];
        //Проверить не заняты ли кем то ещё
        $cid = $this->id;
        if (!$cid) {
            //Только что построенный город
            $cid = 0;
        }
        foreach ($cells as $cell) {
            if (!$cell) {
                continue;
            }
            if (
                MyDB::query(
                    "SELECT city_id FROM city_people WHERE x =:x AND y =:y AND planet =:planet AND city_id <>:cid",
                    [
                        "x" => $cell->x,
                        "y" => $cell->y,
                        "cid" => $cid,
                        "planet" => $this->planet,
                    ],
                    "num_rows",
                ) == 0 &&
                MyDB::query(
                    "SELECT id FROM city WHERE x =:x AND y =:y AND planet =:planet",
                    [
                        "x" => $cell->x,
                        "y" => $cell->y,
                        "planet" => $this->planet,
                    ],
                    "num_rows",
                ) == 0
            ) {
                $result[] = $cell;
            }
        }
        return $result;
    }

    /**
     * Размещает имеющихся в городе жителей по клеткам автоматически
     */
    public function locate_people()
    {
        CityPopulationManager::locatePeople($this);
    }

    /**
     * Размещает жителей на указанных клетках
     * @param array $people_cells Массив клеток с жителями
     */
    public function set_people($people_cells)
    {
        CityPopulationManager::setPeople($this, $people_cells);
    }

    /**
     * Рассчитывает параметры жителей и производства
     */
    public function calculate_people()
    {
        CityPopulationManager::calculatePeople($this);
    }

    /**
     * Возвращает возможные для постройки юниты
     * @return array Массив типов юнитов
     */
    public function get_possible_units()
    {
        return CityProductionManager::getPossibleUnits($this);
    }

    /**
     * Возвращает возможные для постройки здания
     * @return array Массив типов зданий
     */
    public function get_possible_buildings()
    {
        return CityProductionManager::getPossibleBuildings($this);
    }

    /**
     * Расчет производства в городе
     * @return bool|void
     */
    public function calculate_production()
    {
        return CityProductionManager::calculateProduction($this);
    }

    /**
     * Выбирает что следующим будет строится при завершении постройки
     */
    public function select_next_production()
    {
        CityProductionManager::selectNextProduction($this);
    }

    /**
     * Пересчет нового хода для города
     */
    public function calculate()
    {
        CityPopulationManager::checkMood($this);
        $this->eat += $this->peat - $this->population * 2;
        if ($this->eat >= $this->eat_up) {
            $this->population++;
            $this->eat -= $this->eat_up;
            CityPopulationManager::locatePeople($this);
        }
        CityPopulationManager::calculatePeople($this);
        CityBuildingManager::calculateBuildings($this);
        CityProductionManager::calculateProduction($this);
        CityCultureManager::calculateCulture($this);
        $this->save();
    }

    /**
     * Постройка нового юнита в этом городе
     * @param UnitType $type Тип юнита
     * @return Unit Созданный юнит
     */
    public function create_unit($type)
    {
        return CityProductionManager::createUnit($this, $type);
    }

    /**
     * Постройка нового здания в этом городе
     * @param BuildingType $type Тип здания
     * @return Building Созданное здание
     */
    public function create_building($type)
    {
        return CityProductionManager::createBuilding($this, $type);
    }

    /**
     * Записывает данные в БД
     *
     * @throws Exception
     */
    public function save()
    {
        $values = [];
        foreach (
            [
                "x",
                "y",
                "planet",
                "title",
                "eat",
                "eat_up",
                "population",
                "is_coastal",
                "culture",
                "culture_level",
                "pwork",
                "peat",
                "pmoney",
                "presearch",
                "production",
                "production_type",
                "production_complete",
                "people_dis",
                "people_norm",
                "people_happy",
                "people_artist",
            ] as $field
        ) {
            $values[$field] = $this->$field;
        }
        $values["is_coastal"] = $values["is_coastal"] ? 1 : 0;
        if (!isset($values["production"]) || $values["production"] == false) {
            $values["production"] = null;
        }
        $values["user_id"] = $this->user->id;
        if ($this->id !== null) {
            MyDB::update("city", $values, $this->id);
        } else {
            $this->id = MyDB::insert("city", $values);
            City::$_all[$this->id] = $this;
        }
        // Проверяем, что у города есть ID перед работой с city_people
        if ($this->id === null) {
            error_log(
                "City::save() - Attempting to save city_people but city ID is null",
            );
            error_log(
                "City data: " .
                    json_encode([
                        "title" => $this->title,
                        "x" => $this->x,
                        "y" => $this->y,
                        "user_id" => $this->user ? $this->user->id : "null",
                    ]),
            );
            // Не можем сохранить city_people без ID города
            throw new \Exception("City::save() - Attempting to save city_people but city ID is null");
        }

        MyDB::query("DELETE FROM city_people WHERE city_id =:id", [
            "id" => $this->id,
        ]);
        foreach ($this->people_cells as $cell) {
            MyDB::insert("city_people", [
                "x" => $cell->x,
                "y" => $cell->y,
                "planet" => $cell->planet,
                "city_id" => $this->id,
            ]);
        }
    }

    /**
     * Добавляет жителя в город
     */
    public function add_people()
    {
        CityPopulationManager::addPeople($this);
    }

    /**
     * Удаляет жителя из города
     */
    public function remove_people()
    {
        CityPopulationManager::removePeople($this);
    }

    /**
     * Применяет эффекты построек
     */
    public function calculate_buildings()
    {
        CityBuildingManager::calculateBuildings($this);
    }

    /**
     * Проверяет настроение жителей в городе
     */
    public function check_mood()
    {
        CityPopulationManager::checkMood($this);
    }

    /**
     * Культурное влияние города
     */
    public function get_culture_cells()
    {
        return CityCultureManager::getCultureCells($this);
    }
}
