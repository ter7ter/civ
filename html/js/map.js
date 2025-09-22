var Map_cell = {
	//Отобразить верхнего юнита в этой клетке
	show_unit: function () {
		this.el.find('.map_unit').remove();
		this.el.find('.map_unit_selected').remove();
		if (this.units.length == 0) {
			return false;
		}
		var unit = {'points': 0, 'max_points': 0};
		for (var i in this.units) {
			if (this.units[i].points > unit.points) {
				unit = this.units[i];
			} else if (this.units[i].points == unit.points && this.units[i].max_points > unit.max_points) {
				unit = this.units[i];
			}
		}
		this.el.append('<img class="map_unit" src="./img/units/' + unit.type + '.png"></img>');
	},
	show_menu: function (point_x, point_y) {
		if (this.units.length == 0 && !this.city) {
			return false;
		}
		if (this.units.length == 1 && !this.city) {
			this.units[0].select();
			return false;
		}
		$('#cellmenu').html('<ul></ul>');
		if (this.city) {
			$('#cellmenu ul').append('<li class="open-city" cid=' + this.city.id + '>Открыть город ' + this.city.title + '</li>');
		}
		for (var unit in this.units) {
			$('#cellmenu ul').append('<li unit="' + unit + '">' + this.units[unit].title + '</li>');
		}
		$('#cellmenu').css('margin-left', point_x - 8);
		$('#cellmenu').css('margin-top', point_y - 600);
		$('#cellmenu').attr('map-x', this.x);
		$('#cellmenu').attr('map-y', this.y);
		$('#cellmenu').show();
		return true;
	}
}

