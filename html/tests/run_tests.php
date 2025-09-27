<?php
/**
 * Скрипт для запуска всех тестов
 * Запуск: php run_tests.php [опции]
 */

define("PROJECT_ROOT", dirname(__DIR__));

if (php_sapi_name() !== "cli") {
    die("Этот скрипт должен запускаться из командной строки\n");
}

// Устанавливаем временную зону

date_default_timezone_set("Europe/Moscow");

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

    private function parseArguments($argv)
    {
        $this->options = [
            "unit" => true,
            "integration" => true,
            "js" => true,
            "coverage" => false,
            "generate-coverage-report-only" => false,
            "verbose" => false,
            "stop-on-failure" => true,
            "filter" => null,
            "help" => false,
            "no-parallel" => false,
            "processes" => null,
            "timeout" => 200,
        ];

        $i = 1;
        while ($i < count($argv)) {
            $arg = $argv[$i];
            switch ($arg) {
                case "--unit-only":
                    $this->options["integration"] = false;
                    $this->options["js"] = false;
                    break;
                case "--integration-only":
                    $this->options["unit"] = false;
                    $this->options["js"] = false;
                    break;
                case "--js-only":
                    $this->options["unit"] = false;
                    $this->options["integration"] = false;
                    $this->options["js"] = true;
                    break;
                case "--with-js":
                    $this->options["js"] = true;
                    break;
                case "--js-only":
                    $this->options["unit"] = false;
                    $this->options["integration"] = false;
                    $this->options["js"] = true;
                    break;
                case "--coverage":
                    $this->options["coverage"] = true;
                    break;
                case "--generate-coverage-report-only":
                    $this->options["generate-coverage-report-only"] = true;
                    $this->options["unit"] = false;
                    $this->options["integration"] = false;
                    $this->options["js"] = false;
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
                        $this->options["js"] = false;
                        $i++;
                    }
                    break;
                case "--help":
                case "-h":
                    $this->options["help"] = true;
                    break;
                case "--no-parallel":
                    $this->options["no-parallel"] = true;
                    break;
                case "--processes":
                    if (isset($argv[$i + 1])) {
                        $this->options["processes"] = (int)$argv[$i + 1];
                        $i++;
                    }
                    break;
                case "--timeout":
                    if (isset($argv[$i + 1])) {
                        $this->options["timeout"] = (int)$argv[$i + 1];
                        $i++;
                    }
                    break;
                default:
                    echo "Неизвестный параметр: " . $arg . "\n";
                    $this->options["help"] = true;
                    return;
            }
            $i++;
        }
    }

    public function run()
    {
        if ($this->options["help"]) {
            $this->showHelp();
            return 0;
        }

        $this->printHeader();

        $exitCode = 0;

        if (!$this->options["generate-coverage-report-only"] && ($this->options["unit"] || $this->options["integration"])) {
            $phpResult = $this->runPhpTests();
            if ($phpResult !== 0) {
                $exitCode = $phpResult;
            }
        } elseif ($this->options["generate-coverage-report-only"]) {
            $phpResult = $this->runPhpTests();
            if ($phpResult !== 0) {
                $exitCode = $phpResult;
            }
        }

        if ($this->options["js"]) {
            $jsResult = $this->runJavaScriptTests();
            if ($jsResult !== 0) {
                $exitCode = $jsResult;
            }
        }

        $this->printSummary();

        return $exitCode;
    }

    private function runPhpTests()
    {
        echo "🧪 Запуск PHP тестов...\n";
        echo str_repeat("=", 50) . "\n";

        $phpunitConfig = __DIR__ . "/phpunit.xml";
        $paratestConfigPath = __DIR__ . '/phpunit.paratest.xml';
        $phpunitPath = $this->findPhpUnit();
        $paratestPath = $this->findParaTest();
        $useParatest = $paratestPath && !$this->options["no-parallel"];
        if ($this->options["filter"]) {
            $useParatest = false;
        }
        $processes = $this->options["processes"] ?? 4;

        if (!$phpunitPath) {
            echo "❌ PHPUnit не найден. Установите PHPUnit:\n";
            echo "   composer install --dev\n";
            echo "   или скачайте phpunit.phar\n";
            return 1;
        }

        $cmd = [];
        $isWindows = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Windows' : strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($useParatest && $this->options["coverage"] && $isWindows) {
            echo "⚠️  На Windows покрытие кода через Paratest не поддерживается. Будет использован обычный phpunit.\n";
            $useParatest = false;
        }

        if ($useParatest) {
            // Create a temporary phpunit.xml for paratest without the junit logger
            $phpunitConfigContent = file_get_contents($phpunitConfig);
            $phpunitConfigContent = preg_replace('/<junit[^>]+>/', '', $phpunitConfigContent);
            file_put_contents($paratestConfigPath, $phpunitConfigContent);
            
            register_shutdown_function(function() use ($paratestConfigPath) {
                if (file_exists($paratestConfigPath)) {
                    unlink($paratestConfigPath);
                }
            });

            $phpPath = $this->findPhp();
            if ($phpPath) {
                $cmd[] = $phpPath;
            }
            $cmd[] = $paratestPath;
            if ($this->options["coverage"]) {
                $cmd[] = "--processes=1";
                $cmd[] = "--coverage-html=" . __DIR__ . "/coverage-html";
                $cmd[] = "--coverage-text=" . __DIR__ . "/coverage.txt";
                $cmd[] = "--coverage-php=" . __DIR__ . "/coverage.php";
            } else {
                $cmd[] = "--processes=" . $processes;
                $cmd[] = "--no-coverage";
            }
            $cmd[] = "--configuration=" . $paratestConfigPath; // Use the temporary config
            if ($this->options["verbose"]) {
                $cmd[] = "--verbose";
            }
            if ($this->options["stop-on-failure"]) {
                $cmd[] = "--stop-on-error";
            }
            if ($this->options["filter"]) {
                $cmd[] = "--filter=" . $this->options["filter"];
            }
            if ($this->options["unit"] && !$this->options["integration"]) {
                $cmd[] = __DIR__ . "/unit";
            } elseif ($this->options["integration"] && !$this->options["unit"]) {
                $cmd[] = __DIR__ . "/integration";
            }
        } else {
            if (substr($phpunitPath, -5) === '.phar') {
                $cmd[] = 'php';
            }
            $cmd[] = $phpunitPath;
            if (file_exists($phpunitConfig)) {
                $cmd[] = "--configuration";
                $cmd[] = $phpunitConfig;
            }
            if ($this->options["coverage"]) {
                $cmd[] = "--coverage-html";
                $cmd[] = __DIR__ . "/coverage-html";
                $cmd[] = "--coverage-text=" . __DIR__ . "/coverage.txt";
                $cmd[] = "--coverage-php";
                $cmd[] = __DIR__ . "/coverage.php";
            }
            if ($this->options["verbose"]) {
                $cmd[] = "--verbose";
                $cmd[] = "--debug";
            }
            if ($this->options["stop-on-failure"]) {
                $cmd[] = "--stop-on-failure";
            }
            if ($this->options["filter"]) {
                $cmd[] = "--filter";
                $cmd[] = $this->options["filter"];
            }
            if ($this->options["unit"] && !$this->options["integration"]) {
                $cmd[] = __DIR__ . "/unit";
            } elseif ($this->options["integration"] && !$this->options["unit"]) {
                $cmd[] = __DIR__ . "/integration";
            }
        }

        $fullCmd = implode(" ", array_map("escapeshellarg", $cmd));
        if ($this->options["verbose"]) {
            echo "Выполняем: " . $fullCmd . "\n\n";
        }

        $startTime = microtime(true);
        $exitCode = $this->runCommand($fullCmd);
        $duration = microtime(true) - $startTime;

        $this->results["php"] = [
            "exit_code" => $exitCode,
            "duration" => $duration,
        ];

        if ($exitCode === 0) {
            echo "\n✅ PHP тесты завершены успешно (" . number_format($duration, 2) . "s)\n";
        } else {
            echo "\n❌ PHP тесты завершились с ошибками (" . number_format($duration, 2) . "s)\n";
        }

        echo str_repeat("-", 50) . "\n\n";
        return $exitCode;
    }

    private function runJavaScriptTests()
    {
        echo "🌐 Запуск JavaScript тестов...\n";
        echo str_repeat("=", 50) . "\n";

        $testFiles = glob(__DIR__ . "/js/*.html");

        if (empty($testFiles)) {
            echo "❌ JavaScript тест файлы не найдены в " . __DIR__ . "/js/\n";
            return 1;
        }

        $startTime = microtime(true);
        $browsers = $this->findAvailableBrowsers();

        if (empty($browsers)) {
            echo "⚠️  Браузер не найден. JavaScript тесты нужно запускать вручную.\n";
            foreach ($testFiles as $testFile) {
                echo "   Откройте в браузере: " . $testFile . "\n";
            }
            $this->results["js"] = [
                "exit_code" => 0,
                "duration" => microtime(true) - $startTime,
                "manual" => true,
            ];
            return 0;
        }

        $browser = $browsers[0];
        $exitCode = 0;

        foreach ($testFiles as $testFile) {
            try {
                $cmd = "\"" . $browser . "\" \"" . $testFile . "\"";
                echo "🌐 Открываем JavaScript тесты в браузере: " . $testFile . "\n";
                echo "   После завершения тестов закройте браузер\n";
                if ($this->options["verbose"]) {
                    echo "Выполняем: " . $cmd . "\n";
                }
                exec($cmd, $output, $exitCode);
            } catch (Exception $e) {
                echo "❌ Ошибка при запуске JavaScript тестов: " . $e->getMessage() . "\n";
                $exitCode = 1;
            }
        }

        $duration = microtime(true) - $startTime;
        $this->results["js"] = [
            "exit_code" => $exitCode,
            "duration" => $duration,
        ];

        if ($exitCode === 0) {
            echo "✅ JavaScript тесты завершены (" . number_format($duration, 2) . "s)\n";
        } else {
            echo "❌ JavaScript тесты завершились с ошибками (" . number_format($duration, 2) . "s)\n";
        }

        echo str_repeat("-", 50) . "\n\n";
        return $exitCode;
    }

    private function findPhpUnit()
    {
        $composerPhpunit = PROJECT_ROOT . "/vendor/bin/phpunit";
        if (file_exists($composerPhpunit)) {
            return $composerPhpunit;
        }
        $projectPhar = PROJECT_ROOT . "/phpunit.phar";
        if (file_exists($projectPhar)) {
            return $projectPhar;
        }
        $testsPhar = __DIR__ . "/phpunit.phar";
        if (file_exists($testsPhar)) {
            return $testsPhar;
        }
        $isWindows = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Windows' : strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmd = $isWindows ? "where phpunit" : "which phpunit";
        $output = shell_exec($cmd . " 2>&1");
        if ($output && trim($output) && strpos($output, "not found") === false && strpos($output, "Could not find files") === false) {
            $lines = explode("\n", trim($output));
            return trim($lines[0]);
        }
        return null;
    }

    private function runCommand($command)
    {
        if ($this->options["verbose"]) {
            echo "Executing: " . $command . "\n";
        }

        // Change to the project root directory to run the command
        $cwd = getcwd();
        chdir(PROJECT_ROOT);

        passthru($command, $exitCode);

        // Change back to the original directory
        chdir($cwd);

        return $exitCode;
    }

    private function findParaTest()
    {
        $paths = [
            PROJECT_ROOT . "/vendor/bin/paratest",
            PROJECT_ROOT . "/vendor/bin/paratest.bat",
            __DIR__ . "/../vendor/bin/paratest",
            __DIR__ . "/../vendor/bin/paratest.bat",
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }

    private function findPhp()
    {
        $isWindows = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Windows' : strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmd = $isWindows ? "where php" : "which php";
        $output = shell_exec($cmd . " 2>&1");
        if ($output && trim($output) && strpos($output, "not found") === false && strpos($output, "Could not find files") === false) {
            $lines = explode("\n", trim($output));
            return trim($lines[0]);
        }
        return null;
    }

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
        echo "\n📁 Дополнительные файлы:\n";
        $logFiles = [
            "coverage-html/index.html" => "Отчет о покрытии кода",
            "results/junit.xml" => "JUnit XML отчет",
            "results/testdox.html" => "TestDox HTML отчет",
            "results/php_errors.log" => "Лог ошибок PHP",
        ];
        foreach ($logFiles as $file => $description) {
            $fullPath = __DIR__ . "/" . $file;
            if (file_exists($fullPath)) {
                echo "   ✓ " . $description . ": " . $fullPath . "\n";
            }
        }
        echo str_repeat("=", 60) . "\n";
    }

    private function showHelp()
    {
        echo "\n";
        echo "🛠️  СПРАВКА ПО ТЕСТОВОМУ РАННЕРУ\n";
        echo str_repeat("=", 60) . "\n";
        echo "Использование: php run_tests.php [опции]\n\n";
        echo "ОПЦИИ:\n";
        echo "  --unit-only         Запуск только unit тестов\n";
        echo "  --integration-only  Запуск только integration тестов\n";
        echo "  --js-only           Запуск только JavaScript тестов\n";
        echo "  --with-js           Включить JavaScript тесты\n";
        echo "  --coverage           Покрытие кода (только 1 процесс, медленнее, но корректно)\n";
        echo "                      Без --coverage тесты идут параллельно через ParaTest (по умолчанию 4 процесса)\n";
        echo "  --generate-coverage-report-only  Генерация отчета о покрытии кода из ранее собранных данных (из coverage.php) без запуска тестов\n";
        echo "  --verbose, -v       Подробный вывод\n";
        echo "  --stop-on-failure   Остановиться при первой ошибке\n";
        echo "  --filter <pattern>  Фильтр тестов по имени/паттерну\n";
        echo "  --help, -h          Показать эту справку\n";
        echo "  --no-parallel         Запускать тесты без параллелизма (только через phpunit)\n";
        echo "  --processes <n>       Количество процессов для ParaTest (по умолчанию 4)\n";
        echo "  --timeout <n>         Таймаут для PHP тестов в секундах (по умолчанию 300)\n";
        echo "\nПРИМЕРЫ:\n";
        echo "  php run_tests.php                    # Все PHP тесты\n";
        echo "  php run_tests.php --with-js          # Все тесты включая JS\n";
        echo "  php run_tests.php --unit-only -v     # Только unit тесты, подробно\n";
        echo "  php run_tests.php --coverage         # Запуск тестов и генерация отчета о покрытии\n";
        echo "  php run_tests.php --generate-coverage-report-only # Генерация отчета о покрытии\n";
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
