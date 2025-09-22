<?php 
$page_title = 'Карта игры';
include 'partials/header.php'; 
?>
<!-- Original game styles -->
<link type="text/css" href="css/city.css" rel="Stylesheet" />
<link type="text/css" href="css/map.css" rel="Stylesheet" />
<style>
    body {
        background-color: #212529 !important; 
    }
    #cellinfo {
        background-color: #343a40 !important; 
        color: #f8f9fa;
        height: 655px; /* Original height */
        overflow-y: auto;
        padding: 10px;
        border: 1px solid #495057;
        margin-top: 0 !important; /* Override legacy negative margin */
    }
    #game-info-window {
        position: absolute;
        top: 10px;
        right: 300px; /* Position relative to col-auto */
        width: 280px;
        background-color: rgba(33, 37, 41, 0.85) !important; 
        padding: 10px;
        border-radius: .375rem; 
        z-index: 20;
        color: #f8f9fa;
        border: 1px solid #495057;
    }
    .cell-info-img {
        float: none !important; /* Disable float for flexbox layout */
    }
    #message-window {
        border: 1px solid #495057;
        border-radius: .375rem;
    }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-auto">
            <div class="text-end mb-2">
                <a href="index.php?method=selectgame">Выход к выбору игры</a>
            </div>
            <div class="d-flex align-items-start">
                <!-- Map Wrapper -->
                <div id="map-wrapper" style="position: relative; width: 792px; height: 648px;">
                    <div id="mapv"></div>
                    <button onclick="map.up()" class="btn btn-secondary btn-sm" style="position: absolute; top: 5px; left: 50%; transform: translateX(-50%); z-index: 10;">/\</button>
                    <button onclick="map.down()" class="btn btn-secondary btn-sm" style="position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); z-index: 10;">\/</button>
                    <button onclick="map.left()" class="btn btn-secondary btn-sm" style="position: absolute; left: 5px; top: 50%; transform: translateY(-50%); z-index: 10;">&lt;</button>
                    <button onclick="map.right()" class="btn btn-secondary btn-sm" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); z-index: 10;">&gt;</button>
                    <!-- #game-info-window will be moved here by JS -->
                </div>

                <!-- Right Info Panel -->
                <div id="cellinfo" style="width: 300px; margin-left: 10px;">
                    <!-- Content is loaded via AJAX -->
                </div>

                <!-- Main Action Buttons -->
                <div style="width: 100px; margin-left: 10px;" class="align-self-center">
                    <div class="d-grid gap-3">
                        <button type="button" id="open-empire" class="btn btn-primary py-3">Империя</button>
                        <button type="button" id="open-research" class="btn btn-info py-3">Исслед.</button>
                        <button type="button" onclick="window.location.href='index.php?method=logout'" class="btn btn-danger py-3">Выход</button>
                    </div>
                </div>
            </div>

            <!-- Chat window below -->
            <div id="message-window" style="clear: both; margin-top: 10px;">
                <div id="message-window-lines"></div>
                <div id="message-window-tabs">
                    <div class="message-window-tab message-window-tab-active" id="mw-all-messages">Все</div>
                    <div class="message-window-tab" id="mw-system-messages">Системные</div>
                    <div class="message-window-tab" id="mw-chat-messages">Чат</div>
                </div>
                <input type="text" id="message-window-input" class="form-control bg-dark text-light">
            </div>
        </div>
    </div>
</div>


<!-- Original Modals (should be outside the main layout flow) -->
<div id="cellmenu" map-x="0" map-y="0"></div>
<div id="city-create-window">...</div>
<div id="city-window">...</div>
<div id="empire-window"></div>
<div id="event-window-research" eid="">...</div>
<div id="event-window-city" eid="" cid="">...</div>

<?php 
ob_start(); 
?>
<script src="js/map.js"></script>
<script src="js/unit.js"></script>
<script src="js/city.js"></script>
<script src="js/events.js"></script>
<script src="js/research.js"></script>
<script src="js/messages.js"></script>
<?php 
$page_scripts = ob_get_clean();
include 'partials/footer.php'; 
?>
