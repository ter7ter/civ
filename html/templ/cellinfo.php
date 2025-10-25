<div id="cell-info-window" style="padding-bottom: 10px;">
    <div style="margin-bottom: 10px;">
        <strong><?=$data['turn_num']?> ход</strong>
    </div>
    <div style="margin-bottom: 10px;">
        <?php if ($data['turn_status'] == 'play'):?>
        Ваш ход...
        <?php elseif ($data['turn_status'] == 'wait'):?>
        Ждём ваш ход...
        <?php elseif ($data['turn_status'] == 'end'):?>
        Ждём окончания хода
        <?php endif;?>
    </div>
    <div style="margin-bottom: 10px;">
        <strong>Деньги: <?=$data['user_money']?></strong> (<?=$data['user_income']?> за ход)
    </div>
    <div style="margin-bottom: 10px;">
        Эра: <?=$data['user_age']?>
    </div>
    <?php if ($data['user_research_type']):?>
    <div style="margin-bottom: 10px;">
        <strong>Исследуется:<br> <?=$data['user_research_type']?></strong> (<?=$data['user_research_turns']?> ходов)
    </div>
    <?php endif;?>
    <?php if ($data['turn_status'] == 'play'):?>
    <div style="text-align: center; padding: 10px 0; margin-bottom: 15px; border-bottom: 1px solid #495057;">
        <input type="button" value="Следующий ход[Enter]" id="do-next-step" style="padding: 5px 10px;">
    </div>
    <?php endif;?>
    <div class="d-flex align-items-start" style="margin-bottom: 10px;">
        <div class="cell-info-img flex-shrink-0">
            <img src="./img/map_<?=$data['type']?>.png">
        </div>
        <div class="flex-grow-1 ms-3">
            <div style="margin-bottom: 5px;">
                <strong>(<?=$data['x']?>,<?=$data['y']?>)</strong> <?=$data['title']?>
            </div>
            <table style="border-spacing: 4px;">
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
    <?php if (isset($data['resource'])):?>
<div>
    Ресурс <b><?=$data['resource']?></b>
</div>
    <?php endif;?>
    <?php if ($data['owner_name']):?>
<div style="width: 100%;">
    Территория <b><?=$data['owner_name']?></b>, влияние: <?=$data['owner_culture']?>
</div>
    <?php endif;?>
    <?php if (isset($data['road'])):?>
<div>
    <b><?=$data['road']?></b>
</div>
    <?php endif;?>
    <?php if (isset($data['improvement'])):?>
    <div>
        <b><?=$data['improvement']?></b>
    </div>
    <?php endif;?>
    <?php if (isset($data['unit'])):?>
    <div id="selected-unit-info" style="border-top: 1px solid #495057; padding-top: 15px;">
        <div class="d-flex">
            <div style="padding-right: 10px;">
                <img src="./img/units/<?=$data['unit']['image_file']?>" style="width: 66px; height: 66px;">
            </div>
            <div>
                <div style="margin-bottom: 5px;"><strong><?=$data['unit']['title']?></strong> (<?=$data['unit']['owner_name']?>)</div>
                <div style="margin-bottom: 3px;">Боевой опыт: рекрут</div>
                <div style="margin-bottom: 3px;">HP: <?=$data['unit']['health']?>/<?=$data['unit']['health_max']?></div>
                <div style="margin-bottom: 10px;">Движение: <?=$data['unit']['points']?>/<?=$data['unit']['max_points']?></div>
            </div>
        </div>
        <?php if ($data['unit']['mission']):?>
            <div style="margin-top: 10px;">
                Сейчас выполняет: <strong><?=$data['unit']['mission']?></strong>
                <?php if ($data['turn_status'] == 'play'):?>
                <div style="margin-top: 5px;">
                    <input class="unit-cancel-mission" type="button" value="Отменить" style="padding: 3px 8px;">
                </div>
                <?php endif;?>
            </div>
        <?php elseif (count($data['unit']['missions']) && $data['turn_status'] == 'play'):?>
            <div style="margin-top: 10px;">
                <div style="margin-bottom: 8px;"><strong>Приказы:</strong></div>
                <?php foreach ($data['unit']['missions'] as $mtype):?>
                <div style="margin-bottom: 5px;">
                    <input class="unit-do-mission" type="button" mid="<?=$mtype['id']?>" value="<?=$mtype['title']?>" style="padding: 3px 8px; width: 100%;">
                </div>
                <?php endforeach;?>
            </div>
        <?php endif;?>
    </div>
    <?php endif;?>
</div>
<script type="text/javascript">
    map.turn_status = '<?=$data['turn_status']?>';
</script>
