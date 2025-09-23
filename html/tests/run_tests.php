<?php

/**
 * Скрипт для запуска всех тестов
 * Запуск: php run_tests.php [опции]
 */

// Проверяем, что скрипт запускается из командной строки
if (php_sapi_name() !== "cli") {
    die("Этот скрипт должен запускаться из командной строки\n");
}

// Устанавливаем временную зону
date_default_timezone_set("Europe/Moscow");

// Включаем bootstrap
require_once __DIR__ . "/bootstrap.php";

/**
 * Класс для запуска тестов
 */
class TestRunner
{
    private $options = [];
    private $results = [];
    private $startTime;

    public function __construct($argv)
    {
        $this->parseArguments($argv);
        $this->startTime = microtime(true);
    }

    /**
     * Парсинг аргументов командной строки
     */
    private function parseArguments($argv)
    {
        $this->options = [
            "unit" => true,
            "integration" => true,
            "js" => false,
            "coverage" => false,
            "verbose" => false,
            "stop-on-failure" => true, // Останавливаться при первой ошибке по умолчанию
            "filter" => null,
            "help" => false,
        ];

        for ($i = 1; $i < count($argv); $i++) {
            switch ($argv[$i]) {
                case "--unit-only":
                    $this->options["integration"] = false;
                    break;
                case "--integration-only":
                    $this->options["unit"] = false;
                    break;
                case "--with-js":
                    $this->options["js"] = true;
                    break;
                case "--coverage":
                    $this->options["coverage"] = true;
                    break;
                case "--verbose":
                case "-v":
                    $this->options["verbose"] = true;
                    break;
                case "--stop-on-failure":
                    $this->options["stop-on-failure"] = true;
                    break;
                case "--filter":
                    if (isset($argv[$i + 1])) {
                        $this->options["filter"] = $argv[$i + 1];
                        $i++;
                    }
                    break;
                case "--help":
                case "-h":
                    $this->options["help"] = true;
                    break;
            }
        }
    }

    /**
     * Запуск всех тестов
     */
    public function run()
    {
        if ($this->options["help"]) {
            $this->showHelp();
            return 0;
        }

        $this->printHeader();

        $exitCode = 0;

        // Запускаем PHP тесты
        if ($this->options["unit"] || $this->options["integration"]) {
            $phpResult = $this->runPhpTests();
            if ($phpResult !== 0) {
                $exitCode = $phpResult;
            }
        }

        // Запускаем JavaScript тесты
        if ($this->options["js"]) {
            $jsResult = $this->runJavaScriptTests();
            if ($jsResult !== 0) {
                $exitCode = $jsResult;
            }
        }

        $this->printSummary();

        return $exitCode;
    }

