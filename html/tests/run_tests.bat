@echo off
set XDEBUG_MODE=coverage
chcp 65001 >nul
setlocal enabledelayedexpansion

:: Batch файл для запуска тестов на Windows
:: Использование: run_tests.bat [опции]

echo.
echo 🚀 Запуск тестов для системы создания игр (Windows)
echo ================================================================

:: Определяем пути
set "SCRIPT_DIR=%~dp0"
set "PROJECT_ROOT=%SCRIPT_DIR%.."
set "PHP_SCRIPT=%SCRIPT_DIR%run_tests.php"

:: Проверяем наличие PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP не найден в PATH
    echo    Убедитесь, что PHP установлен и добавлен в переменную PATH
    echo    Скачайте PHP с https://windows.php.net/download/
    pause
    exit /b 1
)

:: Проверяем наличие скрипта
if not exist "%PHP_SCRIPT%" (
    echo ❌ Файл run_tests.php не найден: %PHP_SCRIPT%
    pause
    exit /b 1
)

:: Показываем информацию о системе
echo Система: %OS% %PROCESSOR_ARCHITECTURE%
for /f "tokens=2 delims= " %%i in ('php --version ^| findstr /R "^PHP"') do (
    echo PHP версия: %%i
    goto :php_version_done
)
:php_version_done

echo Проект: %PROJECT_ROOT%
echo.

:: Проверяем параметры командной строки
set "SHOW_HELP=false"
set "ARGS="

:parse_args
if "%~1"=="" goto :args_done
if /i "%~1"=="--help" set "SHOW_HELP=true"
if /i "%~1"=="-h" set "SHOW_HELP=true"
if /i "%~1"=="help" set "SHOW_HELP=true"
if /i "%~1"=="/?" set "SHOW_HELP=true"
set "ARGS=!ARGS! %~1"
shift
goto :parse_args

:args_done

:: Показываем справку если нужно
if "%SHOW_HELP%"=="true" (
    echo.
    echo 🛠️  СПРАВКА ПО ЗАПУСКУ ТЕСТОВ
    echo ================================================================
    echo Использование: run_tests.bat [опции]
    echo.
    echo ОПЦИИ:
    echo   --unit-only         Запуск только unit тестов
    echo   --integration-only  Запуск только integration тестов
    echo   --with-js           Включить JavaScript тесты
    echo   --coverage          Генерировать отчет о покрытии кода
    echo   --verbose, -v       Подробный вывод
    echo   --stop-on-failure   Остановиться при первой ошибке
    echo   --filter ^<pattern^>  Фильтр тестов по имени/паттерну
    echo   --help, -h          Показать эту справку
    echo.
    echo ПРИМЕРЫ:
    echo   run_tests.bat                    # Все PHP тесты
    echo   run_tests.bat --with-js          # Все тесты включая JS
    echo   run_tests.bat --unit-only -v     # Только unit тесты, подробно
    echo   run_tests.bat --coverage         # С отчетом покрытия
    echo   run_tests.bat --filter CreateGame # Только тесты CreateGame
    echo.
    echo ДОПОЛНИТЕЛЬНЫЕ КОМАНДЫ:
    echo   run_tests.bat install-phpunit    # Установить PHPUnit через Composer
    echo   run_tests.bat download-phpunit   # Скачать phpunit.phar
    echo   run_tests.bat clean              # Очистить результаты тестов
    echo   run_tests.bat check-deps         # Проверить зависимости
    echo ================================================================
    pause
    exit /b 0
)

:: Специальная команда для проверки зависимостей
if /i "%~1"=="check-deps" (
    echo 🔍 Проверка зависимостей...
    echo ================================================================

    echo Проверка PHP:
    php --version
    if %errorlevel% neq 0 (
        echo ❌ PHP не найден
    ) else (
        echo ✅ PHP найден
    )

    echo.
    echo Проверка PHP расширений:
    php -m | findstr /i "pdo" >nul && echo ✅ PDO || echo ❌ PDO не найдено
    php -m | findstr /i "sqlite" >nul && echo ✅ SQLite || echo ❌ SQLite не найдено
    php -m | findstr /i "json" >nul && echo ✅ JSON || echo ❌ JSON не найдено
    php -m | findstr /i "mbstring" >nul && echo ✅ mbstring || echo ❌ mbstring не найдено

    echo.
    echo Проверка PHPUnit:
    if exist "%PROJECT_ROOT%\vendor\bin\phpunit.bat" (
        echo ✅ PHPUnit найден: vendor\bin\phpunit.bat
    ) else if exist "%SCRIPT_DIR%phpunit.phar" (
        echo ✅ PHPUnit найден: phpunit.phar
    ) else (
        phpunit --version >nul 2>&1
        if %errorlevel% equ 0 (
            echo ✅ PHPUnit найден глобально
        ) else (
            echo ❌ PHPUnit не найден
        )
    )

    echo.
    echo Проверка PowerShell:
    powershell -Command "Get-Host" >nul 2>&1
    if %errorlevel% equ 0 (
        echo ✅ PowerShell доступен
    ) else (
        echo ❌ PowerShell недоступен
    )

    echo.
    echo Проверка curl:
    curl --version >nul 2>&1
    if %errorlevel% equ 0 (
        echo ✅ curl доступен
    ) else (
        echo ❌ curl недоступен
    )

    pause
    exit /b 0
)

