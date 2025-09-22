<table id="#send-order-window">
	<tr>
		<td><b>(<?=$data['cell']['x']?>, <?=$data['cell']['y']?>)</b>, <?=$data['cell']['title']?></td>
	</tr>
	<tr>
		<td>
		Задача
		<select id="send-order-mission">
		<?foreach ($data['missions'] as $mission):?>
			<option value="<?=$mission['id']?>"><?=$mission['title']?></option>
		<?endforeach;?>
		</select>
		</td>
	</tr>
	<tr>
		<td>
			Время в пути(в одну сторону)<br>
			<?=$data['path_time']?> суток
		</td>
	</tr>
	<tr>
		<td><input type="button" id="start-order" value="Отправить">
		    <input type="button" id="close-send-order" value="Отмена">
		</td>
	</tr>
</table>