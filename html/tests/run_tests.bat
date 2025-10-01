@echo off
REM Скрипт для запуска PHPUnit тестов
REM Тесты всегда запускаем через докер - без докера не работают таймауты в тестах а другие настройки БД
REM Использует docker compose exec для выполнения run_tests.php в сервисе php

docker compose exec php php /var/www/html/tests/_run.php %*
