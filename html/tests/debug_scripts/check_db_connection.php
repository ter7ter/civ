<?php
$servername = "db";
$username = "civ_test";
$password = "civ_test";
$dbname = "civ_for_tests";
$port = 3306;

// Создаем соединение
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Проверяем соединение
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to database: " . $dbname . "\n";
$conn->close();
?>
