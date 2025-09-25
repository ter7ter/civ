<?php
class ResearchType {
	public $id;
	//Название
	public $title;
	//Стоимость исследования
	public $cost;
	
	//Требуемые исследования
	public $requirements = [];
	
	//Визуальное расположение на карте исследований
	public $m_top = 30;
	public $m_left = 0;
    /**
     * Эпоха
     * @var int
     */
	public $age = 1;
    /**
     * Требуется ли для перехода в следующий век
     * @var bool
     */
	public $age_need = true;
    /**
     * @var ResearchType[]
     */
	public static $all = [];
	
	public static function get($id) {
		if (isset(ResearchType::$all[$id])) {
			return ResearchType::$all[$id];
		} else {
			$data = MyDB::query(
				"SELECT * FROM research_type WHERE id = :id",
				["id" => $id],
				"row",
			);
			if ($data) {
				return new ResearchType($data);
			} else {
				return false;
			}
		}
	}

	public static function getAll() {
		$data = MyDB::query("SELECT * FROM research_type ORDER BY id");
		$result = [];
		foreach ($data as $row) {
			$result[] = new ResearchType($row);
		}
		return $result;
	}
	
	public function __construct($data) {
		if (isset($data['id'])) {
			$this->id = (int)$data['id'];
		}

		// Устанавливаем значения по умолчанию
		$this->title = '';
		$this->cost = 0;
		$this->requirements = [];
		$this->m_top = 30;
		$this->m_left = 0;
		$this->age = 1;
		$this->age_need = true;

		// Явно устанавливаем известные свойства
		$knownFields = [
			"title",
			"cost",
			"m_top",
			"m_left",
			"age",
			"age_need",
		];

		foreach ($knownFields as $field) {
			if (isset($data[$field])) {
				$this->$field = $data[$field];
			}
		}

		// Обрабатываем JSON поля
		if (isset($data['requirements'])) {
			if (is_string($data['requirements'])) {
				$decoded = json_decode($data['requirements'], true);
				$this->requirements = $decoded !== null ? $decoded : [];
			} else {
				$this->requirements = $data['requirements'];
			}
			$resolvedRequirements = [];
			foreach ($this->requirements as $resId) {
				$resolvedRequirements[$resId] = ResearchType::get($resId);
			}
			$this->requirements = $resolvedRequirements;
		}

		if (isset($data['id'])) {
			ResearchType::$all[$this->id] = $this;
		}
	}
	
	public function get_title() {
		return $this->title;
	}
	
	//Расчитывает сколько будет исследоваться данная наука при заданных затратах на ход
	public function get_turn_count($amount) {
		if ($amount <= 0) {
			return false;
		}
		$result = Ceil($this->cost / $amount);
		if ($result > 50) {
			$result = 50;
		}
		if ($result < 4) {
			$result = 4;
		}
		return $result;
	}

    /**
     * Возвращает список id исследований, необходимых для прохода данной эры
     * @param $age int
     * @return array
     */
	public static function get_need_age_ids($age) {
	    $result = [];
	    foreach (ResearchType::$all as $research) {
	        if ($research->age == $age && $research->age_need) {
	            $result[] = $research->id;
            }
        }
	    return $result;
    }

	public function save() {
		$data = [
			'title' => $this->title,
			'cost' => $this->cost,
			'requirements' => json_encode(array_keys($this->requirements)),
			'm_top' => $this->m_top,
			'm_left' => $this->m_left,
			'age' => $this->age,
			'age_need' => (int)$this->age_need,
		];
		if (isset($this->id)) {
			MyDB::update('research_type', $data, $this->id);
		} else {
			$this->id = (int)MyDB::insert('research_type', $data);
		}
		ResearchType::$all[$this->id] = $this;
	}

	public function delete() {
		if (isset($this->id)) {
			MyDB::query("DELETE FROM research_type WHERE id = :id", ["id" => $this->id]);
			unset(ResearchType::$all[$this->id]);
		}
	}
}

new ResearchType(['id' => 1,
				  'title' => 'Обработка бронзы',
				  'cost' => 100,
				  'requirements' => [],
				  'm_top' => 30, 'm_left' => 30]);
				  
