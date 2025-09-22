<?php
class MyDB {
    /**
     * @var mysqli
     */
	private static $_link = false;
	
	public static $dbhost = DB_HOST;
	public static $dbuser = DB_USER;
	public static $dbpass = DB_PASS;
	public static $dbport = DB_PORT;
	public static $dbname = DB_NAME;
	
	public static function connect() {
		if (MyDB::$_link) {
			MyDB::$_link->close();
		}
		@MyDB::$_link = new mysqli(MyDB::$dbhost, MyDB::$dbuser, MyDB::$dbpass, MyDB::$dbname, MyDB::$dbport);
		if (!MyDB::$_link) {
			die(mysqli_connect_error());
		}
		MyDB::$_link->query("SET charset utf8");
	}
	
	public static function get() {
		if (!MyDB::$_link) {
			MyDB::connect();
		}
		return MyDB::$_link;
	}
	
	public static function query($query, $vars = [], $output = 'assoc') {
		$db = MyDB::get();
		if (count($vars)) {
			foreach ($vars as $key => $var) {
				$var = $db->real_escape_string($var);
				$query = str_replace('?'.$key, $var, $query);
			}
		}
		//var_dump($query);
		$res = $db->query($query);
		if ($db->error) {
			throw new Exception("$query SQL Error:".$db->error);
		}
		if ($res === true)
			return true;
		if ($output == 'assoc') {
			$result = [];
		} else {
			$result = false;
		}
		switch ($output) {
			case 'num_rows':
				if ($res->num_rows) {
					$result = $res->num_rows;
				}
			break;
			case 'row':
				if ($res->num_rows) {
					$result = $res->fetch_assoc();
				}
			break;
			case 'obj':
				if ($res->num_rows) {
					$result = $res->fetch_object();
				}
			break;
			case 'elem':
				if ($res->num_rows) {
					$result = $res->fetch_row();
					$result = $result[0];
				}
			break;
			default:
				while ($next = $res->fetch_assoc()) {
					$result[] = $next;
				}
		}
		$res->close();
		return $result;
	}
	
	public static function insert($table, $values)
    {
        $db = MyDB::get();
        $query = "INSERT INTO `" . $db->real_escape_string($table) . "`";
        if (isset($values[0]) && is_array($values[0])) {
            $keys = [];
            foreach ($values[0] as $key => $val) {
                $keys[] = '`' . $db->real_escape_string($key) . '`';
            }
            $query .= '(' . implode(', ', $keys) . ') VALUES ';
            $rows = [];
            foreach ($values as $row) {
                $inserts = [];
                foreach ($values as $val) {
                    if ($val === 'NULL') {
                        $inserts[] = "NULL";
                    } else {
                        $val = $db->real_escape_string($val);
                        $inserts[] = "'$val'";
                    }
                }
                $rows[] = '(' . implode(', ', $inserts) . ')';
            }
            $query .= implode(', ', $rows);
        } else {
            $query .= " SET ";
            $inserts = [];
            foreach ($values as $key => $val) {
                $key = $db->real_escape_string($key);
                if ($val === 'NULL') {
                    $inserts[] = "`$key` = NULL";
                } else {
                    $val = $db->real_escape_string($val);
                    $inserts[] = "`$key` = '$val'";
                }
            }
            $query .= implode(", ", $inserts);
        }
        if (MyDB::query($query)) {
            return $db->insert_id;
        } else
        {
            return false;
        }
	}
	
	public static function update($table, $values, $where) {
		$db = MyDB::get();
		$table = $db->real_escape_string($table);
		$query = "UPDATE `$table` SET ";
		$updates = [];
		foreach ($values as $key => $val) {
			$key = $db->real_escape_string($key);
			if ($val === 'NULL') {
					$updates[] = "`$key` = NULL";
			} else {
				$val = $db->real_escape_string($val);
				$updates[] = "`$key` = '$val'";
			}
		}
		$query .= implode(", ", $updates);
		if ($where == (int)$where) {
			$query .= " WHERE `id` = $where";
		} else {
			$query .= " WHERE ".$where;
		}
		return MyDB::query($query);
	}
	
	public static function start_transaction() {
		MyDB::query("START TRANSACTION;");
	}
	
	public static function end_transaction() {
		MyDB::query("COMMIT;");
	}
}
?>