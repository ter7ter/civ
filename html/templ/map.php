<?php 
$page_title = 'Карта игры';
include 'partials/header.php'; 
?>
<style>
    #cellinfo {
        background-color: #212529 !important; 
    }
    #game-info-window {
        background-color: #495057 !important; 
    }
    body {
        background-color: #212529 !important; 
    }
</style>
<!-- Original game styles -->
<link type="text/css" href="css/style.css" rel="stylesheet" />
<link type="text/css" href="css/city.css" rel="Stylesheet" />
<link type="text/css" href="css/map.css" rel="Stylesheet" />

<div style="width: 1200px; margin: 20px auto; position: relative;">
    
    <!-- Map and its controls wrapper -->
    <div id="map-wrapper" style="position: relative; width: 792px; /* 11*72px */ height: 648px; /* 9*72px */ float: left;">
        <div id="mapv"></div>
        
        <!-- Map Navigation Buttons -->
        <button onclick="map.up()" class="btn btn-secondary" style="position: absolute; top: 5px; left: 50%; transform: translateX(-50%); z-index: 10;">/\</button>
        <button onclick="map.down()" class="btn btn-secondary" style="position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); z-index: 10;">\/</button>
        <button onclick="map.left()" class="btn btn-secondary" style="position: absolute; left: 5px; top: 50%; transform: translateY(-50%); z-index: 10;">&lt;</button>
        <button onclick="map.right()" class="btn btn-secondary" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); z-index: 10;">&gt;</button>
    </div>

    <!-- Right Info Panel -->
    <div id="cellinfo" style="width: 300px; float: left; margin-left: 10px; height: 648px; overflow-y: auto;">info</div>

    <!-- Main action buttons -->
    <div style="position: absolute; top: 0px; right: -100px; width: 90px;">
        <div class="d-grid gap-3">
            <button type="button" id="open-empire" class="btn btn-primary py-3">Империя</button>
            <button type="button" id="open-research" class="btn btn-info py-3">Исслед.</button>
            <button type="button" onclick="window.location.href='index.php?method=logout'" class="btn btn-danger py-3">Выход</button>
        </div>
    </div>

    <!-- Chat window below -->
    <div id="message-window" style="clear: both;">
        <div id="message-window-lines"></div>
        <div id="message-window-tabs">
            <div class="message-window-tab message-window-tab-active" id="mw-all-messages">Все</div>
            <div class="message-window-tab" id="mw-system-messages">Системные</div>
            <div class="message-window-tab" id="mw-chat-messages">Чат</div>
        </div>
        <input type="text" id="message-window-input">
    </div>

    <!-- Absolutely positioned elements that should be relative to the page or a higher container -->
    <div id="cellmenu" map-x="0" map-y="0"></div>

    <!-- Modals and other windows -->
    <div id="city-create-window">
    Название города<br>
    <input type="text" style="width: 280px;" id="city-create-title"><br><br>
    <input type="button" value="OK" onclick="selected_unit.create_city()">
    <input type="button" value="Отмена" onclick="$('#city-create-window').hide()">
    </div>
    <div id="city-window">
        <div id="city-window-title">City1</div>
        <div id="city-window-close">X</div>
        <div id="city-window-up-panel">
            Население: <span id="city-window-population"></span> |
            Культура: <span id="city-window-culture"></span> / <span id="city-window-culture-up"></span> |
            Уровень культуры: <span id="city-window-culture-level"></span>
        </div>
        <div id="city-resource-info">Ресурсы:</div>
        <div class="city-window-bg city-small-bg" style="width: 960px;margin-top: 88px;height: 120px;">&nbsp;</div>
        <!-- ... (rest of the original modal content) ... -->
    </div>
    <div id="empire-window"></div>
    <div id="event-window-research" eid="">...</div>
    <div id="event-window-city" eid="" cid="">...</div>
</div>

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
