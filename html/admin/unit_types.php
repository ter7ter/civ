<?php
use App\UnitType;

require_once(__DIR__ . "/../includes.php");

global $error;

$error = '';

$action = $_REQUEST['action'] ?? 'list';
$id = $_REQUEST['id'] ?? null;
$message = '';

// Обработка действий перед выводом HTML
if ($action == 'save') {
    if ($id) {
        $unitType = UnitType::get($id);
        if (!$unitType) {
            $message = "Unit type not found.";
        }
    } else {
        $unitType = new UnitType([]);
    }

    if (!$message) {
        // Валидация
        if (empty(trim($_POST['title']))) {
            $message = "Error: Title is required.";
        } else {
            $unitType->title = trim($_POST['title']);
            $unitType->points = (int)($_POST['points'] ?? 1);
            $unitType->cost = (int)($_POST['cost'] ?? 0);
            $unitType->population_cost = (int)($_POST['population_cost'] ?? 0);
            $unitType->type = $_POST['type'] ?? 'land';
            $unitType->attack = (int)($_POST['attack'] ?? 0);
            $unitType->defence = (int)($_POST['defence'] ?? 0);
            $unitType->health = (int)($_POST['health'] ?? 1);
            $unitType->movement = (int)($_POST['movement'] ?? 1);
            $unitType->upkeep = (int)($_POST['upkeep'] ?? 0);
            $unitType->can_found_city = isset($_POST['can_found_city']);
            $unitType->can_build = isset($_POST['can_build']);
            $unitType->description = $_POST['description'] ?? '';
            $unitType->age = (int)($_POST['age'] ?? 1);

            $unitType->missions = isset($_POST['missions']) ? explode(',', $_POST['missions']) : ["move_to"];
            $unitType->can_move = isset($_POST['can_move']) ? (json_decode($_POST['can_move'], true) ?: []) : [
                "plains" => 1,
                "plains2" => 1,
                "forest" => 1,
                "hills" => 1,
                "mountains" => 2,
                "desert" => 1,
                "city" => 1,
            ];

            $unitType->save();
            $message = "Unit type saved successfully.";
            $action = 'list'; // Переходим к списку после сохранения
        }
    }
} elseif ($action == 'delete' && $id) {
    $unitType = UnitType::get($id);
    if ($unitType) {
        $unitType->delete();
        $message = "Unit type deleted successfully.";
    } else {
        $message = "Unit type not found.";
    }
    $action = 'list';
}

if ($action == 'edit' && $id) {
    $unitType = UnitType::get($id);
    if (!$unitType) {
        $message = "Unit type not found.";
        $action = 'list';
    }
}

$unitTypes = UnitType::getAll();

if ($action == 'edit' || $action == 'add') {
    include 'templates/unit_types_form.php';
} else {
    include 'templates/unit_types_list.php';
}
