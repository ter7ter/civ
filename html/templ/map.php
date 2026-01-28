<?php
$page_title = "Карта игры";
include "partials/header.php";
?>
<!-- Original game styles -->
<link type="text/css" href="css/city.css" rel="Stylesheet" />
<link type="text/css" href="css/map.css" rel="Stylesheet" />
<link type="text/css" href="css/style.css" rel="Stylesheet" />
<link type="text/css" href="css/research.css" rel="Stylesheet" />
<link type="text/css" href="css/events.css" rel="Stylesheet" />

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
                        <button type="button" id="open-full-map" class="btn btn-success py-3">Карта</button>
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
                <div style="margin-top: 5px;">
                    <input type="text" id="message-window-input" class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Original Modals (should be outside the main layout flow) -->
<div id="cellmenu" map-x="0" map-y="0"></div>
<div id="city-create-window"></div>
<div id="city-window">
	<div id="city-window-close" onclick="$('#city-window').hide();">X</div>
	<div id="city-window-title">...</div>

    <!-- Контейнер для карты, центрированный -->
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
        <div id="city-map" style="position: relative;top: 3px;left: 3px;">
            <div class="city-small-bg" style="display: none;">
                <table>
					<tr>
						<td class="city-window-cell" id="city-window-cell-n1n1"></td>
						<td class="city-window-cell" id="city-window-cell-n1p0"></td>
						<td class="city-window-cell" id="city-window-cell-n1p1"></td>
					</tr>
					<tr>
						<td class="city-window-cell" id="city-window-cell-p0n1"></td>
						<td></td>
						<td class="city-window-cell" id="city-window-cell-p0p1"></td>
					</tr>
					<tr>
						<td class="city-window-cell" id="city-window-cell-p1n1"></td>
						<td class="city-window-cell" id="city-window-cell-p1p0"></td>
						<td class="city-window-cell" id="city-window-cell-p1p1"></td>
					</tr>
				</table>
            </div>
            <div class="city-big-bg" style="display: none;">
                <table>
                    <tr>
                        <td class="city-window-cell"></td>
                        <td class="city-window-cell" id="city-window-cell-n2n1"></td>
                        <td class="city-window-cell" id="city-window-cell-n2p0"></td>
                        <td class="city-window-cell" id="city-window-cell-n2p1"></td>
                        <td class="city-window-cell"></td>
                    </tr>
					<tr>
                        <td class="city-window-cell" id="city-window-cell-n1n2"></td>
						<td class="city-window-cell" id="city-window-cell-n1n1"></td>
						<td class="city-window-cell" id="city-window-cell-n1p0"></td>
						<td class="city-window-cell" id="city-window-cell-n1p1"></td>
                        <td class="city-window-cell" id="city-window-cell-n1p2"></td>
					</tr>
					<tr>
                        <td class="city-window-cell" id="city-window-cell-p0n2"></td>
						<td class="city-window-cell" id="city-window-cell-p0n1"></td>
						<td></td>
						<td class="city-window-cell" id="city-window-cell-p0p1"></td>
                        <td class="city-window-cell" id="city-window-cell-p0p2"></td>
					</tr>
					<tr>
                        <td class="city-window-cell" id="city-window-cell-p1n2"></td>
						<td class="city-window-cell" id="city-window-cell-p1n1"></td>
						<td class="city-window-cell" id="city-window-cell-p1p0"></td>
						<td class="city-window-cell" id="city-window-cell-p1p1"></td>
                        <td class="city-window-cell" id="city-window-cell-p1p2"></td>
					</tr>
                    <tr>
                        <td class="city-window-cell"></td>
                        <td class="city-window-cell" id="city-window-cell-p2n1"></td>
                        <td class="city-window-cell" id="city-window-cell-p2p0"></td>
                        <td class="city-window-cell" id="city-window-cell-p2p1"></td>
                        <td class="city-window-cell"></td>
                    </tr>
				</table>
            </div>
        </div>
    </div>

    <!-- Левая панель -->
    <div class="city-panel" style="position: absolute; left: 20px; top: 60px; width: 250px; height: calc(100% - 100px); display: flex; flex-direction: column;">
        <div id="city-window-eat-info">...</div>
        <div>Население: <span id="city-window-population">20</span></div>
        <div>Культура: <span id="city-window-culture">0</span> / <span id="city-window-culture-up">20</span></div>
        <div>Уровень: <span id="city-window-culture-level">0</span></div>
        <hr>
        <div id="city-window-up-panel" class="info-panel" style="flex-grow: 1;">
            Недовольных: <span id="city-window-people-dis">-</span><br>
            Нормальных: <span id="city-window-people-norm">-</span><br>
            Счастливых: <span id="city-window-people-happy">-</span><br>
            Артистов: <span id="city-window-people-artist">-</span>
        </div>
        <hr>
        <div class="info-panel">
            Производство: <span id="city-window-pwork-info">-</span><br>
            Еда: <span id="city-window-peat-info">-</span><br>
            Деньги: <span id="city-window-pmoney-info">-</span><br>
            Исследования: <span id="city-window-presearch-info">-</span>
        </div>
    </div>

    <!-- Правая панель -->
    <div class="city-panel" style="position: absolute; right: 20px; top: 60px; width: 250px; height: calc(100% - 100px); display: flex; flex-direction: column;">
        <div id="city-resource-info"></div>
        <hr>
        <div style="flex-grow: 1; overflow-y: auto;">
            <div id="city-production-list"></div>
            <div id="city-building-list"></div>
        </div>
        <div id="city-production-select">
            <div id="city-production-select-pic">
                <img src="./img/units/53.png">
            </div>
            <div id="city-production-select-title"></div>
        </div>
    </div>
