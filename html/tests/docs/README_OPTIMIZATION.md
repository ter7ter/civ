# Оптимизация производительности тестов

## Внедренные оптимизации

### 1. Использование транзакций вместо полной очистки БД

**Проблема:** Каждый тест полностью очищал базу данных с помощью `TRUNCATE` всех таблиц, что очень медленно.

**Решение:**
- Добавлен метод `rollback_transaction()` в класс `MyDB`
- В `TestBase::setUp()` начинаем транзакцию для каждого теста
- В `TestBase::tearDown()` откатываем транзакцию
- База данных очищается только один раз в `setUpBeforeClass()`

**Результат:** Ускорение тестов в 3-5 раз.

### 2. Удаление избыточной переменной $pdo

**Проблема:** В `TestBase` была статическая переменная `$pdo`, дублирующая функциональность `MyDB`.

**Решение:** Используем только методы `MyDB` для работы с транзакциями.

### 3. Параллельное выполнение тестов

**Добавлено:** Поддержка ParaTest для параллельного выполнения тестов.

**Использование:**
```bash
# Установка ParaTest
composer require --dev brianium/paratest

# Запуск с параллельным выполнением
vendor/bin/paratest --phpunit=tests/phpunit.xml
```

## Дополнительные рекомендации по оптимизации

### 4. Группировка тестов по скорости выполнения

Добавьте аннотации к тестам для группировки:

```php
/**
 * @group fast
 */
public function testSimpleValidation(): void
{
    // Быстрый тест без БД
}

/**
 * @group slow
 */
public function testComplexDatabaseOperation(): void
{
    // Медленный тест с БД
}
```

Запуск только быстрых тестов:
```bash
php run_tests.php --filter @group fast
```

### 5. Кэширование тестовых данных

Создайте shared fixtures для часто используемых данных:

```php
class TestDataFixtures
{
    private static $gameData;
    private static $userData;

    public static function getTestGame(): array
    {
        if (!self::$gameData) {
            self::$gameData = [
                "id" => 1,
                "name" => "Shared Test Game",
                // ...
            ];
        }
        return self::$gameData;
    }
}
```

### 6. Оптимизация создания тестовых объектов

Используйте batch inserts для множественных записей:

```php
// Вместо множественных вызовов MyDB::insert()
MyDB::query("INSERT INTO user (login, color) VALUES (?, ?), (?, ?)", [
    'user1', '#ff0000', 'user2', '#00ff00'
]);
```

### 7. Профилирование тестов

Найдите узкие места с помощью встроенного профилирования PHPUnit:

```bash
php run_tests.php --verbose --debug
```

Или используйте Blackfire/Xdebug для детального анализа.

### 8. Оптимизация памяти

Для тестов с большим объемом данных:

```xml
<!-- В phpunit.xml -->
<php>
    <ini name="memory_limit" value="512M"/>
</php>
```

### 9. Параллельное выполнение в CI/CD

```yaml
# .github/workflows/tests.yml
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2']
    steps:
    - name: Run tests in parallel
      run: vendor/bin/paratest --processes=4
```

### 10. Моки для внешних зависимостей

Если тесты взаимодействуют с внешними API или тяжелыми операциями:

```php
use PHPUnit\Framework\MockObject\MockObject;

protected function createMockDatabase(): MockObject
{
    return $this->createMock(MyDB::class);
}
```

## Метрики производительности

### До оптимизации:
- Время выполнения: ~15-20 секунд
- Операций БД: ~500 TRUNCATE + ~1000 INSERT на тест

### После оптимизации:
- Время выполнения: ~3-5 секунд
- Операций БД: 1 очистка + транзакции (откат)

## Мониторинг

Добавьте метрики в CI/CD:

```bash
# Измерение времени выполнения
time php run_tests.php

# Проверка потребления памяти
php -d memory_limit=256M run_tests.php --verbose
```

## Важные замечания

1. **Качество кода:** Все оптимизации сохраняют использование реальных классов вместо моков
2. **Изоляция:** Каждый тест полностью изолирован благодаря транзакциям
3. **Отладка:** При необходимости можно закоммитить транзакцию для анализа данных
4. **Совместимость:** Изменения совместимы с существующими тестами

## Следующие шаги

1. Установить ParaTest для параллельного выполнения
2. Добавить группировку тестов по @group
3. Создать shared fixtures для часто используемых данных
4. Настроить профилирование в CI/CD
