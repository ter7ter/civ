<?php
$page_title = 'Создание новой игры';
include 'partials/header.php';
?>

<div class="container create-game-container">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center mb-4">Создание новой игры</h1>

            <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
            <?php endif; ?>

            <?php if (isset($data["success"]) && $data["success"]): ?>
            <div class="alert alert-success" role="alert">
                Игра успешно создана!
            </div>
            <?php endif; ?>

            <form action="index.php?method=creategame" method="post">
                <div class="row mb-3">
                    <label for="name" class="col-sm-4 col-form-label">Название игры*</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="name" name="name" value="<?= isset($data["name"]) ? htmlspecialchars($data["name"]) : "" ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="map_w" class="col-sm-4 col-form-label">Ширина карты (50-500)</label>
                    <div class="col-sm-8">
                        <input type="number" class="form-control" id="map_w" name="map_w" value="<?= isset($data["map_w"]) ? $data["map_w"] : "100" ?>" min="50" max="500">
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="map_h" class="col-sm-4 col-form-label">Высота карты (50-500)</label>
                    <div class="col-sm-8">
                        <input type="number" class="form-control" id="map_h" name="map_h" value="<?= isset($data["map_h"]) ? $data["map_h"] : "100" ?>" min="50" max="500">
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="turn_type" class="col-sm-4 col-form-label">Порядок ходов</label>
                    <div class="col-sm-8">
                        <select class="form-select" id="turn_type" name="turn_type">
                            <option value="concurrently" <?= isset($data["turn_type"]) && $data["turn_type"] == "concurrently" ? "selected" : "" ?>>Одновременно</option>
                            <option value="byturn" <?= !isset($data["turn_type"]) || $data["turn_type"] == "byturn" ? "selected" : "" ?>>По очереди</option>
                            <option value="onewindow" <?= isset($data["turn_type"]) && $data["turn_type"] == "onewindow" ? "selected" : "" ?>>По очереди за одним компьютером</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <h3 class="mb-3">Игроки (минимум 2, максимум 16)</h3>
                <div id="player-list">
                    <?php
                    $user_list = isset($data["users"]) && is_array($data["users"]) ? $data["users"] : ["", ""];
if (count($user_list) < 2) {
    $user_list = ["", ""];
}
$player_count = 1;
foreach ($user_list as $user_login): ?>
                    <div class="input-group mb-2 player-field">
                        <span class="input-group-text player-color-swatch"></span>
                        <input type="text" class="form-control" name="users[]" value="<?= htmlspecialchars($user_login) ?>" placeholder="Имя игрока <?= $player_count ?>">
                        <?php if ($player_count > 2): ?>
                        <button type="button" class="btn btn-outline-danger remove-player">✕</button>
                        <?php endif; ?>
                    </div>
                    <?php $player_count++; endforeach; ?>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-start mb-4">
                    <button type="button" id="add-player-btn" class="btn btn-outline-primary">Добавить игрока</button>
                </div>

                <hr class="my-4">

                <div class="d-grid gap-2 d-md-flex justify-content-between">
                    <a href="index.php?method=selectgame" class="btn btn-outline-secondary">← Вернуться к выбору</a>
                    <button type="submit" class="btn btn-success">Создать игру</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    function updatePlayerPlaceholdersAndColors() {
        var player_count = 1;
        $('.player-field').each(function() {
            $(this).find('input').attr('placeholder', 'Имя игрока ' + player_count);
            
            var color = "#";
            var sym = "ff";
            var color_num = player_count;
            if (player_count > 8) {
                sym = "88";
                color_num = player_count - 8;
            }
            color += ((color_num & 4) > 0) ? sym : "00";
            color += ((color_num & 2) > 0) ? sym : "00";
            color += ((color_num & 1) > 0) ? sym : "00";
            $(this).find('.player-color-swatch').css('background-color', color);
            
            player_count++;
        });
    }

    $('#add-player-btn').click(function() {
        var playerCount = $('.player-field').length;
        if (playerCount >= 16) {
            alert('Максимум 16 игроков');
            return;
        }
        var newPlayerField = `
            <div class="input-group mb-2 player-field">
                <span class="input-group-text player-color-swatch"></span>
                <input type="text" class="form-control" name="users[]" value="">
                <button type="button" class="btn btn-outline-danger remove-player">✕</button>
            </div>`;
        $('#player-list').append(newPlayerField);
        updatePlayerPlaceholdersAndColors();
    });

    $('#player-list').on('click', '.remove-player', function() {
        $(this).closest('.player-field').remove();
        updatePlayerPlaceholdersAndColors();
    });

    updatePlayerPlaceholdersAndColors();

    $('form').submit(function(e) {
        var name = $('input[name="name"]').val().trim();
        if (!name) {
            alert('Введите название игры');
            e.preventDefault();
            return false;
        }

        var players = [];
        $('input[name="users[]"]').each(function() {
            var val = $(this).val().trim();
            if (val) {
                players.push(val);
            }
        });

        if (players.length < 2) {
            alert('Необходимо минимум 2 игрока');
            e.preventDefault();
            return false;
        }

        var unique_players = [...new Set(players)];
        if (unique_players.length !== players.length) {
            alert('Имена игроков не должны повторяться');
            e.preventDefault();
            return false;
        }

        return true;
    });
});
</script>
<?php
$page_scripts = ob_get_clean();
include 'partials/footer.php';
?>
