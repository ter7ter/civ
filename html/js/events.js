function get_next_event(del = false) {
	var vals = {};
	if (del) {
		vals['del'] = del;
	}
	$.post('index.php?method=event&json=1', vals, function (data) {
		resp = $.parseJSON(data);
		if (resp.status == 'ok') {
			if (resp.data.type == 'none') {
				//Больше нет событий
				return ;
			} else if (resp.data.type == 'research') {
				$('#event-window-research-title').text(resp.data.research_title);
				$('#event-window-select-research').empty();
				for (var i in resp.data.aresearch) {
					var research = resp.data.aresearch[i];
					$('#event-window-select-research').append('<option value="' + research.id + '">' +
						research.title + '(' + research.turns + ') ходов' +
						'</option>');
				}
				$('#event-window-research').attr('eid', resp.data.id);
				$('#event-window-research').show();
			} else if (resp.data.type == 'city_building' || resp.data.type == 'city_unit') {
				$('#event-window-city').attr('eid', resp.data.id);
				$('#event-window-city').attr('cid', resp.data.city_id);
				$('#event-window-city-title').text(resp.data.city_title);
				$('#event-window-city-build').text(resp.data.build_title);
				$('#event-window-select-build').empty();
				for (var i in resp.data.possible_units) {
					var unit = resp.data.possible_units[i];
					$('#event-window-select-build').append('<option value="unit' + unit.id + '">' +
						unit.title + '(' + unit.turns + ') ходов' +
						'</option>');
				}
				for (var i in resp.data.buildings_possible) {
					var building = resp.data.buildings_possible[i];
					$('#event-window-select-build').append('<option value="buil' + building.id + '">' +
						building.title + '(' + building.turns + ') ходов' +
						'</option>');
				}
				$('#event-window-city').show();
			}
		} else {
			window.alert(resp.error);
		}
	});
}
$(document).on('click', '#event-window-research-ok', function (e) {
	$.post('index.php?method=research&json=1', {'rid': $('#event-window-select-research').val()}, function(data) {
		resp = $.parseJSON(data);
		if (resp.status == 'ok') {
			$('#event-window-research').hide();
			map.show_cell_info();
			get_next_event($('#event-window-research').attr('eid'));
		} else {
			window.alert(resp.error);
		}
	});
});
$(document).on('click', '#event-window-research-cancel', function (e) {
	$('#event-window-research').hide();
	get_next_event($('#event-window-research').attr('eid'));
});
$(document).on('click', '#event-window-build-ok', function (e) {
	var vals = {'cid': $('#event-window-city').attr('cid')};
	var build = $('#event-window-select-build').val();
	vals['production_type'] = build.substr(0, 4);
	vals['production'] = build.substr(4);
	$.post('index.php?method=city&json=1', vals, function(data) {
		resp = $.parseJSON(data);
		if (resp.status == 'ok') {
			$('#event-window-city').hide();
			map.show_cell_info();
			get_next_event($('#event-window-city').attr('eid'));
		} else {
			window.alert(resp.error);
		}
	});
});
$(document).on('click', '#event-window-build-tocity', function (e) {
	$.post('index.php?method=event&json=1', {del: $('#event-window-city').attr('eid')}, function (data) {
		$('#event-window-city').hide();
		resp = $.parseJSON(data);
		if (resp.status == 'ok') {
			if (resp.type != 'none') {
				city.load_events_after = true;
			}
			city.load($('#event-window-city').attr('cid'));
		} else {
			window.alert(resp.error);
		}
	});
});
$(document).ready(function (e) {
	map.load(false);
	messages.load();
	get_next_event();
});
$(document).on('keydown', 'body', function(e) {
	if (!selected_unit) return true;
	var dx = 0;
	var dy = 0;
	if (e.originalEvent.code == "Numpad8") {
		dy = -1;
	} else if (e.originalEvent.code == "Numpad7") {
		dy = -1;
		dx = -1;
	} else if (e.originalEvent.code == "Numpad9") {
		dy = -1;
		dx = 1;
	} else if (e.originalEvent.code == "Numpad4") {
		dx = -1;
	} else if (e.originalEvent.code == "Numpad6") {
		dx = 1;
	} else if (e.originalEvent.code == "Numpad1") {
		dx = -1;
		dy = 1;
	} else if (e.originalEvent.code == "Numpad2") {
		dy = 1;
	} else if (e.originalEvent.code == "Numpad3") {
		dy = 1;
		dx = 1;
	} else {
		return true;
	}
	selected_unit.move(dx, dy);
	return true;
});
$(document).on('keydown', 'body', function(e) {
	if (e.originalEvent.code == "ArrowLeft") {
		map.left();
	}
	if (e.originalEvent.code == "ArrowRight") {
		map.right();
	}
	if (e.originalEvent.code == "ArrowUp") {
		map.up();
	}
	if (e.originalEvent.code == "ArrowDown") {
		map.down();
	}
	if (e.originalEvent.code == "Enter" || e.originalEvent.code == "NumpadEnter") {
		next_step();
	}
	return true;
});
$(document).on('click', '#cellmenu ul li', function (e) {
	var x = $('#cellmenu').attr('map-x');
	var y = $('#cellmenu').attr('map-y');
	var cell = map.get_cell(x, y);
	if ($(e.target).hasClass('open-city')) {
		map.center_x = x;
		map.center_y = y;
		map.load();
		city.load($(e.target).closest('li').attr('cid'));
	} else {
		var unit = cell.units[$(e.target).closest('li').attr('unit')];
		unit.select();
		map.show_cell_info();
	}
	$('#cellmenu').hide();
});
//Центровка карты на юните
$(document).on('click', '#selected-unit-info img', function (e) {
	map.center_x = selected_unit.x;
	map.center_y = selected_unit.y;
	map.load();
});
//Центровка карты на выделенной клетке
$(document).on('click', '#selected-cell-info', function (e) {
	map.center_x = map.select_x;
	map.center_y = map.select_y;
	map.load();
});
$(document).on('click', 'body', function (e) {
	if ($(e.target).closest('#cellmenu').length == 0 && $(e.target).closest('.map_cell').length == 0 ) {
		$("#cellmenu").hide();
	}
	if (selected_unit.select_mission &&
		$(e.target).closest('.map_cell').length == 0 &&
		$(e.target).closest('.unit-do-mission[mid=move_to]').length == 0) {
		selected_unit.cancel_select_target();
	}
});
$(document).on('click', '#do-next-step', function(e) {
	next_step();
});
$(document).on('click', '#open-empire', function(e) {
	$.post('index.php?method=emperie', {}, function(data) {
		$('#empire-window').html(data);
		$('#empire-window').toggle();
		$('#empire-window').css('background-image', "");
	});
});
$(document).on('click', '.emperie-city-line', function(e) {
	$('#empire-window').hide();
	city.load($(e.currentTarget).closest('tr').attr('cid'));
});
$(document).on('click', '#game-info-window', function(e) {
	if ($('.game-info-player:visible').length > 1) {
		$('.game-info-player').hide();
		$('.game-info-you-player').show();
	} else {
		$('.game-info-player').show();
	}
});