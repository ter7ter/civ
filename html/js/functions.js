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
  $.post("index.php?method=calculate&json=1", {}, function (data) {
    resp = $.parseJSON(data);
    if (resp.status == "ok") {
      if (resp.data.reload) {
        window.location.href = "index.php";
      } else {
        map.load();
        map.show_cell_info();
      }
    } else {
      showError(resp.error);
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
  var res2 = k1 * 1 + (max * 1 + 1) - k2;
  var res3 = k1 - (max * 1 + k2 * 1 + 1);
  if (Math.abs(res1) < Math.abs(res2) && Math.abs(res1) < Math.abs(res3)) {
    return res1;
  } else if (
    Math.abs(res2) < Math.abs(res1) &&
    Math.abs(res2) < Math.abs(res3)
  ) {
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
  var res = k1 * 1 + k2 * 1;
  if (res < 0) {
    res = max * 1 - res * 1 + 1;
  }
  if (res > max) {
    res = res * 1 - max * 1 - 1;
  }
  return res;
}

/**
 * Показать сообщение об ошибке
 * @param {string} message - текст ошибки
 */
function showError(message) {
  // Создаем или находим элемент для ошибок
  let errorDiv = document.getElementById("error-message");
  if (!errorDiv) {
    // Создаем элемент для ошибок
    errorDiv = document.createElement("div");
    errorDiv.id = "error-message";
    errorDiv.style.position = "fixed";
    errorDiv.style.top = "20px";
    errorDiv.style.right = "20px";
    errorDiv.style.zIndex = "9999";
    errorDiv.style.backgroundColor = "#dc3545";
    errorDiv.style.color = "white";
    errorDiv.style.padding = "10px 15px";
    errorDiv.style.borderRadius = "5px";
    errorDiv.style.boxShadow = "0 4px 6px rgba(0,0,0,0.1)";
    errorDiv.style.display = "none";
    errorDiv.style.maxWidth = "400px";
    errorDiv.style.wordWrap = "break-word";

    // Добавляем кнопку закрытия
    const closeBtn = document.createElement("span");
    closeBtn.innerHTML = "&times;";
    closeBtn.style.float = "right";
    closeBtn.style.cursor = "pointer";
    closeBtn.style.fontSize = "20px";
    closeBtn.style.lineHeight = "1";
    closeBtn.onclick = function () {
      errorDiv.style.display = "none";
    };

    errorDiv.appendChild(closeBtn);
    document.body.appendChild(errorDiv);
  }

  // Устанавливаем текст ошибки
  errorDiv.innerHTML = "";
  const closeBtn = document.createElement("span");
  closeBtn.innerHTML = "&times;";
  closeBtn.style.float = "right";
  closeBtn.style.cursor = "pointer";
  closeBtn.style.fontSize = "20px";
  closeBtn.style.lineHeight = "1";
  closeBtn.onclick = function () {
    errorDiv.style.display = "none";
  };

  errorDiv.appendChild(closeBtn);
  errorDiv.appendChild(document.createTextNode(message));

  // Показываем сообщение
  errorDiv.style.display = "block";

  // Автоматически скрываем через 10 секунд
  setTimeout(function () {
    errorDiv.style.display = "none";
  }, 10000);
}
