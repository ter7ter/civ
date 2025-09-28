<?php

/**
 * Скрипт для автоматической установки PHPUnit
 * Поддерживает несколько методов установки
 */

// Проверяем, что скрипт запускается из командной строки
if (php_sapi_name() !== 'cli') {
    die("Этот скрипт должен запускаться из командной строки\n");
}

echo "🔧 PHPUnit Installation Helper\n";
echo "==============================\n\n";

// Определяем пути
$testsDir = __DIR__;
$projectDir = dirname(__DIR__);
$phpunitPhar = $testsDir . DIRECTORY_SEPARATOR . 'phpunit.phar';

/**
 * Проверка системных требований
 */
function checkRequirements()
{
    echo "📋 Проверка системных требований:\n";

    // Проверка версии PHP
    $phpVersion = PHP_VERSION;
    echo "   PHP версия: {$phpVersion} ";
    if (version_compare($phpVersion, '7.3.0', '>=')) {
        echo "✅\n";
    } else {
        echo "❌ (требуется PHP 7.3+)\n";
        return false;
    }

    // Проверка расширений
    $requiredExtensions = ['pdo', 'json', 'mbstring', 'xml', 'dom'];
    foreach ($requiredExtensions as $ext) {
        echo "   Расширение {$ext}: ";
        if (extension_loaded($ext)) {
            echo "✅\n";
        } else {
            echo "❌\n";
        }
    }

    // Проверка curl/openssl для загрузки
    echo "   cURL: ";
    if (extension_loaded('curl')) {
        echo "✅\n";
    } else {
        echo "❌\n";
    }

    echo "\n";
    return true;
}

/**
 * Проверка наличия Composer
 */
function checkComposer()
{
    echo "🔍 Проверка Composer:\n";

    // Проверяем composer в PATH
    $output = [];
    $returnCode = 0;
    exec('composer --version 2>&1', $output, $returnCode);

    if ($returnCode === 0) {
        echo "   ✅ Composer найден: " . implode(' ', $output) . "\n";
        return true;
    } else {
        echo "   ❌ Composer не найден в PATH\n";

        // Проверяем composer.phar в текущей директории
        if (file_exists('composer.phar')) {
            echo "   ✅ composer.phar найден в текущей директории\n";
            return 'phar';
        }

        return false;
    }
}

/**
 * Проверка существующих установок PHPUnit
 */
function checkExistingPHPUnit($projectDir, $phpunitPhar)
{
    echo "🔍 Поиск существующих установок PHPUnit:\n";

    $found = [];

    // Проверяем vendor/bin/phpunit
    $vendorPhpunit = $projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';
    if (file_exists($vendorPhpunit) || file_exists($vendorPhpunit . '.bat')) {
        $found[] = 'composer';
        echo "   ✅ Найден через Composer: {$vendorPhpunit}\n";
    }

    // Проверяем phpunit.phar
    if (file_exists($phpunitPhar)) {
        $found[] = 'phar';
        echo "   ✅ Найден PHAR файл: {$phpunitPhar}\n";
    }

    // Проверяем глобальную установку
    $output = [];
    $returnCode = 0;
    exec('phpunit --version 2>&1', $output, $returnCode);
    if ($returnCode === 0) {
        $found[] = 'global';
        echo "   ✅ Найден глобально: " . implode(' ', $output) . "\n";
    }

    if (empty($found)) {
        echo "   ❌ PHPUnit не найден\n";
    }

    echo "\n";
    return $found;
}

/**
 * Установка PHPUnit через Composer
 */
