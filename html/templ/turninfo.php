<div id="game-info-window">
    <?php foreach ($data['players'] as $player):?>
    <div class="game-info-player<?=($player['login'] == $data['user_login']) ? ' game-info-you-player' : ''?>">
        <b><?=$player['turn_order']?>.</b> Игрок <span style="color: <?=$player['color']?>; font-weight: bold"><?=$player['login']?></span>
        <?if ($player['turn_status'] == 'wait') {
            echo 'Ждёт своего хода';
        } elseif ($player['turn_status'] == 'play') {
            echo 'Ходит';
        } elseif ($player['turn_status'] == 'end') {
            echo 'Закончил ход';
        } ?>
    </div>
    <?endforeach;?>
</div>
<script type="text/javascript">
    map.turn_status = '<?=$data['turn_status']?>';
</script>