# Тесты для системы создания игр

## Обзор

Этот каталог содержит полный набор тестов для функции создания игры (`index.php?method=creategame`). Тесты покрывают как серверную (PHP), так и клиентскую (JavaScript) части системы.

## Структура тестов

```
tests/
├── unit/                    # Модульные тесты
│   ├── CreateGameTest.php   # Тесты логики создания игры
│   ├── EditGameTest.php     # Тесты логики редактирования игры
│   ├── OpenGameTest.php     # Тесты логики открытия игры
│   └── DatabaseConfigTest.php # Тесты конфигурации БД
├── integration/             # Интеграционные тесты
│   ├── CreateGameIntegrationTest.php # Тесты полного процесса создания
│   ├── EditGameIntegrationTest.php   # Тесты полного процесса редактирования
│   └── MapActionsIntegrationTest.php # Тесты действий на карте
├── js/                      # JavaScript тесты
│   ├── creategame.test.html # QUnit тесты для UI
│   └── map_actions.test.html # QUnit тесты для действий на карте
├── results/                 # Результаты тестов (генерируется)
├── coverage-html/           # HTML отчет покрытия (генерируется)
├── TestBase.php            # Базовый класс для тестов
├── bootstrap.php           # Инициализация тестового окружения
├── phpunit.xml             # Конфигурация PHPUnit
├── run_tests.php           # Основной скрипт запуска
└── README.md               # Этот файл
```

## Быстрый старт

### Windows (двойной клик)
1. Двойной клик на `run_tests_docker.bat`
2. Следуйте инструкциям на экране

### Командная строка (Windows)
```batch
# Все PHP тесты
run_tests_docker.bat

# С JavaScript тестами
run_tests_docker.bat --with-js

# Только unit тесты с подробным выводом
run_tests_docker.bat --unit-only --verbose

# С отчетом покрытия кода
run_tests_docker.bat --coverage
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

#### CreateGameTest.php
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

#### EditGameTest.php
**Расположение:** `tests/unit/EditGameTest.php`

**Что тестируют:**
- Логику редактирования существующих игр
- Валидацию изменений параметров игры
- Проверку прав доступа к редактированию
- Обработку конфликтов при одновременном редактировании

**Примеры тестов:**
- ✅ Изменение названия и параметров игры
- ✅ Добавление/удаление игроков в существующей игре
- ✅ Валидация прав доступа (только создатель может редактировать)
- ✅ Проверка блокировки редактирования после начала игры
- ✅ Обработка конфликтов версий при параллельном редактировании

#### OpenGameTest.php
**Расположение:** `tests/unit/OpenGameTest.php`

**Что тестируют:**
- Логику открытия и загрузки игр
- Проверку доступности игр для пользователей
- Валидацию состояния игры перед открытием
- Обработку ошибок загрузки

**Примеры тестов:**
- ✅ Открытие доступной игры
- ✅ Проверка прав доступа к игре
- ✅ Загрузка данных игры и игроков
- ✅ Обработка несуществующих игр
- ✅ Проверка статуса игры (активная/завершенная)

#### DatabaseConfigTest.php
**Расположение:** `tests/unit/DatabaseConfigTest.php`

**Что тестируют:**
- Конфигурацию подключения к базе данных
- Валидацию параметров подключения
- Обработку ошибок подключения
- Тестовые моки базы данных

**Примеры тестов:**
- ✅ Проверка корректности параметров подключения
- ✅ Тестирование соединения с БД
- ✅ Валидация учетных данных
- ✅ Обработка таймаутов подключения
- ✅ Проверка работы с тестовыми моками

#### CityTest.php
**Расположение:** `tests/unit/CityTest.php`

**Что тестируют:**
- Класс City и всю функциональность городов
- Создание, управление и расчеты городов
- Взаимодействие с реальными игровыми классами
- Размещение населения и производство

**Примеры тестов:**
- ✅ Создание городов через статический метод new_city()
- ✅ Расчет производства (работа, еда, деньги, наука)
- ✅ Размещение и расчет настроения жителей
- ✅ Получение клеток города и возможных зданий
- ✅ Тестирование прибрежных городов
- ✅ Кэширование объектов городов
- ✅ Сохранение и обновление данных в БД

#### BuildingTest.php
**Расположение:** `tests/unit/BuildingTest.php`

**Что тестируют:**
- Класс Building и систему зданий
- Связи между зданиями, городами и типами зданий
- Создание зданий различных типов
- Кэширование и валидацию данных

**Примеры тестов:**
- ✅ Создание зданий различных типов (гранарий, казармы, библиотека и др.)
- ✅ Связи Building-City-User-BuildingType
- ✅ Сохранение и обновление зданий в БД
- ✅ Получение названий и характеристик зданий
- ✅ Кэширование объектов зданий
- ✅ Валидация обязательных полей

#### ResearchTest.php
**Расположение:** `tests/unit/ResearchTest.php`

**Что тестируют:**
- Класс Research и систему исследований
- Связи между исследованиями, пользователями и типами
- Множественные исследования для игроков
- Валидацию типов исследований

**Примеры тестов:**
- ✅ Создание исследований различных типов (гончарное дело, бронза, письменность и др.)
- ✅ Связи Research-User-ResearchType
- ✅ Множественные исследования для одного пользователя
- ✅ Исследования для разных пользователей
- ✅ Сохранение и обновление данных исследований
- ✅ Валидация обязательных полей

#### GameTest.php, UserTest.php, ResourceTest.php, MessageTest.php, UnitTest.php
**Расположение:** `tests/unit/`

**Что тестируют:**
- Основные игровые классы с использованием реальных объектов (без моков)
- Создание, сохранение и управление игровыми объектами
- Кэширование и связи между объектами
- Валидацию и бизнес-логику

**Статистика тестов:**
- ✅ **GameTest**: 10 тестов, 42 утверждения
- ✅ **UserTest**: 13 тестов, 55 утверждений  
- ✅ **ResourceTest**: 6 тестов, 21 утверждение
- ✅ **MessageTest**: 5 тестов, 24 утверждения
- ✅ **UnitTest**: 7 тестов, 27 утверждений

### 2. Интеграционные тесты (Integration Tests)

#### CreateGameIntegrationTest.php
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

#### EditGameIntegrationTest.php
**Расположение:** `tests/integration/EditGameIntegrationTest.php`

**Что тестируют:**
- Полный процесс редактирования существующих игр
- Взаимодействие с БД при изменении параметров
- Проверку прав доступа и валидации
- Обработку конфликтов при параллельном редактировании

**Примеры тестов:**
- ✅ Изменение параметров игры и сохранение в БД
- ✅ Добавление/удаление игроков с обновлением записей
- ✅ Проверка блокировки редактирования после старта игры
- ✅ Обработка конфликтов версий при одновременном доступе
- ✅ Валидация прав доступа к редактированию

#### MapActionsIntegrationTest.php
**Расположение:** `tests/integration/MapActionsIntegrationTest.php`

**Что тестируют:**
- Действия пользователей на игровой карте
- Взаимодействие с системой координат карты
- Обработку перемещений и взаимодействий
- Валидацию границ карты и доступных действий

**Примеры тестов:**
- ✅ Перемещение юнитов по карте
- ✅ Проверка границ и препятствий
- ✅ Взаимодействие с клетками карты (исследование, захват)
- ✅ Обработка одновременных действий нескольких игроков
- ✅ Валидация координат и доступных зон

#### MapVPageTest.php
**Расположение:** `tests/integration/MapVPageTest.php`

**Что тестируют:**
- Загрузку данных для отображения карты (`mapv`).
- Корректную работу страницы `pages/mapv.php` после исправления ошибки.
- Отсутствие критических ошибок при запросе данных карты.

**Примеры тестов:**
- ✅ Успешная загрузка данных карты без ошибок.

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

### Примеры использования

```bash
# Запуск всех тестов
php tests/run_tests.php

