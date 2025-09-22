# Тесты для системы создания игр

## Обзор

Этот каталог содержит полный набор тестов для функции создания игры (`index.php?method=creategame`). Тесты покрывают как серверную (PHP), так и клиентскую (JavaScript) части системы.

## Структура тестов

```
tests/
├── unit/                    # Модульные тесты
│   └── CreateGameTest.php   # Тесты логики создания игры
├── integration/             # Интеграционные тесты
│   └── CreateGameIntegrationTest.php # Тесты полного процесса
├── js/                      # JavaScript тесты
│   └── creategame.test.html # QUnit тесты для UI
├── results/                 # Результаты тестов (генерируется)
├── coverage-html/           # HTML отчет покрытия (генерируется)
├── TestBase.php            # Базовый класс для тестов
├── bootstrap.php           # Инициализация тестового окружения
├── phpunit.xml             # Конфигурация PHPUnit
├── run_tests.php           # Основной скрипт запуска
├── run_tests.bat           # Batch файл для Windows
└── README.md               # Этот файл
```

## Быстрый старт

### Windows (двойной клик)
1. Двойной клик на `run_tests.bat`
2. Следуйте инструкциям на экране

### Командная строка (Windows)
```batch
# Все PHP тесты
run_tests.bat

# С JavaScript тестами
run_tests.bat --with-js

# Только unit тесты с подробным выводом
run_tests.bat --unit-only --verbose

# С отчетом покрытия кода
run_tests.bat --coverage
```

### Командная строка (Linux/Mac)
```bash
# Все PHP тесты
php run_tests.php

# С JavaScript тестами
php run_tests.php --with-js

# Только integration тесты
php run_tests.php --integration-only

# С отчетом покрытия
php run_tests.php --coverage
```

## Установка зависимостей

### Автоматическая установка PHPUnit

#### Метод 1: Интерактивный установщик (рекомендуется)
```bash
# Универсальный PHP скрипт с выбором методов
php tests/install_phpunit.php
```

#### Метод 2: Через batch файл (Windows)
```batch
# Установка через Composer
run_tests.bat install-phpunit

# Загрузка PHAR файла
run_tests.bat download-phpunit

# Проверка зависимостей
run_tests.bat check-deps
```

#### Метод 3: PowerShell скрипт (Windows)
```powershell
# Загрузка с проверкой
.\download_phpunit.ps1

# С указанием версии
.\download_phpunit.ps1 -Version 10.5.0 -Verify

# Просмотр доступных версий
.\download_phpunit.ps1 -Help
```

### Ручная установка через Composer
```bash
# Создать composer.json (если не существует)
composer init --dev

# Установить PHPUnit
composer require --dev phpunit/phpunit ^9.0

# Запустить тесты
vendor/bin/phpunit --configuration tests/phpunit.xml
```

### Скачать PHPUnit.phar вручную

#### Linux/Mac
```bash
wget https://phar.phpunit.de/phpunit-9.phar -O tests/phpunit.phar
chmod +x tests/phpunit.phar
```

#### Windows (PowerShell)
```powershell
Invoke-WebRequest -Uri "https://phar.phpunit.de/phpunit-9.phar" -OutFile "tests\phpunit.phar"
```

#### Через браузер
1. Перейдите на https://phar.phpunit.de/phpunit-9.phar
2. Сохраните файл как `tests/phpunit.phar`

## Типы тестов

### 1. Модульные тесты (Unit Tests)

**Расположение:** `tests/unit/CreateGameTest.php`

**Что тестируют:**
- Валидацию входных данных
- Обработку ошибок
- Безопасность (XSS, SQL-инъекции)
- Граничные случаи

**Примеры тестов:**
- ✅ Создание базовой игры с корректными данными
- ✅ Валидация пустого названия игры
- ✅ Проверка минимального/максимального количества игроков
- ✅ Проверка размеров карты
- ✅ Дублирующиеся имена игроков
- ✅ HTML/JavaScript инъекции

### 2. Интеграционные тесты (Integration Tests)

**Расположение:** `tests/integration/CreateGameIntegrationTest.php`

**Что тестируют:**
- Полный процесс создания игры
- Взаимодействие с базой данных
- Генерацию карты и начальных условий
- Создание пользователей с корректными параметрами

**Примеры тестов:**
- ✅ Создание игры и проверка записей в БД
- ✅ Генерация уникальных цветов игроков
- ✅ Корректность порядка ходов
- ✅ Начальные параметры игроков
- ✅ Производительность с максимальным количеством игроков

### 3. JavaScript тесты (Frontend Tests)

**Расположение:** `tests/js/creategame.test.html`

**Что тестируют:**
- Добавление/удаление игроков
- Валидацию формы на клиенте
- Генерацию цветов
- UI интерактивность

**Запуск JavaScript тестов:**
1. Откройте `tests/js/creategame.test.html` в браузере
2. Или используйте `--with-js` флаг в скриптах запуска

