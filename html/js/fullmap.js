function setupDragFunctionality() {
  var $container = $("#full-map-container");
  var $wrapper = $("#full-map-content-wrapper");
  var isDragging = false;
  var startX, startY;
  var scrollLeft, scrollTop;

  $wrapper.on("mousedown", function (e) {
    isDragging = true;
    startX = e.pageX - $container.offset().left;
    startY = e.pageY - $container.offset().top;
    scrollLeft = $container.scrollLeft();
    scrollTop = $container.scrollTop();
    $wrapper.css("cursor", "grabbing");
    e.preventDefault();
  });

  $(document).on("mousemove", function (e) {
    if (!isDragging) return;
    e.preventDefault();

    var x = e.pageX - $container.offset().left;
    var y = e.pageY - $container.offset().top;
    var walkX = (x - startX) * 2; // Увеличиваем чувствительность
    var walkY = (y - startY) * 2; // Увеличиваем чувствительность

    $container.scrollLeft(scrollLeft - walkX);
    $container.scrollTop(scrollTop - walkY);
  });

  $(document).on("mouseup", function () {
    isDragging = false;
    $wrapper.css("cursor", "grab");
  });

  // Возвращаем курсор grab при выходе за пределы области
  $wrapper.on("mouseleave", function () {
    if (isDragging) {
      isDragging = false;
      $wrapper.css("cursor", "grab");
    }
  });
}

function loadFullMap() {
  // Показываем сообщение о загрузке
  $("#full-map-content").html(
    "<div style='display: flex; justify-content: center; align-items: center; height: 100%;'>Загрузка полной карты...</div>",
  );

  // Загружаем данные полной карты
  $.post("index.php?method=fullmap&json=1", {}, function (response) {
    try {
      var data = $.parseJSON(response);
      if (data.status === "ok") {
        renderFullMap(data.data);
      } else {
        showError(data.error || "Ошибка при загрузке полной карты");
      }
    } catch (e) {
      showError("Ошибка при обработке данных полной карты: " + e.message);
    }
  }).fail(function (jqXHR, textStatus, errorThrown) {
    var error_msg =
      "Ошибка при загрузке полной карты: " + textStatus + "\n" + errorThrown;
    if (jqXHR.responseText) {
      try {
        var resp = $.parseJSON(jqXHR.responseText);
        error_msg += "\n\nСерверная ошибка: " + resp.error;
      } catch (e) {
        error_msg +=
          "\n\nОтвет сервера не является допустимым JSON:\n" +
          jqXHR.responseText.substring(0, 500);
      }
    }
    showError(error_msg);
  });
}

function renderFullMap(mapData) {
  var container = $("#full-map-content");
  container.empty();

  // Определяем размеры карты
  var minX = Infinity,
    maxX = -Infinity,
    minY = Infinity,
    maxY = -Infinity;
  for (var x in mapData) {
    for (var y in mapData[x]) {
      var cell = mapData[x][y];
      if (cell.x < minX) minX = cell.x;
      if (cell.x > maxX) maxX = cell.x;
      if (cell.y < minY) minY = cell.y;
      if (cell.y > maxY) maxY = cell.y;
    }
  }

  // Рассчитываем размеры контейнера для клеток
  var mapWidth = maxX - minX + 1;
  var mapHeight = maxY - minY + 1;

  // Устанавливаем размеры контейнера
  container.css({
    width: mapWidth * 8 + "px",
    height: mapHeight * 8 + "px",
    position: "relative",
  });

  // Создаем HTML для всех клеток за один раз
  var html = "";
  for (var x in mapData) {
    for (var y in mapData[x]) {
      var cell = mapData[x][y];

      // Устанавливаем цвет рельефа
      var terrainColor = "#CCCCCC"; // серый по умолчанию
      // Определяем цвет в зависимости от типа клетки
      switch (cell.type) {
        case "plains":
        case "plains2":
          terrainColor = "#90EE90"; // светло-зеленый
          break;
        case "forest":
          terrainColor = "#228B22"; // темно-зеленый
          break;
        case "hills":
          terrainColor = "#A0522D"; // коричневый
          break;
        case "desert":
          terrainColor = "#F4A460"; // светло-коричневый
          break;
        case "mountains":
          terrainColor = "#C0C0C0"; // серый
          break;
        case "water1":
        case "water2":
        case "water3":
          terrainColor = "#4169E1"; // королевский синий
          break;
      }

      // Устанавливаем позицию
      var posX = (cell.x - minX) * 8;
      var posY = (cell.y - minY) * 8;

      // Добавляем клетку рельефа с пониженной яркостью
      html +=
        '<div class="full-map-cell" style="left: ' +
        posX +
        "px; top: " +
        posY +
        "px; background-color: " +
        terrainColor +
        '; opacity: 0.5; z-index: 0;"></div>';

      // Если у клетки есть владелец, добавляем поверх рельефа слой территории игрока
      if (cell.owner && cell.owner.color) {
        html +=
          '<div class="full-map-cell" style="left: ' +
          posX +
          "px; top: " +
          posY +
          "px; background-color: " +
          cell.owner.color +
          '; opacity: 0.6; z-index: 1;"></div>';
      }

      // Если есть город, добавляем индикатор поверх всего
      if (cell.city) {
        html +=
          '<div class="full-map-city-indicator" style="left: ' +
          (posX + 3) +
          "px; top: " +
          (posY + 3) +
          'px; z-index: 2;"></div>';
      }
    }
  }

  // Создаем обертку для центрирования содержимого
  var mapWrapper = $('<div style="display: inline-block;"></div>');
  mapWrapper.html(html);
  container.html(mapWrapper);

  // Добавляем функциональность перетаскивания
  setupDragFunctionality();
}
