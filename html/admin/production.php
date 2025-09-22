<?php
$item_types = ItemType::get_all();
?>
<script>
function item_add(type) {
	var add_item = {'id': $('#' + type + '-items-select').val(), 'title': $('#' + type + '-items-select option:selected').text(), 'amount': $('#' + type + '-items-amount').val() };
	var items = $.parseJSON($('#' + type + '-items').val());
	items[$('#' + type + '-items-select').val()] = add_item;
	$('#' + type + '-items').val(JSON.stringify(items));
	$('#' + type + '-items-info').text('');
	for (var k in items) {
		$('#' + type + '-items-info').append('<span>' + items[k].title + '(' + items[k].amount + ')'
		+' <input type="button" class="delete-' + type + '-item" item-id="' + items[k].id + '" value="X"></span>');
	}
}
function item_delete(e, type) {
	var items = $.parseJSON($('#' + type + '-items').val());
	items[$(e.target).closest('input').attr('item-id')] = undefined;
	$(e.target).closest('span').remove();
	$('#' + type + '-items').val(JSON.stringify(items));
}
$(document).on('click', '#need-items-add', function (e) {
	item_add('need');
});
$(document).on('click', '#required-items-add', function (e) {
	item_add('required');
});
$(document).on('click', '.delete-need-item', function (e) {
	item_delete(e, 'need');
});
$(document).on('click', '.delete-required-item', function (e) {
	item_delete(e, 'required');
});
</script>
<form action="index.php?page=production" method="POST">
Название <input type="text" name="title">
Время производства <input type="text" name="production_time" style="width: 30px;"><br>
<input type="hidden" name="need_items" id="need-items" value="{}">
Затрты: <select id="need-items-select"><?foreach ($item_types as $item):?>
	<option value="<?=$item->id?>"><?=$item->get_title()?></option>
	<?endforeach?></select>
	<input type="text" id="need-items-amount" value="1" style="width: 30px">
	<input type="button" id="need-items-add" value="добавить">
<div id="need-items-info"></div>

Требования: <select id="required-items-select"><?foreach ($item_types as $item):?>
	<option value="<?=$item->id?>"><?=$item->get_title()?></option>
	<?endforeach?></select>
	<input type="text" id="required-items-amount" value="1" style="width: 30px">
	<input type="button" id="required-items-add" value="добавить">
<div id="required-items-info"></div>
<input type="hidden" name="required_items" id="required-items" value="{}">
<input type="hidden" name="result_items">
<input type="hidden" name="building_types">

</form>