<?php

$methods = [
    'testGetExistingGame',
    'testGetNonExistingGame',
    'testConstruct',
    'testSaveNew',
    'testSaveUpdate',
    'testGameList',
    'testAllSystemMessage',
    'testGetActivePlayerByTurn',
    'testGetActivePlayerConcurrently',
    'testGetActivePlayerNoActive',
    'testGetFirstPlanet',
    'testGetFirstPlanetNoPlanets',
    'testCalculateByTurn',
    'testCalculateConcurrently',
    'testCreateNewGame'
];

foreach ($methods as $method) {
    echo "Running GameTest::$method\n";
    $start = microtime(true);

    $command = "php tests/run_tests.php --filter \"GameTest::$method\" --no-parallel --verbose 2>&1";
    $output = shell_exec($command);

    $duration = microtime(true) - $start;
    echo "Duration: " . number_format($duration, 2) . "s\n";

    if (strpos($output, 'ПРОЙДЕН') !== false) {
        echo "✅ PASSED\n\n";
    } else {
        echo "❌ FAILED or TIMEOUT\n";
        echo "Output:\n$output\n\n";
        break;
    }
}