    /**
     * Запуск PHP тестов через PHPUnit
     */
    private function runPhpTests()
    {
        echo "🧪 Запуск PHP тестов...\n";
        echo str_repeat("=", 50) . "\n";

        $phpunitConfig = TESTS_ROOT . "/phpunit.xml";
        $phpunitPath = $this->findPhpUnit();

        if (!$phpunitPath) {
            echo "❌ PHPUnit не найден. Установите PHPUnit:\n";
            echo "   composer install --dev\n";
            echo "   или скачайте phpunit.phar\n";
            return 1;
        }

        // Формируем команду для запуска PHPUnit
        $cmd = [];
        if (substr($phpunitPath, -5) === '.phar') {
            $cmd[] = 'php';
        }
        $cmd[] = $phpunitPath;

        if (file_exists($phpunitConfig)) {
            $cmd[] = "--configuration";
            $cmd[] = $phpunitConfig;
        }

        if ($this->options["verbose"]) {
            $cmd[] = "--verbose";
        }

        if ($this->options["stop-on-failure"]) {
            $cmd[] = "--stop-on-failure";
        }

        if ($this->options["coverage"]) {
            $cmd[] = "--coverage-html";
            $cmd[] = TESTS_ROOT . "/coverage-html";
        }

        if ($this->options["filter"]) {
            $cmd[] = "--filter";
            $cmd[] = $this->options["filter"];
        }

        // Определяем какие тесты запускать
        if ($this->options["unit"] && !$this->options["integration"]) {
            $cmd[] = TESTS_ROOT . "/unit";
        } elseif ($this->options["integration"] && !$this->options["unit"]) {
            $cmd[] = TESTS_ROOT . "/integration";
        }

        $fullCmd = implode(" ", array_map("escapeshellarg", $cmd));

        if ($this->options["verbose"]) {
            echo "Выполняем: {$fullCmd}\n\n";
        }

        $startTime = microtime(true);

        // Запускаем PHPUnit без таймаута, чтобы ошибки выводились сразу
        $exitCode = $this->runCommand($fullCmd);

        $duration = microtime(true) - $startTime;

        $this->results["php"] = [
            "exit_code" => $exitCode,
            "duration" => $duration,
        ];

        if ($exitCode === 0) {
            echo "\n✅ PHP тесты завершены успешно (" .
                number_format($duration, 2) .
                "s)\n";
        } else {
            echo "\n❌ PHP тесты завершились с ошибками (" .
                number_format($duration, 2) .
                "s)\n";
        }

        echo str_repeat("-", 50) . "\n\n";

        return $exitCode;
    }

    /**
     * Запуск JavaScript тестов
     */
    private function runJavaScriptTests()
    {
        echo "🌐 Запуск JavaScript тестов...\n";
        echo str_repeat("=", 50) . "\n";

        $testFile = TESTS_ROOT . "/js/creategame.test.html";

        if (!file_exists($testFile)) {
            echo "❌ JavaScript тест файл не найден: {$testFile}\n";
            return 1;
        }

        $startTime = microtime(true);

        // Пытаемся найти браузер для запуска тестов
        $browsers = $this->findAvailableBrowsers();

        if (empty($browsers)) {
            echo "⚠️  Браузер не найден. JavaScript тесты нужно запускать вручную.\n";
            echo "   Откройте в браузере: {$testFile}\n";

            $this->results["js"] = [
                "exit_code" => 0,
                "duration" => microtime(true) - $startTime,
                "manual" => true,
            ];

            return 0;
        }

        // Запускаем тесты в первом доступном браузере
        $browser = $browsers[0];
        $exitCode = 0;

        try {
            // Открываем файл в браузере для ручного запуска тестов
            $cmd = "{$browser} {$testFile}";
            echo "🌐 Открываем JavaScript тесты в браузере...\n";
            echo "   После завершения тестов закройте браузер\n";

            if ($this->options["verbose"]) {
                echo "Выполняем: {$cmd}\n";
            }

            exec($cmd, $output, $exitCode);
        } catch (Exception $e) {
            echo "❌ Ошибка при запуске JavaScript тестов: " .
                $e->getMessage() .
                "\n";
            $exitCode = 1;
        }

        $duration = microtime(true) - $startTime;

        $this->results["js"] = [
            "exit_code" => $exitCode,
            "duration" => $duration,
        ];

        if ($exitCode === 0) {
            echo "✅ JavaScript тесты завершены (" .
                number_format($duration, 2) .
                "s)\n";
        } else {
            echo "❌ JavaScript тесты завершились с ошибками (" .
                number_format($duration, 2) .
                "s)\n";
        }

        echo str_repeat("-", 50) . "\n\n";

        return $exitCode;
    }

