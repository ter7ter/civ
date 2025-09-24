@echo off
chcp 65001 >nul
echo.
echo Testing fixed and working tests
echo ===============================

cd /d "%~dp0.."

echo Checking PHP...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP not found!
    pause
    exit /b 1
)
echo PHP found

echo.
echo Checking PHPUnit...
if exist "vendor\bin\phpunit.bat" (
    set "PHPUNIT=vendor\bin\phpunit.bat"
    echo Using Composer PHPUnit
) else if exist "tests\phpunit.phar" (
    set "PHPUNIT=php tests\phpunit.phar"
    echo Using phpunit.phar
) else (
    phpunit --version >nul 2>&1
    if %errorlevel% equ 0 (
        set "PHPUNIT=phpunit"
        echo Using global PHPUnit
    ) else (
        echo ERROR: PHPUnit not found!
        pause
        exit /b 1
    )
)

echo.
echo Running tests...
echo ===============================

set PASSED=0
set FAILED=0

echo.
echo [1/11] CreateGameSimpleTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\CreateGameSimpleTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [2/11] DatabaseConfigTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\DatabaseConfigTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [3/11] UserTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\UserTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [4/11] MessageTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\MessageTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [5/11] PlanetTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\PlanetTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [6/11] ResourceTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\ResourceTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [7/11] BuildingTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\BuildingTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [8/11] CityTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\CityTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [9/11] ResearchTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\ResearchTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [10/11] UnitTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\UnitTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [11/11] EditGameTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\EditGameTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [12/13] OpenGameTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\OpenGameTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo [13/13] CreateGameTest...
%PHPUNIT% --configuration tests\phpunit.xml --no-coverage tests\unit\CreateGameTest.php
if %errorlevel% equ 0 (
    echo PASSED
    set /a PASSED+=1
) else (
    echo FAILED
    set /a FAILED+=1
)

echo.
echo ===============================
echo RESULTS:
echo Passed: %PASSED%
echo Failed: %FAILED%

if %FAILED% equ 0 (
    echo.
    echo ALL TESTS PASSED!
    pause
    exit /b 0
) else (
    echo.
    echo SOME TESTS FAILED!
    pause
    exit /b 1
)
