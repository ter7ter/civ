<div style="clear: both;text-align: center;margin-top: 10px;font-size: 18pt;">
    Эра: <?=$age_show?>
</div>
<?php foreach ($research as $res):?>
<div class="research-info-block" style="margin-top: <?=$res['m_top']?>px;
                                        margin-left: <?=$res['m_left']?>px;
<?if ($res['status'] == 'can') {
    echo 'background-color: yellow';
}
    if ($res['status'] == 'complete') {
        echo 'background-color: green';
    }
    if ($res['status'] == 'process') {
        echo 'background-color: aqua';
    }?>"
<?if ($res['status'] == 'can' || $data['turn_status'] == 'play') {
    echo 'rid="'.$res['id'].'"';
}?>;>
<div>(<?=$res['id']?>)<?=$res['title']?></div>
<?if ($res['status'] == 'can' || $res['status'] == 'process'):?>
<div><?=$res['turns']?> ходов</div>
<?endif;?>
</div>
<?endforeach;?>
<div style="width: 1100px;text-align: center;position: absolute;margin-top: 615px;">
    <?if ($age_show > 1):?>
        <input type="button" value="<==" class="research-age-button" agenum="<?=($age_show - 1)?>">
    <?endif;?>
    <?if ($age_show < GameConfig::$MAX_AGE):?>
        <input type="button" value="==>" class="research-age-button" agenum="<?=($age_show + 1)?>">
    <?endif;?>
</div>