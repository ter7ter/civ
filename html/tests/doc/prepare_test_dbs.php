<?php
// prepare_test_dbs.php

// Этот скрипт не может использовать bootstrap.php, так как он сам настраивает окружение

$dbHost = 'db';
$dbUser = 'civ_test';
$dbPass = 'civ_test';
$dbPort = 3306;
$num_dbs = 8; // Создадим с запасом

echo "Preparing test databases...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    for ($i = 1; $i <= $num_dbs; $i++) {
        $dbName = "civ_for_tests_$i";
        echo "Dropping and creating database: $dbName\n";
        $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
        $pdo->exec("CREATE DATABASE `$dbName`");
    }
    
    // Также пересоздадим базу для одиночных запусков
    echo "Dropping and creating database: civ_for_tests\n";
    $pdo->exec("DROP DATABASE IF EXISTS `civ_for_tests`");
    $pdo->exec("CREATE DATABASE `civ_for_tests`");

    echo "Done.\n";

} catch (PDOException $e) {
    die("Failed: " . $e->getMessage() . "\n");
}

