<?php 
$page_title = 'Карта игры';
include 'partials/header.php'; 
?>
<style>
    #cellinfo {
        background-color: #212529 !important; /* Override beige background for dark theme */
    }
    #game-info-window {
        background-color: #495057 !important; /* Override lightblue background for dark theme */
    }
    body {
        background-color: #212529 !important; /* Override legacy background */
    }
    #cellinfo {
        margin-top: 0 !important;
        float: none !important;
        display: inline-block;
        vertical-align: top;
        height: 600px; /* Adjust height to align better */
    }
    #mapv {
        display: inline-block;
    }
</style>
<!-- Original game styles -->
<link type="text/css" href="css/style.css" rel="stylesheet" />
<link type="text/css" href="css/city.css" rel="Stylesheet" />
<link type="text/css" href="css/map.css" rel="Stylesheet" />

<div style="width: 1200px; margin: 20px auto; position: relative;">
    
    <!-- Main Game Content -->
    <div id="mapv"></div>
    <div id="cellinfo">info</div>
    <input type="button" value="/\" onclick="map.up()" class="map_move" style="width: 100px; height: 25px; margin-left:-766px; margin-top:-590px;" />
    <input type="button" value="\/" onclick="map.down()" class="map_move" style="width: 100px; height: 25px; margin-left:-766px; margin-top:45px;" />
    <input type="button" value="<" onclick="map.left()" class="map_move" style="width: 25px; height: 100px; margin-left:-1122px; margin-top:-310px;" />
    <input type="button" value=">" onclick="map.right()" class="map_move" style="width: 25px; height: 100px; margin-left:-337px; margin-top:-310px;" />
    <div id="cellmenu" map-x="0" map-y="0"></div>
    <div id="message-window">
        <div id="message-window-lines"></div>
        <div id="message-window-tabs">
            <div class="message-window-tab message-window-tab-active" id="mw-all-messages">Все</div>
            <div class="message-window-tab" id="mw-system-messages">Системные</div>
            <div class="message-window-tab" id="mw-chat-messages">Чат</div>
        </div>
        <input type="text" id="message-window-input">
    </div>

    <!-- Right side buttons -->
    <div style="position: absolute; top: 80px; left: 1120px; width: 80px;">
        <div class="d-grid gap-3">
            <button type="button" id="open-empire" class="btn btn-primary py-3">Империя</button>
            <button type="button" id="open-research" class="btn btn-info py-3">Исслед.</button>
            <button type="button" onclick="window.location.href='index.php?method=logout'" class="btn btn-danger py-3">Выход</button>
        </div>
    </div>

    <!-- Modals and other windows -->
    <div id="city-create-window">
    Название города<br>
    <input type="text" style="width: 280px;" id="city-create-title"><br><br>
    <input type="button" value="OK" onclick="selected_unit.create_city()">
    <input type="button" value="Отмена" onclick="$(\'#city-create-window\').hide()">
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
        <div class="city-window-bg city-small-bg" style="width: 269px;height: 222px;margin-top: 208px;">&nbsp;</div>
        <div class="city-window-bg city-small-bg" style="width: 470px;height: 222px;margin-top: 208px;margin-left: 491px;">&nbsp;</div>
        <div class="city-window-bg city-small-bg" style="width: 960px;margin-top: 430px;height: 175px;">&nbsp;</div>
        <div class="city-window-bg city-big-bg" style="width: 960px;height: 46px;margin-top: 88px;">&nbsp;</div>
        <div class="city-window-bg city-big-bg" style="width: 269px;margin-top: 134px;height: 74px;">&nbsp;</div>
        <div class="city-window-bg city-big-bg" style="width: 469px;margin-top: 134px;height: 74px;margin-left: 491px;">&nbsp;</div>
        <div class="city-window-bg city-big-bg" style="width: 195px;margin-top: 208px;height: 221px;">&nbsp;</div>
        <div class="city-window-bg city-big-bg" style="width: 395px;margin-top: 208px;height: 221px;margin-left: 565px;">&nbsp;</div>
        <div class="city-window-bg city-big-bg" style="width: 269px;margin-top: 429px;height: 74px;">&nbsp;</div>
        <div class="city-window-bg city-big-bg" style="width: 470px;margin-top: 429px;height: 74px;margin-left: 491px;">&nbsp;</div>
        <div class="city-window-bg city-big-bg" style="width: 960px;margin-top: 503px;height: 102px;">&nbsp;</div>
        <div id="city-window-cell-n1n1" class="city-window-cell" style="margin-top: 210px; margin-left: 270px;"></div>
        <div id="city-window-cell-p0n1" class="city-window-cell" style="margin-top: 210px; margin-left: 343px;"></div>
        <div id="city-window-cell-p1n1" class="city-window-cell" style="margin-top: 210px; margin-left: 418px;"></div>
        <div id="city-window-cell-n1p0" class="city-window-cell" style="margin-top: 283px; margin-left: 270px;"></div>
        <div id="city-window-cell-p1p0" class="city-window-cell" style="margin-top: 283px; margin-left: 418px;"></div>
        <div id="city-window-cell-n1p1" class="city-window-cell" style="margin-top: 356px; margin-left: 270px;"></div>
        <div id="city-window-cell-p0p1" class="city-window-cell" style="margin-top: 356px; margin-left: 343px;"></div>
        <div id="city-window-cell-p1p1" class="city-window-cell" style="margin-top: 356px; margin-left: 418px;"></div>

        <div id="city-building-list">X</div>
        <div id="city-production-list">X</div>
        <div id="city-window-bottom-panel">
            <div><b>Жители:</b>
                несчастные: <span id="city-window-people-dis"></span> |
                довольные: <span id="city-window-people-norm"></span> |
                счастливые: <span id="city-window-people-happy"></span> |
                артисты:  <span id="city-window-people-artist"></span>
            </div>
            <div style="float: left">
                Производство/ход: <span id="city-window-pwork-info"></span><br>
                Еда/ход: <span id="city-window-peat-info"></span><br>
                Деньги/ход: <span id="city-window-pmoney-info"></span><br>
                Наука/ход: <span id="city-window-presearch-info"></span><br>
                Еды накоплено: <span id="city-window-eat-info"></span><br>
            </div>
            <div id="city-production-select">
                        <div id="city-production-select-pic"><img src="./img/icons/hammer.svg"></div>			<div id="city-production-select-title">Нет</div>
            </div>
        </div>
    </div>
    <div id="empire-window">
    </div>
    <div id="event-window-research" eid="">
        Завершено исследование <span id="event-window-research-title"></span><br>
        Что исследовать дальше?<br>
        <select id="event-window-select-research">

        </select><br>
        <input type="button" id="event-window-research-ok" value="OK">
        <input id="event-window-research-cancel" type="button" value="Отмена">
    </div>
    <div id="event-window-city" eid="" cid="">
        В городе <span id="event-window-city-title"></span> завершено производство
        <span id="event-window-city-build"></span><br>
        Что производить дальше?<br>
        <select id="event-window-select-build">

        </select><br>
        <input type="button" id="event-window-build-ok" value="OK">
        <input id="event-window-build-tocity" type="button" value="Перейти к городу">
    </div>
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