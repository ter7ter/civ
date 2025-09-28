<div style="float: left">
Общие доходы: <span id="emperie-all-income"><?=$data['all_amount']?></span><br>
Расходы на содержание: <span id="emperie-all-amount">0</span><br>
Расходы на науку: <span id="emperie-research-amount"><?=$data['research_amount']?></span><br>
Доход за ход: <span id="emperie-income"><?=$data['income']?></span><br>
</div>
<div style="float: left">
Расходы на науку
<?if ($data['turn_status'] == 'play'):?>
<select id="emperie-research-percent">
<?for ($p = 0;$p < 11;$p++):?>
<option <?if ($data['research_percent'] == $p) {
    echo 'selected';
}?> value="<?=$p?>"><?=($p * 10)?>%</option>
<?endfor;?>
</select>
<? else:?>
<b><?=($data['research_percent'] * 10)?></b>
<?endif?>
</div>
<table id="empire-city-list">
<tr>
	<td>Население</td>
	<td>Название города</td>
	<td>Сейчас производится</td>
	<td>Еда</td>
	<td>Производство</td>
	<td>Деньги</td>
</tr>
<?foreach ($data['cities'] as $city):?>
<tr class="emperie-city-line" cid="<?=$city['id']?>">
	<td>(<?=$city['population']?>)</td>
	<td><?=$city['title']?></td>
	<td><?=$city['production']?></td>
	<td><?=$city['peat']?></td>
	<td><?=$city['pwork']?></td>
	<td><?=$city['pmoney']?></td>
</tr>
<?endforeach;?>
</table>