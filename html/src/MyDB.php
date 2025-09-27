<?php

namespace App;

class MyDB
{
    /**
     * @var PDO
     */
    private static $_link = false;

    /**
     * @var string
     */
    private static $_tablePrefix = '';

    public static $dbhost;
    public static $dbuser;
    public static $dbpass;
    public static $dbport;
    public static $dbname;

    public static function setDBConfig(
        $dbhost,
        $dbuser,
        $dbpass,
        $dbport,
        $dbname,
    ) {
        MyDB::$dbhost = $dbhost;
        MyDB::$dbuser = $dbuser;
        MyDB::$dbpass = $dbpass;
        MyDB::$dbport = $dbport;
        MyDB::$dbname = $dbname;
    }

    public static function connect()
    {
        MyDB::$_link = null;

        // Используем MySQL
        $dsn = "mysql:host=" . MyDB::$dbhost . ";dbname=" . MyDB::$dbname . ";charset=utf8;port=" . MyDB::$dbport;
        MyDB::$_link = new PDO($dsn, MyDB::$dbuser, MyDB::$dbpass);

        MyDB::$_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        MyDB::$_link->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function get()
    {
        if (!MyDB::$_link) {
            MyDB::connect();
        }
        return MyDB::$_link;
    }

    public static function query($query, $vars = [], $output = "assoc")
    {
        $db = MyDB::get();

        // Проверяем, является ли это запросом, который не возвращает данные
        $queryStart = strtoupper(substr(trim($query), 0, 10));

        // Для DDL запросов (CREATE, ALTER, DROP) используем exec
        /*if (
            strpos($queryStart, "CREATE") === 0 ||
            strpos($queryStart, "ALTER") === 0 ||
            strpos($queryStart, "DROP") === 0
        ) {
            $db->exec($query);
        } else {*/
            $stmt = $db->prepare($query);
            $stmt->execute($vars);
        //}

        if ($stmt->columnCount() == 0) {
            // Запрос не возвращает столбцы
            return true;
        }

        switch ($output) {
            case "num_rows":
                $result = $stmt->rowCount();
                break;
            case "row":
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case "obj":
                $result = $stmt->fetch(PDO::FETCH_OBJ);
                break;
            case "elem":
                $row = $stmt->fetch(PDO::FETCH_NUM);
                $result = $row ? $row[0] : null;
                break;
            case "column":
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                break;
            default:
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public static function insert($table, $values)
    {
        $db = MyDB::get();
        if (isset($values[0]) && is_array($values[0])) {
            // Multiple rows
            $keys = array_keys($values[0]);
            $placeholders =
                "(" .
                implode(
                    ",",
                    array_map(fn($i) => ":p$i", range(0, count($keys) - 1)),
                ) .
                ")";
            $query =
                "INSERT INTO `$table` (" .
                implode(",", array_map(fn($k) => "`$k`", $keys)) .
                ") VALUES " .
                implode(",", array_fill(0, count($values), $placeholders));
            $params = [];
            $i = 0;
            foreach ($values as $row) {
                foreach ($keys as $key) {
                    $params[":p$i"] = $row[$key] === "NULL" ? null : $row[$key];
                    $i++;
                }
            }
        } else {
            // Single row
            $keys = array_keys($values);
            $placeholders = implode(", ", array_map(fn($k) => ":$k", $keys));
            $query =
                "INSERT INTO `$table` (" .
                implode(",", array_map(fn($k) => "`$k`", $keys)) .
                ") VALUES (" .
                $placeholders .
                ")";
            $params = [];
            foreach ($values as $k => $v) {
                $params[":$k"] = $v === "NULL" ? null : $v;
            }
        }
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $db->lastInsertId();
    }

    public static function update($table, $values, $where)
    {
        $db = MyDB::get();
        $setParts = array_map(fn($k) => "`$k` = :$k", array_keys($values));
        $query = "UPDATE `$table` SET " . implode(", ", $setParts);
        $params = [];
        foreach ($values as $k => $v) {
            $params[":$k"] = $v === "NULL" ? null : $v;
        }
        if ($where == (int) $where) {
            $query .= " WHERE `id` = :id";
            $params["id"] = $where;
        } else {
            $query .= " WHERE " . $where;
            // Assuming $where does not have placeholders, or handle separately
        }
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return true;
    }

    public static function start_transaction()
    {
        $db = MyDB::get();
        if (!$db->inTransaction()) {
            $db->beginTransaction();
        }
    }

    public static function end_transaction()
    {
        $db = MyDB::get();
        if ($db->inTransaction()) {
            $db->commit();
        }
    }

    public static function rollback_transaction()
    {
        $db = MyDB::get();
        if ($db->inTransaction()) {
            $db->rollBack();
        }
    }
}
