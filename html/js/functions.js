function array_remove(array, index) {
	var new_array = [];
	for (var i in array) {
		if (i != index) {
			new_array.push(array[i]);
		}
	}
	return new_array;
}
function next_step() {
	$.post('index.php?method=calculate&json=1', {}, function(data) {
		resp = $.parseJSON(data);
		if (resp.status == 'ok') {
			if (resp.data.reload) {
				window.location.href = 'index.php';
			} else {
				map.load();
				map.show_cell_info()
			}
		} else {
			window.alert(resp.error);
		}
	});
}
/**
 * Расстояние по координате, с учётом зацикливания
 * @param k1 int
 * @param k2 int
 * @param max int
 * @returns int
 */
function diff_coord(k1, k2, max) {
	var res1 = k1 - k2;
	var res2 = k1*1 + (max*1 + 1) - k2;
	var res3 = k1 - (max*1 + k2*1 + 1);
	if (Math.abs(res1) < Math.abs(res2) && Math.abs(res1) < Math.abs(res3)) {
		return res1;
	} else if (Math.abs(res2) < Math.abs(res1) && Math.abs(res2) < Math.abs(res3)) {
		return res2;
	} else {
		return res3;
	}
	
}

/**
 * Сложение двух координат, с учётом зациклюивания
 * @param k1 int
 * @param k2 int
 * @param max int
 */
function add_coord(k1, k2, max) {
	var res = k1*1 + k2*1;
	if (res < 0) {
		res = max*1 - res*1 + 1;
	}
	if (res > max) {
		res = res*1 - max*1 - 1;
	}
	return res;
}