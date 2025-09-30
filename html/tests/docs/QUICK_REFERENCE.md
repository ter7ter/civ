# Быстрая справка по тестированию

## 🚀 Быстрый старт

```bash
# Тесты запускаем через докер
docker compose exec php php /var/www/html/tests/_run.php
# Скрипт запуска тестов
tests\run_tests.bat

# Один конкретный тест
tests\run_tests.bat --filter UserTest
```


## ⚠️ Избегайте этих тестов

- `CreateGameTest.php` - медленный (5+ мин)
- Все `integration/*` тесты - требуют оптимизации
- Тесты с `createGameViaPage()` - генерируют полную карту

## 🛠️ Полезные команды

```bash
# Проверить зависимости
tests\run_tests.bat check-deps

# Очистить результаты
tests\run_tests.bat clean

# Установить PHPUnit
tests\run_tests.bat install-phpunit

# Один тест из CreateGameTest
run_tests.bat --filter testWhitespaceGameName tests\unit\CreateGameTest.php
```

## 📝 Создание новых тестов

### DO ✅
```php
// Используй прямые вызовы классов
$game = new Game($data);
$game->save();

// Создавай тестовые данные через TestDataFactory
$gameData = TestDataFactory::createTestGame();
$userData = TestDataFactory::createTestUser();
```

### DON'T ❌
```php
// НЕ используй полную генерацию карты
$game->create_new_game(); // Медленно!

// НЕ используй большие карты в тестах
"map_w" => 500, "map_h" => 500 // Медленно!

// НЕ используй createGameViaPage() без необходимости
$this->createGameViaPage($data); // Медленно!
```

## 🐛 Частые проблемы

| Ошибка | Решение |
|---------|---------|
| `sqlite_master doesn't exist` | Используй `SHOW TABLES` вместо SQLite команд |
| `Timeout/Memory limit` | Уменьши размер карты до 20x20 |
| `PHPUnit not found` | Запусти `tests\run_tests.bat install-phpunit` |
| `Table not found` | Проверь что `DatabaseTestAdapter::createTestTables()` вызван |

## 📊 Покрытие тестами

- ✅ Базовые классы: 95%
- ✅ Управление БД: 90% 
- ✅ Пользователи: 85%
- ⚠️ Генерация карт: 20%
- ❌ Веб-интерфейс: 10%

## 🔧 Настройка IDE

### PHPStorm
```xml
<configuration>
  <phpunit_settings>
    <option name="configuration_file_path" value="tests/phpunit.xml" />
    <option name="use_configuration_file" value="true" />
  </phpunit_settings>
</configuration>
```

### VSCode
```json
{
  "phpunit.command": "php tests/phpunit.phar",
  "phpunit.args": [
    "--configuration", "tests/phpunit.xml",
    "--no-coverage"
  ]
}
```

---

💡 **Совет:** Запускай `tests\run_quick_tests.bat` перед каждым коммитом!