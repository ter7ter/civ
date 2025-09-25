<?php
require_once("../includes.php");

$action = $_REQUEST['action'] ?? 'list';
$id = $_REQUEST['id'] ?? null;

if ($action == 'edit' && $id) {
    $unitType = UnitType::get($id);
    if (!$unitType) {
        echo "Unit type not found.";
        exit;
    }
} elseif ($action == 'save') {
    if ($id) {
        $unitType = UnitType::get($id);
        if (!$unitType) {
            echo "Unit type not found.";
            exit;
        }
    } else {
        $unitType = new UnitType([]);
    }

    $unitType->title = $_POST['title'];
    $unitType->points = (int)$_POST['points'];
    $unitType->cost = (int)$_POST['cost'];
    $unitType->population_cost = (int)$_POST['population_cost'];
    $unitType->type = $_POST['type'];
    $unitType->attack = (int)$_POST['attack'];
    $unitType->defence = (int)$_POST['defence'];
    $unitType->health = (int)$_POST['health'];
    $unitType->movement = (int)$_POST['movement'];
    $unitType->upkeep = (int)$_POST['upkeep'];
    $unitType->can_found_city = isset($_POST['can_found_city']);
    $unitType->can_build = isset($_POST['can_build']);
    $unitType->description = $_POST['description'];
    $unitType->age = (int)$_POST['age'];

    $unitType->missions = explode(',', $_POST['missions']);
    $unitType->can_move = json_decode($_POST['can_move'], true) ?: [];

    $unitType->save();
    header("Location: index.php?page=unit_types");
    exit;
} elseif ($action == 'delete' && $id) {
    $unitType = UnitType::get($id);
    if ($unitType) {
        $unitType->delete();
    }
    header("Location: index.php?page=unit_types");
    exit;
}

$unitTypes = UnitType::getAll();
?>

<h2>Unit Types</h2>

<?php if ($action == 'edit' || $action == 'add'): ?>
<form method="POST" action="index.php?page=unit_types&action=save<?= $id ? '&id=' . $id : '' ?>">
    <label>Title: <input type="text" name="title" value="<?= $unitType->title ?? '' ?>" required></label><br>
    <label>Points: <input type="number" name="points" value="<?= $unitType->points ?? 1 ?>"></label><br>
    <label>Cost: <input type="number" name="cost" value="<?= $unitType->cost ?? 0 ?>"></label><br>
    <label>Population Cost: <input type="number" name="population_cost" value="<?= $unitType->population_cost ?? 0 ?>"></label><br>
    <label>Type: <select name="type">
        <option value="land" <?= ($unitType->type ?? 'land') == 'land' ? 'selected' : '' ?>>Land</option>
        <option value="water" <?= ($unitType->type ?? 'land') == 'water' ? 'selected' : '' ?>>Water</option>
        <option value="air" <?= ($unitType->type ?? 'land') == 'air' ? 'selected' : '' ?>>Air</option>
    </select></label><br>
    <label>Attack: <input type="number" name="attack" value="<?= $unitType->attack ?? 0 ?>"></label><br>
    <label>Defence: <input type="number" name="defence" value="<?= $unitType->defence ?? 0 ?>"></label><br>
    <label>Health: <input type="number" name="health" value="<?= $unitType->health ?? 1 ?>"></label><br>
    <label>Movement: <input type="number" name="movement" value="<?= $unitType->movement ?? 1 ?>"></label><br>
    <label>Upkeep: <input type="number" name="upkeep" value="<?= $unitType->upkeep ?? 0 ?>"></label><br>
    <label>Can Found City: <input type="checkbox" name="can_found_city" <?= ($unitType->can_found_city ?? false) ? 'checked' : '' ?>></label><br>
    <label>Can Build: <input type="checkbox" name="can_build" <?= ($unitType->can_build ?? false) ? 'checked' : '' ?>></label><br>
    <label>Description: <textarea name="description"><?= $unitType->description ?? '' ?></textarea></label><br>
    <label>Age: <input type="number" name="age" value="<?= $unitType->age ?? 1 ?>"></label><br>
    <label>Missions (comma separated): <input type="text" name="missions" value="<?= implode(',', $unitType->missions ?? []) ?>"></label><br>
    <label>Can Move (JSON): <textarea name="can_move"><?= json_encode($unitType->can_move ?? []) ?></textarea></label><br>
    <input type="submit" value="Save">
    <a href="index.php?page=unit_types">Cancel</a>
</form>
<?php else: ?>
<a href="index.php?page=unit_types&action=add">Add New Unit Type</a>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Type</th>
        <th>Cost</th>
        <th>Attack</th>
        <th>Defence</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($unitTypes as $ut): ?>
    <tr>
        <td><?= $ut->id ?></td>
        <td><?= $ut->title ?></td>
        <td><?= $ut->type ?></td>
        <td><?= $ut->cost ?></td>
        <td><?= $ut->attack ?></td>
        <td><?= $ut->defence ?></td>
        <td>
            <a href="index.php?page=unit_types&action=edit&id=<?= $ut->id ?>">Edit</a>
            <a href="index.php?page=unit_types&action=delete&id=<?= $ut->id ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