    /**
     * Поиск PHPUnit
     */
    private function findPhpUnit()
    {
        // 1. Проверяем PHPUnit, установленный через Composer
        $composerPhpunit = PROJECT_ROOT . "/vendor/bin/phpunit";
        if (file_exists($composerPhpunit)) {
            return $composerPhpunit;
        }

        // 2. Проверяем phpunit.phar в корне проекта
        $projectPhar = PROJECT_ROOT . "/phpunit.phar";
        if (file_exists($projectPhar)) {
            return $projectPhar;
        }

        // 3. Проверяем phpunit.phar в папке tests
        $testsPhar = TESTS_ROOT . "/phpunit.phar";
        if (file_exists($testsPhar)) {
            return $testsPhar;
        }

        // 4. Проверяем глобальную установку через PATH
        $isWindows = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Windows' : strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmd = $isWindows ? "where phpunit" : "which phpunit";
        $output = shell_exec($cmd . " 2>&1");
        if ($output && trim($output) && strpos($output, "not found") === false && strpos($output, "Could not find files") === false) {
            // `where` может вернуть несколько путей, по одному на строку. Берем первый.
            $lines = explode("\n", trim($output));
            return trim($lines[0]);
        }

        return null;
    }

    /**
     * Поиск доступных браузеров
     */
    private function findAvailableBrowsers()
    {
        $browsers = [];

        $isWindows = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Windows' : strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $paths = [
                "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe",
                "C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe",
                'C:\\Program Files\\Mozilla Firefox\\firefox.exe',
                'C:\\Program Files (x86)\\Mozilla Firefox\\firefox.exe',
            ];
        } else {
            $paths = [
                "/usr/bin/google-chrome",
                "/usr/bin/chromium-browser",
                "/usr/bin/firefox",
                "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
                "/Applications/Firefox.app/Contents/MacOS/firefox",
            ];
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $browsers[] = $path;
            }
        }

        return $browsers;
    }

    /**
     * Вывод заголовка
     */
    private function printHeader()
    {
        echo "\n";
        echo "🚀 Запуск тестов для системы создания игр\n";
        echo str_repeat("=", 60) . "\n";
        echo "Время запуска: " . date("Y-m-d H:i:s") . "\n";
        echo "PHP версия: " . PHP_VERSION . "\n";
        echo "Проект: " . PROJECT_ROOT . "\n";

        $testTypes = [];
        if ($this->options["unit"]) {
            $testTypes[] = "Unit";
        }
        if ($this->options["integration"]) {
            $testTypes[] = "Integration";
        }
        if ($this->options["js"]) {
            $testTypes[] = "JavaScript";
        }

        echo "Типы тестов: " . implode(", ", $testTypes) . "\n";

        if ($this->options["filter"]) {
            echo "Фильтр: " . $this->options["filter"] . "\n";
        }

        echo str_repeat("=", 60) . "\n\n";
    }

    /**
     * Вывод итогового отчета
     */
    private function printSummary()
    {
        echo "\n";
        echo "📊 ИТОГОВЫЙ ОТЧЕТ\n";
        echo str_repeat("=", 60) . "\n";

        $totalDuration = microtime(true) - $this->startTime;
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;

        foreach ($this->results as $type => $result) {
            $status = $result["exit_code"] === 0 ? "✅ ПРОЙДЕН" : "❌ ПРОВАЛЕН";
            $duration = number_format($result["duration"], 2);

            echo sprintf(
                "%-15s: %s (%ss)\n",
                strtoupper($type),
                $status,
                $duration,
            );

            if ($result["exit_code"] === 0) {
                $passedTests++;
            } else {
                $failedTests++;
            }
            $totalTests++;
        }

        echo str_repeat("-", 60) . "\n";
        echo sprintf(
            "Общее время выполнения: %ss\n",
            number_format($totalDuration, 2),
        );
        echo sprintf("Всего наборов тестов: %d\n", $totalTests);
        echo sprintf("Пройдено: %d\n", $passedTests);
        echo sprintf("Провалено: %d\n", $failedTests);

        if ($failedTests === 0) {
            echo "\n🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!\n";
        } else {
            echo "\n💥 НЕКОТОРЫЕ ТЕСТЫ ПРОВАЛЕНЫ!\n";
        }

        // Информация о дополнительных файлах
        echo "\n📁 Дополнительные файлы:\n";

        $logFiles = [
            "coverage-html/index.html" => "Отчет о покрытии кода",
            "results/junit.xml" => "JUnit XML отчет",
            "results/testdox.html" => "TestDox HTML отчет",
            "results/php_errors.log" => "Лог ошибок PHP",
        ];

        foreach ($logFiles as $file => $description) {
            $fullPath = TESTS_ROOT . "/" . $file;
            if (file_exists($fullPath)) {
                echo "   ✓ {$description}: {$fullPath}\n";
            }
        }

        echo str_repeat("=", 60) . "\n";
    }

    /**
     * Запуск команды без таймаута
     */
    private function runCommand($command)
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            echo "❌ Не удалось запустить команду: {$command}\n";
            return 1;
        }

        // Закрываем stdin
        fclose($pipes[0]);

        // Читаем вывод в реальном времени
        while (!feof($pipes[1])) {
            $data = fread($pipes[1], 8192);
            if ($data !== false) {
                echo $data;
            }
        }

        while (!feof($pipes[2])) {
            $data = fread($pipes[2], 8192);
            if ($data !== false) {
                echo $data;
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return $exitCode;
    }

    /**
     * Запуск команды с таймаутом
     */
    private function runCommandWithTimeout($command, $timeout = 300)
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            echo "❌ Не удалось запустить команду: {$command}\n";
            return 1;
        }

        // Закрываем stdin
        fclose($pipes[0]);

        $startTime = time();
        $output = '';
        $errorOutput = '';

        // Читаем вывод в реальном времени
        $stdoutDone = false;
        $stderrDone = false;

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (!$stdoutDone || !$stderrDone) {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            if (stream_select($read, $write, $except, 1) > 0) {
                foreach ($read as $stream) {
                    if ($stream === $pipes[1]) {
                        $data = fread($stream, 8192);
                        if ($data === false || $data === '') {
                            $stdoutDone = true;
                        } else {
                            echo $data;
                            $output .= $data;
                        }
                    } elseif ($stream === $pipes[2]) {
                        $data = fread($stream, 8192);
                        if ($data === false || $data === '') {
                            $stderrDone = true;
                        } else {
                            echo $data;
                            $errorOutput .= $data;
                        }
                    }
                }
            }

            // Проверяем таймаут
            if (time() - $startTime > $timeout) {
                echo "\n❌ Превышен таймаут {$timeout} секунд. Завершаем процесс.\n";
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                return 124; // Код выхода для таймаута
            }

            // Проверяем, завершен ли процесс
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return $exitCode;
    }

    /**
     * Вывод справки
     */
    private function showHelp()
    {
        echo "\n";
        echo "🛠️  СПРАВКА ПО ТЕСТОВОМУ РАННЕРУ\n";
        echo str_repeat("=", 60) . "\n";
        echo "Использование: php run_tests.php [опции]\n\n";
        echo "ОПЦИИ:\n";
        echo "  --unit-only         Запуск только unit тестов\n";
        echo "  --integration-only  Запуск только integration тестов\n";
        echo "  --with-js           Включить JavaScript тесты\n";
        echo "  --coverage          Генерировать отчет о покрытии кода\n";
        echo "  --verbose, -v       Подробный вывод\n";
        echo "  --stop-on-failure   Остановиться при первой ошибке\n";
        echo "  --filter <pattern>  Фильтр тестов по имени/паттерну\n";
        echo "  --help, -h          Показать эту справку\n\n";
        echo "ПРИМЕРЫ:\n";
        echo "  php run_tests.php                    # Все PHP тесты\n";
        echo "  php run_tests.php --with-js          # Все тесты включая JS\n";
        echo "  php run_tests.php --unit-only -v     # Только unit тесты, подробно\n";
        echo "  php run_tests.php --coverage         # С отчетом покрытия\n";
        echo "  php run_tests.php --filter CreateGame # Только тесты CreateGame\n";
        echo str_repeat("=", 60) . "\n";
    }
}

// Запуск тестового раннера
try {
    $runner = new TestRunner($argv);
    $exitCode = $runner->run();
    exit($exitCode);
} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
