# PowerShell скрипт для запуска только исправленных и работающих тестов
# Использование: .\run_working_tests.ps1

param(
    [switch]$Verbose,
    [switch]$Help
)

if ($Help) {
    Write-Host @"

🧪 Скрипт для запуска исправленных тестов
================================================================

ИСПОЛЬЗОВАНИЕ:
    .\run_working_tests.ps1 [-Verbose] [-Help]

ПАРАМЕТРЫ:
    -Verbose    Подробный вывод
    -Help       Показать эту справку

ОПИСАНИЕ:
    Запускает только те тесты, которые были исправлены и
    гарантированно работают без ошибок.

ПРИМЕРЫ:
    .\run_working_tests.ps1              # Обычный запуск
    .\run_working_tests.ps1 -Verbose     # С подробным выводом

================================================================
"@
    exit 0
}

Write-Host ""
Write-Host "🧪 Запуск исправленных и работающих тестов" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan

# Определяем пути
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

# Проверяем наличие PHP
try {
    $phpVersion = & php --version 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP не найден"
    }
    if ($Verbose) {
        Write-Host "✅ PHP найден: $($phpVersion[0])" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ PHP не найден в PATH" -ForegroundColor Red
    Write-Host "   Убедитесь, что PHP установлен и добавлен в переменную PATH" -ForegroundColor Yellow
    exit 1
}

# Поиск PHPUnit
$phpunitCmd = $null
$phpunitFound = $false

if (Test-Path "$ProjectRoot\vendor\bin\phpunit.bat") {
    $phpunitCmd = "$ProjectRoot\vendor\bin\phpunit.bat"
    $phpunitFound = $true
    if ($Verbose) { Write-Host "✅ Используем PHPUnit из Composer" -ForegroundColor Green }
} elseif (Test-Path "$ScriptDir\phpunit.phar") {
    $phpunitCmd = "php `"$ScriptDir\phpunit.phar`""
    $phpunitFound = $true
    if ($Verbose) { Write-Host "✅ Используем phpunit.phar" -ForegroundColor Green }
} else {
    try {
        & phpunit --version 2>$null | Out-Null
        if ($LASTEXITCODE -eq 0) {
            $phpunitCmd = "phpunit"
            $phpunitFound = $true
            if ($Verbose) { Write-Host "✅ Используем глобальный PHPUnit" -ForegroundColor Green }
        }
    } catch {}
}

if (-not $phpunitFound) {
    Write-Host "❌ PHPUnit не найден!" -ForegroundColor Red
    Write-Host "   Установите PHPUnit: .\run_tests.bat install-phpunit" -ForegroundColor Yellow
    exit 1
}

# Список рабочих тестов
$workingTests = @(
    "tests\unit\CreateGameSimpleTest.php",
    "tests\unit\DatabaseConfigTest.php",
    "tests\unit\UserTest.php",
    "tests\unit\MessageTest.php",
    "tests\unit\PlanetTest.php",
    "tests\unit\ResourceTest.php",
    "tests\unit\BuildingTest.php",
    "tests\unit\GameTest.php"
)

# Счетчики для статистики
$totalFiles = $workingTests.Count
$passedFiles = 0
$failedFiles = 0
$totalTests = 0
$totalAssertions = 0
$startTime = Get-Date

Write-Host ""
Write-Host "📝 Список тестов для запуска:" -ForegroundColor White
Write-Host "----------------------------------------------------------------" -ForegroundColor Gray
foreach ($test in $workingTests) {
    Write-Host "   ✓ $test" -ForegroundColor Gray
}
Write-Host ""

# Запускаем каждый тест отдельно
Write-Host "🚀 Запуск тестов..." -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan

Set-Location $ProjectRoot

foreach ($testFile in $workingTests) {
    Write-Host ""
    Write-Host "📁 Запуск: $testFile" -ForegroundColor White
    Write-Host "----------------------------------------------------------------" -ForegroundColor Gray

    $testStartTime = Get-Date

    # Формируем команду
    $fullCommand = "$phpunitCmd --configuration tests\phpunit.xml --no-coverage `"$testFile`""

    if ($Verbose) {
        Write-Host "Команда: $fullCommand" -ForegroundColor DarkGray
    }

    try {
        # Выполняем тест
        $output = & cmd /c "$fullCommand 2>&1"
        $exitCode = $LASTEXITCODE

        if ($Verbose -or $exitCode -ne 0) {
            $output | ForEach-Object { Write-Host "   $_" -ForegroundColor DarkGray }
        }

        # Анализируем результат
        if ($exitCode -eq 0) {
            Write-Host "✅ ПРОЙДЕН" -ForegroundColor Green
            $passedFiles++

            # Извлекаем статистику
            $okLine = $output | Where-Object { $_ -match "OK \((\d+) tests?, (\d+) assertions?\)" }
            if ($okLine) {
                if ($okLine -match "OK \((\d+) tests?, (\d+) assertions?\)") {
                    $totalTests += [int]$matches[1]
                    $totalAssertions += [int]$matches[2]
                }
            }
        } else {
            Write-Host "❌ ПРОВАЛЕН (код: $exitCode)" -ForegroundColor Red
            $failedFiles++
        }

    } catch {
        Write-Host "❌ ОШИБКА ВЫПОЛНЕНИЯ: $_" -ForegroundColor Red
        $failedFiles++
    }

    $testDuration = (Get-Date) - $testStartTime
    if ($Verbose) {
        Write-Host "⏱️  Время выполнения: $($testDuration.TotalSeconds.ToString('F2'))с" -ForegroundColor DarkGray
    }
}

