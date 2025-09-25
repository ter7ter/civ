<?php
require_once(__DIR__ . "/../includes.php");

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

            $buildingType->req_research = isset($_POST['req_research']) ? (json_decode($_POST['req_research'], true) ?: []) : [];
            $buildingType->req_resources = isset($_POST['req_resources']) ? (json_decode($_POST['req_resources'], true) ?: []) : [];
            $buildingType->need_research = isset($_POST['need_research']) ? (json_decode($_POST['need_research'], true) ?: []) : [];

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
?>

<div class="card">
    <div class="card-header">
        <h5>Building Types</h5>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($action == 'edit' || $action == 'add'): ?>
        <form method="POST" action="index.php?page=building_types&action=save<?php echo $id ? '&id=' . $id : ''; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo $buildingType->title ?? ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Cost</label>
                <input type="number" name="cost" id="cost" class="form-control" value="<?php echo $buildingType->cost ?? 0; ?>">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="need_coastal" id="need_coastal" <?php echo ($buildingType->need_coastal ?? false) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="need_coastal">Need Coastal</label>
            </div>
            <div class="mb-3">
                <label for="culture" class="form-label">Culture</label>
                <input type="number" name="culture" id="culture" class="form-control" value="<?php echo $buildingType->culture ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="upkeep" class="form-label">Upkeep</label>
                <input type="number" name="upkeep" id="upkeep" class="form-control" value="<?php echo $buildingType->upkeep ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="culture_bonus" class="form-label">Culture Bonus</label>
                <input type="number" name="culture_bonus" id="culture_bonus" class="form-control" value="<?php echo $buildingType->culture_bonus ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="research_bonus" class="form-label">Research Bonus</label>
                <input type="number" name="research_bonus" id="research_bonus" class="form-control" value="<?php echo $buildingType->research_bonus ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="money_bonus" class="form-label">Money Bonus</label>
                <input type="number" name="money_bonus" id="money_bonus" class="form-control" value="<?php echo $buildingType->money_bonus ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control"><?php echo $buildingType->description ?? ''; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="req_research" class="form-label">Req Research (JSON)</label>
                <textarea name="req_research" id="req_research" class="form-control"><?php echo json_encode($buildingType->req_research ?? []); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="req_resources" class="form-label">Req Resources (JSON)</label>
                <textarea name="req_resources" id="req_resources" class="form-control"><?php echo json_encode($buildingType->req_resources ?? []); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="need_research" class="form-label">Need Research (JSON)</label>
                <textarea name="need_research" id="need_research" class="form-control"><?php echo json_encode($buildingType->need_research ?? []); ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="index.php?page=building_types" class="btn btn-secondary">Cancel</a>
        </form>
        <?php else: ?>
        <a href="index.php?page=building_types&action=add" class="btn btn-primary mb-3">Add New Building Type</a>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Cost</th>
                    <th>Culture</th>
                    <th>Upkeep</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buildingTypes as $bt): ?>
                <tr>
                    <td><?php echo $bt->id; ?></td>
                    <td><?php echo $bt->title; ?></td>
                    <td><?php echo $bt->cost; ?></td>
                    <td><?php echo $bt->culture; ?></td>
                    <td><?php echo $bt->upkeep; ?></td>
                    <td>
                        <a href="index.php?page=building_types&action=edit&id=<?php echo $bt->id; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="index.php?page=building_types&action=delete&id=<?php echo $bt->id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
