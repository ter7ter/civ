<?php

/**
 * Отладочный скрипт для проверки типов юнитов
 */

// Устанавливаем временную зону
date_default_timezone_set("Europe/Moscow");

// Включаем bootstrap
require_once __DIR__ . "/bootstrap.php";

echo "🔧 ОТЛАДКА ТИПОВ ЮНИТОВ\n";
echo str_repeat("=", 50) . "\n";

// Проверяем таблицу unit_type
echo "📊 Проверка таблицы unit_type:\n";
try {
    $unitTypes = MyDB::query("SELECT * FROM unit_type ORDER BY id");
    echo "   Найдено записей: " . count($unitTypes) . "\n";

    if (count($unitTypes) > 0) {
        foreach ($unitTypes as $type) {
            echo "   - ID: {$type['id']}, Title: '{$type['title']}', Points: {$type['points']}\n";
        }
    } else {
        echo "   ⚠️ Таблица unit_type пустая!\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка получения unit_type: " . $e->getMessage() . "\n";
}

// Проверяем статический массив UnitType::$all
echo "\n🏗️ Проверка UnitType::\$all:\n";
try {
    if (isset(UnitType::$all) && is_array(UnitType::$all)) {
        echo "   Элементов в UnitType::\$all: " . count(UnitType::$all) . "\n";
        foreach (UnitType::$all as $id => $unitType) {
            echo "   - ID: $id, Title: '{$unitType->title}'\n";
        }
    } else {
        echo "   ⚠️ UnitType::\$all не инициализирован или пуст!\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка проверки UnitType::\$all: " . $e->getMessage() . "\n";
}

// Пробуем получить конкретные типы юнитов
echo "\n🧪 Тест получения конкретных типов:\n";
$testIds = [1, 2, 3, 4, 5];

foreach ($testIds as $id) {
    try {
        echo "   Получаем UnitType::get($id)...\n";
        $unitType = UnitType::get($id);

        if ($unitType) {
            echo "   ✅ ID $id: '{$unitType->title}' (Points: {$unitType->points})\n";
        } else {
            echo "   ❌ ID $id: метод вернул false\n";
        }
    } catch (Exception $e) {
        echo "   ❌ ID $id: исключение - " . $e->getMessage() . "\n";
    }
}

// Проверяем инициализацию данных
echo "\n🔄 Проверка инициализации данных:\n";
try {
    // Проверяем, вызывается ли TestGameDataInitializer
    echo "   Проверяем класс TestGameDataInitializer...\n";
    if (class_exists('TestGameDataInitializer')) {
        echo "   ✅ TestGameDataInitializer существует\n";

        // Проверяем методы инициализации
        $methods = get_class_methods('TestGameDataInitializer');
        echo "   Доступные методы: " . implode(', ', $methods) . "\n";

        if (method_exists('TestGameDataInitializer', 'initializeUnitTypes')) {
            echo "   Вызываем initializeUnitTypes()...\n";
            TestGameDataInitializer::initializeUnitTypes();
            echo "   ✅ initializeUnitTypes() выполнен\n";
        } else {
            echo "   ⚠️ Метод initializeUnitTypes не найден\n";
        }
    } else {
        echo "   ❌ TestGameDataInitializer не существует\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка инициализации: " . $e->getMessage() . "\n";
}

// Повторная проверка после инициализации
echo "\n🔄 Повторная проверка после инициализации:\n";
try {
    $unitTypesAfter = MyDB::query("SELECT * FROM unit_type ORDER BY id");
    echo "   Записей в unit_type: " . count($unitTypesAfter) . "\n";

    if (isset(UnitType::$all)) {
        echo "   Элементов в UnitType::\$all: " . count(UnitType::$all) . "\n";
    }

    // Снова пробуем получить тип с ID 1
    $unitType1 = UnitType::get(1);
    if ($unitType1) {
        echo "   ✅ UnitType::get(1) теперь работает: '{$unitType1->title}'\n";
    } else {
        echo "   ❌ UnitType::get(1) все еще возвращает false\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка повторной проверки: " . $e->getMessage() . "\n";
}

// Ручное создание тестового типа юнита
echo "\n✋ Ручное создание тестового типа:\n";
try {
    // Вставляем тестовую запись
    $testId = MyDB::insert('unit_type', [
        'title' => 'Test Unit',
        'points' => 2,
        'mission_points' => 2
    ]);

    if ($testId) {
        echo "   ✅ Тестовый тип создан с ID: $testId\n";

        // Пробуем получить его через UnitType::get()
        $testUnit = UnitType::get($testId);
        if ($testUnit) {
            echo "   ✅ Получен через UnitType::get(): '{$testUnit->title}'\n";
        } else {
            echo "   ❌ Не удалось получить через UnitType::get()\n";
        }
    } else {
        echo "   ❌ Не удалось создать тестовый тип\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка создания тестового типа: " . $e->getMessage() . "\n";
}

// Проверяем структуру таблицы
echo "\n📋 Структура таблицы unit_type:\n";
try {
    $pragma = MyDB::query("PRAGMA table_info(unit_type)");
    echo "   Колонки таблицы unit_type:\n";
    foreach ($pragma as $column) {
        echo "   - {$column['name']}: {$column['type']} (nullable: " .
             ($column['notnull'] ? 'no' : 'yes') . ")\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка получения структуры таблицы: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Отладка типов юнитов завершена\n";