# Только unit тесты с подробным выводом
php tests/run_tests.php --unit-only --verbose

# Только интеграционные тесты
php tests/run_tests.php --integration-only

# Тесты с JavaScript
php tests/run_tests.php --with-js

# Тесты с отчетом покрытия
php tests/run_tests.php --coverage

# Остановиться при первой ошибке
php tests/run_tests.php --stop-on-failure

# Запустить только конкретный тест
php tests/run_tests.php --filter testGameCreation

# Запустить тесты определенного класса
php tests/run_tests.php --filter CreateGameTest

# Комбинирование параметров
php tests/run_tests.php --unit-only --verbose --coverage --stop-on-failure

# Запуск только тестов создания игры с подробным выводом
php tests/run_tests.php --filter CreateGame --verbose
```

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
Время запуска: 2025-01-15 20:30:00
PHP версия: 8.4.1
Проект: I:\pr1\html
Типы тестов: Unit, Integration

🧪 Запуск PHP тестов...
==================================================
PHPUnit 9.6.3 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.1
Configuration: I:\pr1\html\tests\phpunit.xml

....................................................................  68 / 68 (100%)

Time: 00:02.114, Memory: 26.00 MB

OK (68 tests, 267 assertions)

✅ PHP тесты завершены успешно (2.11s)

📊 ИТОГОВЫЙ ОТЧЕТ
============================================================
PHP           : ✅ ПРОЙДЕН (2.11s)
Общее время выполнения: 2.15s
Всего наборов тестов: 1
Пройдено: 1
Провалено: 0

🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!

**Статистика по классам тестов:**
- CityTest: 17 тестов, 63 утверждения ✅
- BuildingTest: 9 тестов, 51 утверждение ✅  
- ResearchTest: 11 тестов, 64 утверждения ✅
- GameTest: 10 тестов, 42 утверждения ✅
- UserTest: 13 тестов, 55 утверждений ✅
- ResourceTest: 6 тестов, 21 утверждение ✅
- MessageTest: 5 тестов, 24 утверждения ✅
- UnitTest: 7 тестов, 27 утверждений ✅
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
TEST_DB_HOST     = 'db'
TEST_DB_USER     = 'civ_test' 
TEST_DB_PASS     = 'civ_test'
TEST_DB_NAME     = 'civ_for_tests'
TEST_DB_PORT     = 3306

// Основные константы проекта (не затрагиваются)
DB_HOST          = ваши_настройки
DB_USER          = ваши_настройки
// и т.д.
```

### Настройка тестовой БД

#### Через переменные окружения
```bash
export TEST_DB_HOST=db
export TEST_DB_USER=civ_test
export TEST_DB_PASS=civ_test
export TEST_DB_NAME=civ_for_tests
export TEST_DB_PORT=3306
```

#### Через phpunit.xml
```xml
<php>
    <const name="TEST_DB_HOST" value="db"/>
    <const name="TEST_DB_USER" value="civ_test"/>
    <const name="TEST_DB_PASS" value="civ_test"/>
    <const name="TEST_DB_NAME" value="civ_for_tests"/>
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

*Последнее обновление: 2025-09-24*
*Версия тестов: 1.1.0*
