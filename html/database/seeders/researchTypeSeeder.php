<?php

require_once 'baseSeeder.php';

use App\ResearchType;

setupDatabase();
checkTables(['research_type', 'research_requirements', 'research']);
$clear = parseClearArgv($argv);
clearData($clear, ["DELETE FROM research_requirements;", "DELETE FROM research_type;", "DELETE FROM research;"]);

// Данные исследований Civilization 3 с деревом зависимостей
$researches = [
    // Ancient Age
    ['id' => 1, 'title' => 'Agriculture', 'cost' => 50, 'age' => 1, 'age_need' => false, 'req' => []],
    ['id' => 2, 'title' => 'Pottery', 'cost' => 50, 'age' => 1, 'age_need' => false, 'req' => []],
    ['id' => 3, 'title' => 'Writing', 'cost' => 100, 'age' => 1, 'age_need' => false, 'req' => []],
    ['id' => 4, 'title' => 'Mathematics', 'cost' => 100, 'age' => 1, 'age_need' => false, 'req' => []],
    ['id' => 5, 'title' => 'Mysticism', 'cost' => 100, 'age' => 1, 'age_need' => false, 'req' => []],
    ['id' => 6, 'title' => 'Warrior Code', 'cost' => 100, 'age' => 1, 'age_need' => false, 'req' => []],
    ['id' => 7, 'title' => 'Alphabet', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [3]],
    ['id' => 8, 'title' => 'Code of Laws', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [3]],
    ['id' => 9, 'title' => 'Literature', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [3]],
    ['id' => 10, 'title' => 'Currency', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [4]],
    ['id' => 11, 'title' => 'Construction', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [4]],
    ['id' => 12, 'title' => 'Horseback Riding', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [6]],
    ['id' => 13, 'title' => 'Ceremonial Burial', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [5]],
    ['id' => 14, 'title' => 'Polytheism', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [5]],
    ['id' => 15, 'title' => 'Bronze Working', 'cost' => 150, 'age' => 1, 'age_need' => false, 'req' => [5]],
    ['id' => 16, 'title' => 'Map Making', 'cost' => 200, 'age' => 1, 'age_need' => false, 'req' => [7]],
    ['id' => 17, 'title' => 'Republic', 'cost' => 200, 'age' => 1, 'age_need' => false, 'req' => [8]],
    ['id' => 18, 'title' => 'Monarchy', 'cost' => 200, 'age' => 1, 'age_need' => false, 'req' => [8]],
    ['id' => 19, 'title' => 'Engineering', 'cost' => 200, 'age' => 1, 'age_need' => false, 'req' => [4, 11]],
    ['id' => 20, 'title' => 'Iron Working', 'cost' => 200, 'age' => 1, 'age_need' => false, 'req' => [15]],
    ['id' => 21, 'title' => 'The Wheel', 'cost' => 200, 'age' => 1, 'age_need' => false, 'req' => [12]],
    ['id' => 22, 'title' => 'Meditation', 'cost' => 200, 'age' => 1, 'age_need' => false, 'req' => [14]],
    ['id' => 23, 'title' => 'Monotheism', 'cost' => 200, 'age' => 1, 'age_need' => false, 'req' => [14]],
    // Classical Age
    ['id' => 24, 'title' => 'Feudalism', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [18]],
    ['id' => 25, 'title' => 'Democracy', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [17]],
    ['id' => 26, 'title' => 'Philosophy', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [9]],
    ['id' => 27, 'title' => 'Trade', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [10]],
    ['id' => 28, 'title' => 'Navigation', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [16]],
    ['id' => 29, 'title' => 'University', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [26]],
    ['id' => 30, 'title' => 'Banking', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [27]],
    ['id' => 31, 'title' => 'Astronomy', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [22]],
    ['id' => 32, 'title' => 'Music Theory', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [23]],
    ['id' => 33, 'title' => 'Military Tradition', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [24]],
    ['id' => 34, 'title' => 'Conscription', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [25]],
    ['id' => 35, 'title' => 'Economics', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [30]],
    ['id' => 36, 'title' => 'Chemistry', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [31]],
    ['id' => 37, 'title' => 'Physics', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [31]],
    ['id' => 38, 'title' => 'Theology', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [32]],
    ['id' => 39, 'title' => 'Education', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [29]],
    ['id' => 40, 'title' => 'Artillery', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [37]],
    ['id' => 41, 'title' => 'Metallurgy', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [20]],
    ['id' => 42, 'title' => 'Gunpowder', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [36]],
    ['id' => 43, 'title' => 'Printing Press', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [39]],
    ['id' => 44, 'title' => 'Magnetism', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [37]],
    ['id' => 45, 'title' => 'Theory of Gravity', 'cost' => 250, 'age' => 2, 'age_need' => false, 'req' => [37]],
    // Medieval Age
    ['id' => 46, 'title' => 'Chivalry', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [33]],
    ['id' => 47, 'title' => 'Nationalism', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [34]],
    ['id' => 48, 'title' => 'Free Artistry', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [38]],
    ['id' => 49, 'title' => 'Scientific Method', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [39]],
    ['id' => 50, 'title' => 'Steam Power', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [37]],
    ['id' => 51, 'title' => 'Explosives', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [42]],
    ['id' => 52, 'title' => 'Replaceable Parts', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [49]],
    ['id' => 53, 'title' => 'Flight', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [44]],
    ['id' => 54, 'title' => 'Electricity', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [45]],
    ['id' => 55, 'title' => 'Corporation', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [35]],
    ['id' => 56, 'title' => 'Industrialization', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [50]],
    ['id' => 57, 'title' => 'Railroad', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [50]],
    ['id' => 58, 'title' => 'Steel', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [41]],
    ['id' => 59, 'title' => 'Refining', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [36]],
    ['id' => 60, 'title' => 'Combustion', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [54]],
    ['id' => 61, 'title' => 'Mass Production', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [52]],
    ['id' => 62, 'title' => 'Atomic Theory', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [49]],
    ['id' => 63, 'title' => 'Sanitation', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [49]],
    ['id' => 64, 'title' => 'Medicine', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [63]],
    ['id' => 65, 'title' => 'Communism', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [47]],
    ['id' => 66, 'title' => 'Fascism', 'cost' => 300, 'age' => 3, 'age_need' => false, 'req' => [47]],
    // Modern Age
    ['id' => 67, 'title' => 'Computers', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [61]],
    ['id' => 68, 'title' => 'Rocketry', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [53]],
    ['id' => 69, 'title' => 'Plastics', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [59]],
    ['id' => 70, 'title' => 'Electronics', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [54]],
    ['id' => 71, 'title' => 'Recycling', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [63]],
    ['id' => 72, 'title' => 'Genetics', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [64]],
    ['id' => 73, 'title' => 'Synthetic Fibers', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [69]],
    ['id' => 74, 'title' => 'Miniaturization', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [67]],
    ['id' => 75, 'title' => 'Superconductors', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [70]],
    ['id' => 76, 'title' => 'Satellites', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [68]],
    ['id' => 77, 'title' => 'Lasers', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [62]],
    ['id' => 78, 'title' => 'Nuclear Power', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [62]],
    ['id' => 79, 'title' => 'Fusion', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [78]],
    ['id' => 80, 'title' => 'Environmentalism', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [71]],
    ['id' => 81, 'title' => 'Space Flight', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [76]],
    ['id' => 82, 'title' => 'The Internet', 'cost' => 350, 'age' => 4, 'age_need' => false, 'req' => [74]],
    ['id' => 83, 'title' => 'Future Tech', 'cost' => 400, 'age' => 5, 'age_need' => false, 'req' => [79, 81, 82]],
];

$researchObjects = [];

// Сначала создаем все объекты без зависимостей
foreach ($researches as $data) {
    $rt = new ResearchType($data);
    foreach ($data['req'] as $id) {
        $rt->addRequirement(ResearchType::get($id));
    }
    $rt->save();
    $researchObjects[$rt->id] = $rt;
}

// Затем добавляем зависимости и сохраняем снова
foreach ($researches as $data) {
    $rt = $researchObjects[$data['id']];
    $rt->requirements = array_map(fn($id) => $researchObjects[$id], $data['req']);
    $rt->save();
}

echo "Seeder выполнен успешно. Добавлено " . count($researches) . " исследований.\n";
