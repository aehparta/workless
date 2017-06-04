$(document).ready(function() {
	$('body').on('click', '.summernote-single-edit', function(e) {
		e.preventDefault();
		$('.summernote').dblclick();
	});
	$('body').on('dblclick', '.summernote', function() {
		$(this).children('.summernote-content').summernote({
			focus: true,
			keyMap: {
				pc: {
					'TAB': 'indent'
				},
				mac: {
					'TAB': 'indent'
				}
			},
		});
		$(this).children('.summernote-toolbox').show();
	});
	$('body').on('click', '.summernote-cancel', function() {
		var summernote = $(this).parent().parent();
		$(summernote).children('.summernote-content').summernote('reset');
		$(summernote).children('.summernote-content').summernote('destroy');
		$(summernote).children('.summernote-toolbox').hide();
	});
	$('body').on('click', '.summernote-save', function() {
		var summernote = $(this).parent().parent();
		var code = $(summernote).children('.summernote-content').summernote('code');
		$.ajax({
			url: $(summernote).attr('rest-block'),
			method: 'put',
			processData: false,
			data: code
		}).success(function() {
			$(summernote).children('.summernote-content').summernote('destroy');
			$(summernote).children('.summernote-toolbox').hide();
		});
	});
});