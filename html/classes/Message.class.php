<?php
class Message
{
    /**
     * @var int
     */
    public $id = null;
    /**
     * @var bool|User
     */
    public $from;
    /**
     * @var bool|User
     */
    public $to;
    /**
     * @var string
     */
    public $text;
    /**
     * Тип сообщения (chat, system)
     * @var string
     */
    public $type;

    public function __construct($data)
    {
        foreach (["id", "text", "type"] as $field) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }
        $this->text = $data["text"];
        $this->from = isset($data["from_id"])
            ? User::get($data["from_id"])
            : false;
        $this->to = isset($data["to_id"]) ? User::get($data["to_id"]) : false;
    }

    public function save()
    {
        $values = [
            "text" => $this->text,
            "from_id" => $this->from ? $this->from->id : null,
            "to_id" => $this->to ? $this->to->id : null,
            "type" => $this->type,
        ];
        if ($this->id !== null) {
            MyDB::update("message", $values, $this->id);
        } else {
            $this->id = MyDB::insert("message", $values);
        }
    }
}
