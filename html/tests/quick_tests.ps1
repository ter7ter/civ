# Упрощенный PowerShell скрипт для запуска рабочих тестов
# Использование: .\quick_tests.ps1

Write-Host ""
Write-Host "🧪 Быстрый запуск исправленных тестов" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan

# Переходим в корневую директорию проекта
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
Set-Location $ProjectRoot

# Проверяем PHP
try {
    & php --version | Out-Null
    if ($LASTEXITCODE -ne 0) { throw }
    Write-Host "✅ PHP найден" -ForegroundColor Green
} catch {
    Write-Host "❌ PHP не найден!" -ForegroundColor Red
    exit 1
}

# Определяем PHPUnit
$phpunitCmd = ""
if (Test-Path "vendor\bin\phpunit.bat") {
    $phpunitCmd = "vendor\bin\phpunit.bat"
} elseif (Test-Path "tests\phpunit.phar") {
    $phpunitCmd = "php tests\phpunit.phar"
} else {
    try {
        & phpunit --version | Out-Null
        if ($LASTEXITCODE -eq 0) { $phpunitCmd = "phpunit" }
    } catch {}
}

if (!$phpunitCmd) {
    Write-Host "❌ PHPUnit не найден!" -ForegroundColor Red
    exit 1
}

Write-Host "✅ PHPUnit: $phpunitCmd" -ForegroundColor Green
Write-Host ""

# Рабочие тесты
$tests = @(
    "tests\unit\CreateGameSimpleTest.php",
    "tests\unit\DatabaseConfigTest.php",
    "tests\unit\UserTest.php",
    "tests\unit\MessageTest.php",
    "tests\unit\PlanetTest.php",
    "tests\unit\ResourceTest.php"
)

$passed = 0
$failed = 0

foreach ($test in $tests) {
    Write-Host "🔄 Запуск: $test" -ForegroundColor Yellow

    try {
        & cmd /c "$phpunitCmd --configuration tests\phpunit.xml --no-coverage `"$test`" 2>&1" | Out-Host

        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ ПРОЙДЕН" -ForegroundColor Green
            $passed++
        } else {
            Write-Host "❌ НЕ ПРОЙДЕН" -ForegroundColor Red
            $failed++
        }
    } catch {
        Write-Host "❌ ОШИБКА: $_" -ForegroundColor Red
        $failed++
    }

    Write-Host ""
}

# Итог
Write-Host "=================================" -ForegroundColor Cyan
Write-Host "📊 РЕЗУЛЬТАТЫ:" -ForegroundColor Cyan
Write-Host "Пройдено: $passed" -ForegroundColor Green
Write-Host "Провалено: $failed" -ForegroundColor Red

if ($failed -eq 0) {
    Write-Host ""
    Write-Host "🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ!" -ForegroundColor Green
    exit 0
} else {
    Write-Host ""
    Write-Host "⚠️ ЕСТЬ ПРОБЛЕМЫ" -ForegroundColor Yellow
    exit 1
}
