<?php
use App\BuildingType;

require_once(__DIR__ . "/../includes.php");
require_once(__DIR__ . "/functions.php");

$action = $_REQUEST['action'] ?? 'list';
$id = $_REQUEST['id'] ?? null;
$message = '';

// Обработка действий перед выводом HTML
if ($action == 'save') {
    if ($id) {
        $buildingType = BuildingType::get($id);
        if (!$buildingType) {
            $message = "Building type not found.";
        }
    } else {
        $buildingType = new BuildingType([]);
    }

    // Обработка загрузки изображения
    if (!$message && isset($_FILES['image_file'])) {
        $fileError = $_FILES['image_file']['error'];
        if ($fileError === UPLOAD_ERR_OK) {
            try {
                $result = uploadAndResizeImage($_FILES['image_file'], 'buils', $buildingType->id);
                $buildingType->image_file = $result;
            } catch (Exception $ex) {
                $message = $ex->getMessage();
            }
        } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
            $message = "Ошибка загрузки файла: " . $fileError;
        }
    }

    if (!$message) {
        // Валидация
        if (empty(trim($_POST['title']))) {
            $message = "Error: Title is required.";
        } else {
            $buildingType->title = trim($_POST['title']);
            $buildingType->cost = (int)($_POST['cost'] ?? 0);
            $buildingType->need_coastal = isset($_POST['need_coastal']);
            $buildingType->culture = (int)($_POST['culture'] ?? 0);
            $buildingType->upkeep = (int)($_POST['upkeep'] ?? 0);
            $buildingType->culture_bonus = (int)($_POST['culture_bonus'] ?? 0);
            $buildingType->research_bonus = (int)($_POST['research_bonus'] ?? 0);
            $buildingType->money_bonus = (int)($_POST['money_bonus'] ?? 0);
            $buildingType->description = $_POST['description'] ?? '';

            $buildingType->req_research = $_POST['req_research'] ?? [];
            // Convert to objects
            $reqObjs = [];
            foreach ($buildingType->req_research as $reqId) {
                if ($reqId) {
                    $obj = \App\ResearchType::get($reqId);
                    if ($obj) {
                        $reqObjs[] = $obj;
                    }
                }
            }
            $buildingType->req_research = $reqObjs;

            $buildingType->req_resources = $_POST['req_resources'] ?? [];
            // Convert to objects
            $reqObjs = [];
            foreach ($buildingType->req_resources as $reqId) {
                if ($reqId) {
                    $obj = \App\ResourceType::get($reqId);
                    if ($obj) {
                        $reqObjs[] = $obj;
                    }
                }
            }
            $buildingType->req_resources = $reqObjs;

            $buildingType->save();
            $message = "Building type saved successfully.";
            $action = 'list'; // Переходим к списку после сохранения
        }
    }
} elseif ($action == 'delete' && $id) {
    $buildingType = BuildingType::get($id);
    if ($buildingType) {
        $buildingType->delete();
        $message = "Building type deleted successfully.";
    } else {
        $message = "Building type not found.";
    }
    $action = 'list';
}

if ($action == 'edit' && $id) {
    $buildingType = BuildingType::get($id);
    if (!$buildingType) {
        $message = "Building type not found.";
        $action = 'list';
    }
}

$buildingTypes = BuildingType::getAll();
$researchTypes = \App\ResearchType::loadAll();
$resourceTypes = \App\ResourceType::loadAll();

if ($action == 'edit' || $action == 'add') {
    include 'templates/building_types_form.php';
} else {
    include 'templates/building_types_list.php';
}
