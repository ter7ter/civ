<h1>�� php �������� � It works</h1>
<?php phpinfo(); ?>
<?php
// ����������� � ���� ������

$servername = "db";
$username = "docker";
$password1 = "dockerpass";
$password2 = "rootpass";
$dbname = "base_docker";

$conn = new mysqli($servername, $username, $password1, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}




// �������� �������
$sql = "CREATE TABLE IF NOT EXISTS random_text (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(255) NOT NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'random_text' created successfully<br><br>";
} else {
    echo "Error creating table: " . $conn->error;
}

// ���������� ������� ��������� �������
for ($i = 1; $i <= 10; $i++) {
    $randomText = generateRandomText();
    $sql = "INSERT INTO random_text (text) VALUES ('$randomText')";
    if ($conn->query($sql) === TRUE) {
        echo "Inserted random text: $randomText<br>";
    } else {
        echo "Error inserting text: " . $conn->error;
    }
}

// ����� ������ �� ���� ������ �� �����
$sql = "SELECT * FROM random_text";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<br>Random Text:<br>";
    while($row = $result->fetch_assoc()) {
        echo $row["text"] . "<br>";
    }
} else {
    echo "No random text found";
}

$conn->close();

// ������� ��� ��������� ���������� ������
function generateRandomText($length = 20) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomText = '';

    for ($i = 0; $i < $length; $i++) {
        $randomText .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $randomText;
}
?>