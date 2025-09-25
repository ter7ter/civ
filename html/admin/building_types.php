<?php
require_once("../includes.php");

$action = $_REQUEST['action'] ?? 'list';
$id = $_REQUEST['id'] ?? null;

if ($action == 'edit' && $id) {
    $buildingType = BuildingType::get($id);
    if (!$buildingType) {
        echo "Building type not found.";
        exit;
    }
} elseif ($action == 'save') {
    if ($id) {
        $buildingType = BuildingType::get($id);
        if (!$buildingType) {
            echo "Building type not found.";
            exit;
        }
    } else {
        $buildingType = new BuildingType([]);
    }

    $buildingType->title = $_POST['title'];
    $buildingType->cost = (int)$_POST['cost'];
    $buildingType->need_coastal = isset($_POST['need_coastal']);
    $buildingType->culture = (int)$_POST['culture'];
    $buildingType->upkeep = (int)$_POST['upkeep'];
    $buildingType->culture_bonus = (int)$_POST['culture_bonus'];
    $buildingType->research_bonus = (int)$_POST['research_bonus'];
    $buildingType->money_bonus = (int)$_POST['money_bonus'];
    $buildingType->description = $_POST['description'];

    $buildingType->req_research = json_decode($_POST['req_research'], true) ?: [];
    $buildingType->req_resources = json_decode($_POST['req_resources'], true) ?: [];
    $buildingType->need_research = json_decode($_POST['need_research'], true) ?: [];

    $buildingType->save();
    header("Location: index.php?page=building_types");
    exit;
} elseif ($action == 'delete' && $id) {
    $buildingType = BuildingType::get($id);
    if ($buildingType) {
        $buildingType->delete();
    }
    header("Location: index.php?page=building_types");
    exit;
}

$buildingTypes = BuildingType::getAll();
?>

<h2>Building Types</h2>

<?php if ($action == 'edit' || $action == 'add'): ?>
<form method="POST" action="index.php?page=building_types&action=save<?= $id ? '&id=' . $id : '' ?>">
    <label>Title: <input type="text" name="title" value="<?= $buildingType->title ?? '' ?>" required></label><br>
    <label>Cost: <input type="number" name="cost" value="<?= $buildingType->cost ?? 0 ?>"></label><br>
    <label>Need Coastal: <input type="checkbox" name="need_coastal" <?= ($buildingType->need_coastal ?? false) ? 'checked' : '' ?>></label><br>
    <label>Culture: <input type="number" name="culture" value="<?= $buildingType->culture ?? 0 ?>"></label><br>
    <label>Upkeep: <input type="number" name="upkeep" value="<?= $buildingType->upkeep ?? 0 ?>"></label><br>
    <label>Culture Bonus: <input type="number" name="culture_bonus" value="<?= $buildingType->culture_bonus ?? 0 ?>"></label><br>
    <label>Research Bonus: <input type="number" name="research_bonus" value="<?= $buildingType->research_bonus ?? 0 ?>"></label><br>
    <label>Money Bonus: <input type="number" name="money_bonus" value="<?= $buildingType->money_bonus ?? 0 ?>"></label><br>
    <label>Description: <textarea name="description"><?= $buildingType->description ?? '' ?></textarea></label><br>
    <label>Req Research (JSON): <textarea name="req_research"><?= json_encode($buildingType->req_research ?? []) ?></textarea></label><br>
    <label>Req Resources (JSON): <textarea name="req_resources"><?= json_encode($buildingType->req_resources ?? []) ?></textarea></label><br>
    <label>Need Research (JSON): <textarea name="need_research"><?= json_encode($buildingType->need_research ?? []) ?></textarea></label><br>
    <input type="submit" value="Save">
    <a href="index.php?page=building_types">Cancel</a>
</form>
<?php else: ?>
<a href="index.php?page=building_types&action=add">Add New Building Type</a>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Cost</th>
        <th>Culture</th>
        <th>Upkeep</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($buildingTypes as $bt): ?>
    <tr>
        <td><?= $bt->id ?></td>
        <td><?= $bt->title ?></td>
        <td><?= $bt->cost ?></td>
        <td><?= $bt->culture ?></td>
        <td><?= $bt->upkeep ?></td>
        <td>
            <a href="index.php?page=building_types&action=edit&id=<?= $bt->id ?>">Edit</a>
            <a href="index.php?page=building_types&action=delete&id=<?= $bt->id ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
