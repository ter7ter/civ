<?php
require_once __DIR__ . "/bootstrap.php";

echo "Starting class check debug test...\n";

// Check which classes are loaded and available
echo "\nChecking loaded classes:\n";

$classes_to_check = ['Game', 'User', 'Cell', 'GameTestMock', 'UserTestMock', 'CellTestMock'];

foreach ($classes_to_check as $class_name) {
    if (class_exists($class_name, false)) {
        $reflection = new ReflectionClass($class_name);
        echo "- $class_name: EXISTS (defined in " . $reflection->getFileName() . ")\n";

        // Check parent class
        $parent = $reflection->getParentClass();
        if ($parent) {
            echo "  └─ Extends: " . $parent->getName() . "\n";
        }

        // Check if it has specific methods
        $methods_to_check = ['save', 'create_new_game', 'generate_map'];
        foreach ($methods_to_check as $method_name) {
            if ($reflection->hasMethod($method_name)) {
                $method = $reflection->getMethod($method_name);
                echo "  └─ Has method $method_name (defined in " . $method->getDeclaringClass()->getName() . ")\n";
            }
        }
    } else {
        echo "- $class_name: NOT EXISTS\n";
    }
}

echo "\nTesting instantiation:\n";

// Test Game creation
echo "Creating Game instance...\n";
try {
    $game = new Game([
        'name' => 'Test Game',
        'map_w' => 100,
        'map_h' => 100,
        'turn_type' => 'byturn',
        'turn_num' => 1
    ]);
    echo "- Game created: " . get_class($game) . "\n";

    if (method_exists($game, 'create_new_game')) {
        echo "- Game has create_new_game method\n";
    } else {
        echo "- Game does NOT have create_new_game method\n";
    }
} catch (Exception $e) {
    echo "- Error creating Game: " . $e->getMessage() . "\n";
}

// Test User creation
echo "\nCreating User instance...\n";
try {
    $user = new User([
        'login' => 'testuser',
        'color' => '#ff0000',
        'game' => 1,
        'turn_order' => 1,
        'turn_status' => 'wait',
        'money' => 50,
        'age' => 1
    ]);
    echo "- User created: " . get_class($user) . "\n";

    if (method_exists($user, 'save')) {
        echo "- User has save method\n";
    } else {
        echo "- User does NOT have save method\n";
    }
} catch (Exception $e) {
    echo "- Error creating User: " . $e->getMessage() . "\n";
}

// Test Cell creation
echo "\nCreating Cell instance...\n";
try {
    $cell = new Cell([
        'x' => 10,
        'y' => 10,
        'planet' => 1,
        'type' => 'plains'
    ]);
    echo "- Cell created: " . get_class($cell) . "\n";

    if (method_exists($cell, 'save')) {
        echo "- Cell has save method\n";
    } else {
        echo "- Cell does NOT have save method\n";
    }

    // Check Cell static properties
    echo "- Cell::\$map_planet = " . Cell::$map_planet . "\n";
    echo "- Cell::\$map_width = " . Cell::$map_width . "\n";
    echo "- Cell::\$map_height = " . Cell::$map_height . "\n";

} catch (Exception $e) {
    echo "- Error creating Cell: " . $e->getMessage() . "\n";
}

// Test calling static methods
echo "\nTesting static method calls:\n";

try {
    echo "Calling Cell::generate_map()...\n";

    // This should trigger our issue
    DatabaseTestAdapter::resetTestDatabase();

    // Create a minimal game and user first
    $game_id = MyDB::insert('game', [
        'name' => 'Debug Game',
        'map_w' => 100,
        'map_h' => 100,
        'turn_type' => 'byturn',
        'turn_num' => 1
    ]);

    $user_id = MyDB::insert('user', [
        'login' => 'testuser',
        'color' => '#ff0000',
        'game' => $game_id,
        'turn_order' => 1,
        'turn_status' => 'wait',
        'money' => 50,
        'age' => 1,
        'income' => 0,
        'research_amount' => 0,
        'research_percent' => 0,
        'process_research_complete' => 0,
        'process_research_turns' => 0,
        'process_research_type' => 0
    ]);

    echo "Game and user created. Calling Cell::generate_map($game_id)...\n";

    Cell::generate_map($game_id);

    echo "Cell::generate_map() completed successfully\n";

} catch (Exception $e) {
    echo "Error in Cell::generate_map(): " . $e->getMessage() . "\n";
    echo "This error occurred in: " . get_class(Cell::class) . "::generate_map()\n";
}

echo "\nClass check debug test completed.\n";
