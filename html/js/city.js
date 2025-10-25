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
				city.draw_production();
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
				'<img src="./img/units/' + this.possible_units[i].image_file + '">' +
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
				'<img src="./img/buils/' + this.possible_buildings[i].image_file + '">' +
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
			var dx = parseInt(diff_coord(this.people_cells[i].x, this.x, map.max_x));
			var dy = parseInt(diff_coord(this.people_cells[i].y, this.y, map.max_y));

			var citizen = $('<div class="city_map_citizen"></div>');
						var hammer_svg = '<svg width="12" height="12" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="5" width="4" height="11" fill="saddlebrown"/><rect x="2" y="2" width="12" height="4" fill="darkgray"/></svg>';
			var apple_svg = '<svg width="800px" height="800px" viewBox="0 0 1024 1024" class="icon"  version="1.1" xmlns="http://www.w3.org/2000/svg"><path d="M854.634667 169.365333c103.509333 103.530667 2.325333 309.12-186.901334 498.368-189.226667 189.162667-394.837333 290.368-498.346666 186.88-103.530667-103.530667-2.346667-309.12 186.88-498.368 189.205333-189.184 394.837333-290.389333 498.368-186.88z" fill="#FFB300" /><path d="M424.661333 617.322667c6.506667 89.216-7.36 140.138667-25.493333 169.237333-38.72 34.794667-57.002667 38.72-57.002667 38.72s-6.122667-242.474667 0.512-334.613333c0.448-6.229333 29.781333-45.781333 29.781334-45.781334s45.482667 80.277333 52.202666 172.437334zM595.328 474.645333c6.506667 89.216-7.381333 140.138667-25.493333 169.237334-38.72 34.794667-57.002667 38.72-57.002667 38.72s-6.122667-242.453333 0.490667-334.613334c0.448-6.229333 29.781333-45.781333 29.781333-45.781333s45.525333 80.298667 52.224 172.437333zM766.464 340.416c6.506667 89.216-7.381333 140.138667-25.493333 169.237333-38.72 34.794667-57.002667 38.72-57.002667 38.72s-6.122667-242.453333 0.490667-334.613333c0.448-6.229333 29.781333-45.781333 29.781333-45.781333s45.482667 80.298667 52.224 172.437333z" fill="#FFA000" /><path d="M384 657.984c0 92.394667-41.834667 167.296-41.834667 167.296s-41.813333-74.901333-41.813333-167.296c0-92.394667 41.813333-167.317333 41.813333-167.317333S384 565.589333 384 657.984zM554.965333 515.349333c0 92.416-41.813333 167.317333-41.813333 167.317334s-41.813333-74.901333-41.834667-167.317334c0-92.373333 41.834667-167.296 41.834667-167.296s41.813333 74.922667 41.813333 167.296zM725.333333 380.629333c0 92.416-41.834667 167.317333-41.834666 167.317334s-41.813333-74.901333-41.834667-167.317334c0-92.373333 41.834667-167.296 41.834667-167.296S725.333333 288.256 725.333333 380.629333z" fill="#FFE082" /></svg>';
			var coin_svg = '<svg width="12" height="12" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="7" fill="gold"/><circle cx="8" cy="8" r="5" fill="goldenrod"/></svg>';

			function get_icon_html(icon_svg, count) {
				var html = '';
				var icon_data_uri = 'data:image/svg+xml;utf8,' + encodeURIComponent(icon_svg);
				for (var i = 0; i < count; i++) {
					html += '<img src="' + icon_data_uri + '" width="15" height="15">';
				}
				return html;
			}

			citizen.html(
				'<div class="icon-line">' + get_icon_html(hammer_svg, this.people_cells[i].work) + '</div>' +
				'<div class="icon-line">' + get_icon_html(apple_svg, this.people_cells[i].eat) + '</div>' +
				'<div class="icon-line">' + get_icon_html(coin_svg, this.people_cells[i].money) + '</div>'
			);

			$('#city-map').append(citizen);
			citizen.css('left', ((dx+1)*72 + 6) + 'px');
			citizen.css('top', ((dy+1)*72 + 6) + 'px');
		}
		for (var dy = -1; dy < 2; dy++) {
			for (var dx = -1; dx < 2; dx++) {
				if (dx == 0 && dy == 0) continue;
                
                var dy_str = (dy < 0 ? "n" : "p") + Math.abs(dy);
                var dx_str = (dx < 0 ? "n" : "p") + Math.abs(dx);
                var cell_id = '#city-window-cell-' + dy_str + dx_str;
                var cell = $(cell_id);

                if (cell.length) {
                    cell.attr('coordx', add_coord(this.x, dx, map.max_x));
                    cell.attr('coordy', add_coord(this.y, dy, map.max_y));
                }
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
                            image_file: item.find('img').attr('src').split('/').pop(),
							complete:  complete};
		city.draw_production();
		$('#city-production-list').hide();
	},
	draw_production: function () {
        if (this.production) {
    		$('#city-production-select-pic img').attr('src', './img/' + this.production.type + 's/' + this.production.image_file);
	    	let turns = Math.ceil((this.production.cost - this.production.complete) / city.pwork);
		    if (turns < 1) {
			    turns = 1;
		    }
		    $('#city-production-select-title').html(this.production.title +
			    '<br>' + turns + ' ходов ');
        } else {
            $('#city-production-select-pic img').attr('src', './img/units/no_production.svg');
            $('#city-production-select-title').html('Выберите постройку');
        }
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
