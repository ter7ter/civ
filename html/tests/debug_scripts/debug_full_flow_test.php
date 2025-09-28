<?php

require_once __DIR__ . "/bootstrap.php";

echo "Starting full creategame.php flow debug test...\n";

// Override MyDB class to intercept all calls
class MyDBInterceptor
{
    private static $original_methods = [];

    public static function insert($table, $values)
    {
        echo "DEBUG: MyDB::insert called with table: '$table'\n";
        echo "DEBUG: Values: " . print_r($values, true) . "\n";

        if ($table === 'user') {
            echo "*** USER INSERT DETECTED ***\n";
            if (isset($values['planet'])) {
                echo "*** ERROR: PLANET FIELD FOUND IN USER INSERT ***\n";
                echo "Stack trace:\n";
                foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $i => $frame) {
                    echo "  $i: " . ($frame['file'] ?? 'unknown') . ":" . ($frame['line'] ?? '?') .
                         " in " . ($frame['function'] ?? 'unknown') . "()\n";
                }
                echo "\n";
                // Remove planet field to prevent error
                unset($values['planet']);
                echo "Planet field removed from values\n";
            }
        }

        // Call original MyDB::insert
        $pdo = MyDB::get();
        $keys = array_keys($values);
        $placeholders = implode(", ", array_map(fn ($k) => ":$k", $keys));
        $query = "INSERT INTO `$table` (" .
                 implode(",", array_map(fn ($k) => "`$k`", $keys)) .
                 ") VALUES (" . $placeholders . ")";

        echo "DEBUG: SQL Query: $query\n";

        $params = [];
        foreach ($values as $k => $v) {
            $params[":$k"] = $v === "NULL" ? null : $v;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    }

    public static function query($query, $params = [], $type = 'all')
    {
        echo "DEBUG: MyDB::query called: " . substr($query, 0, 100) . "...\n";
        return MyDB::query($query, $params, $type);
    }

    public static function update($table, $values, $where)
    {
        echo "DEBUG: MyDB::update called with table: '$table'\n";
        return MyDB::update($table, $values, $where);
    }

    public static function get()
    {
        return MyDB::get();
    }
}

// Test with override
echo "\nTesting with MyDBInterceptor...\n";
try {
    DatabaseTestAdapter::resetTestDatabase();

    // Set up request data
    $_REQUEST = [
        "name" => "Debug Full Flow Game",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "users" => ["player1", "player2"]
    ];

    echo "Request setup complete\n";

    // Create custom version of creategame.php logic with our interceptor
    $name = trim(htmlspecialchars($_REQUEST["name"]));
    $map_w = (int) $_REQUEST["map_w"];
    $map_h = (int) $_REQUEST["map_h"];
    $turn_type = $_REQUEST["turn_type"];

    $errors = [];

    // Process users
    $users = [];
    $user_logins = [];
    $num = 0;

    foreach ($_REQUEST["users"] as $user_login) {
        $user_login = trim(htmlspecialchars($user_login));
        if (empty($user_login)) {
            continue;
        }

        $num++;
        $user_logins[] = $user_login;

        $color = "#";
        $sym = "ff";
        $color_num = $num;

        if ($num > 8) {
            $sym = "88";
            $color_num = $num - 8;
        }

        if (($color_num & 4) > 0) {
            $color .= $sym;
        } else {
            $color .= "00";
        }
        if (($color_num & 2) > 0) {
            $color .= $sym;
        } else {
            $color .= "00";
        }
        if (($color_num & 1) > 0) {
            $color .= $sym;
        } else {
            $color .= "00";
        }

        $users[] = [
            "login" => $user_login,
            "color" => $color,
            "order" => $num,
        ];
    }

    echo "Users processed: " . count($users) . "\n";

    // Create game using interceptor
    $game_data = [
        "name" => $name,
        "map_w" => $map_w,
        "map_h" => $map_h,
        "turn_type" => $turn_type,
        "turn_num" => 1,
    ];

    echo "Creating Game object...\n";
    $game = new Game($game_data);

    echo "Saving game...\n";
    $game_id = MyDBInterceptor::insert("game", $game_data);
    $game->id = $game_id;

    echo "Game saved with ID: $game_id\n";

    // Create users with interceptor
    echo "Creating users...\n";
    foreach ($users as $user_data) {
        $user_create_data = [
            "login" => $user_data["login"],
            "color" => $user_data["color"],
            "game" => $game->id,
            "turn_order" => $user_data["order"],
            "turn_status" => "wait",
            "money" => 50,
            "age" => 1,
        ];

        echo "Creating user: " . $user_data["login"] . "\n";

        // Create User object
        $u = new User($user_create_data);
        echo "User object created\n";

        // Check for planet property
        $all_props = get_object_vars($u);
        if (isset($all_props['planet'])) {
            echo "WARNING: User object has planet property: " . $all_props['planet'] . "\n";
        }

        // Save using interceptor (simulating User::save())
        $values = [];
        foreach (['login', 'pass', 'money', 'income', 'color', 'age', 'game', 'turn_status', 'turn_order',
                'research_amount', 'research_percent',
                'process_research_complete', 'process_research_turns'] as $field) {
            if (isset($u->$field)) {
                $values[$field] = $u->$field;
            }
        }

        if (isset($u->process_research_type) && $u->process_research_type) {
            $values['process_research_type'] = $u->process_research_type->id;
        } else {
            $values['process_research_type'] = 0;
        }

        // Check if somehow planet got into values
        if (isset($values['planet'])) {
            echo "ERROR: Planet field found in values array!\n";
        }

        $user_id = MyDBInterceptor::insert("user", $values);
        $u->id = $user_id;

        echo "User saved with ID: $user_id\n";
    }

    echo "Now calling Game::create_new_game()...\n";

    // This is where the real issue might be
    $game->create_new_game();

    echo "create_new_game() completed successfully\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nDebug test completed.\n";
