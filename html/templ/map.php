<?php 
$page_title = 'Карта игры';
include 'partials/header.php'; 
?>
<style>
    body {
        background-color: #212529 !important; 
    }
    #cellinfo {
        background-color: #343a40 !important; 
        color: #f8f9fa;
        height: 648px; 
        overflow-y: auto;
        padding: 10px;
    }
    #game-info-window {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 280px;
        background-color: rgba(33, 37, 41, 0.8) !important; /* Dark, semi-transparent background */
        padding: 10px;
        border-radius: 5px;
        z-index: 10;
        color: #f8f9fa;
    }
    .cell-info-img {
        float: none !important;
    }
</style>
<!-- Original game styles -->
<link type="text/css" href="css/city.css" rel="Stylesheet" />
<link type="text/css" href="css/map.css" rel="Stylesheet" />
<link type="text/css" href="css/style.css" rel="Stylesheet" />

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-auto">
            <div class="d-flex align-items-start">
                <!-- Map Wrapper -->
                <div id="map-wrapper" style="position: relative; width: 792px; height: 648px;">
                    <div id="mapv"></div>
                    <button onclick="map.up()" class="btn btn-secondary btn-sm" style="position: absolute; top: 5px; left: 50%; transform: translateX(-50%); z-index: 10;">/\</button>
                    <button onclick="map.down()" class="btn btn-secondary btn-sm" style="position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); z-index: 10;">\/</button>
                    <button onclick="map.left()" class="btn btn-secondary btn-sm" style="position: absolute; left: 5px; top: 50%; transform: translateY(-50%); z-index: 10;">&lt;</button>
                    <button onclick="map.right()" class="btn btn-secondary btn-sm" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); z-index: 10;">&gt;</button>
                    
                    <div id="game-info-window">
                        <?php foreach ($data['players'] as $player):?>
                        <div class="game-info-player<?=($player['login']==$data['user_login']) ? ' game-info-you-player' : ''?>">
                            <b><?=$player['turn_order']?>.</b> Игрок <span style="color: <?=$player['color']?>; font-weight: bold"><?=$player['login']?></span>
                            <?if ($player['turn_status'] == 'wait') echo 'Ждёт своего хода';
                            elseif ($player['turn_status'] == 'play') echo 'Ходит';
                            elseif ($player['turn_status'] == 'end') echo 'Закончил ход'; ?>
                        </div>
                        <?endforeach;?>
                    </div>
                </div>

                <!-- Right Info Panel -->
                <div id="cellinfo" style="width: 300px; margin-left: 10px;">
                    <!-- Content is loaded via AJAX -->
                </div>

                <!-- Main Action Buttons -->
                <div style="width: 100px; margin-left: 10px;">
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
