<?php

require_once 'baseSeeder.php';

use App\ResearchType;

setupDatabase();
checkTables(['research_type', 'research_requirements', 'research']);
$clear = ($SHOULD_CLEAN ?? false) || parseClearArgv($argv);

if ($clear) {
    echo "Clearing research types...\n";
    $allResearchTypes = ResearchType::getAll();
    foreach ($allResearchTypes as $researchType) {
        $researchType->delete();
    }
    echo "Finished clearing research types.\n";
}

$researches = [
    // Древний век
    ['id' => 1, 'title' => 'Гончарное дело', 'cost' => 40, 'age' => 1, 'age_need' => false, 'req' => [], 'm_top' => 20, 'm_left' => 10],
    ['id' => 2, 'title' => 'Воинский кодекс', 'cost' => 40, 'age' => 1, 'age_need' => false, 'req' => [], 'm_top' => 100, 'm_left' => 10],
    ['id' => 3, 'title' => 'Погребальный ритуал', 'cost' => 40, 'age' => 1, 'age_need' => false, 'req' => [], 'm_top' => 180, 'm_left' => 10],
    ['id' => 4, 'title' => 'Алфавит', 'cost' => 60, 'age' => 1, 'age_need' => true, 'req' => [1], 'm_top' => 20, 'm_left' => 120],
    ['id' => 5, 'title' => 'Обработка бронзы', 'cost' => 60, 'age' => 1, 'age_need' => false, 'req' => [2], 'm_top' => 100, 'm_left' => 120],
    ['id' => 6, 'title' => 'Мистицизм', 'cost' => 60, 'age' => 1, 'age_need' => false, 'req' => [3], 'm_top' => 180, 'm_left' => 120],
    ['id' => 7, 'title' => 'Письменность', 'cost' => 80, 'age' => 1, 'age_need' => true, 'req' => [4], 'm_top' => 20, 'm_left' => 230],
    ['id' => 8, 'title' => 'Верховая езда', 'cost' => 80, 'age' => 1, 'age_need' => false, 'req' => [5], 'm_top' => 100, 'm_left' => 230],
    ['id' => 9, 'title' => 'Каменная кладка', 'cost' => 80, 'age' => 1, 'age_need' => false, 'req' => [6], 'm_top' => 180, 'm_left' => 230],
    ['id' => 10, 'title' => 'Свод законов', 'cost' => 100, 'age' => 1, 'age_need' => true, 'req' => [7], 'm_top' => 20, 'm_left' => 340],
    ['id' => 11, 'title' => 'Обработка железа', 'cost' => 100, 'age' => 1, 'age_need' => false, 'req' => [5], 'm_top' => 100, 'm_left' => 340],
    ['id' => 12, 'title' => 'Политеизм', 'cost' => 100, 'age' => 1, 'age_need' => false, 'req' => [6], 'm_top' => 180, 'm_left' => 340],
    ['id' => 13, 'title' => 'Литература', 'cost' => 120, 'age' => 1, 'age_need' => true, 'req' => [10], 'm_top' => 20, 'm_left' => 450],
    ['id' => 14, 'title' => 'Колесо', 'cost' => 120, 'age' => 1, 'age_need' => false, 'req' => [8], 'm_top' => 100, 'm_left' => 450],
    ['id' => 15, 'title' => 'Строительство', 'cost' => 120, 'age' => 1, 'age_need' => false, 'req' => [9], 'm_top' => 180, 'm_left' => 450],
    ['id' => 16, 'title' => 'Философия', 'cost' => 140, 'age' => 1, 'age_need' => true, 'req' => [13], 'm_top' => 20, 'm_left' => 560],
    ['id' => 17, 'title' => 'Математика', 'cost' => 140, 'age' => 1, 'age_need' => false, 'req' => [11], 'm_top' => 100, 'm_left' => 560],
    ['id' => 18, 'title' => 'Картография', 'cost' => 140, 'age' => 1, 'age_need' => false, 'req' => [14], 'm_top' => 180, 'm_left' => 560],
    ['id' => 19, 'title' => 'Республика', 'cost' => 160, 'age' => 1, 'age_need' => true, 'req' => [16], 'm_top' => 20, 'm_left' => 670],
    ['id' => 20, 'title' => 'Денежное обращение', 'cost' => 160, 'age' => 1, 'age_need' => false, 'req' => [17], 'm_top' => 100, 'm_left' => 670],
    ['id' => 22, 'title' => 'Монархия', 'cost' => 160, 'age' => 1, 'age_need' => false, 'req' => [12], 'm_top' => 260, 'm_left' => 670],

    // Средневековье
    ['id' => 21, 'title' => 'Инженерное дело', 'cost' => 160, 'age' => 2, 'age_need' => false, 'req' => [15], 'm_top' => 180, 'm_left' => 670],
    ['id' => 23, 'title' => 'Монотеизм', 'cost' => 160, 'age' => 2, 'age_need' => false, 'req' => [12], 'm_top' => 340, 'm_left' => 670],
    ['id' => 24, 'title' => 'Феодализм', 'cost' => 200, 'age' => 2, 'age_need' => true, 'req' => [16, 22], 'm_top' => 20, 'm_left' => 780],
    ['id' => 25, 'title' => 'Теология', 'cost' => 200, 'age' => 2, 'age_need' => false, 'req' => [16, 23], 'm_top' => 100, 'm_left' => 780],
    ['id' => 26, 'title' => 'Образование', 'cost' => 200, 'age' => 2, 'age_need' => true, 'req' => [13, 16], 'm_top' => 180, 'm_left' => 780],
    ['id' => 27, 'title' => 'Музыка', 'cost' => 200, 'age' => 2, 'age_need' => false, 'req' => [13], 'm_top' => 260, 'm_left' => 780],
    ['id' => 28, 'title' => 'Рыцарство', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [24, 8], 'm_top' => 20, 'm_left' => 890],
    ['id' => 29, 'title' => 'Изобретение', 'cost' => 250, 'age' => 2, 'age_need' => true, 'req' => [26], 'm_top' => 100, 'm_left' => 890],
    ['id' => 30, 'title' => 'Банковское дело', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [26, 20], 'm_top' => 180, 'm_left' => 890],
    ['id' => 31, 'title' => 'Порох', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [11, 17], 'm_top' => 260, 'm_left' => 890],
    ['id' => 32, 'title' => 'Астрономия', 'cost' => 300, 'age' => 2, 'age_need' => true, 'req' => [17, 18], 'm_top' => 20, 'm_left' => 1000],
    ['id' => 33, 'title' => 'Химия', 'cost' => 300, 'age' => 2, 'age_need' => false, 'req' => [29, 31], 'm_top' => 100, 'm_left' => 1000],
    ['id' => 34, 'title' => 'Экономика', 'cost' => 300, 'age' => 2, 'age_need' => true, 'req' => [30], 'm_top' => 180, 'm_left' => 1000],
    ['id' => 35, 'title' => 'Навигация', 'cost' => 300, 'age' => 2, 'age_need' => false, 'req' => [32], 'm_top' => 260, 'm_left' => 1000],

    // Индустриальный век
    ['id' => 36, 'title' => 'Печатный станок', 'cost' => 350, 'age' => 3, 'age_need' => true, 'req' => [29], 'm_top' => 20, 'm_left' => 1110],
    ['id' => 37, 'title' => 'Металлургия', 'cost' => 350, 'age' => 3, 'age_need' => false, 'req' => [29, 31], 'm_top' => 100, 'm_left' => 1110],
    ['id' => 38, 'title' => 'Военная традиция', 'cost' => 350, 'age' => 3, 'age_need' => false, 'req' => [28], 'm_top' => 180, 'm_left' => 1110],
    ['id' => 39, 'title' => 'Демократия', 'cost' => 350, 'age' => 3, 'age_need' => true, 'req' => [25, 26], 'm_top' => 260, 'm_left' => 1110],
    ['id' => 40, 'title' => 'Паровой двигатель', 'cost' => 400, 'age' => 3, 'age_need' => true, 'req' => [36, 37], 'm_top' => 20, 'm_left' => 1220],
    ['id' => 41, 'title' => 'Национализм', 'cost' => 400, 'age' => 3, 'age_need' => false, 'req' => [38], 'm_top' => 100, 'm_left' => 1220],
    ['id' => 42, 'title' => 'Медицина', 'cost' => 400, 'age' => 3, 'age_need' => false, 'req' => [33], 'm_top' => 180, 'm_left' => 1220],
    ['id' => 43, 'title' => 'Электричество', 'cost' => 450, 'age' => 3, 'age_need' => true, 'req' => [40, 35], 'm_top' => 20, 'm_left' => 1330],
    ['id' => 44, 'title' => 'Сталь', 'cost' => 450, 'age' => 3, 'age_need' => false, 'req' => [40], 'm_top' => 100, 'm_left' => 1330],
    ['id' => 45, 'title' => 'Научный метод', 'cost' => 450, 'age' => 3, 'age_need' => true, 'req' => [33, 39], 'm_top' => 180, 'm_left' => 1330],

    // Современность
    ['id' => 46, 'title' => 'Коммунизм', 'cost' => 500, 'age' => 4, 'age_need' => false, 'req' => [45, 34], 'm_top' => 20, 'm_left' => 1440],
    ['id' => 47, 'title' => 'Индустриализация', 'cost' => 500, 'age' => 4, 'age_need' => true, 'req' => [40, 34], 'm_top' => 100, 'm_left' => 1440],
    ['id' => 48, 'title' => 'Взрывчатые вещества', 'cost' => 500, 'age' => 4, 'age_need' => false, 'req' => [31, 40], 'm_top' => 180, 'm_left' => 1440],
    ['id' => 49, 'title' => 'Теория атома', 'cost' => 550, 'age' => 4, 'age_need' => true, 'req' => [45], 'm_top' => 20, 'm_left' => 1550],
    ['id' => 50, 'title' => 'Радио', 'cost' => 550, 'age' => 4, 'age_need' => false, 'req' => [43], 'm_top' => 100, 'm_left' => 1550],
    ['id' => 51, 'title' => 'Массовое производство', 'cost' => 550, 'age' => 4, 'age_need' => false, 'req' => [47], 'm_top' => 180, 'm_left' => 1550],
    ['id' => 52, 'title' => 'Двигатель внутреннего сгорания', 'cost' => 550, 'age' => 4, 'age_need' => false, 'req' => [48], 'm_top' => 260, 'm_left' => 1550],
    ['id' => 53, 'title' => 'Ядерное деление', 'cost' => 600, 'age' => 4, 'age_need' => true, 'req' => [49], 'm_top' => 20, 'm_left' => 1660],
    ['id' => 54, 'title' => 'Электроника', 'cost' => 600, 'age' => 4, 'age_need' => false, 'req' => [50], 'm_top' => 100, 'm_left' => 1660],
    ['id' => 55, 'title' => 'Сменные детали', 'cost' => 600, 'age' => 4, 'age_need' => false, 'req' => [51], 'm_top' => 180, 'm_left' => 1660],
    ['id' => 56, 'title' => 'Полет', 'cost' => 600, 'age' => 4, 'age_need' => false, 'req' => [52], 'm_top' => 260, 'm_left' => 1660],
    ['id' => 57, 'title' => 'Ракетная техника', 'cost' => 650, 'age' => 4, 'age_need' => false, 'req' => [56], 'm_top' => 20, 'm_left' => 1770],
    ['id' => 58, 'title' => 'Компьютеры', 'cost' => 650, 'age' => 4, 'age_need' => true, 'req' => [54, 55], 'm_top' => 100, 'm_left' => 1770],
    ['id' => 59, 'title' => 'Пластмассы', 'cost' => 650, 'age' => 4, 'age_need' => false, 'req' => [52], 'm_top' => 180, 'm_left' => 1770],
    ['id' => 60, 'title' => 'Лазер', 'cost' => 700, 'age' => 4, 'age_need' => false, 'req' => [53], 'm_top' => 20, 'm_left' => 1880],
    ['id' => 61, 'title' => 'Миниатюризация', 'cost' => 700, 'age' => 4, 'age_need' => false, 'req' => [58], 'm_top' => 100, 'm_left' => 1880],
    ['id' => 62, 'title' => 'Синтетические волокна', 'cost' => 700, 'age' => 4, 'age_need' => false, 'req' => [59], 'm_top' => 180, 'm_left' => 1880],
    ['id' => 63, 'title' => 'Генетика', 'cost' => 750, 'age' => 4, 'age_need' => false, 'req' => [42], 'm_top' => 20, 'm_left' => 1990],
    ['id' => 64, 'title' => 'Спутники', 'cost' => 750, 'age' => 4, 'age_need' => false, 'req' => [57], 'm_top' => 100, 'm_left' => 1990],
    ['id' => 65, 'title' => 'Робототехника', 'cost' => 750, 'age' => 4, 'age_need' => false, 'req' => [61], 'm_top' => 180, 'm_left' => 1990],
    ['id' => 66, 'title' => 'Термоядерный синтез', 'cost' => 800, 'age' => 4, 'age_need' => true, 'req' => [60], 'm_top' => 20, 'm_left' => 2100],
    ['id' => 67, 'title' => 'Космический полет', 'cost' => 800, 'age' => 4, 'age_need' => true, 'req' => [64], 'm_top' => 100, 'm_left' => 2100],
    ['id' => 68, 'title' => 'Стелс', 'cost' => 800, 'age' => 4, 'age_need' => false, 'req' => [65], 'm_top' => 180, 'm_left' => 2100],
    ['id' => 69, 'title' => 'Будущее', 'cost' => 1000, 'age' => 5, 'age_need' => false, 'req' => [66, 67, 68], 'm_top' => 100, 'm_left' => 2210],
];

echo "Starting to seed research types...\n";
// Сначала создаем все объекты без зависимостей
foreach ($researches as $data) {
    $researchType = new ResearchType($data);
    $researchType->save();
}
echo "Finished seeding research types.\n";

// Затем добавляем зависимости и сохраняем снова
foreach ($researches as $data) {
    $rt = ResearchType::get($data['id']);
    foreach ($data['req'] as $req_id) {
        $rt->addRequirement(ResearchType::get($req_id));
    }
    $rt->save();
}

echo "Seeder выполнен успешно. Добавлено " . count($researches) . " исследований.\n";

ResearchType::clearAll();