</div>
<div id="empire-window"></div>
<div id="research-window"></div>
<div id="full-map-window" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90vw; height: 80vh; background-color: rgba(52, 58, 64, 0.95); color: #f8f9fa; border: 1px solid #495057; border-radius: 0.375rem; z-index: 1002; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;">Полная карта</h3>
        <button id="close-full-map" class="btn btn-secondary" style="padding: 5px 10px; font-size: 16px;">Закрыть</button>
    </div>
    <div id="full-map-container" style="width: 100%; height: calc(100% - 40px); overflow: hidden; position: relative; display: flex; justify-content: center;">
        <div id="full-map-content-wrapper" style="position: relative; cursor: grab;">
            <div id="full-map-content" style="position: relative;"></div>
        </div>
    </div>
</div>
<div id="modal-backdrop"></div>
<div id="event-window-research" eid="" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 20px; z-index: 1001;">
    <h3 id="event-window-research-title"></h3>
    <p>Выберите новое исследование:</p>
    <select id="event-window-select-research"></select>
    <button id="event-window-research-ok">OK</button>
    <button id="event-window-research-cancel">Отмена</button>
</div>
<div id="event-window-city" eid="" cid="" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 20px; z-index: 1001;">
    <h3 id="event-window-city-title"></h3>
    <p>Завершено: <span id="event-window-city-build"></span></p>
    <p>Выберите новое производство:</p>
    <select id="event-window-select-build"></select>
    <button id="event-window-build-ok">OK</button>
    <button id="event-window-build-tocity">В город</button>
</div>

<script src="js/functions.js"></script>
<script src="js/map.js"></script>
<script src="js/unit.js"></script>
<script src="js/city.js"></script>
<script src="js/events.js"></script>
<script src="js/fullmap.js"></script>
<script src="js/research.js"></script>
<script src="js/research.js"></script>
<script src="js/messages.js"></script>
<script src="js/forms.js"></script>
<?php
$page_scripts = ob_get_clean();
include "partials/footer.php";


?>
