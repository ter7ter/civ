@echo off
set PHP_PATH="C:\php\php.exe"
set PHPUNIT_PATH="phpunit.phar"

if exist %PHP_PATH% (
    echo Using PHP from %PHP_PATH%
) else (
    echo PHP executable not found at %PHP_PATH%. Please update PHP_PATH in run_tests.bat.
    exit /b 1
)

if exist %PHPUNIT_PATH% (
    echo Using PHPUnit from %PHPUNIT_PATH%.
) else (
    echo PHPUnit phar not found at %PHPUNIT_PATH%. Please ensure phpunit.phar is in the same directory as run_tests.bat.
    exit /b 1
)

set "RUN_COVERAGE_ONLY="
echo %* | findstr /I /C:"--coverage" > nul && set "RUN_COVERAGE_ONLY=true"

if "%RUN_COVERAGE_ONLY%"=="true" (
    echo Generating code coverage report from previously collected data...
    rem PHPUnit's --coverage-html and --coverage-text options typically require a test run to collect data.
    rem There isn't a direct PHPUnit command to generate reports from a standalone .php coverage file without running tests.
    rem This command will attempt to generate reports, but it might not work as expected if no coverage data was collected in the current run.
    rem A more robust solution would involve a separate PHP script using php-code-coverage library to process coverage.php.
    %PHP_PATH% %PHPUNIT_PATH% --configuration phpunit.xml --coverage-html coverage-html --coverage-text coverage.txt --no-tests
    if %errorlevel% neq 0 (
        echo Failed to generate coverage report. Ensure tests were run previously to generate coverage data (e.g., by running run_tests.bat without --coverage).
        exit /b %errorlevel%
    )
    echo Code coverage report generated in coverage-html and coverage.txt.
) else (
    echo Running PHPUnit tests and collecting raw coverage data...
    %PHP_PATH% %PHPUNIT_PATH% --configuration phpunit.xml --coverage-php coverage.php %*

    if %errorlevel% neq 0 (
        echo PHPUnit tests failed!
        exit /b %errorlevel%
    ) else (
        echo PHPUnit tests passed successfully.
        echo Raw coverage data saved to coverage.php.
        echo To generate HTML/text reports from this data without re-running tests, run: run_tests.bat --coverage
    )
)

echo Test run complete.
