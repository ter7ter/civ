<?php
$page_title = 'Карта игры';
include 'partials/header.php';
?>
<!-- Original game styles -->
<link type="text/css" href="css/city.css" rel="Stylesheet" />
<link type="text/css" href="css/map.css" rel="Stylesheet" />
<link type="text/css" href="css/style.css" rel="Stylesheet" />

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-auto">
            <div class="text-end mb-2">
                <a href="index.php?method=selectgame">Выход к выбору игры</a>
            </div>
            <div class="d-flex align-items-start">
                <!-- Map Wrapper -->
                <div id="map-wrapper" style="position: relative;">
                    <div id="mapv"></div>
                    <div id="turninfo-container">
                        <!-- Content loaded via AJAX -->
                    </div>
                    <button onclick="map.up()" class="btn btn-secondary btn-sm" style="position: absolute; top: 5px; left: 50%; transform: translateX(-50%); z-index: 10;">/\</button>
                    <button onclick="map.down()" class="btn btn-secondary btn-sm" style="position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); z-index: 10;">\/</button>
                    <button onclick="map.left()" class="btn btn-secondary btn-sm" style="position: absolute; left: 5px; top: 50%; transform: translateY(-50%); z-index: 10;">&lt;</button>
                    <button onclick="map.right()" class="btn btn-secondary btn-sm" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); z-index: 10;">&gt;</button>
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

<script src="js/functions.js"></script>
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
