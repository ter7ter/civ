$(document).on('click', '#open-research', function(e) {
	$.post('index.php?method=research', {}, function(data) {
		$('#empire-window').html(data);
		$('#empire-window').toggle();
		//$('#empire-window').css('background-image', "url('img/research1.png')");
	});
});
$(document).on('change', '#emperie-research-percent', function(e) {
	var percent = $('#emperie-research-percent').val();
	$.post('index.php?method=emperie', {'research_percent': percent}, function(data) {
		$('#empire-window').html(data);
	});
});
$(document).on('click', '.research-info-block', function(e) {
	var research = $(e.currentTarget).closest('.research-info-block');
	if (research.attr('rid')) {
		$.post('index.php?method=research', {'rid': research.attr('rid')}, function(data) {
			$('#empire-window').html(data);
		});
	}
});
$(document).on('click', '.research-age-button', function(e) {
	var age = $(e.currentTarget).attr('agenum');
	if (age) {
		$.post('index.php?method=research', {'age': age}, function(data) {
			$('#empire-window').html(data);
		});
	}
});