<?php
require_once(__DIR__ . "/../includes.php");

global $error;

$error = '';

error_log("Error at start: $error");

$action = $_REQUEST['action'] ?? 'list';
$id = $_REQUEST['id'] ?? null;
$message = '';

error_log("unit_types.php loaded, action: $action, id: $id, REQUEST: " . json_encode($_REQUEST));

// Обработка действий перед выводом HTML
if ($action == 'save') {
    error_log("Action: $action, ID: $id");
    if ($id) {
        error_log("Editing id: $id");
        $unitType = UnitType::get($id);
        if (!$unitType) {
            $message = "Unit type not found.";
        }
    } else {
        error_log("Creating new");
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

            error_log("Before save");
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

error_log("Final error: $error");
?>

<div class="card">
    <div class="card-header">
        <h5>Unit Types</h5>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($action == 'edit' || $action == 'add'): ?>
        <form method="POST" action="index.php?page=unit_types&action=save<?php echo $id ? '&id=' . $id : ''; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo $unitType->title ?? ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="points" class="form-label">Points</label>
                <input type="number" name="points" id="points" class="form-control" value="<?php echo $unitType->points ?? 1; ?>">
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Cost</label>
                <input type="number" name="cost" id="cost" class="form-control" value="<?php echo $unitType->cost ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="population_cost" class="form-label">Population Cost</label>
                <input type="number" name="population_cost" id="population_cost" class="form-control" value="<?php echo $unitType->population_cost ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="land" <?php echo ($unitType->type ?? 'land') == 'land' ? 'selected' : ''; ?>>Land</option>
                    <option value="water" <?php echo ($unitType->type ?? 'land') == 'water' ? 'selected' : ''; ?>>Water</option>
                    <option value="air" <?php echo ($unitType->type ?? 'land') == 'air' ? 'selected' : ''; ?>>Air</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="attack" class="form-label">Attack</label>
                <input type="number" name="attack" id="attack" class="form-control" value="<?php echo $unitType->attack ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="defence" class="form-label">Defence</label>
                <input type="number" name="defence" id="defence" class="form-control" value="<?php echo $unitType->defence ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="health" class="form-label">Health</label>
                <input type="number" name="health" class="form-control" value="<?php echo $unitType->health ?? 1; ?>">
            </div>
            <div class="mb-3">
                <label for="movement" class="form-label">Movement</label>
                <input type="number" name="movement" id="movement" class="form-control" value="<?php echo $unitType->movement ?? 1; ?>">
            </div>
            <div class="mb-3">
                <label for="upkeep" class="form-label">Upkeep</label>
                <input type="number" name="upkeep" id="upkeep" class="form-control" value="<?php echo $unitType->upkeep ?? 0; ?>">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="can_found_city" id="can_found_city" <?php echo ($unitType->can_found_city ?? false) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="can_found_city">Can Found City</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="can_build" id="can_build" <?php echo ($unitType->can_build ?? false) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="can_build">Can Build</label>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control"><?php echo $unitType->description ?? ''; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="age" class="form-label">Age</label>
                <input type="number" name="age" id="age" class="form-control" value="<?php echo $unitType->age ?? 1; ?>">
            </div>
            <div class="mb-3">
                <label for="missions" class="form-label">Missions (comma separated)</label>
                <input type="text" name="missions" id="missions" class="form-control" value="<?php echo implode(',', $unitType->missions ?? []); ?>">
            </div>
            <div class="mb-3">
                <label for="can_move" class="form-label">Can Move (JSON)</label>
                <textarea name="can_move" id="can_move" class="form-control"><?php echo json_encode($unitType->can_move ?? []); ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="index.php?page=unit_types" class="btn btn-secondary">Cancel</a>
        </form>
        <?php else: ?>
        <a href="index.php?page=unit_types&action=add" class="btn btn-primary mb-3">Add New Unit Type</a>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Cost</th>
                    <th>Attack</th>
                    <th>Defence</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unitTypes as $ut): ?>
                <tr>
                    <td><?php echo $ut->id; ?></td>
                    <td><?php echo $ut->title; ?></td>
                    <td><?php echo $ut->type; ?></td>
                    <td><?php echo $ut->cost; ?></td>
                    <td><?php echo $ut->attack; ?></td>
                    <td><?php echo $ut->defence; ?></td>
                    <td>
                        <a href="index.php?page=unit_types&action=edit&id=<?php echo $ut->id; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="index.php?page=unit_types&action=delete&id=<?php echo $ut->id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