**Примеры тестов:**
- ✅ Добавление игроков (до максимума 16)
- ✅ Удаление игроков (минимум 2)
- ✅ Клиентская валидация формы
- ✅ Генерация уникальных цветов
- ✅ Обновление placeholder'ов

## Параметры запуска

| Параметр | Описание |
|----------|----------|
| `--unit-only` | Запуск только модульных тестов |
| `--integration-only` | Запуск только интеграционных тестов |
| `--with-js` | Включить JavaScript тесты |
| `--coverage` | Генерировать отчет о покрытии кода |
| `--verbose` | Подробный вывод |
| `--stop-on-failure` | Остановиться при первой ошибке |
| `--filter <pattern>` | Запустить только тесты, соответствующие паттерну |
| `--help` | Показать справку |

## Результаты тестов

После запуска тестов генерируются следующие файлы:

### Отчеты
- `results/junit.xml` - JUnit XML отчет
- `results/testdox.html` - TestDox HTML отчет  
- `results/testdox.txt` - TestDox текстовый отчет
- `coverage-html/index.html` - HTML отчет покрытия кода

### Логи
- `results/php_errors.log` - Лог PHP ошибок
- Консольный вывод с детальной информацией

### Пример успешного запуска
```
🚀 Запуск тестов для системы создания игр
============================================================
Время запуска: 2024-01-15 14:30:00
PHP версия: 8.1.0
Проект: I:\pr1\html
Типы тестов: Unit, Integration

🧪 Запуск PHP тестов...
==================================================
PHPUnit 9.6.3 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.1.0
Configuration: I:\pr1\html\tests\phpunit.xml

..........................                          26 / 26 (100%)

Time: 00:02.445, Memory: 10.00 MB

OK (26 tests, 89 assertions)

✅ PHP тесты завершены успешно (2.45s)

📊 ИТОГОВЫЙ ОТЧЕТ
============================================================
PHP           : ✅ ПРОЙДЕН (2.45s)
Общее время выполнения: 2.48s
Всего наборов тестов: 1
Пройдено: 1
Провалено: 0

🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!
```

## Тестовые сценарии

### Позитивные тесты
1. **Создание базовой игры** - корректные данные, 2 игрока
2. **Максимум игроков** - 16 игроков с уникальными именами
3. **Разные размеры карт** - от 50x50 до 500x500
4. **Все типы ходов** - concurrently, byturn, onewindow

### Негативные тесты  
1. **Валидация полей** - пустые и некорректные значения
2. **Безопасность** - XSS, SQL-инъекции
3. **Граничные случаи** - превышение лимитов
4. **Дублирование** - одинаковые имена игроков

### UI тесты
1. **Интерактивность** - добавление/удаление игроков
2. **Валидация формы** - проверка на клиенте
3. **Визуализация** - корректность цветов игроков

## Отладка тестов

### Запуск отдельного теста
```bash
# Конкретный тест
php run_tests.php --filter testCreateBasicGame

# Класс тестов
php run_tests.php --filter CreateGameTest

# С остановкой на первой ошибке
php run_tests.php --stop-on-failure --verbose
```

### Анализ ошибок
1. Проверьте `results/php_errors.log`
2. Запустите с `--verbose` флагом
3. Используйте `--stop-on-failure` для детального анализа

### Распространенные проблемы

**PHPUnit не найден:**
```bash
# Интерактивная установка (рекомендуется)
php tests/install_phpunit.php

# Установить через Composer
composer require --dev phpunit/phpunit

# Скачать PHAR (Windows)
run_tests.bat download-phpunit
# или PowerShell:
.\download_phpunit.ps1

# Проверить что именно отсутствует
run_tests.bat check-deps
```

**Проблемы с загрузкой:**
- Проверьте подключение к интернету
- Отключите антивирус временно
- Используйте VPN если есть блокировки
- Попробуйте разные методы загрузки

**Ошибки PowerShell:**
```powershell
# Разрешить выполнение скриптов
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser

# Или запустить напрямую
powershell -ExecutionPolicy Bypass -File .\download_phpunit.ps1
```

**Ошибки базы данных:**
- Тесты используют SQLite в памяти
- Проверьте наличие PDO и SQLite расширений PHP

**JavaScript тесты не работают:**
- Убедитесь что jQuery загружается
- Откройте браузерную консоль для отладки
- Проверьте пути к скриптам

## Настройка CI/CD

### GitHub Actions
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: pdo, sqlite
    - name: Run tests
      run: php tests/run_tests.php --coverage