new ResearchType(['id' => 2,
				  'title' => 'Каменная кладка',
				  'cost' => 100,
				  'requirements' => [],
				  'm_top' => 130, 'm_left' => 30]);
				  
new ResearchType(['id' => 3,
				  'title' => 'Алфавит',
				  'cost' => 120,
				  'requirements' => [],
				  'm_top' => 230, 'm_left' => 30]);
				  
new ResearchType(['id' => 4,
				  'title' => 'Верховая езда',
				  'cost' => 80,
				  'requirements' => [],
				  'm_top' => 330, 'm_left' => 30]);
				  
new ResearchType(['id' => 5,
				  'title' => 'Гончарное дело',
				  'cost' => 80,
				  'requirements' => [],
				  'm_top' => 430, 'm_left' => 30]);
				  
new ResearchType(['id' => 6,
				  'title' => 'Мистицизм',
				  'cost' => 80,
				  'requirements' => [],
				  'm_top' => 530, 'm_left' => 30]);
				  
new ResearchType(['id' => 7,
				  'title' => 'Обработка железа',
				  'cost' => 150,
				  'requirements' => [1],
				  'm_top' => 30, 'm_left' => 300]);

new ResearchType(['id' => 8,
				  'title' => 'Математика',
				  'cost' => 150,
				  'requirements' => [2, 3],
				  'm_top' => 130, 'm_left' => 300]);
				  
new ResearchType(['id' => 9,
				  'title' => 'Письменность',
				  'cost' => 180,
				  'requirements' => [3],
				  'm_top' => 230, 'm_left' => 300]);
				  
new ResearchType(['id' => 10,
				  'title' => 'Колесо',
				  'cost' => 120,
				  'requirements' => [3, 4],
				  'm_top' => 330, 'm_left' => 300]);
				  
new ResearchType(['id' => 11,
				  'title' => 'Идолопоклоничество',
				  'cost' => 150,
				  'requirements' => [5, 6],
				  'm_top' => 530, 'm_left' => 300]);
				  
new ResearchType(['id' => 12,
				  'title' => 'Строительство',
				  'cost' => 200,
				  'requirements' => [7, 8],
				  'm_top' => 30, 'm_left' => 570]);
				  
new ResearchType(['id' => 13,
				  'title' => 'Свод законов',
				  'cost' => 200,
				  'requirements' => [9],
				  'm_top' => 140, 'm_left' => 570]);
new ResearchType(['id' => 14,
				  'title' => 'Философия',
				  'cost' => 200,
				  'requirements' => [9],
				  'm_top' => 230, 'm_left' => 570]);
new ResearchType(['id' => 15,
				  'title' => 'Литература',
				  'cost' => 230,
				  'requirements' => [9],
                  'age_need' => false,
				  'm_top' => 320, 'm_left' => 570]);
new ResearchType(['id' => 16,
				  'title' => 'Создание карт',
				  'cost' => 250,
				  'requirements' => [9],
				  'm_top' => 410, 'm_left' => 570]);
new ResearchType(['id' => 17,
				  'title' => 'Политеизм',
				  'cost' => 200,
				  'requirements' => [11],
				  'm_top' => 530, 'm_left' => 570]);
new ResearchType(['id' => 18,
				  'title' => 'Конструкции',
				  'cost' => 250,
				  'requirements' => [12],
				  'm_top' => 30, 'm_left' => 840]);
new ResearchType(['id' => 19,
				  'title' => 'Деньги',
				  'cost' => 240,
				  'requirements' => [12],
				  'm_top' => 110, 'm_left' => 840]);
new ResearchType(['id' => 20,
				  'title' => 'Республика',
				  'cost' => 280,
				  'requirements' => [13, 14],
                  'age_need' => false,
				  'm_top' => 210, 'm_left' => 840]);
new ResearchType(['id' => 21,
				  'title' => 'Монархия',
				  'cost' => 280,
				  'requirements' => [13, 14],
                  'age_need' => false,
				  'm_top' => 530, 'm_left' => 840]);

new ResearchType([  'id' => 22,
                    'title' => 'Монотеизм',
                    'cost' => 350,
                    'requirements' => [],
                    'age' => 2,
                    'm_top' => 30, 'm_left' => 30]);