function installViaComposer($projectDir, $composerType = true)
{
    echo "📦 Установка PHPUnit через Composer:\n";

    $composerCmd = ($composerType === 'phar') ? 'php composer.phar' : 'composer';

    // Переходим в директорию проекта
    $oldDir = getcwd();
    chdir($projectDir);

    try {
        // Создаем или обновляем composer.json
        $composerJson = 'composer.json';
        $config = [];

        if (file_exists($composerJson)) {
            $config = json_decode(file_get_contents($composerJson), true);
            if (!$config) {
                echo "   ⚠️  composer.json поврежден, создаем новый\n";
                $config = [];
            }
        }

        // Добавляем PHPUnit в require-dev
        if (!isset($config['require-dev'])) {
            $config['require-dev'] = [];
        }

        if (!isset($config['require-dev']['phpunit/phpunit'])) {
            $config['require-dev']['phpunit/phpunit'] = '^9.0';
            echo "   📝 Добавляем PHPUnit в composer.json\n";
        }

        // Устанавливаем базовые поля если их нет
        if (!isset($config['name'])) {
            $config['name'] = 'local/game-project';
        }
        if (!isset($config['type'])) {
            $config['type'] = 'project';
        }

        // Сохраняем composer.json
        file_put_contents($composerJson, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Запускаем composer install
        echo "   🔄 Запуск composer install --dev\n";
        $output = [];
        $returnCode = 0;
        exec("{$composerCmd} install --dev --no-interaction 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            echo "   ✅ PHPUnit установлен успешно через Composer\n";
            echo "   📍 Путь: vendor/bin/phpunit\n";
            return true;
        } else {
            echo "   ❌ Ошибка установки через Composer:\n";
            foreach ($output as $line) {
                echo "      {$line}\n";
            }
            return false;
        }
    } catch (Exception $e) {
        echo "   ❌ Исключение: " . $e->getMessage() . "\n";
        return false;
    } finally {
        chdir($oldDir);
    }
}

/**
 * Загрузка PHPUnit PHAR файла
 */
function downloadPHPUnitPhar($phpunitPhar)
{
    echo "📥 Загрузка PHPUnit PHAR:\n";

    $url = 'https://phar.phpunit.de/phpunit-9.phar';
    echo "   🌐 URL: {$url}\n";
    echo "   📁 Путь: {$phpunitPhar}\n";

    // Метод 1: file_get_contents с контекстом
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: PHPUnit Installer/1.0',
            'timeout' => 60
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    echo "   🔄 Загрузка... ";
    $data = @file_get_contents($url, false, $context);

    if ($data === false) {
        echo "❌\n";
        echo "   ⚠️  file_get_contents не удалось, пробуем cURL\n";

        // Метод 2: cURL
        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHPUnit Installer/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($data === false || $httpCode !== 200) {
                echo "❌ cURL также не удался (HTTP {$httpCode})\n";
                return false;
            }
        } else {
            echo "❌ cURL недоступен\n";
            return false;
        }
    }

    echo "✅\n";

    // Сохраняем файл
    if (file_put_contents($phpunitPhar, $data) === false) {
        echo "   ❌ Не удалось сохранить файл\n";
        return false;
    }

    // Проверяем размер файла
    $size = filesize($phpunitPhar);
    if ($size < 1024 * 1024) { // менее 1MB
        echo "   ❌ Файл слишком мал ({$size} байт), возможно загрузка не удалась\n";
        unlink($phpunitPhar);
        return false;
    }

    echo "   ✅ Файл сохранен (" . round($size / 1024 / 1024, 2) . " MB)\n";

    // Проверяем работоспособность
    $output = [];
    $returnCode = 0;
    exec("php \"{$phpunitPhar}\" --version 2>&1", $output, $returnCode);

    if ($returnCode === 0) {
        echo "   ✅ PHPUnit PHAR работает: " . implode(' ', $output) . "\n";
        return true;
    } else {
        echo "   ❌ PHPUnit PHAR не работает:\n";
        foreach ($output as $line) {
            echo "      {$line}\n";
        }
        unlink($phpunitPhar);
        return false;
    }
}

/**
 * Основная функция установки
 */
function main()
{
    global $testsDir, $projectDir, $phpunitPhar;

    // Проверяем требования
    if (!checkRequirements()) {
        echo "❌ Системные требования не выполнены\n";
        exit(1);
    }

    // Проверяем существующие установки
    $existing = checkExistingPHPUnit($projectDir, $phpunitPhar);
    if (!empty($existing)) {
        echo "🤔 PHPUnit уже установлен. Продолжить установку? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $choice = trim(fgets($handle));
        fclose($handle);

        if (strtolower($choice) !== 'y') {
            echo "Установка отменена\n";
            exit(0);
        }
        echo "\n";
    }

    // Проверяем Composer
    $composerAvailable = checkComposer();
    echo "\n";

    // Предлагаем варианты установки
    echo "🎯 Выберите метод установки:\n";
    echo "1. Через Composer (рекомендуется)\n";
    echo "2. Скачать PHAR файл\n";
    echo "3. Оба метода\n";
    echo "0. Выход\n";
    echo "\nВаш выбор (1-3): ";

    $handle = fopen("php://stdin", "r");
    $choice = trim(fgets($handle));
    fclose($handle);

    echo "\n";

    $success = false;

    switch ($choice) {
        case '1':
            if ($composerAvailable) {
                $success = installViaComposer($projectDir, $composerAvailable);
            } else {
                echo "❌ Composer недоступен\n";
            }
            break;

        case '2':
            $success = downloadPHPUnitPhar($phpunitPhar);
            break;

        case '3':
            if ($composerAvailable) {
                $success = installViaComposer($projectDir, $composerAvailable);
            }
            if (!$success || $choice === '3') {
                $success = downloadPHPUnitPhar($phpunitPhar) || $success;
            }
            break;

        case '0':
            echo "Выход\n";
            exit(0);

        default:
            echo "❌ Неверный выбор\n";
            exit(1);
    }

    echo "\n";
    echo "================================\n";

    if ($success) {
        echo "🎉 Установка завершена успешно!\n\n";

        echo "💡 Как использовать:\n";
        if (file_exists($projectDir . '/vendor/bin/phpunit')) {
            echo "   vendor/bin/phpunit tests/\n";
            echo "   php run_tests.php\n";
        }
        if (file_exists($phpunitPhar)) {
            echo "   php {$phpunitPhar} tests/\n";
        }
        echo "   run_tests.bat (Windows)\n";

        echo "\n📊 Для запуска тестов:\n";
        echo "   cd " . dirname(__DIR__) . "\n";
        echo "   php tests/run_tests.php\n";

    } else {
        echo "❌ Установка не удалась\n\n";

        echo "💡 Альтернативные варианты:\n";
        echo "1. Установите Composer: https://getcomposer.org/\n";
        echo "2. Скачайте вручную: https://phar.phpunit.de/phpunit-9.phar\n";
        echo "3. Глобальная установка: composer global require phpunit/phpunit\n";

        exit(1);
    }
}

// Запуск основной функции
main();
