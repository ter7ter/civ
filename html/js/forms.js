$(document).on("click", "#create-game-add-player", function (e) {
  e.preventDefault();
  var currentPlayers = $(".create-player").length;

  // Ограничиваем максимальное количество игроков
  if (currentPlayers >= 16) {
    alert("Максимальное количество игроков: 16");
    return;
  }

  var count = currentPlayers + 1;
  var el = $(".create-player:first").clone();
  el.find("input")
    .val("")
    .attr("placeholder", "Имя игрока " + count);

  // Генерируем цвет для нового игрока
  var color = "#";
  var sym = "ff";
  var color_num = count;
  if (count > 8) {
    sym = "88";
    color_num = count - 8;
  }
  if ((color_num & 4) > 0) {
    color += sym;
  } else {
    color += "00";
  }
  if ((color_num & 2) > 0) {
    color += sym;
  } else {
    color += "00";
  }
  if ((color_num & 1) > 0) {
    color += sym;
  } else {
    color += "00";
  }

  el.find("span").css("background-color", color);

  // Добавляем кнопку удаления для новых игроков (кроме первого)
  if (!el.find(".remove-player").length && count > 1) {
    el.append(
      '<button type="button" class="remove-player" style="margin-left: 5px; color: red; background: none; border: none; font-size: 16px; cursor: pointer;">✕</button>',
    );
  }

  $(".create-player:last").after(el);
});

// Обработчик удаления игроков
$(document).on("click", ".remove-player", function (e) {
  e.preventDefault();
  var playersCount = $(".create-player").length;
  if (playersCount > 2) {
    $(this).parent().remove();

    // Обновляем placeholder'ы для оставшихся полей
    $(".create-player").each(function (index) {
      $(this)
        .find("input")
        .attr("placeholder", "Имя игрока " + (index + 1));
    });
  } else {
    alert("Нужно минимум 2 игрока для игры");
  }
});

// Валидация формы создания игры
$(document).on("submit", 'form[action*="creategame"]', function (e) {
  var name = $('input[name="name"]').val().trim();
  var mapW = parseInt($('input[name="map_w"]').val());
  var mapH = parseInt($('input[name="map_h"]').val());
  var players = [];

  // Собираем список игроков
  $('input[name="users[]"]').each(function () {
    var val = $(this).val().trim();
    if (val) {
      players.push(val);
    }
  });

  // Валидация названия
  if (!name) {
    alert("Введите название игры");
    $('input[name="name"]').focus();
    e.preventDefault();
    return false;
  }

  // Валидация размеров карты
  if (mapW < 50 || mapW > 500) {
    alert("Ширина карты должна быть от 50 до 500");
    $('input[name="map_w"]').focus();
    e.preventDefault();
    return false;
  }

  if (mapH < 50 || mapH > 500) {
    alert("Высота карты должна быть от 50 до 500");
    $('input[name="map_h"]').focus();
    e.preventDefault();
    return false;
  }

  // Валидация игроков
  if (players.length < 2) {
    alert("Необходимо минимум 2 игрока");
    e.preventDefault();
    return false;
  }

  if (players.length > 16) {
    alert("Максимальное количество игроков: 16");
    e.preventDefault();
    return false;
  }

  // Проверяем на дублирующиеся имена
  var uniquePlayers = [...new Set(players)];
  if (uniquePlayers.length !== players.length) {
    alert("Имена игроков не должны повторяться");
    e.preventDefault();
    return false;
  }

  // Проверяем на пустые имена и слишком длинные
  for (var i = 0; i < players.length; i++) {
    if (players[i].length > 50) {
      alert("Имя игрока не должно превышать 50 символов");
      e.preventDefault();
      return false;
    }
  }

  return true;
});
function select_game_change() {
  var val = $("#select-game-select").val();
  $.post("index.php?method=gameinfo", { json: 1, id: val }, function (data) {
    resp = $.parseJSON(data);
    if (resp.status == "ok") {
      var users = resp.data.users;
      $("#select-game-user").empty();
      for (var i in users) {
        $("#select-game-user").append(
          '<option value="' +
            users[i].id +
            '" ' +
            'style="color:' +
            users[i].color +
            '">' +
            users[i].login +
            "</option>",
        );
      }
      select_user_change();
    } else {
      window.alert(resp.error);
    }
  });
}
$(document).on("change", "#select-game-select", function (e) {
  select_game_change();
});
function select_user_change() {
  $("#select-game-user").css(
    "color",
    $("#select-game-user option:selected")[0].style.color,
  );
}
$(document).on("change", "#select-game-user", function (e) {
  select_user_change();
});
