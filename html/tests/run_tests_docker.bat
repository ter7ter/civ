@echo off
REM Скрипт для запуска PHPUnit тестов внутри Docker-контейнера
REM Использует docker compose exec для выполнения run_tests.php в сервисе php

docker compose exec php php /var/www/html/tests/run_tests.php %*
