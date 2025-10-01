<?php
$page_title = 'Выбор игры';
include 'partials/header.php';
?>

<div class="container game-select-container">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center mb-4">Выбор игры</h1>
            <form action="index.php" method="post">
                <input type="hidden" name="method" value="login">
                
                <div class="mb-3">
                    <label for="select-game-select" class="form-label">Игра</label>
                    <div class="input-group">
                        <select id="select-game-select" name="gid" class="form-select">
                            <?php
                            $turn_type_labels = [
                                'concurrently' => 'Одновременно',
                                'byturn' => 'По очереди',
                                'onewindow' => 'По очереди за одним компьютером'
                            ];
foreach ($gamelist as $game):?>
                                <option value="<?=$game['id']?>">
                                    <?=$game['name']?> (<?=$game['map_w']?>x<?=$game['map_h']?>, <?=$game['ucount']?> игрока, <?=$turn_type_labels[$game['turn_type']] ?? $game['turn_type']?>)
                                </option>
                            <?endforeach;?>
                        </select>
                        <button type="button" id="edit-selected-game" class="btn btn-outline-secondary">Редактировать</button>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="select-game-user" class="form-label">Игрок</label>
                    <select id="select-game-user" name="uid" class="form-select"></select>
                </div>
                
                <div class="d-grid gap-2 mb-3">
                    <input id="select-game-open" type="submit" value="Открыть игру" class="btn btn-primary">
                </div>
                <div class="text-center">
                     <a href="index.php?method=creategame" class="btn btn-link">Создать новую игру</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/functions.js"></script>
<script src="js/forms.js"></script>
<script>
    $(document).ready(function() {
        // Initial population of the user dropdown
        select_game_change();

        // Handler for the edit button
        $('#edit-selected-game').click(function() {
            var game_id = $('#select-game-select').val();
            if (game_id) {
                window.location.href = 'index.php?method=editgame&game_id=' + game_id;
            }
        });
    });
</script>
<?php
$page_scripts = ob_get_clean(); // Get the buffered content into a variable
include 'partials/footer.php';
?>