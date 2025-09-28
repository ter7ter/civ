var city = {
	id: 0,
	x: 0,
	y: 0,
	eat: 0,
	eat_up_multiplier: 1,
	population: 0,
	possible_units: [],
	possible_buildings: [],
	production: false,
	people_cells: [],
	people_dis: 0,
	people_norm: 0,
	people_happy: 0,
	people_artist: 0,
	culture: 0,
	culture_level: 0,
	culture_up: 0,
	buildings: [],
	pwork: 0,
	peat: 0,
	pmoney: 0,
    presearch: 0,
	title: "",
	load_events_after: false,
	load: function(cid, options = {}) {
		options['cid'] = cid;
		$.post('index.php?method=city&json=1', options, function(data) {
			city.id = cid;
			resp = $.parseJSON(data);
			if (resp.status == 'ok') {
				for (var field in resp.data) {
					city[field] = resp.data[field];
				}
				city.draw_production_list();
				city.draw_buildings();
				$('#city-window-title').text(city.title);
				$('#city-window-eat-info').text(city.eat + ' / ' + city.eat_up);
				if (city.production) {
					city.draw_production();
				}
				$(document).on('click', '.city-production-list-item', function(e) {
					city.select_production($(e.target).closest('.city-production-list-item'));
				});
				$('.city-window-bg').hide();
				$('#city-window-population').text(city.population);
				$('#city-window-culture').text(city.culture);
				$('#city-window-culture-up').text(city.culture_up);
				$('#city-window-culture-level').text(city.culture_level);
				if (city.culture_level > 0) {
					$('.city-big-bg').show();
				} else {
					$('.city-small-bg').show();
				}
				$('#city-window').show();
				city.draw_people();
				city.draw_resources();
			} else {
				window.alert(resp.error);
			}
		});
	},
	draw_resources: function() {
		var html = 'Ресурсы:'
		if (this.resources.length == 0) {
			html += ' нет';
		} else {
			html += '<br>';
			for (var i in this.resources) {
				html += '<b>' + this.resources[i].title + '</b>: ' + this.resources[i].count + '<br>';
			}
		}
		$('#city-resource-info').html(html);
	},
	draw_production_list: function() {
		$('#city-production-list').empty();
		let complete = 0;
		if (this.production) {
			complete = this.production.complete;
		}
		for (var i in this.possible_units) {
			let turns = Math.ceil((this.possible_units[i].cost - complete) / this.pwork);
			if (turns < 1) {
				turns = 1;
			}
			$('#city-production-list').append(
				'<div class="city-production-list-item" pid="unit' + this.possible_units[i].id +
				'" cost="' + this.possible_units[i].cost + '">' +
				'<img src="./img/units/' + this.possible_units[i].id + '.png">' +
				'<div class="city-production-list-item-description">' +
				'<div class="city-production-list-item-title">' + city.possible_units[i].title + '</div>' +
				turns + ' ходов ' +
				'</div>' +
				'</div>');
		}
		for (var i in this.possible_buildings) {
			let turns = Math.ceil((this.possible_buildings[i].cost - complete) / this.pwork);
			if (turns < 1) {
				turns = 1;
			}
			$('#city-production-list').append(
				'<div class="city-production-list-item" pid="buil' + this.possible_buildings[i].id + '" cost="' + city.possible_buildings[i].cost + '">' +
				'<img src="./img/buils/' + this.possible_buildings[i].id + '.png">' +
				'<div class="city-production-list-item-description">' +
				'<div class="city-production-list-item-title">' + this.possible_buildings[i].title + '</div>' +
				turns + ' ходов ' +
				'</div>' +
				'</div>');
		}
	},
	draw_buildings: function () {
		$('#city-building-list').empty();
		for (var i in this.buildings) {
			$('#city-building-list').append('<div class="city-building-list-item">' +
				this.buildings[i].title +
				'</div>'
			);
		}
	},
	draw_people: function() {
		$('.city_map_citizen').remove();
		for (var i in this.people_cells) {
			var dx = diff_coord(this.people_cells[i].x, this.x, map.max_x);
			var dy = diff_coord(this.people_cells[i].y, this.y, map.max_y);
			var citizen = $('<div class="city_map_citizen"></div>');
			citizen.html('П: ' + this.people_cells[i].work + '<br>'
				+ 'Е: ' + this.people_cells[i].eat + '<br>'
				+ 'Д: ' + this.people_cells[i].money)
			$('#city-window').append(citizen);
			citizen.css('margin-left', parseInt(citizen.css('margin-left')) + dx*72);
			citizen.css('margin-top', parseInt(citizen.css('margin-top')) + dy*72);
		}
		for (var dx = -1; dx < 2; dx++) {
			for (var dy = -1; dy < 2; dy++) {
				if (dx == 0 && dy == 0) continue;
				var num = "";
				if (dx < 0) {
					num += "n" + Math.abs(dx);
				} else {
					num += "p" + dx;
				}
				if (dy < 0) {
					num += "n" + Math.abs(dy);
				} else {
					num += "p" + dy;
				}
				$('#city-window-cell-' + num).attr('coordx', add_coord(this.x, dx, map.max_x));
				$('#city-window-cell-' + num).attr('coordy', add_coord(this.y, dy, map.max_y));
			}
		}
		$('#city-window-people-dis').text(this.people_dis);
		$('#city-window-people-norm').text(this.people_norm);
		$('#city-window-people-happy').text(this.people_happy);
		$('#city-window-people-artist').text(this.people_artist);

		$('#city-window-pwork-info').text(this.pwork);
		$('#city-window-peat-info').text(this.peat);
		$('#city-window-pmoney-info').text(this.pmoney);
        $('#city-window-presearch-info').text(this.presearch);
	},
	select_production: function (item) {
		if (map.turn_status != 'play') return false;
        let complete = 0;
        if (this.production) {
            complete = this.production.complete;
        }
		var pid = item.attr('pid');
		this.production = {	id : pid.substr(4),
							type : pid.substr(0, 4),
							title : item.find('.city-production-list-item-title').text(),
							cost : item.attr('cost'),
							complete:  complete};
		city.draw_production();
		$('#city-production-list').hide();
	},
	draw_production: function () {
		$('#city-production-select-pic img').attr('src', './img/' + this.production.type + 's/' + this.production.id + '.png');
		let turns = Math.ceil((this.production.cost - this.production.complete) / city.pwork);
		if (turns < 1) {
			turns = 1;
		}
		$('#city-production-select-title').html(this.production.title +
			'<br>' + turns + ' ходов ');
	},
	save_production: function() {
		if (map.turn_status != 'play') return false;
		var production_id, production_type;
		if (city.production) {
			production_id = city.production.id;
			production_type = city.production.type;
		} else {
			production_id = false;
			production_type = false;
		}
		$.post('index.php?method=city&json=1', {'cid': this.id, 'production': production_id, 'production_type': production_type}, 
		function(data) {
			resp = $.parseJSON(data);
			if (resp.status == 'error') {
				window.alert(resp.error);
			}
		});
	}
}
$(document).on('click', '#city-window-close', function(e) {
	city.save_production();
	$('#city-window').hide();
	if (city.load_events_after) {
		get_next_event();
	}
});
$(document).on('click', '#city-production-select', function(e) {
	$('#city-production-list').toggle();
});
$(document).on('click', '.city-window-cell', function (e) {
	if ($('#city-window:visible').length) {
		var x = $(e.target).attr('coordx');
		var y = $(e.target).attr('coordy');
		var people_cell = false;
		var peoples_var = {'change_people': 1};
		var people_index = 0;
		for (var i in city.people_cells) {
			if (city.people_cells[i].x == x && city.people_cells[i].y == y) {
				city.people_artist++;
				city.people_cells = array_remove(city.people_cells, i);
				people_cell = true;
				break;
			}
		}
		if (!people_cell) {
			if (city.people_artist == 0) {
				var remove_cell = 0;
				for (var i in city.people_cells) {
					if (city.people_cells[i].eat < city.people_cells[remove_cell].eat) {
						remove_cell = i;
					} else if (city.people_cells[i].eat == city.people_cells[remove_cell].eat && city.people_cells[i].work < city.people_cells[remove_cell].work) {
						remove_cell = i;
					} else if (city.people_cells[i].eat == city.people_cells[remove_cell].eat && city.people_cells[i].work == city.people_cells[remove_cell].work && city.people_cells[i].money < city.people_cells[remove_cell].money) {
						remove_cell = i;
					}
				}
				city.people_cells = array_remove(city.people_cells, remove_cell);
				city.people_artist++;
			}
			city.people_artist--;
			var cell = map.get_cell(x, y);
			var field_name = 'peoplex[' + people_index + ']';
			peoples_var[field_name] = x;
			field_name = 'peopley[' + people_index + ']';
			peoples_var[field_name] = y;
			people_index++;
		}
		for (var i in city.people_cells) {
			var field_name = 'peoplex[' + people_index + ']';
			peoples_var[field_name] = city.people_cells[i].x;
			field_name = 'peopley[' + people_index + ']';
			peoples_var[field_name] = city.people_cells[i].y;
			people_index++;
		}
		peoples_var['people_artist'] = city.people_artist;
		city.load(city.id, peoples_var);
	}
});