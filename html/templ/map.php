<?php
$page_title = 'Карта игры';
include 'partials/header.php';
?>
<!-- Original game styles -->
<link type="text/css" href="css/city.css" rel="Stylesheet" />
<link type="text/css" href="css/map.css" rel="Stylesheet" />
<link type="text/css" href="css/style.css?v=2" rel="Stylesheet" />

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
	<table>
		<tr>
			<td>
				<div id="city-window-title">...</div>
				<div id="city-window-eat-info">...</div>
				<div>
					Население: <span id="city-window-population">20</span>
				</div>
				<div>
					Культура: <span id="city-window-culture">0</span> / <span id="city-window-culture-up">20</span>
				</div>
				<div>
					Уровень: <span id="city-window-culture-level">0</span>
				</div>
			</td>
			<td id="city-resource-info"></td>
		</tr>
	</table>
	<div id="city-window-bottom-panel">
		<table>
			<tr>
				<td>
				<div id="city-window-up-panel">
					Недовольных: <span id="city-window-people-dis">-</span><br>
					Нормальных: <span id="city-window-people-norm">-</span><br>
					Счастливых: <span id="city-window-people-happy">-</span><br>
					Художников: <span id="city-window-people-artist">-</span>
				</div>
					<div id="city-map">
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
					</div>
				</td>
				<td valign="top" style="padding-left: 20px;">
					<div id="city-production-select">
						<div id="city-production-select-pic">
							<img src="./img/units/53.png">
						</div>
						<div id="city-production-select-title"></div>
					</div>
					<div id="city-production-list"></div>
					<div id="city-building-list"></div>
				</td>
			</tr>
			<tr>
				<td colspan="4" style="white-space: nowrap;">
					Производство: <span id="city-window-pwork-info">-</span> |
					Еда: <span id="city-window-peat-info">-</span> |
					Деньги: <span id="city-window-pmoney-info">-</span> |
					Исследования: <span id="city-window-presearch-info">-</span>
				</td>
			</tr>
		</table>
	</div>
</div>
<div id="empire-window"></div>
<div id="event-window-research" eid="">...</div>
<div id="event-window-city" eid="" cid="">...</div>

<script src="js/functions.js"></script>
<script src="js/map.js"></script>
<script src="js/unit.js?v=2"></script>
<script src="js/city.js"></script>
<script src="js/events.js"></script>
<script src="js/research.js"></script>
<script src="js/messages.js"></script>
<?php
$page_scripts = ob_get_clean();
include 'partials/footer.php';
?>
