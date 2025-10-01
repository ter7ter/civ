<?php
use App\MyDB;
use App\UnitType;
use App\BuildingType;
use App\ResearchType;
use App\ResourceType;

require_once(__DIR__ . "/../includes.php");
MyDB::setDBConfig(DB_HOST, DB_USER, DB_PASS, DB_PORT, DB_NAME);
if (isset($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
} else {
    $page = 'production';
}
$page_title = 'Админка - ' . ucfirst($page);
include __DIR__ . '/../templ/partials/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-auto">
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
                <div class="container-fluid">
                    <div class="navbar-nav">
                        <a class="nav-link <?php echo ($page == 'production') ? 'active' : ''; ?>" href="index.php?page=production">ProductionType</a>
                        <a class="nav-link <?php echo ($page == 'unit_types') ? 'active' : ''; ?>" href="index.php?page=unit_types">Unit Types</a>
                        <a class="nav-link <?php echo ($page == 'building_types') ? 'active' : ''; ?>" href="index.php?page=building_types">Building Types</a>
                        <a class="nav-link <?php echo ($page == 'research_types') ? 'active' : ''; ?>" href="index.php?page=research_types">Research Types</a>
                    </div>
                </div>
            </nav>

            <div>
                <?php include(__DIR__ . "/$page.php"); ?>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../templ/partials/footer.php';
?>
