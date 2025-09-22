<div id="cell-info-window">
<div style="width: 100%">
    <?=$data['turn_num']?> ход
</div>
<div style="width: 100%">
<?if ($data['turn_status'] == 'play'):?>
    Ваш ход...
<?elseif ($data['turn_status'] == 'wait'):?>
    Ждём ваш ход...
<?elseif ($data['turn_status'] == 'end'):?>
    Ждём окончания хода
<?endif;?>
</div>
<div style="width: 100%">
    <b>Деньги: <?=$data['user_money']?></b> (<?=$data['user_income']?> за ход)
</div>
<div style="width: 100%">
Эра: <?=$data['user_age']?>
</div>
<?if ($data['user_research_type']):?>
<div style="width: 100%">
    <b>Исследуется:<br> <?=$data['user_research_type']?></b> (<?=$data['user_research_turns']?> ходов)
</div>
<?endif;?>
<?if ($data['turn_status'] == 'play'):?>
<div style="text-align: center; padding: 10px">
    <input type="button" value="Следующий ход[Enter]" id="do-next-step">
</div>
<?endif;?>
<div style="width: 100%">
    <div class="cell-info-img">
        <img src="./img/map_<?=$data['type']?>.png"></img>
    </div>
    <div style="margin-left: 90px;">
        <div style="width: 100%">
            <b>(<?=$data['x']?>,<?=$data['y']?>)</b> <?=$data['title']?>
        </div>
        <div>
            <table>
                <tr>
                    <td>производство</td>
                    <td><?=$data['work']?></td>
                </tr>
                <tr>
                    <td>еда</td>
                    <td><?=$data['eat']?></td>
                </tr>
                <tr>
                    <td>деньги</td>
                    <td><?=$data['money']?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
<?if (isset($data['resource'])):?>
<div>
    Ресурс <b><?=$data['resource']?></b>
</div>
<?endif;?>
<?if ($data['owner_name']):?>
<div style="width: 100%;">
    Территория <b><?=$data['owner_name']?></b>, влияние: <?=$data['owner_culture']?>
</div>
<?endif;?>
<?if (isset($data['road'])):?>
<div>
    <b><?=$data['road']?></b>
</div>
<?endif;?>
<?if (isset($data['improvement'])):?>
    <div>
        <b><?=$data['improvement']?></b>
    </div>
<?endif;?>
<?if (isset($data['unit'])):?>
<div id="selected-unit-info">
	<div style="float: left; padding: 10px">
        <img src="./img/units/<?=$data['unit']['type']?>.png"></img>
    </div>
    <div>
        <div><?=$data['unit']['title']?> (<?=$data['unit']['owner_name']?>)</div>
        <div>Боевой опыт: рекрут</div>
        <div>HP: <?=$data['unit']['health']?>/<?=$data['unit']['health_max']?></div>
        <div>Движение <?=$data['unit']['points']?>/<?=$data['unit']['max_points']?></div>
    </div>
    <?if ($data['unit']['mission']):?>
        <div style="clear:both;">
            Сейчас выполняет <b><?=$data['unit']['mission']?>
            <?if ($data['turn_status'] == 'play'):?>
            <input class="unit-cancel-mission" type="button" value="Отменить">
            <?endif;?>
        </div>
    <?elseif (count($data['unit']['missions']) && $data['turn_status'] == 'play'):?>
    <div style="clear: both;padding-left: 10px;">
    <div style="float: left">Приказы:</div>
        <div style="float: left;padding-left: 10px">
        <?foreach ($data['unit']['missions'] as $mtype):?>
        <div>
            <input class="unit-do-mission" type="button" mid="<?=$mtype['id']?>" value="<?=$mtype['title']?>">
        </div>
        <?endforeach;?>
        </div>
    </div>
    <?endif;?>
</div>
<?endif;?>
</div>
<div id="game-info-window">
    <?php foreach ($data['players'] as $player):?>
    <div class="game-info-player<?=($player['login']==$data['user_login']) ? ' game-info-you-player' : ''?>">
        <b><?=$player['turn_order']?>.</b> Игрок <span style="color: <?=$player['color']?>; font-weight: bold"><?=$player['login']?></span>
        <?if ($player['turn_status'] == 'wait') echo 'Ждёт своего хода';
        elseif ($player['turn_status'] == 'play') echo 'Ходит';
        elseif ($player['turn_status'] == 'end') echo 'Закончил ход'; ?>
    </div>
    <?endforeach;?>
</div>
<script type="text/javascript">
    map.turn_status = '<?=$data['turn_status']?>';
</script>