```

### Jenkins
```groovy
pipeline {
    agent any
    stages {
        stage('Test') {
            steps {
                sh 'php tests/run_tests.php --coverage'
                publishHTML([
                    allowMissing: false,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: 'tests/coverage-html',
                    reportFiles: 'index.html',
                    reportName: 'Coverage Report'
                ])
            }
        }
    }
}
```

## Расширение тестов

### Добавление нового теста
1. Создайте метод в соответствующем классе
2. Используйте префикс `test` в названии
3. Добавьте assertions для проверки результатов

```php
public function testNewFeature(): void
{
    // Подготовка данных
    $data = ['key' => 'value'];
    
    // Выполнение тестируемого кода
    $result = someFunction($data);
    
    // Проверка результатов  
    $this->assertTrue($result);
    $this->assertEquals('expected', $result['key']);
}
```

### Создание нового тестового класса
```php
<?php
require_once __DIR__ . '/../TestBase.php';

class NewFeatureTest extends TestBase 
{
    protected function setUp(): void
    {
        parent::setUp();
        // Подготовка для каждого теста
    }
    
    public function testSomething(): void
    {
        // Ваш тест
    }
}
```

## Метрики качества

### Покрытие кода
- **Цель:** > 80% покрытия для критических функций
- **Отчет:** `coverage-html/index.html`
- **Команда:** `--coverage` флаг

### Производительность
- **Лимит времени:** < 5 секунд для создания игры
- **Лимит памяти:** < 256MB для тестов
- **Мониторинг:** встроен в интеграционные тесты

### Качество кода
- **Стандарт:** PSR-12 для PHP
- **Линтер:** ESLint для JavaScript  
- **Статический анализ:** PHPStan (рекомендуется)

## Файлы установки и поддержки

### Скрипты установки
- `install_phpunit.php` - Интерактивный установщик PHPUnit
- `download_phpunit.ps1` - PowerShell скрипт для загрузки
- `run_tests.bat` - Batch файл с командами установки
- `bootstrap.php` - Автоматический поиск PHPUnit

### Устранение неполадок

#### Команды диагностики
```bash
# Проверка системы
run_tests.bat check-deps
php tests/install_phpunit.php

# Проверка PHPUnit
php tests/phpunit.phar --version
vendor/bin/phpunit --version
```

#### Очистка и переустановка
```bash
# Очистка результатов
run_tests.bat clean

# Удаление PHPUnit
del tests\phpunit.phar
rmdir /s vendor

# Переустановка
php tests/install_phpunit.php
```

## Поддержка

### Контакты
- **Разработчик:** Команда разработки игры
- **Документация:** `docs/` папка проекта
- **Issues:** GitHub Issues или внутренняя система

## Конфигурация базы данных для тестов

### Тестовые константы БД

Тесты используют отдельные константы для подключения к БД, чтобы не конфликтовать с основным проектом:

```php
// Тестовые константы (автоматически определяются)
TEST_DB_HOST     = 'localhost'
TEST_DB_USER     = 'test_user' 
TEST_DB_PASS     = 'test_pass'
TEST_DB_NAME     = 'test_db'
TEST_DB_PORT     = 3306

// Основные константы проекта (не затрагиваются)
DB_HOST          = ваши_настройки
DB_USER          = ваши_настройки
// и т.д.
```

### Настройка тестовой БД

#### Через переменные окружения
```bash
export TEST_DB_HOST=localhost
export TEST_DB_USER=test_user
export TEST_DB_PASS=test_password
export TEST_DB_NAME=test_database
export TEST_DB_PORT=3306
```

#### Через phpunit.xml
```xml
<php>
    <const name="TEST_DB_HOST" value="localhost"/>
    <const name="TEST_DB_USER" value="test_user"/>
    <const name="TEST_DB_PASS" value="test_pass"/>
    <const name="TEST_DB_NAME" value="test_db"/>
    <const name="TEST_DB_PORT" value="3306"/>
</php>
```

#### По умолчанию
- Тесты используют SQLite в памяти
- Не требуют внешней БД
- Автоматически создают все таблицы
- Полностью изолированы от основного проекта

### Тестовые моки

Система включает моки для основных классов:

```php
TestMyDB    - мок класса MyDB с тестовыми константами
TestGame    - мок класса Game для тестов
TestUser    - мок класса User для тестов
```

#### Автоматическая инициализация
```php
// В начале каждого теста автоматически вызывается
initializeTestEnvironment();
```

#### Ручная настройка (если нужно)
```php
setupTestDatabase();  // Настройка моков БД
setupTestClasses();   // Настройка моков классов
TestMyDB::resetTestDatabase(); // Очистка данных
```

### Полезные ссылки
- [PHPUnit документация](https://phpunit.de/documentation.html)
- [QUnit документация](https://qunitjs.com/)
- [Composer документация](https://getcomposer.org/doc/)
- [PowerShell документация](https://docs.microsoft.com/powershell/)
- [Тестирование в PHP](https://www.php.net/manual/en/features.testing.php)

---

*Последнее обновление: 2024-01-15*  
*Версия тестов: 1.0.0*