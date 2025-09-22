<?php
class City {
	//int
	public $id, $x, $y;
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
	public $production_type = 'unit';
	public $production_complete = 0;
	public $pwork = 1; //Производство за ход в городе
	public $peat = 2;  //Добыча еды за ход в городе
	public $pmoney = 1; //Добыча денег за ход в городе
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
	
	private static $_all = [];
    /**
     * Владелец города
     * @var User
     */
	public $user;
    /**
     * Ресурсы, доступные в городе
     * @var array
     */
	public $resources = [];

    public $resource_group;

    /**
     * @param $id int
     * @return City
     * @throws Exception
     */
	public static function get($id) {
		if (isset(City::$_all[$id])) {
			return City::$_all[$id];
		} else {
			$data = MyDB::query("SELECT * FROM city WHERE id = ?id", ['id' => $id], 'row');
			return new City($data);
		}
	}

    /**
     * @description Возвращает город по координатам, если  такой есть
     * @param $x int
     * @param $y int
     * @param null $planet int
     * @return bool|City
     * @throws Exception
     */
	public static function by_coords($x, $y, $planet = null) {
		if (is_null($planet)) $planet = Cell::$map_planet;
		$data = MyDB::query("SELECT * FROM city WHERE x = '?x' AND y = '?y' AND planet = '?planet'", 
			['x' => $x, 'y' => $y, 'planet' => $planet], 'row');
		if ($data) {
			return new City($data);
		} else {
			return false;
		}
	}