:: Специальные команды для установки PHPUnit
if /i "%~1"=="install-phpunit" (
    echo 📦 Установка PHPUnit через Composer...
    echo ================================================================

    :: Проверяем наличие Composer
    composer --version >nul 2>&1
    if %errorlevel% neq 0 (
        echo ❌ Composer не найден
        echo    Скачайте и установите Composer с https://getcomposer.org/
        echo    Или используйте команду: run_tests.bat download-phpunit
        pause
        exit /b 1
    )

    cd /d "%PROJECT_ROOT%"

    :: Создаем composer.json если не существует
    if not exist "composer.json" (
        echo 📝 Создание composer.json...
        echo { > composer.json
        echo   "name": "game-project/tests", >> composer.json
        echo   "description": "Game creation system with tests", >> composer.json
        echo   "type": "project", >> composer.json
        echo   "require": { >> composer.json
        echo     "php": "^7.4 ^|^| ^8.0" >> composer.json
        echo   }, >> composer.json
        echo   "require-dev": { >> composer.json
        echo     "phpunit/phpunit": "^9.0" >> composer.json
        echo   } >> composer.json
        echo } >> composer.json
        echo ✅ composer.json создан
    )

    :: Устанавливаем зависимости
    echo 🔄 Запуск composer install...
    composer install --dev --no-interaction

    if %errorlevel% equ 0 (
        echo ✅ PHPUnit установлен успешно через Composer
        echo    Расположение: vendor\bin\phpunit.bat
    ) else (
        echo ❌ Ошибка установки через Composer
        echo    Попробуйте: run_tests.bat download-phpunit
    )

    pause
    exit /b %errorlevel%
)

if /i "%~1"=="download-phpunit" (
    echo 📥 Скачивание phpunit.phar...
    echo ================================================================

    cd /d "%SCRIPT_DIR%"

    :: Метод 1: PowerShell с Invoke-WebRequest
    echo 🔄 Попытка 1: Использование PowerShell...
    powershell -Command "try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://phar.phpunit.de/phpunit-9.phar' -OutFile 'phpunit.phar' -UserAgent 'Mozilla/5.0' } catch { exit 1 }" >nul 2>&1

    if exist "phpunit.phar" (
        echo ✅ phpunit.phar скачан через PowerShell
        goto :verify_phpunit
    )

    :: Метод 2: curl (если доступен)
    echo 🔄 Попытка 2: Использование curl...
    curl -L -o phpunit.phar https://phar.phpunit.de/phpunit-9.phar >nul 2>&1

    if exist "phpunit.phar" (
        echo ✅ phpunit.phar скачан через curl
        goto :verify_phpunit
    )

    :: Метод 3: bitsadmin (встроенный в Windows)
    echo 🔄 Попытка 3: Использование bitsadmin...
    bitsadmin /transfer "PHPUnit" https://phar.phpunit.de/phpunit-9.phar "%SCRIPT_DIR%phpunit.phar" >nul 2>&1

    if exist "phpunit.phar" (
        echo ✅ phpunit.phar скачан через bitsadmin
        goto :verify_phpunit
    )

    :: Если ничего не получилось
    echo ❌ Автоматическая загрузка не удалась
    echo.
    echo 📋 РУЧНАЯ ЗАГРУЗКА:
    echo 1. Откройте браузер
    echo 2. Перейдите на: https://phar.phpunit.de/phpunit-9.phar
    echo 3. Сохраните файл как: %SCRIPT_DIR%phpunit.phar
    echo.
    echo 📋 АЛЬТЕРНАТИВЫ:
    echo - Установите Composer и выполните: run_tests.bat install-phpunit
    echo - Установите PHPUnit глобально: composer global require phpunit/phpunit
    echo.
    pause
    exit /b 1

    :verify_phpunit
    :: Проверяем что файл рабочий
    echo 🔍 Проверка phpunit.phar...
    php phpunit.phar --version >nul 2>&1
    if %errorlevel% equ 0 (
        echo ✅ phpunit.phar работает корректно
        php phpunit.phar --version
    ) else (
        echo ❌ phpunit.phar поврежден, удаляем...
        del phpunit.phar >nul 2>&1
        echo    Попробуйте другой метод установки
        pause
        exit /b 1
    )

    pause
    exit /b 0
)

