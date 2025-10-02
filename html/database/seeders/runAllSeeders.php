<?php

require_once 'baseSeeder.php';

// Определяем, нужно ли очищать данные.
// Эта переменная будет видна во всех подключаемых файлах.
$SHOULD_CLEAN = in_array('--clean', $argv);

if ($SHOULD_CLEAN) {
    echo "Запуск сидеров с очисткой таблиц...\n";
} else {
    echo "Запуск сидеров...\n";
}

// Подключаем все сидеры.
require_once 'cellTypeSeeder.php';
require_once 'unitTypeSeeder.php';
require_once 'researchTypeSeeder.php';
require_once 'resourceTypeSeeder.php';

echo "Все сидеры выполнены успешно.\n";
