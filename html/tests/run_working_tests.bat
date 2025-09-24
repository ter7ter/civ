@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

:: Скрипт для запуска только исправленных и работающих тестов
:: Использование: run_working_tests.bat

echo.
echo 🧪 Запуск исправленных и работающих тестов
echo ================================================================

:: Определяем пути
set "SCRIPT_DIR=%~dp0"
set "PROJECT_ROOT=%SCRIPT_DIR%.."

:: Проверяем наличие PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP не найден в PATH
    echo    Убедитесь, что PHP установлен и добавлен в переменную PATH
    pause
    exit /b 1
)

:: Проверяем наличие PHPUnit
set "PHPUNIT_FOUND=false"
if exist "%PROJECT_ROOT%\vendor\bin\phpunit.bat" (
    set "PHPUNIT_CMD=%PROJECT_ROOT%\vendor\bin\phpunit.bat"
    set "PHPUNIT_FOUND=true"
) else if exist "%SCRIPT_DIR%phpunit.phar" (
    set "PHPUNIT_CMD=php %SCRIPT_DIR%phpunit.phar"
    set "PHPUNIT_FOUND=true"
) else (
    phpunit --version >nul 2>&1
    if %errorlevel% equ 0 (
        set "PHPUNIT_CMD=phpunit"
        set "PHPUNIT_FOUND=true"
    )
)

if "%PHPUNIT_FOUND%"=="false" (
    echo ❌ PHPUnit не найден!
    echo    Установите PHPUnit: run_tests.bat install-phpunit
    pause
    exit /b 1
)

echo ✅ Используем PHPUnit: %PHPUNIT_CMD%
echo.

:: Список рабочих тестов
set WORKING_TESTS[0]=tests\unit\CreateGameSimpleTest.php
set WORKING_TESTS[1]=tests\unit\DatabaseConfigTest.php
set WORKING_TESTS[2]=tests\unit\UserTest.php
set WORKING_TESTS[3]=tests\unit\MessageTest.php
set WORKING_TESTS[4]=tests\unit\PlanetTest.php
set WORKING_TESTS[5]=tests\unit\ResourceTest.php
set WORKING_TESTS[6]=tests\unit\BuildingTest.php
set WORKING_TESTS[7]=tests\unit\GameTest.php

:: Счетчики для статистики
set "TOTAL_TESTS=0"
set "PASSED_TESTS=0"
set "FAILED_TESTS=0"
set "TOTAL_ASSERTIONS=0"
set "TOTAL_TIME=0"

echo 📝 Список тестов для запуска:
echo ----------------------------------------------------------------
for /l %%i in (0,1,7) do (
    if defined WORKING_TESTS[%%i] (
        call set "test_file=%%WORKING_TESTS[%%i]%%"
        echo    ✓ !test_file!
    )
)
echo.

:: Запускаем каждый тест отдельно
echo 🚀 Запуск тестов...
echo ================================================================

for /l %%i in (0,1,7) do (
    if defined WORKING_TESTS[%%i] (
        call set "test_file=%%WORKING_TESTS[%%i]%%"
        echo.
        echo 📁 Запуск: !test_file!
        echo ----------------------------------------------------------------

        cd /d "%PROJECT_ROOT%"
        %PHPUNIT_CMD% --configuration tests\phpunit.xml --no-coverage "!test_file!" > temp_output.txt 2>&1

        set "test_exit_code=!errorlevel!"

        :: Анализируем результат
        if !test_exit_code! equ 0 (
            echo ✅ ПРОЙДЕН
            set /a PASSED_TESTS+=1
        ) else (
            echo ❌ ПРОВАЛЕН ^(код: !test_exit_code!^)
            set /a FAILED_TESTS+=1
        )

        :: Показываем вывод теста
        if exist temp_output.txt (
            type temp_output.txt
            :: Извлекаем статистику из вывода (если возможно)
            for /f "tokens=2 delims=(" %%a in ('findstr /c:"OK (" temp_output.txt 2^>nul') do (
                for /f "tokens=1,3 delims= ," %%b in ("%%a") do (
                    set /a TOTAL_TESTS+=%%b
                    set /a TOTAL_ASSERTIONS+=%%c
                )
            )
            del temp_output.txt >nul 2>&1
        )
    )
)

echo.
echo ================================================================
echo 📊 ИТОГОВАЯ СТАТИСТИКА
echo ================================================================

echo Всего тестовых файлов: 8
echo Пройденных файлов: %PASSED_TESTS%
echo Проваленных файлов: %FAILED_TESTS%

if %TOTAL_TESTS% gtr 0 (
    echo.
    echo Всего тестов: %TOTAL_TESTS%
    echo Всего проверок: %TOTAL_ASSERTIONS%
)

if %FAILED_TESTS% equ 0 (
    echo.
    echo 🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!
    echo    Система готова к работе
) else (
    echo.
    echo ⚠️  НЕКОТОРЫЕ ТЕСТЫ НЕ ПРОЙДЕНЫ
    echo    Проверьте ошибки выше
)

:: Показываем дополнительную информацию
echo.
echo 📁 Результаты тестов:
if exist "%SCRIPT_DIR%results\junit.xml" (
    echo    ✓ JUnit отчет: %SCRIPT_DIR%results\junit.xml
)
if exist "%SCRIPT_DIR%results\testdox.html" (
    echo    ✓ TestDox отчет: %SCRIPT_DIR%results\testdox.html
)

echo.
echo 💡 Дополнительные команды:
echo    run_tests.bat --help           # Полная справка по тестам
echo    run_tests.bat clean            # Очистить результаты
echo    run_working_tests.bat          # Повторить этот запуск

:: Проверяем, запущен ли файл двойным кликом
echo %cmdcmdline% | find /i "%~0" >nul
if not errorlevel 1 (
    echo.
    echo Нажмите любую клавишу для выхода...
    pause >nul
)

:: Возвращаем код ошибки
if %FAILED_TESTS% gtr 0 (
    exit /b 1
) else (
    exit /b 0
)