    /**
     * @param $user User
     * @param $x int
     * @param $y int
     * @param $title string
     * @return City
     * @throws Exception
     */
	public static function new_city($user, $x, $y, $title) {
		$city = new City(['user_id' => $user->id,
						  'x' => $x,
						  'y' => $y,
						  'title' => $title,
						  'planet' => Cell::$map_planet,
						  'population' => 1]);
		//Проверяем есть ли вода в соседних клетках
		$city->is_coastal = false;
		for ($ix = -1; $ix < 2; $ix++) {
            for ($iy = -1; $iy < 2; $iy++) {
                $cell = Cell::d_coord($city->x, $city->y, $ix, $iy);
                if ($cell && $cell->type->id == 'water1') {
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

	public function __construct($data) {
		foreach ($data as $field => $val) {
			if ($field == 'user_id') continue;
			$this->$field = $val;
		}
		$this->user = User::get($data['user_id']);
		if (isset($this->id)) {
			City::$_all[$this->id] = $this;
			$this->people_cells = [];
			$people_cells = MyDB::query("SELECT * FROM city_people WHERE city_id = ?id", ['id' => $this->id]);
			foreach ($people_cells as $pcell) {
				$this->people_cells[] = Cell::get($pcell['x'], $pcell['y']);
			}
			$buildings = MyDB::query("SELECT * FROM building WHERE city_id = ?id ORDER BY `type`", ['id' => $this->id]);
			$this->buildings = [];
			foreach ($buildings as $building) {
                $this->buildings[$building['type']] = new Building($building);
            }
            $this->resources = [];
			if ($data['resource_group']) {
                $resources = MyDB::query("SELECT resource.* FROM resource_group 
                    INNER JOIN resource ON resource.id = resource_group.resource_id
                    WHERE group_id = ?gid AND user_id = ?uid",
                    ['gid' => $data['resource_group'], 'uid' => $this->user->id]);
                foreach ($resources as $row) {
                    $resource = new Resource($row);
                    if (isset($this->resources[$resource->type->id])) {
                        $this->resources[$resource->type->id]['count']++;
                    } else {
                        $this->resources[$resource->type->id] = ['type' => $resource->type, 'count' => 1];
                    }
                }
            }
		}
	}

    /**
     * Возвращает название города
     * @return string
     */
	public function get_title() {
		return $this->title;
	}
	
	//Возвращает клетки, на которых могут жить жители города
	public function get_city_cells() {
		$cells = [];	
		$cells[] = Cell::d_coord($this->x, $this->y, 0, -1);
		$cells[] = Cell::d_coord($this->x, $this->y, 0, 1);
		$cells[] = Cell::d_coord($this->x, $this->y, -1, 0);
		$cells[] = Cell::d_coord($this->x, $this->y, 1, 0);
		$cells[] = Cell::d_coord($this->x, $this->y, -1, -1);
		$cells[] = Cell::d_coord($this->x, $this->y, 1, -1);
		$cells[] = Cell::d_coord($this->x, $this->y, -1, 1);
		$cells[] = Cell::d_coord($this->x, $this->y, 1, 1);
		if ($this->culture_level > 0) {
			$cells[] = Cell::d_coord($this->x, $this->y, -1, -2);
			$cells[] = Cell::d_coord($this->x, $this->y, 0, -2);
			$cells[] = Cell::d_coord($this->x, $this->y, 1, -2);
			
			$cells[] = Cell::d_coord($this->x, $this->y, -1, 2);
			$cells[] = Cell::d_coord($this->x, $this->y, 0, 2);
			$cells[] = Cell::d_coord($this->x, $this->y, 1, 2);
			
			$cells[] = Cell::d_coord($this->x, $this->y, -2, -1);
			$cells[] = Cell::d_coord($this->x, $this->y, -2, 0);
			$cells[] = Cell::d_coord($this->x, $this->y, -2, 1);
			
			$cells[] = Cell::d_coord($this->x, $this->y, 2, -1);
			$cells[] = Cell::d_coord($this->x, $this->y, 2, 0);
			$cells[] = Cell::d_coord($this->x, $this->y, 2, 1);
		}
		$result = [];
		//Проверить не заняты ли кем то ещё
        $cid = $this->id;
        if (!$cid) { //Только что построенный город
            $cid = 0;
        }
		foreach ($cells as $cell) {
			if ((MyDB:: query("SELECT city_id FROM city_people WHERE x = ?x AND y = ?y AND planet = ?planet AND city_id <> ?cid",
				['x' => $cell->x, 'y' => $cell->y, 'cid' => $cid, 'planet' => $this->planet], 'num_rows') == 0) &&
                (MyDB::query("SELECT id FROM city WHERE x = ?x AND y = ?y AND planet = ?planet",
                    ['x' => $cell->x, 'y' => $cell->y, 'planet' => $this->planet], 'num_rows') == 0)
            ) {
					$result[] = $cell;
				}
		}
		return $result;
	}
	
	//Размещает имеющихся в городе жителей по клеткам(автоматически)
	public function locate_people() {
		$this->people_cells = [];
		$cells = $this->get_city_cells();
		$people_count = $this->population;
		while ($people_count > 0 && count($cells) > 0) {
			$best = $cells[0];
			$best_key = 0;
			foreach ($cells as $key => $cell) {
				if ($cell->get_eat($this) > $best->get_eat($this)) {
					$best = $cell;
					$best_key = $key;
				} elseif ($cell->get_eat($this) == $best->get_eat($this) && $cell->get_work($this) > $best->get_work($this)) {
					$best = $cell;
					$best_key = $key;
				} elseif ($cell->get_eat($this) == $best->get_eat($this) && $cell->get_work($this) == $best->get_work($this) && $cell->get_money($this) > $best->get_money($this)) {
					$best = $cell;
					$best_key = $key;
				}
			}
			$this->people_cells[] = $best;
			array_splice($cells, $best_key, 1);
			$people_count--;
		}
	}

	//Размещает жителей на указанных клетках
	public function set_people($people_cells) {
        $this->people_cells = [];
        $city_cells = $this->get_city_cells();
        $people_count = $this->population;
        foreach ($people_cells as $cellp) {
            if ($people_count == 0) break;
            foreach ($city_cells as $cellc) {
                if ($cellp['x'] == $cellc->x && $cellp['y'] == $cellc->y) {
                    $this->people_cells[] = $cellc;
                    $people_count--;
                }
            }
        }
    }
	
	public function calculate_people() {
		$this->pwork = 1;
		$this->peat = 2;
		$money = 1;
		foreach ($this->people_cells as $cell) {
			$this->pwork += $cell->get_work();
			$this->peat += $cell->get_eat();
            $money += $cell->get_money();
		}
		$this->presearch = round($money * $this->user->research_percent / 10);
		$this->pmoney = $money - $this->presearch;
        $this->people_dis = 0;
        $this->people_happy = 0;
        $this->people_norm = count($this->people_cells);
        if ($this->people_norm >= POPULATION_PEOPLE_DIS) {
            $this->people_norm = POPULATION_PEOPLE_DIS - 1;
            $this->people_dis = $this->population - $this->people_norm;
        }
        $add_happy = $this->people_artist;
        $this->people_norm -= $add_happy;
        if ($this->people_norm < 0) {
            $add_happy += $this->people_norm;
            $this->people_norm = 0;
        }
        $this->people_happy += $add_happy;
	}

	public function get_possible_units() {
	    $units = UnitType::$all;
        $result = [];
        $have_research = $this->user->get_research();
	    foreach ($units as $unit) {
            if ($unit->type == 'water' && $this->is_coastal == false) continue;
            $can_build = true;
            foreach ($unit->req_research as $research) {
                if (!isset($have_research[$research->id])) {
                    $can_build = false;
                }
            }
            foreach ($unit->req_resources as $res) {
                if (!isset($this->resources[$res->id])) {
                    $can_build = false;
                }
            }
            if (!$can_build) continue;
            $result[$unit->id] = $unit;
        }
		return $result;
	}

    public function get_possible_buildings()
    {
        $buildings = BuildingType::$all;
        $result = [];
        $have_research = $this->user->get_research();
        foreach ($buildings as $building) {
            if (isset($this->buildings[$building->id])) continue;
            $can_build = true;
            foreach ($building->req_research as $research) {
                if (!isset($have_research[$research->id])) {
                    $can_build = false;
                }
            }
            foreach ($building->req_resources as $res) {
                if (!isset($this->resources[$res->id])) {
                    $can_build = false;
                }
            }
            if (!$can_build) continue;
            $result[$building->id] = $building;
        }
        return $result;
    }

    //Расчёт производства в этой постройке
	public function calculate_production() {
		if (!$this->production) {
			return false;
		}
		switch ($this->production_type) {
            case 'unit':
                $production = UnitType::get($this->production);
            break;
            case 'buil':
                $production = BuildingType::get($this->production);
            break;
            default:
                throw new Exception("Missing production type {$this->production_type}");
        }
        if ($this->production_complete < $production->cost) {
            $this->production_complete += $this->pwork;
        }
		if ($this->production_complete >= $production->cost) {
		    if ($this->production_type == 'unit' && $this->population <= $production->population_cost) {
                $this->production_complete = $production->cost;
		        return true;
            }
			//Закончили производство
            switch ($this->production_type) {
                case 'unit':
                    $this->population -= $production->population_cost;
                    $this->create_unit($production);
                    if ($production->population_cost > 0) {
                        $this->locate_people();
                        $this->calculate_people();
                    }
                    $event = new Event(['type' => 'city_unit',
                        'user_id' => $this->user->id,
                        'object' => $production->id,
                        'source' => $this->id]);
                    break;
                case 'buil':
                    $this->create_building($production);
                    $production->city_effect($this);
                    $event = new Event(['type' => 'city_building',
                        'user_id' => $this->user->id,
                        'object' => $production->id,
                        'source' => $this->id]);
                break;
                default:
                    $this->production = false;
            }
            $event->save();
			$this->production_complete = 0;
            $this->select_next_production();
		}
	}

    /**
     * Выбирает что следующим будет строится при завершении постройки
     */
	public function select_next_production() {
        if ($this->production_type == 'buil') {
            $this->production_type = 'unit';
            $units = $this->get_possible_units();
            $unit = array_shift($units);
            $this->production = $unit->id;
        }
    }
	
	//Пересчёт нового хода для города
	public function calculate() {
        $this->check_mood();
		$this->eat += ($this->peat - $this->population * 2);
		if ($this->eat >= $this->eat_up) {
			$this->population++;
			$this->eat -= $this->eat_up;
			$this->locate_people();
		}
		//$this->locate_people();
		$this->calculate_people();
		$this->calculate_buildings();
		$this->calculate_production();
        foreach ($this->buildings as $building) {
            $this->culture += $building->type->culture;
        }
        if (isset(GameConfig::$CULTURE_LEVELS[$this->culture_level + 1]) && $this->culture >= GameConfig::$CULTURE_LEVELS[$this->culture_level + 1]) {
            //Набралось культуры на следующий уровень
            $this->culture_level++;
            $this->culture -= GameConfig::$CULTURE_LEVELS[$this->culture_level];
        }
		$this->save();
	}

    /**
     * Постройка нового юнита в этом городе
     *
     * @param $type UnitType
     * @return Unit
     */
	public function create_unit($type) {
		$unit = new Unit(['x' => $this->x,
						  'y' => $this->y,
						  'planet' => $this->planet,
						  'health' => 3,
						  'points' => $type->points,
						  'user_id' => $this->user->id,
						  'type' => $type->id]);
		$unit->save();
		return $unit;
	}

    /**
     * Постройка нового здания в этом городе
     *
     * @param $type BuildingType
     * @return Building
     */
	public function create_building($type) {
        $building = new Building(['type' => $type->id,
                                  'city_id' => $this->id]);
        $building->save();
        $this->buildings[] = $building;
        return $building;
    }

    /**
     * Записывает данные в БД
     *
     * @throws Exception
     */
	public function save() {
		$values = [];
		foreach (['x', 'y', 'planet', 'title', 'eat', 'eat_up', 'population', 'is_coastal',
                 'culture', 'culture_level',
				  'pwork', 'peat', 'pmoney', 'presearch',
				  'production', 'production_type', 'production_complete',
                  'people_dis', 'people_norm', 'people_happy', 'people_artist'] as $field) {
			$values[$field] = $this->$field;
		}
		$values['is_coastal'] = $values['is_coastal'] ? 1 : 0;
		if (!isset($values['production']) || $values['production'] == false) {
			$values['production'] = 'NULL';
		}
		$values['user_id'] = $this->user->id;
		if ($this->id) {
			MyDB::update('city', $values, $this->id);
		} else {
			$this->id = MyDB::insert('city', $values);
			City::$_all[$this->id] = $this;
		}
		MyDB::query("DELETE FROM city_people WHERE city_id = ?id", ['id' => $this->id]);
		foreach ($this->people_cells as $cell) {
			MyDB::insert('city_people', ['x' => $cell->x, 'y' => $cell->y, 'planet' => $cell->planet, 'city_id' => $this->id]);
		}
	}

    /**
     * Применяет эффекты построек
     */
    public function calculate_buildings()
    {
        foreach ($this->buildings as $building) {
            $building->type->city_effect($this);
            if ($building->type->upkeep > 0 && $this->pwork > 0) {
                $this->pmoney -= $building->type->upkeep;
            }
        }
    }

    /**
     * Проверяет настроение жителей в городе
     */
    public function check_mood()
    {
        if ($this->people_dis > $this->people_happy) {
            $this->pwork = 0;
            $this->pmoney = 0;
        }
    }

    /**
     * Культурное влияние города
     */
    public function get_culture_cells() {
        $cells = [];
        $cellsu = [ $this->x.'x'.$this->y ];
        $cells[0] = [];
        $culture_up = GameConfig::$CULTURE_LEVELS[$this->culture_level + 1];
        for ($dx = -1; $dx < 2; $dx++) {
            for ($dy = -1; $dy < 2; $dy++) {
                $x = $this->x;
                $y = $this->y;
                if ($dx == 0 && $dy == 0) continue;
                Cell::calc_coord($x, $y, $dx, $dy);
                $cells[0][] = ['x' => $x, 'y' => $y,
                    'culture' => $this->culture_level * 10 + ceil($this->culture * 10 / $culture_up)];
                $cellsu[] = $x.'x'.$y;
            }
        }
        for ($level = 1; $level <= $this->culture_level; $level++) {
            $cells[$level] = [];
            foreach ($cells[$level-1] as $cell) {
                foreach ([ [-1, 0], [1, 0], [0, -1], [0, 1] ]as $diff) {
                    $x = $cell['x']; $y = $cell['y'];
                    Cell::calc_coord($x, $y, $diff[0], $diff[1]);
                    if (!in_array($x.'x'.$y, $cellsu)) {
                        $cells[$level][] = ['x' => $x, 'y' => $y,
                            'culture' => ($this->culture_level - $level) * 10 + ceil($this->culture * 10 / $culture_up)];
                        $cellsu[] = $x.'x'.$y;
                    }
                }
            }
        }
        $result = [];
        foreach ($cells as $items) {
            foreach ($items as $item) {
                array_push($result, $item);
            }
        }
        return $result;
    }
}