new ResearchType([  'id' => 23,
                    'title' => 'Феодализм',
                    'cost' => 350,
                    'requirements' => [],
                    'age' => 2,
                    'm_top' => 270, 'm_left' => 30]);
new ResearchType([  'id' => 24,
                    'age' => 2,
                    'title' => 'Инженерное дело',
                    'cost' => 350,
                    'requirements' => [],
                    'm_top' => 530, 'm_left' => 30]);
new ResearchType([  'id' => 25,
                    'title' => 'Рыцарство',
                    'cost' => 380,
                    'requirements' => [22, 23],
                    'age' => 2,
                    'age_need' => false,
                    'm_top' => 150, 'm_left' => 150]);
new ResearchType([  'id' => 26,
                    'title' => 'Изобретательство',
                    'cost' => 400,
                    'requirements' => [24],
                    'age' => 2,
                    'm_top' => 530, 'm_left' => 250]);
new ResearchType([  'id' => 27,
                    'title' => 'Образование',
                    'cost' => 400,
                    'requirements' => [22],
                    'age' => 2,
                    'm_top' => 30, 'm_left' => 260]);
new ResearchType([  'id' => 28,
                    'title' => 'Астрономия',
                    'cost' => 420,
                    'requirements' => [23, 27],
                    'age' => 2,
                    'm_top' => 270, 'm_left' => 330]);
new ResearchType([  'id' => 29,
                    'title' => 'Химия',
                    'cost' => 450,
                    'requirements' => [26],
                    'age' => 2,
                    'm_top' => 530, 'm_left' => 470]);
new ResearchType([  'id' => 30,
                    'title' => 'Печатный пресс',
                    'cost' => 430,
                    'requirements' => [27],
                    'age' => 2,
                    'm_top' => 30, 'm_left' => 490]);
new ResearchType([  'id' => 31,
                    'title' => 'Архитектура',
                    'cost' => 450,
                    'requirements' => [27],
                    'age' => 2,
                    'm_top' => 150, 'm_left' => 490]);
new ResearchType([  'id' => 32,
                    'title' => 'Демократия',
                    'cost' => 500,
                    'requirements' => [30],
                    'age' => 2,
                    'age_need' => false,
                    'm_top' => 30, 'm_left' => 710]);
new ResearchType([  'id' => 33,
                    'title' => 'Свободные искуства',
                    'cost' => 550,
                    'requirements' => [31, 32],
                    'age' => 2,
                    'age_need' => false,
                    'm_top' => 150, 'm_left' => 710]);
new ResearchType([  'id' => 34,
                    'title' => 'Банковское дело',
                    'cost' => 500,
                    'requirements' => [28],
                    'age' => 2,
                    'm_top' => 240, 'm_left' => 630]);
new ResearchType([  'id' => 35,
                    'title' => 'Экономика',
                    'cost' => 600,
                    'requirements' => [28],
                    'age' => 2,
                    'm_top' => 240, 'm_left' => 900]);
new ResearchType([  'id' => 36,
                    'title' => 'Физика',
                    'cost' => 500,
                    'requirements' => [28, 29],
                    'age' => 2,
                    'm_top' => 380, 'm_left' => 630]);
new ResearchType([  'id' => 37,
                    'title' => 'Магнетизм',
                    'cost' => 580,
                    'requirements' => [36],
                    'age' => 2,
                    'm_top' => 330, 'm_left' => 900]);
new ResearchType([  'id' => 38,
                    'title' => 'Парусники',
                    'cost' => 600,
                    'requirements' => [36],
                    'age' => 2,
                    'age_need' => false,
                    'm_top' => 430, 'm_left' => 900]);
new ResearchType([  'id' => 39,
                    'title' => 'Кавалерия',
                    'cost' => 570,
                    'requirements' => [36],
                    'age' => 2,
                    'm_top' => 530, 'm_left' => 690]);
new ResearchType([  'id' => 40,
                    'title' => 'Артилерия',
                    'cost' => 620,
                    'requirements' => [39],
                    'age' => 2,
                    'age_need' => false,
                    'm_top' => 530, 'm_left' => 900]);
