<?php

function setupDatabase() {
    require_once __DIR__ . '/../../includes.php';
    \App\MyDB::setDBConfig('localhost', DB_USER, DB_PASS, DB_PORT, DB_NAME);
}

function checkTables($tables) {
    foreach ($tables as $table) {
        $exists = \App\MyDB::query("SHOW TABLES LIKE '$table'");
        if (empty($exists)) {
            die("Таблица $table не существует. Сначала запустите database/migrations.php для создания таблиц.\n");
        }
    }
}

function parseClearArgv($argv) {
    $clear = false;
    if (in_array('--clean', $argv)) {
        $clear = true;
    } elseif (in_array('--no-clean', $argv)) {
        $clear = false;
    } else {
        $clearInput = readline("Очищать существующие данные? (y/n): ");
        $clear = strtolower($clearInput) === 'y';
    }
    return $clear;
}

function clearData($clear, $deleteQueries) {
    if ($clear) {
        \App\MyDB::startTransaction();
        \App\MyDB::query("SET FOREIGN_KEY_CHECKS=0;\n" . implode("\n", $deleteQueries) . "\nSET FOREIGN_KEY_CHECKS=1;");
        \App\MyDB::endTransaction();
        echo "Данные очищены.\n";
    } else {
        echo "Очистка пропущена.\n";
    }
}
