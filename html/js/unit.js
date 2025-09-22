var selected_unit = false;

var Unit = {
	select_mission: false,
	select: function() {
		selected_unit = this;
		var el = $('.map_cell[coordx=' + this.x + '][coordy=' + this.y + ']');
		if (el.length == 0) {
			return false;
		}
		$('.map_unit_selected').removeClass('map_unit_selected').addClass('map_unit');
		el.find('.map_unit').remove();
		el.find('.map_unit_selected').remove();
		el.append('<img class="map_unit_selected" src="./img/units/' + this.type + '.png" style="border-color: ' + this.owner_color + '"></img>');
	},
	//Перемещение юнита
	move: function (dx, dy) {
		if (this.points == 0) {
			return false;
		}
		var move_x = this.x*1 + dx;
		if (move_x < 0) move_x = map.max_x;
		if (move_x > map.max_x) move_x = 0;
		var move_y = this.y*1 + dy;
		if (move_y < 0) move_y = map.max_y;
		if (move_y > map.max_y) move_y = 0;
		var unit = this;
		$.post('index.php?method=unitaction&json=1', {'action': 'move',
													  'x': move_x, 
													  'y': move_y, 
													  'uid': this.id}, function(data) {
			var data = JSON.parse(data);
			if (data.status == 'error') {
				window.alert(data.error);
				return false;
			}
			var cell_from = map.get_cell(unit.x, unit.y);
			var cell_to = map.get_cell(move_x, move_y);
			unit.x = move_x;
			unit.y = move_y;
			unit.points = data.data.points;
			map.select_x = move_x;
			map.select_y = move_y;
			for (var uid in cell_from.units) {
				if (cell_from.units[uid] == unit) {
					cell_from.units = array_remove(cell_from.units, uid);
				}
			}
			if (unit.points == 0) {
				for (var i in map.units_turn) {
					if (map.units_turn[i].id == unit.id) {
						array_remove(map.units_turn, i);
					}
				}
				if (map.units_turn.length > 0) {
					selected_unit = map.units_turn[0];
					map.center_x = selected_unit.x;
					map.center_y = selected_unit.y;
					map.load();
					selected_unit.select();
					map.show_cell_info();
					return ;
				}
			}
			var update_map = false;
			var min_x = map.cells[0][0].x;
			var min_y = map.cells[0][0].y;
			var max_x = map.cells[map.width - 1][map.height - 1].x;
			var max_y = map.cells[map.width - 1][map.height - 1].y;
			if (min_x > max_x) {
				min_x -= 100;
				if (move_x > map.max_x / 2) {
					move_x -= 100;
				}
			}
			if (min_y > max_y) {
				min_y = min_y - 100;
				if (move_y > map.max_y / 2) {
					move_y -= 100;
				}
			}
			if (move_x <= min_x) {
				update_map = true;
				map.left();
			}
			if (move_x >= max_x) {
				update_map = true;
				map.right();
			}
			if (move_y <= min_y) {
				update_map = true;
				map.up();
			}
			if (move_y >= max_y) {
				update_map = true;
				map.down();
			}
			if (!update_map) {
				cell_to.units.push(unit);
				cell_from.show_unit();
				cell_to.show_unit();
				selected_unit.select();
			}
			map.show_cell_info();
		});
	},
	create_city_window: function() {
		$('#city-create-window').show();
	},
	create_city: function() {
		var title = $('#city-create-title').val();
		if (!title) {
			window.alert("Введите название города");
			return false;
		}
		$.post('index.php?method=unitaction&json=1', {'action': 'mission',
													  'mission': 'build_city',
													  'uid': this.id,
													  'title': title}, function(data) {
			var data = JSON.parse(data);
			if (data.status == 'error') {
				window.alert(data.error);
				return false;
			}
			selected_unit = false;
			map.load();
			map.show_cell_info();
			$('#city-create-title').val('');
			$('#city-create-window').hide();
		});
	},
	select_move_target: function() {
		for (var y = 0; y < map.height; y++) {
			for (var x = 0; x < map.width; x++) {
				var cell = map.cells[x][y];
				if (this.can_move[cell.type]) {
					cell.el.css('cursor', 'crosshair');
				} else {
					cell.el.css('cursor', 'not-allowed');
				}
			}
		}
		this.select_mission = 'move_to';
	},
	cancel_select_target: function() {
		for (var y = 0; y < map.height; y++) {
			for (var x = 0; x < map.width; x++) {
				map.cells[x][y].el.css('cursor', 'default');
			}
		}
		this.select_mission = false;
	},
	calculate_path: function(target_x, target_y) {
		var i1, i2, k1, k2;
		for (var i in map.cells) {
			for (var k in map.cells[i]) {
				if (map.cells[i][k].x == this.x && map.cells[i][k].y == this.y) { //Клетка отправки
					i1 = i;
					k1 = k;
				}
				if (map.cells[i][k].x == target_x && map.cells[i][k].y == target_y) { //Клетка прибытия
					i2 = i;
					k2 = k;
				}
			}
		}
		var moves = this.can_move;
		var cells_next = [{'i': i1, 'k': k1, 'dist': 0, 'prev': false}];
		var cells_path = {};
		var dist = map.max_path + 1;
		while (cells_next.length > 0) {
			var next = cells_next.shift();
			if (cells_path[next.i+'_'+next.k] && cells_path[next.i+'_'+next.k].dist <= next.dist) {
				continue; //Сюда уже нашли путь не хуже
			}
			if (next.dist >= dist) continue; //Слишком длинный путь
			if (next.i == i2 && next.k == k2) {
				dist = next.dist; //Нашли возможный путь
			}
			cells_path[next.i+'_'+next.k] = next;
			var map_next = map.cells[next.i][next.k];
			for (var di = -1; di < 2; di++) for (var dk = -1; dk < 2; dk++) {
				if (di == 0 && dk == 0) continue;
				if (next.i*1+di < 0 || next.i*1+di >= map.width || next.k*1+dk < 0 || next.k*1+dk >= map.height) continue;
				var dist_near;
				var map_near = map.cells[next.i*1+di][next.k*1+dk];
				if (!moves[map_near.type] && !map_near.city) continue; //Сюда пути нет
				if ((map_next.road || map_next.city) && (map_near.road || map_near.city)) {
					dist_near = 0.25; //Есть дорога
				} else {
					if (map_near.city) {
						dist_near = moves['city'];
					} else {
						dist_near = moves[map_near.type];
					}
				}
				cells_next.push({'i': next.i*1 + di, 'k': next.k*1 + dk, 'dist': next.dist + dist_near,
						'prev':	{'i': next.i, 'k': next.k}});
			}
		}
		if (dist <= map.max_path) { //Нашли путь
			var i = i2;
			var k = k2;
			var path = [{'i': i2, 'k': k2}];
			while (!(i == i1 && k == k1)) {
				if (cells_path[i+'_'+k].prev) {
					path.push({'i': cells_path[i + '_' + k].prev.i, 'k': cells_path[i + '_' + k].prev.k});
					i = path[path.length - 1].i;
					k = path[path.length - 1].k;
				} else {
					break;
				}
			}
			path = path.reverse();
			return path;
		}
		return false;
	},
	move_to: function (path) {
		var data = {
			'action': 'move_to',
			'uid': this.id
		}
		for (var i in path) {
			data['path['+i+'][x]'] = map.cells[path[i].i][path[i].k].x;
			data['path['+i+'][y]'] = map.cells[path[i].i][path[i].k].y;
		}
		$('.map-path-line').remove();
		$.post('index.php?method=unitaction&json=1', data, function (data) {
			var data = JSON.parse(data);
			if (data.status == 'error') {
				window.alert(data.error);
				return false;
			}
			map.load();
		});
	}
}
$(document).on('click', '.unit-do-mission', function(e) {
	var mid = $(e.target).closest('.unit-do-mission').attr('mid');
	if (mid == 'build_city') {
		selected_unit.create_city_window();
	} else if(mid == 'move_to') {
		selected_unit.select_move_target();
	} else {
		$.post('index.php?method=unitaction&json=1', {'action': 'mission',
													  'mission': mid,
													  'uid': selected_unit.id }, function (data) {
			var data = JSON.parse(data);
			if (data.status == 'error') {
				window.alert(data.error);
				return false;
			}
			map.show_cell_info();
		});
	}
});
$(document).on('mouseenter', '.map_cell', function (e) {
	if (selected_unit.select_mission) {
		var x = $(e.target).closest('.map_cell').attr('coordx');
		var y = $(e.target).closest('.map_cell').attr('coordy');
		$('.map-path-line').remove();
		if ($(e.target).closest('.map_cell').css('cursor') == 'crosshair') {
			var path = selected_unit.calculate_path(x, y);
			if (path) {
				map.draw_path(path);
			}
		}
	}
});