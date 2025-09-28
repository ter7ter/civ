<?php

// Проверяем, что скрипт запускается из командной строки
if (php_sapi_name() !== "cli") {
    die("Этот скрипт должен запускаться из командной строки\n");
}

// Устанавливаем временную зону по умолчанию
date_default_timezone_set("Europe/Moscow");

// Пути к файлам проекта
define("PROJECT_ROOT", dirname(__DIR__));
define("TESTS_ROOT", __DIR__);

// Попытка загрузить автозагрузчик Composer
$autoloadPath = PROJECT_ROOT . "/vendor/autoload.php";
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Если Composer не используется, пытаемся загрузить phpunit.phar
    $phpunitPharPath = TESTS_ROOT . "/phpunit.phar";
    if (!file_exists($phpunitPharPath)) {
        die("phpunit.phar не найден по пути: {$phpunitPharPath} и Composer autoload не доступен.\n");
    }
    require_once $phpunitPharPath;
}

// Проверяем, доступны ли классы PHP_CodeCoverage
$codeCoverageClass = "SebastianBergmann\\CodeCoverage\\CodeCoverage";
if (!class_exists($codeCoverageClass)) {
    die("Классы PHP_CodeCoverage не найдены. Убедитесь, что phpunit.phar содержит их или установите php-code-coverage через Composer.\n");
}

// Путь к файлу с необработанными данными о покрытии
$coverageFilePath = TESTS_ROOT . "/coverage.php";

// Проверяем наличие файла с данными о покрытии
if (!file_exists($coverageFilePath)) {
    die("Файл с данными о покрытии ({$coverageFilePath}) не найден. Сначала запустите тесты с параметром --coverage.\n");
}

echo "Генерация отчетов о покрытии кода из {$coverageFilePath}...";

try {
    // Загружаем объект CodeCoverage из файла
    $coverage = include $coverageFilePath;

    if (!($coverage instanceof $codeCoverageClass)) {
        die("Файл {$coverageFilePath} не содержит корректных данных о покрытии.\n");
    }

    // Генерация HTML отчета
    $htmlReportPath = TESTS_ROOT . "/coverage-html";
    echo "   Генерация HTML отчета в: {$htmlReportPath}\n";
    $htmlFacadeClass = "SebastianBergmann\\CodeCoverage\\Report\\Html\\Facade";
    $htmlWriter = new $htmlFacadeClass();
    $htmlWriter->process($coverage, $htmlReportPath);

    // Генерация текстового отчета
    $textReportPath = TESTS_ROOT . "/coverage.txt";
    echo "   Генерация текстового отчета в: {$textReportPath}\n";
    $textReportClass = "SebastianBergmann\\CodeCoverage\\Report\\Text";
    $textWriter = new $textReportClass();
    file_put_contents($textReportPath, $textWriter->process($coverage, false)); // false for not showing colors

    echo "Отчеты о покрытии кода успешно сгенерированы.\n";
    exit(0);
} catch (Exception $e) {
    echo "Ошибка при генерации отчетов о покрытии: " . $e->getMessage() . "\n";
    exit(1);
}