$totalDuration = (Get-Date) - $startTime

Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "📊 ИТОГОВАЯ СТАТИСТИКА" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan

Write-Host "Всего тестовых файлов: $totalFiles" -ForegroundColor White
Write-Host "Пройденных файлов: $passedFiles" -ForegroundColor Green
Write-Host "Проваленных файлов: $failedFiles" -ForegroundColor Red

if ($totalTests -gt 0) {
    Write-Host ""
    Write-Host "Всего тестов: $totalTests" -ForegroundColor White
    Write-Host "Всего проверок: $totalAssertions" -ForegroundColor White
}

Write-Host "Общее время выполнения: $($totalDuration.TotalSeconds.ToString('F2'))с" -ForegroundColor White

if ($failedFiles -eq 0) {
    Write-Host ""
    Write-Host "🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!" -ForegroundColor Green
    Write-Host "   Система готова к работе" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "⚠️  НЕКОТОРЫЕ ТЕСТЫ НЕ ПРОЙДЕНЫ" -ForegroundColor Yellow
    Write-Host "   Проверьте ошибки выше" -ForegroundColor Yellow
}

# Показываем дополнительную информацию о результатах
Write-Host ""
Write-Host "📁 Результаты тестов:" -ForegroundColor White
if (Test-Path "$ScriptDir\results\junit.xml") {
    Write-Host "   ✓ JUnit отчет: $ScriptDir\results\junit.xml" -ForegroundColor Gray
}
if (Test-Path "$ScriptDir\results\testdox.html") {
    Write-Host "   ✓ TestDox отчет: $ScriptDir\results\testdox.html" -ForegroundColor Gray
}

Write-Host ""
Write-Host "💡 Дополнительные команды:" -ForegroundColor White
Write-Host "   .\run_tests.bat --help        # Полная справка по тестам" -ForegroundColor Gray
Write-Host "   .\run_tests.bat clean         # Очистить результаты" -ForegroundColor Gray
Write-Host "   .\run_working_tests.ps1       # Повторить этот запуск" -ForegroundColor Gray

# Возвращаем код ошибки
if ($failedFiles -gt 0) {
    exit 1
} else {
    exit 0
}
