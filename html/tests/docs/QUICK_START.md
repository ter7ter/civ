# 🚀 Быстрый старт тестирования

## Установка PHPUnit (выберите любой способ)

### 🎯 Самый простой способ
```bash
# Запустите интерактивный установщик
php tests/install_phpunit.php
```

### 💻 Windows пользователи
```batch
# Двойной клик или из командной строки
run_tests.bat download-phpunit
```

### 🔧 Через Composer
```bash
composer require --dev phpunit/phpunit
```

## Запуск тестов

### 🖱️ Самый быстрый способ (Windows)
- Двойной клик на `run_tests.bat`

### ⌨️ Из командной строки
```bash
# Все тесты
php run_tests.php

# С JavaScript тестами
php run_tests.php --with-js

# С покрытием кода
php run_tests.php --coverage
```

### 🔍 Конкретные тесты
```bash
# Только модульные тесты
php run_tests.php --unit-only

# Только один тест
php run_tests.php --filter testCreateBasicGame
```

## Проблемы? 🔧

### PHPUnit не найден?
```bash
# Проверить что не хватает
run_tests.bat check-deps

# Установить автоматически
php tests/install_phpunit.php
```

### Ошибки загрузки?
1. Проверьте интернет
2. Попробуйте PowerShell: `.\download_phpunit.ps1`
3. Скачайте вручную: https://phar.phpunit.de/phpunit-9.phar

### Очистка результатов
```bash
run_tests.bat clean
```

## Результаты тестов 📊

После запуска смотрите:
- `results/junit.xml` - JUnit отчет
- `coverage-html/index.html` - покрытие кода
- `js/creategame.test.html` - JavaScript тесты

## Всего 3 шага:
1. `php tests/install_phpunit.php` - установить PHPUnit
2. `run_tests.bat` - запустить тесты
3. Открыть `coverage-html/index.html` - посмотреть результаты

---
**Нужна подробная справка?** → Откройте `README.md`
