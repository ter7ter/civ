<?php

require_once __DIR__ . "/bootstrap.php";

echo "Starting debug test for planet column issue...\n";

// Test 1: Check if User table exists and its structure
try {
    $pdo = MyDB::get();
    $result = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='user'")->fetch();
    echo "User table SQL: " . ($result['sql'] ?? 'NOT FOUND') . "\n";
} catch (Exception $e) {
    echo "Error checking table structure: " . $e->getMessage() . "\n";
}

// Test 2: Try to create a simple user without going through creategame.php
echo "\nTest 2: Creating user directly...\n";
try {
    DatabaseTestAdapter::resetTestDatabase();

    // Create a game first
    $game_data = [
        "name" => "Debug Test Game",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "turn_num" => 1,
    ];
    $game = new GameTestMock($game_data);
    $game->save();
    echo "Game created with ID: " . $game->id . "\n";

    // Create user data - exactly what creategame.php creates
    $user_create_data = [
        "login" => "testuser",
        "color" => "#ff0000",
        "game" => $game->id,
        "turn_order" => 1,
        "turn_status" => "wait",
        "money" => 50,
        "age" => 1,
    ];

    echo "User data to create: " . print_r($user_create_data, true) . "\n";

    // Create user
    $user = new UserTestMock($user_create_data);
    echo "User object created\n";

    // Check all properties of user object
    $all_props = get_object_vars($user);
    echo "All user properties: " . print_r($all_props, true) . "\n";

    // Try to save
    $user->save();
    echo "User saved successfully with ID: " . $user->id . "\n";

} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test 3: Check what happens when we add planet property manually
echo "\nTest 3: Adding planet property manually...\n";
try {
    DatabaseTestAdapter::resetTestDatabase();

    $game_data = [
        "name" => "Debug Test Game 2",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "turn_num" => 1,
    ];
    $game = new GameTestMock($game_data);
    $game->save();

    $user_create_data = [
        "login" => "testuser2",
        "color" => "#00ff00",
        "game" => $game->id,
        "turn_order" => 1,
        "turn_status" => "wait",
        "money" => 50,
        "age" => 1,
    ];

    $user = new UserTestMock($user_create_data);

    // Manually add planet property
    $user->planet = 1;
    echo "Added planet property: " . $user->planet . "\n";

    // Check all properties
    $all_props = get_object_vars($user);
    echo "All user properties with planet: " . print_r($all_props, true) . "\n";

    // Try to save - this should fail
    $user->save();
    echo "User with planet saved - THIS SHOULD NOT HAPPEN\n";

} catch (Exception $e) {
    echo "Expected error with planet property: " . $e->getMessage() . "\n";
}

// Test 4: Test the actual creategame.php flow
echo "\nTest 4: Testing creategame.php flow...\n";
try {
    DatabaseTestAdapter::resetTestDatabase();

    $_REQUEST = [
        "name" => "Debug Game",
        "map_w" => 100,
        "map_h" => 100,
        "turn_type" => "byturn",
        "users" => ["player1", "player2"]
    ];

    // Simulate the creategame.php execution
    $vars = mockIncludeFile(__DIR__ . "/../pages/creategame.php");

    if (isset($vars["error"]) && $vars["error"]) {
        echo "Error from creategame.php: " . $vars["error"] . "\n";
    } else {
        echo "creategame.php executed successfully\n";
    }

} catch (Exception $e) {
    echo "Error in creategame.php flow: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDebug test completed.\n";
