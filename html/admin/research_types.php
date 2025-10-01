<?php

use App\ResearchType;

require_once(__DIR__ . "/../includes.php");

$action = $_REQUEST['action'] ?? 'list';
$id = $_REQUEST['id'] ?? null;
$message = '';

// Обработка действий перед выводом HTML
if ($action == 'save') {
    if ($id) {
        $researchType = ResearchType::get($id);
        if (!$researchType) {
            $message = "Research type not found.";
        }
    } else {
        $researchType = new ResearchType([]);
    }

    if (!$message) {
        // Валидация
        if (empty(trim($_POST['title']))) {
            $message = "Error: Title is required.";
        } else {
            $researchType->title = trim($_POST['title']);
            $researchType->cost = (int)($_POST['cost'] ?? 0);
            $researchType->requirements = $_POST['requirements'] ?? [];
            // Convert to objects
            $reqObjs = [];
            foreach ($researchType->requirements as $reqId) {
                if ($reqId && is_numeric($reqId)) {
                    $obj = ResearchType::get($reqId);
                    if ($obj) {
                        $reqObjs[] = $obj;
                    }
                }
            }
            $researchType->requirements = $reqObjs;
            $researchType->m_top = (int)($_POST['m_top'] ?? 30);
            $researchType->m_left = (int)($_POST['m_left'] ?? 0);
            $researchType->age = (int)($_POST['age'] ?? 1);
            $researchType->age_need = isset($_POST['age_need']);

            $researchType->save();
            $message = "Research type saved successfully.";
            $action = 'list'; // Переходим к списку после сохранения
        }
    }
} elseif ($action == 'delete' && $id) {
    $researchType = ResearchType::get($id);
    if ($researchType) {
        $researchType->delete();
        $message = "Research type deleted successfully.";
    } else {
        $message = "Research type not found.";
    }
    $action = 'list';
}

if ($action == 'edit' && $id) {
    $researchType = ResearchType::get($id);
    if (!$researchType) {
        $message = "Research type not found.";
        $action = 'list';
    } else {
        $researchType->loadRequirements();
    }
}

$researchTypes = ResearchType::loadAll();

if ($action == 'edit' || $action == 'add') {
    include 'templates/research_types_form.php';
} else {
    include 'templates/research_types_list.php';
}
