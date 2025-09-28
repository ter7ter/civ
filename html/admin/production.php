<?php
use App\ResearchType;
use App\ResourceType;

$item_types = ResourceType::getAll();
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
<?php include 'templates/production_form.php'; ?>
