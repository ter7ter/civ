<script>
var possible_production = {
<?
$first_production = false;
foreach ($data['possible_production'] as $production):
	if (!$first_production) $first_production = $production;?>
	'<?=$production['id']?>': 
	{'need': [
	<?foreach ($production['need_items'] as $item):?>
		{'type': <?=$item['type']?>, 'title': '<?=$item['title']?>', 'amount': <?=$item['amount']?>}
	<?endforeach;?>
	],
	'required':[
	<?foreach ($production['required_items'] as $item):?>
		{'type': <?=$item['type']?>, 'title': '<?=$item['title']?>', 'amount': <?=$item['amount']?>}
	<?endforeach;?>
	],
	'title': '<?=$production['title']?>',
	'time': <?=$production['time']?>
	},
<?endforeach;?>
};
</script>
<table id="production-window" bid="<?=$data['building']['id']?>">
<?if (isset($error)):?>
<tr>
	<td colspan="2" style="color: red"><?=$error?></td>
</tr>
<?endif;?>
<tr><td colspan="2">
	<select id="production-order-type"><?foreach ($data['possible_production'] as $production):?>
	<option value="<?=$production['id']?>"><?=$production['title']?>(<?=$production['time']?> ч/дней)</option>
	<?endforeach;?></select>
</td></tr>
<tr id="production-type-info" <?if (!$first_production) echo 'style="display: none"'?>>
	<td colspan="2">
	<span id="production-type-info-title" style="font-weight: bold;"><?=$first_production['title']?></span><br>
	<b>Трудоёмкость:</b> <span id="production-type-info-time"><?=$first_production['time']?></span> ч/дней<br>
	<b>Затраты:</b> <span id="production-type-need"><?foreach ($first_production['need_items'] as $item):?>
	<?=$item['title']?>(<?=$item['amount']?>) 
	<?endforeach;?>
	<?if (count($first_production['need_items']) == 0) echo 'нет'?></span>
	<br>
	<b>Требования:</b> <span id="production-type-required"><?foreach ($first_production['required_items'] as $item):?>
	<?=$item['title']?>(<?=$item['amount']?>) 
	<?endforeach;?>
	<?if (count($first_production['required_items']) == 0) echo 'нет'?></span>
	</td>
</tr>
<tr>
	<td><input type="text" id="production-order-amount" value="1"></td>
	<td><input type="button" id="production-order" value="Заказать"></td>
</tr></td>
<tr><td colspan="2"><hr></td></tr>
<?foreach ($data['productions'] as $production):?>
<tr>
	<td><?=$production['title']?></td><td><?=$production['amount']?></td>
</tr>
<?endforeach;?>
</table>