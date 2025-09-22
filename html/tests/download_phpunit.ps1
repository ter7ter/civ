# PowerShell скрипт для загрузки PHPUnit
# Автор: Test System
# Версия: 1.0

param(
    [string]$Version = "9.6.19",
    [string]$OutputPath = "phpunit.phar",
    [switch]$Verify,
    [switch]$Help
)

# Цвета для вывода
$Red = "Red"
$Green = "Green"
$Yellow = "Yellow"
$Blue = "Blue"

function Write-ColorText {
    param([string]$Text, [string]$Color = "White")
    Write-Host $Text -ForegroundColor $Color
}

function Show-Help {
    Write-ColorText "📋 PHPUnit Downloader" $Blue
    Write-ColorText "=====================" $Blue
    Write-Host ""
    Write-Host "Использование: .\download_phpunit.ps1 [параметры]"
    Write-Host ""
    Write-Host "Параметры:"
    Write-Host "  -Version <версия>     Версия PHPUnit (по умолчанию: $Version)"
    Write-Host "  -OutputPath <путь>    Путь сохранения (по умолчанию: phpunit.phar)"
    Write-Host "  -Verify              Проверить загруженный файл"
    Write-Host "  -Help                Показать эту справку"
    Write-Host ""
    Write-Host "Примеры:"
    Write-Host "  .\download_phpunit.ps1"
    Write-Host "  .\download_phpunit.ps1 -Version 10.5.0"
    Write-Host "  .\download_phpunit.ps1 -OutputPath .\tools\phpunit.phar -Verify"
}

function Test-InternetConnection {
    try {
        $response = Invoke-WebRequest -Uri "https://www.google.com" -TimeoutSec 5 -UseBasicParsing
        return $true
    }
    catch {
        return $false
    }
}

function Get-PhpUnitUrl {
    param([string]$Version)

    if ($Version -eq "latest") {
        return "https://phar.phpunit.de/phpunit.phar"
    }
    else {
        return "https://phar.phpunit.de/phpunit-$Version.phar"
    }
}

function Download-PhpUnit {
    param(
        [string]$Url,
        [string]$OutputPath
    )

    Write-ColorText "🔄 Загрузка PHPUnit..." $Blue
    Write-Host "URL: $Url"
    Write-Host "Путь: $OutputPath"
    Write-Host ""

    try {
        # Настраиваем TLS для безопасного соединения
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 -bor [Net.SecurityProtocolType]::Tls11 -bor [Net.SecurityProtocolType]::Tls

        # Создаем WebClient с настройками
        $webClient = New-Object System.Net.WebClient
        $webClient.Headers.Add("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")

        # Обработчик прогресса
        $webClient.DownloadProgressChanged += {
            param($sender, $e)
            $percent = [Math]::Round($e.ProgressPercentage, 0)
            $downloaded = [Math]::Round($e.BytesReceived / 1MB, 2)
            $total = [Math]::Round($e.TotalBytesToReceive / 1MB, 2)

            Write-Progress -Activity "Загрузка PHPUnit" -Status "$percent% завершено" -PercentComplete $percent -CurrentOperation "$downloaded MB из $total MB"
        }

        # Загружаем файл
        $webClient.DownloadFileTaskAsync($Url, $OutputPath).Wait()

        Write-Progress -Completed -Activity "Загрузка PHPUnit"
        Write-ColorText "✅ Загрузка завершена!" $Green

        return $true
    }
    catch {
        Write-ColorText "❌ Ошибка загрузки: $($_.Exception.Message)" $Red
        return $false
    }
    finally {
        if ($webClient) {
            $webClient.Dispose()
        }
    }
}

function Verify-PhpUnit {
    param([string]$FilePath)

    Write-ColorText "🔍 Проверка PHPUnit..." $Blue

    # Проверка существования файла
    if (-not (Test-Path $FilePath)) {
        Write-ColorText "❌ Файл не найден: $FilePath" $Red
        return $false
    }

    # Проверка размера файла
    $fileSize = (Get-Item $FilePath).Length
    if ($fileSize -lt 1MB) {
        Write-ColorText "❌ Файл слишком мал ($fileSize байт). Возможно, загрузка не удалась." $Red
        return $false
    }

    Write-Host "Размер файла: $([Math]::Round($fileSize / 1MB, 2)) MB"

    # Проверка через PHP
    try {
        $phpVersion = php --version 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-ColorText "⚠️  PHP не найден. Невозможно проверить работоспособность PHPUnit." $Yellow
            return $true
        }

        Write-Host "Проверка работоспособности через PHP..."
        $phpunitOutput = php $FilePath --version 2>&1

        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ PHPUnit работает корректно!" $Green
            Write-Host $phpunitOutput
            return $true
        }
        else {
            Write-ColorText "❌ PHPUnit не работает: $phpunitOutput" $Red
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Ошибка при проверке: $($_.Exception.Message)" $Red
        return $false
    }
}

