<?php
class Event {
    /**
     * @var int
     */
	public $id;
    /**
     * @var User
     */
	public $user;
    /**
     * @var string(research, city_building, city_unit)
     */
	public $type;
    /**
     * Источник события
     * @var null|City|User
     */
	public $soruce = null;
    /**
     * Предмет события
     * @var ResearchType|BuildingType|UnitType
     */
	public $object;

    /**
     * @param $id
     * @return Event
     * @throws Exception
     */
	public static function get($id) {
		$data = MyDB::query("SELECT * FROM event WHERE id = ?id", ['id' => $id], 'row');
        return new Event($data);
	}
	
	
	public function __construct($data) {
		if (isset($data['id'])) {
            $this->id = $data['id'];
        }
		$this->type = $data['type'];
		$this->user = User::get($data['user_id']);
		if ($data['type'] == 'research') {
		    $this->soruce = null;
		    $this->object = ResearchType::get($data['object']);
        } elseif ($data['type'] == 'city_building') {
		    $this->soruce = City::get($data['source']);
		    $this->object = BuildingType::get($data['object']);
        } elseif ($data['type'] == 'city_unit') {
            $this->soruce = City::get($data['source']);
            $this->object = UnitType::get($data['object']);
        }
	}
	
	public function save() {
		$values =  ['type' => $this->type,
					'object' => $this->object->id,
                    'user_id' => $this->user->id];
		if ($this->soruce) {
		    $values['source'] = $this->soruce->id;
        } else {
            $values['source'] = 'NULL';
        }
		if ($this->id) {
			MyDB::update('event', $values, $this->id);
		} else {
			$this->id = MyDB::insert('event', $values);
		}
	}

	public function remove() {
	    MyDB::query("DELETE FROM event WHERE id = ?id", ['id' => $this->id]);
    }
}