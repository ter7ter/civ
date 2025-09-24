<?php
require_once __DIR__ . "/bootstrap.php";

echo "Starting MyDB::insert debug test...\n";

// Create a wrapper class to intercept MyDB::insert calls
class MyDBDebugWrapper {
    public static function insert($table, $values) {
        echo "DEBUG: MyDB::insert called with table: '$table'\n";
        echo "DEBUG: Values: " . print_r($values, true) . "\n";

        if ($table === 'user' && isset($values['planet'])) {
            echo "*** FOUND PLANET IN USER INSERT ***\n";
            echo "Stack trace:\n";
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $i => $frame) {
                echo "  $i: " . ($frame['file'] ?? 'unknown') . ":" . ($frame['line'] ?? '?') .
                     " in " . ($frame['function'] ?? 'unknown') . "()\n";
            }
            echo "\n";
        }

        // Call original method
        return MyDB::insert($table, $values);
    }
}

// Test with creategame.php
echo "\nTesting creategame.php with MyDB debug wrapper...\n";
try {
    DatabaseTestAdapter::resetTestDatabase();

    // Override MyDB::insert temporarily
    $originalMyDB = new ReflectionClass('MyDB');

    $_REQUEST = [
        "name" => "Debug Game",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "users" => ["player1", "player2"]
    ];

    echo "Request data: " . print_r($_REQUEST, true) . "\n";

    // Manually process creategame.php logic step by step
    $name = trim(htmlspecialchars($_REQUEST["name"]));
    $map_w = (int) $_REQUEST["map_w"];
    $map_h = (int) $_REQUEST["map_h"];
    $turn_type = $_REQUEST["turn_type"];

    echo "Processing users...\n";
    $users = [];
    $user_logins = [];
    $num = 0;

    foreach ($_REQUEST["users"] as $user_login) {
        $user_login = trim(htmlspecialchars($user_login));
        if (empty($user_login)) continue;

        $num++;
        $user_logins[] = $user_login;

        $color = "#";
        $sym = "ff";
        $color_num = $num;

        if ($num > 8) {
            $sym = "88";
            $color_num = $num - 8;
        }

        if (($color_num & 4) > 0) $color .= $sym; else $color .= "00";
        if (($color_num & 2) > 0) $color .= $sym; else $color .= "00";
        if (($color_num & 1) > 0) $color .= $sym; else $color .= "00";

        $users[] = [
            "login" => $user_login,
            "color" => $color,
            "order" => $num,
        ];
    }

    echo "Users prepared: " . print_r($users, true) . "\n";

    // Create game
    echo "Creating game...\n";
    $game_data = [
        "name" => $name,
        "map_w" => $map_w,
        "map_h" => $map_h,
        "turn_type" => $turn_type,
        "turn_num" => 1,
    ];

    echo "Game data: " . print_r($game_data, true) . "\n";
    $game = new Game($game_data);
    echo "Game object created\n";

    MyDBDebugWrapper::insert("game", $game_data);
    echo "Game saved\n";

    // Simulate game ID
    $game->id = 1;

    // Create users
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

        echo "Creating user with data: " . print_r($user_create_data, true) . "\n";

        $u = new User($user_create_data);
        echo "User object created\n";

        // Check user properties before save
        $props = get_object_vars($u);
        echo "User properties: " . print_r($props, true) . "\n";

        MyDBDebugWrapper::insert("user", $user_create_data);
        echo "User saved\n";
    }

    echo "All users created successfully\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nDebug test completed.\n";