function Get-PhpUnitVersions {
    Write-ColorText "📋 Популярные версии PHPUnit:" $Blue
    Write-Host ""
    Write-Host "PHPUnit 9.x (PHP 7.3+):"
    Write-Host "  9.6.19 (стабильная, рекомендуется)"
    Write-Host "  9.6.15"
    Write-Host "  9.5.28"
    Write-Host ""
    Write-Host "PHPUnit 10.x (PHP 8.1+):"
    Write-Host "  10.5.16 (новейшая)"
    Write-Host "  10.4.2"
    Write-Host ""
    Write-Host "PHPUnit 8.x (PHP 7.2+):"
    Write-Host "  8.5.33"
    Write-Host ""
    Write-Host "Для автоматического выбора используйте: -Version latest"
}

# Основная логика

if ($Help) {
    Show-Help
    exit 0
}

Write-ColorText "🚀 PHPUnit Downloader v1.0" $Blue
Write-ColorText "===========================" $Blue
Write-Host ""

# Проверка подключения к интернету
Write-Host "Проверка подключения к интернету..."
if (-not (Test-InternetConnection)) {
    Write-ColorText "❌ Нет подключения к интернету" $Red
    Write-Host "Проверьте сетевое соединение и повторите попытку."
    exit 1
}

Write-ColorText "✅ Подключение к интернету активно" $Green
Write-Host ""

# Получаем URL для загрузки
$downloadUrl = Get-PhpUnitUrl -Version $Version
Write-Host "Версия PHPUnit: $Version"
Write-Host "URL загрузки: $downloadUrl"
Write-Host ""

# Проверяем существование целевого файла
if (Test-Path $OutputPath) {
    $choice = Read-Host "Файл $OutputPath уже существует. Перезаписать? (y/N)"
    if ($choice -ne "y" -and $choice -ne "Y") {
        Write-ColorText "Операция отменена пользователем" $Yellow
        exit 0
    }
    Remove-Item $OutputPath -Force
}

# Создаем директорию если не существует
$directory = Split-Path $OutputPath -Parent
if ($directory -and -not (Test-Path $directory)) {
    New-Item -ItemType Directory -Path $directory -Force | Out-Null
}

# Загружаем PHPUnit
$downloadSuccess = Download-PhpUnit -Url $downloadUrl -OutputPath $OutputPath

if (-not $downloadSuccess) {
    Write-ColorText "❌ Загрузка не удалась" $Red
    Write-Host ""
    Write-ColorText "💡 Возможные решения:" $Blue
    Write-Host "1. Проверьте подключение к интернету"
    Write-Host "2. Попробуйте другую версию: .\download_phpunit.ps1 -Version latest"
    Write-Host "3. Загрузите вручную с https://phar.phpunit.de/"
    Write-Host "4. Используйте Composer: composer require --dev phpunit/phpunit"
    exit 1
}

# Проверяем загруженный файл
if ($Verify -or $true) {
    $verifySuccess = Verify-PhpUnit -FilePath $OutputPath

    if (-not $verifySuccess) {
        Write-ColorText "❌ Проверка не прошла. Удаляем поврежденный файл..." $Red
        Remove-Item $OutputPath -Force -ErrorAction SilentlyContinue
        exit 1
    }
}

Write-Host ""
Write-ColorText "🎉 PHPUnit успешно загружен и готов к использованию!" $Green
Write-Host ""
Write-ColorText "📋 Как использовать:" $Blue
Write-Host "  php $OutputPath --version"
Write-Host "  php $OutputPath tests/"
Write-Host ""
Write-ColorText "💡 Для запуска тестов используйте:" $Blue
Write-Host "  .\run_tests.bat"
Write-Host ""

# Показываем информацию о файле
$fileInfo = Get-Item $OutputPath
Write-Host "Информация о файле:"
Write-Host "  Путь: $($fileInfo.FullName)"
Write-Host "  Размер: $([Math]::Round($fileInfo.Length / 1MB, 2)) MB"
Write-Host "  Создан: $($fileInfo.CreationTime)"

exit 0