var map = {
	cell: [],
	max_x: 0, //Максимально возможные
	max_y: 0, //координаты
	select_x: 0, //Выбранная
	select_y: 0, //клетка
	center_x: 0,   //Центр отображаемой
	center_y: 0,    //части карты
	height: 9,
	width: 11,
	max_path: 25,
	turn_status: 'wait',
	status_timer: false,
	units_turn: [],
	
	//Прокрутка карты
	up: function () {
		map.center_y--;
		if (map.center_y < 0) map.center_y = map.max_y;
		map.load();
	},
	down: function () {
		map.center_y++;
		if (map.center_y > map.max_y) map.center_y = 0;
		map.load();
	},
	left: function () {
		map.center_x--;
		if (map.center_x < 0) map.center_x = map.max_x;
		map.load();
	},
	right: function () {
		map.center_x++;
		if (map.center_x > map.max_y) map.center_x = 0;
		map.load();
	},
	//Загрузка карты с сервера
	load: function(centr = true) {
		var vals = {};
		if (centr) {
			vals.cx = map.center_x;
			vals.cy = map.center_y;
		}
		$.post('index.php?method=mapv&json=1', vals, function(data) {
			resp = $.parseJSON(data);
			if (resp.status == 'ok') {
				map.cells = resp.data.mapv;
				map.units_turn = [];
				for (var i in map.cells) {
					for (var j in map.cells[i]) {
						map.cells[i][j].__proto__ = Map_cell;
						for (var u in map.cells[i][j].units) {
							map.cells[i][j].units[u].__proto__ = Unit;
							if (map.cells[i][j].units[u].points > 0) {
								map.units_turn.push(map.cells[i][j].units[u]);
							}
						}
					}
				}
				map.max_x = resp.data.max_x;
				map.max_y = resp.data.max_y;
				map.center_x = resp.data.center_x;
				map.center_y = resp.data.center_y;
				if (selected_unit) {
					var cell = map.get_cell(selected_unit.x, selected_unit.y);
					if (cell) {
						var found = false;
						for (var i in cell.units) {
							if (cell.units[i].id == selected_unit.id) {
								selected_unit = cell.units[i];
							}
						}
						if (!found) {
							selected_unit = false;
						}
					}
				}
				if (!centr) {
					map.select_x = map.center_x;
					map.select_y = map.center_y;
					map.show_cell_info();
				}
				map.draw();
			} else {
				window.alert(resp.error);
			}
		        }).error(function(jqXHR, textStatus, errorThrown) {
		            var error_msg = "AJAX Error: " + textStatus + "\n" + errorThrown;
		            if (jqXHR.responseText) {
		                try {
		                    var resp = $.parseJSON(jqXHR.responseText);
		                    error_msg += "\n\nServer Error: " + resp.error;
		                    error_msg += "\nFile: " + resp.file + " on line " + resp.line;
		                } catch (e) {
		                    error_msg += "\n\nServer response is not valid JSON:\n" + jqXHR.responseText.substring(0, 500);
		                }
		            }
		            window.alert(error_msg);
		        });	},
	//Отрисовка карты
	draw: function() {
		$('#mapv').empty();
		for (var y = 0; y < map.height; y++) {
			var line = $('#mapv').append('<div class="map_row"></div>');
			for (var x = 0; x < map.width; x++) {
				var cdata = ' coordx="' + map.cells[x][y].x + '" coordy="' + map.cells[x][y].y + '" ';
				var el;
				if (map.cells[x][y] == undefined || map.cells[x][y].title == 'none') {
					el = $('<div class="map_cell map_cell_none"' + cdata + '>&nbsp</div>');
				} else {
					var cell = map.cells[x][y];
					var owner = '';
					if (cell.owner_name) {
						owner = '<div class="map-cell-owner-color" style="background-color:' + cell.owner_color + '"></div>';
					}
					var road = '';
					if (cell.road) {
						road = '<div class="map-cell-road"></div>';
					}
					var resource = '';
					if (cell.resource_id) {
						resource = '<div class="map-cell-resource" style="background-image: url(img/res/' + cell.resource_id + '.png);"></div>';
					}
					var improvement = '';
					if (cell.improvement) {
						resource = '<div class="map-cell-improvement" style="background-image: url(img/improvement/' + cell.improvement + '.png);"></div>';
					}
					if (map.cells[x][y].city) {
						el = '<div class="map_cell map_cell_city1"' + cdata + '>' + owner;
						el += '<div class="map-city-population">' + cell.city.population + '</div>';
						el += '<div class="map-city-title">' + cell.city.title.substr(0, 7) + '</div>';
						el += '</div>';
						el = $(el);
					} else {
						el = $('<div class="map_cell map_cell_' + cell.type + '"' + cdata + '>' + owner + resource + improvement + road + '</div>');
					}
					
				}
				map.cells[x][y].el = el;
				map.cells[x][y].show_unit();
				line.find('.map_row:last').append(el);
			}
		}
		for (var y = 0; y < map.height; y++) {
			for (var x = 0; x < map.width; x++) {
				var cell = map.cells[x][y];
				if (cell.owner_name) {
					if (y > 0 && cell.owner_name != map.cells[x][y - 1].owner_name) {
						cell.el.css('border-top-color', cell.owner_color);
						cell.el.css('border-top-style', 'solid');
					}
					if ((y < map.height - 1) && cell.owner_name != map.cells[x][y + 1].owner_name) {
						cell.el.css('border-bottom-color', cell.owner_color);
						cell.el.css('border-bottom-style', 'solid');
					}
					if (x > 0 && cell.owner_name != map.cells[x - 1][y].owner_name) {
						cell.el.css('border-left-color', cell.owner_color);
						cell.el.css('border-left-style', 'solid');
					}
					if ((x < map.width - 1) && cell.owner_name != map.cells[x + 1][y].owner_name) {
						cell.el.css('border-right-color', cell.owner_color);
						cell.el.css('border-right-style', 'solid');
					}
				}
				if (cell.road) {
					var class_name = "map-cell-road-";
					if (x > 0 && y < (map.height - 1) && (map.cells[x-1][y+1].road || map.cells[x-1][y+1].city) &&
						!map.cells[x-1][y].road && !map.cells[x-1][y].city && !map.cells[x][y+1].road && !map.cells[x][y+1].city) {
						class_name = class_name + '1';
					}
					if (y < (map.height - 1) && (map.cells[x][y+1].road || map.cells[x][y+1].city)) {
						class_name = class_name + '2';
					}
					if (x < (map.width - 1) && y < (map.height - 1) && (map.cells[x+1][y+1].road || map.cells[x+1][y+1].city) &&
						!map.cells[x][y+1].road && !map.cells[x][y+1].city && !map.cells[x+1][y].road && !map.cells[x+1][y].city) {
						class_name = class_name + '3';
					}
					if (x > 0 && (map.cells[x-1][y].road || map.cells[x-1][y].city)) {
						class_name = class_name + '4';
					}
					if (x < (map.width - 1) && (map.cells[x+1][y].road || map.cells[x+1][y].city)) {
						class_name = class_name + '6';
					}
					if (x > 0 && y > 0 && (map.cells[x-1][y-1].road || map.cells[x-1][y-1].city) &&
						!map.cells[x][y-1].road && !map.cells[x][y-1].city && !map.cells[x-1][y].road && !map.cells[x-1][y].city) {
						class_name = class_name + '7';
					}
					if (y > 0 && (map.cells[x][y-1].road || map.cells[x][y-1].city)) {
						class_name = class_name + '8';
					}
					if (x < (map.width - 1) && y > 0 && (map.cells[x+1][y-1].road || map.cells[x+1][y-1].city) &&
						!map.cells[x][y-1].road && !map.cells[x][y-1].city && !map.cells[x+1][y].road && !map.cells[x+1][y].city) {
						class_name = class_name + '9';
					}
					cell.el.find('.map-cell-road').addClass(class_name);
				}
			}
		}
		$('.map_cell[coordx="' + map.select_x + '"][coordy="' + map.select_y + '"]').addClass('selected_cell');
		if (selected_unit) {
			selected_unit.select();
		}
	},
	//Отобразить панель с информацией справа
	show_cell_info: function () {
		var unit_id = 0;
		if (selected_unit && selected_unit.x == this.select_x && selected_unit.y == this.select_y) {
			unit_id = selected_unit.id;
		}
		        $.post('index.php?method=cellinfo', {'x': map.select_x, 'y': map.select_y, 'unit_id': unit_id}, function(data) {                
		            $('.map_cell').removeClass('selected_cell');
		            $('.map_cell[coordx="' + map.select_x + '"][coordy="' + map.select_y + '"]').addClass('selected_cell');
		            $('#cellinfo').html(data);
		            $.post('index.php?method=turninfo', {}, function(turnInfoData) {
		                $('#turninfo-container').html(turnInfoData);
		            }).fail(function(jqXHR, textStatus, errorThrown) {
		                console.error("Error loading turn info:", textStatus, errorThrown, jqXHR.responseText);
		                $('#turninfo-container').html('<div class="error">Failed to load turn info. See console for details.</div>');
		            });
		            					        }).fail(function(jqXHR, textStatus, errorThrown) {
		            console.error("Error loading cell info:", textStatus, errorThrown, jqXHR.responseText);
		            $('#cellinfo').html('<div class="error">Failed to load cell info. See console for details.</div>');
		        });	},
	get_cell: function(x, y) {
		for (var i in map.cells) {
			for (var k in map.cells[i]) {
				if (map.cells[i][k].x == x && map.cells[i][k].y == y) {
					return map.cells[i][k];
				}
			}
		}
	},
	draw_path: function(path) {
		for (var n = 0; n < path.length; n++) {
			var num = '';
			var cells = [];
			if (n > 0) {
				cells.push({'i': path[n-1].i, 'k': path[n-1].k});
			}
			if (n < path.length - 1) {
				cells.push({'i': path[n+1].i, 'k': path[n+1].k});
			}
			for (var j in cells) {
				var pnum = 0;
				if (cells[j].i < path[n].i  && cells[j].k > path[n].k)  pnum = 1;
				if (cells[j].i == path[n].i && cells[j].k > path[n].k)  pnum = 2;
				if (cells[j].i > path[n].i  && cells[j].k > path[n].k)  pnum = 3;
				if (cells[j].i < path[n].i  && cells[j].k == path[n].k) pnum = 4;
				if (cells[j].i > path[n].i  && cells[j].k == path[n].k) pnum = 6;
				if (cells[j].i < path[n].i  && cells[j].k < path[n].k)  pnum = 7;
				if (cells[j].i == path[n].i && cells[j].k < path[n].k)  pnum = 8;
				if (cells[j].i > path[n].i  && cells[j].k < path[n].k)  pnum = 9;
				if (num.length && parseInt(num) > pnum) {
					num = pnum.toString() + num;
				} else {
					num = num + pnum.toString();
				}
			}
			map.cells[path[n].i][path[n].k].el.append('<div class="map-path-line map-path-line-' + num + '"></div>');
		}
	}
};
//
//Клики по карте
//
$(document).on('click', '.map_cell', function (e) {
	var x = $(e.target).closest('.map_cell').attr('coordx');
	var y = $(e.target).closest('.map_cell').attr('coordy');
	if (selected_unit.select_mission) {
		if ($(e.target).closest('.map_cell').css('cursor') == 'crosshair') {
			var path = selected_unit.calculate_path(x, y);
			path.shift();
			selected_unit.move_to(path);
		} else {
			selected_unit.cancel_select_target();
		}
	} else {
		$("#cellmenu").hide();
		map.select_x = x;
		map.select_y = y;
		//Показать инфо о клетке
		var cell = map.get_cell(map.select_x, map.select_y);
		if (!cell.show_menu(e.pageX, e.pageY)) {
			map.show_cell_info();
		}
	}
});
$(document).on('click', '.cell-info-img', function (e) {
	map.center_x = map.select_x;
	map.center_y = map.select_y;
	map.load();
});