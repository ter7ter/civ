<? foreach ($research as $res):?>
<div class="research-info-block" style="margin-top: <?=$res['m_top']?>px;
										margin-left: <?=$res['m_left']?>px;
<?if ($res['status'] == 'can') echo 'background-color: yellow';
if ($res['status'] == 'complete') echo 'background-color: green';
if ($res['status'] == 'process') echo 'background-color: aqua';?>"
<?if ($res['status'] == 'can') echo 'rid="'.$res['id'].'"'?>;>
<div>(<?=$res['id']?>)<?=$res['title']?></div>
<?if ($res['status'] == 'can' || $res['status'] == 'process'):?>
<div><?=$res['turns']?> ходов</div>
<?endif;?>
</div>
<?endforeach;?>