if /i "%~1"=="clean" (
    echo 🧹 Очистка результатов тестов...
    echo ================================================================

    if exist "%SCRIPT_DIR%results" (
        rmdir /s /q "%SCRIPT_DIR%results"
        echo ✅ Папка results очищена
    )

    if exist "%SCRIPT_DIR%coverage-html" (
        rmdir /s /q "%SCRIPT_DIR%coverage-html"
        echo ✅ Папка coverage-html очищена
    )

    if exist "%SCRIPT_DIR%temp" (
        rmdir /s /q "%SCRIPT_DIR%temp"
        echo ✅ Папка temp очищена
    )

    if exist "%SCRIPT_DIR%.phpunit.cache" (
        rmdir /s /q "%SCRIPT_DIR%.phpunit.cache"
        echo ✅ PHPUnit cache очищен
    )

    if exist "%SCRIPT_DIR%.phpunit.result.cache" (
        del /q "%SCRIPT_DIR%.phpunit.result.cache"
        echo ✅ PHPUnit result cache очищен
    )

    echo ✅ Результаты тестов очищены
    pause
    exit /b 0
)

:: Основная логика - запуск тестов
echo 🚀 Запуск тестов...
echo ================================================================

:: Проверяем наличие PHPUnit перед запуском
set "PHPUNIT_FOUND=false"

if exist "%PROJECT_ROOT%\vendor\bin\phpunit.bat" (
    set "PHPUNIT_FOUND=true"
    echo ✅ Используем PHPUnit из Composer
) else if exist "%SCRIPT_DIR%phpunit.phar" (
    set "PHPUNIT_FOUND=true"
    echo ✅ Используем phpunit.phar
) else (
    phpunit --version >nul 2>&1
    if %errorlevel% equ 0 (
        set "PHPUNIT_FOUND=true"
        echo ✅ Используем глобальный PHPUnit
    )
)

if "%PHPUNIT_FOUND%"=="false" (
    echo ❌ PHPUnit не найден!
    echo.
    echo 💡 ВАРИАНТЫ УСТАНОВКИ:
    echo    1. run_tests.bat install-phpunit    # Через Composer
    echo    2. run_tests.bat download-phpunit   # Скачать PHAR файл
    echo    3. run_tests.bat check-deps         # Проверить что не хватает
    echo.
    echo 📚 РУЧНАЯ УСТАНОВКА:
    echo    - Composer: composer require --dev phpunit/phpunit
    echo    - Глобально: composer global require phpunit/phpunit
    echo    - Скачать: https://phar.phpunit.de/phpunit-9.phar
    echo.
    pause
    exit /b 1
)

:: Запускаем тесты
php "%PHP_SCRIPT%" %ARGS%

set "PHP_EXIT_CODE=%errorlevel%"

echo.
echo ================================================================

if %PHP_EXIT_CODE% equ 0 (
    echo ✅ Тесты завершены успешно!
) else (
    echo ❌ Тесты завершились с ошибками ^(код: %PHP_EXIT_CODE%^)
)

:: Показываем дополнительную информацию
echo.
echo 📁 Результаты тестов:
if exist "%SCRIPT_DIR%coverage-html\index.html" (
    echo    ✓ Отчет о покрытии: %SCRIPT_DIR%coverage-html\index.html
)
if exist "%SCRIPT_DIR%results\junit.xml" (
    echo    ✓ JUnit отчет: %SCRIPT_DIR%results\junit.xml
)
if exist "%SCRIPT_DIR%results\testdox.html" (
    echo    ✓ TestDox отчет: %SCRIPT_DIR%results\testdox.html
)
if exist "%SCRIPT_DIR%js\creategame.test.html" (
    echo    ✓ JavaScript тесты: %SCRIPT_DIR%js\creategame.test.html
)

echo.
echo 💡 Полезные команды:
echo    run_tests.bat --help           # Справка
echo    run_tests.bat clean            # Очистить результаты
echo    run_tests.bat check-deps       # Проверить зависимости
echo    run_tests.bat install-phpunit  # Установить PHPUnit

:: Пауза только если запущено двойным кликом
echo %cmdcmdline% | find /i "%~0" >nul
if not errorlevel 1 (
    echo.
    echo Нажмите любую клавишу для выхода...
    pause >nul
)

exit /b %PHP_EXIT_CODE%
