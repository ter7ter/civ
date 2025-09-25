<?php
require_once(__DIR__ . "/../includes.php");

global $error;

$error = '';

error_log("research_types.php loaded, action: " . ($_REQUEST['action'] ?? 'list') . ", id: " . ($_REQUEST['id'] ?? 'null') . ", REQUEST: " . json_encode($_REQUEST));

$action = $_REQUEST['action'] ?? 'list';
$id = $_REQUEST['id'] ?? null;
$message = '';

// Обработка действий перед выводом HTML
if ($action == 'save') {
    error_log("Action: $action, ID: $id");
    if ($id) {
        error_log("Editing id: $id");
        $researchType = ResearchType::get($id);
        if (!$researchType) {
            $message = "Research type not found.";
        }
    } else {
        error_log("Creating new");
        $researchType = new ResearchType([]);
    }

    if (!$message) {
        // Валидация
        if (empty(trim($_POST['title']))) {
            $message = "Error: Title is required.";
        } else {
            $researchType->title = trim($_POST['title']);
            $researchType->cost = (int)($_POST['cost'] ?? 0);
            $researchType->requirements = isset($_POST['requirements']) ? json_decode($_POST['requirements'], true) ?: [] : [];
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

            error_log("Before save");
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
    }
}

$researchTypes = ResearchType::getAll();

error_log("Final error: $error");
?>

<div class="card">
    <div class="card-header">
        <h5>Research Types</h5>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($action == 'edit' || $action == 'add'): ?>
        <form method="POST" action="index.php?page=research_types&action=save<?php echo $id ? '&id=' . $id : ''; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo $researchType->title ?? ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Cost</label>
                <input type="number" name="cost" id="cost" class="form-control" value="<?php echo $researchType->cost ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="requirements" class="form-label">Requirements (JSON array of IDs)</label>
                <textarea name="requirements" id="requirements" class="form-control"><?php echo json_encode($researchType->requirements ?? []); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="m_top" class="form-label">Map Top</label>
                <input type="number" name="m_top" id="m_top" class="form-control" value="<?php echo $researchType->m_top ?? 30; ?>">
            </div>
            <div class="mb-3">
                <label for="m_left" class="form-label">Map Left</label>
                <input type="number" name="m_left" id="m_left" class="form-control" value="<?php echo $researchType->m_left ?? 0; ?>">
            </div>
            <div class="mb-3">
                <label for="age" class="form-label">Age</label>
                <input type="number" name="age" id="age" class="form-control" value="<?php echo $researchType->age ?? 1; ?>">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="age_need" id="age_need" <?php echo ($researchType->age_need ?? true) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="age_need">Age Need</label>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="index.php?page=research_types" class="btn btn-secondary">Cancel</a>
        </form>
        <?php else: ?>
        <a href="index.php?page=research_types&action=add" class="btn btn-primary mb-3">Add New Research Type</a>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Cost</th>
                    <th>Age</th>
                    <th>Age Need</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($researchTypes as $rt): ?>
                <tr>
                    <td><?php echo $rt->id; ?></td>
                    <td><?php echo $rt->title; ?></td>
                    <td><?php echo $rt->cost; ?></td>
                    <td><?php echo $rt->age; ?></td>
                    <td><?php echo $rt->age_need ? 'Yes' : 'No'; ?></td>
                    <td>
                        <a href="index.php?page=research_types&action=edit&id=<?php echo $rt->id; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="index.php?page=research_types&action=delete&id=<?php echo $rt->id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
