<?php

// Переопределяем хост БД для локального запуска
define("DB_HOST", "localhost");

require_once __DIR__ . '/../includes.php';

\App\MyDB::setDBConfig(DB_HOST, DB_USER, DB_PASS, DB_PORT, DB_NAME);

use App\MyDB;

// Создаем таблицу migrations, если не существует
MyDB::query("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Получаем список выполненных миграций
$executed = MyDB::query("SELECT filename FROM migrations");
$executedFiles = array_column($executed, 'filename');

// Получаем список файлов в sql/
$sqlDir = __DIR__ . '/../sql';
$files = scandir($sqlDir);
$files = array_filter($files, fn($f) => pathinfo($f, PATHINFO_EXTENSION) === 'sql');
sort($files); // Сортируем по имени для порядка

$imported = 0;
foreach ($files as $file) {
    if (in_array($file, $executedFiles)) {
        echo "Миграция $file уже выполнена, пропускаем.\n";
        continue;
    }

    $filePath = $sqlDir . '/' . $file;
    $sql = file_get_contents($filePath);

    if (!$sql) {
        echo "Ошибка чтения файла $file.\n";
        continue;
    }

    try {
        $db = MyDB::get();
        // Отключаем проверки внешних ключей
        $db->exec("SET FOREIGN_KEY_CHECKS=0;");
        // Выполняем весь SQL файл
        $db->exec($sql);
        // Включаем обратно
        $db->exec("SET FOREIGN_KEY_CHECKS=1;");

        // Записываем в migrations
        MyDB::insert('migrations', ['filename' => $file]);

        echo "Миграция $file выполнена успешно.\n";
        $imported++;
    } catch (Exception $e) {
        echo "Ошибка выполнения миграции $file: " . $e->getMessage() . "\n";
    }
}

echo "Миграции завершены. Импортировано $imported файлов.\n";
