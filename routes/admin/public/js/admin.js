$(document).ready(function() {
	$('body').on('click', '.roles-user-search-list', function(e) {
		e.preventDefault();
		var name = $(this).attr('name');
		var username = $(this).attr('username');
		var list = $(this).parents('.roles-users-list');
		var template = $(list).children('.roles-user-template').html();
		if (name != username) {
			name = name + ' (' + username + ')';
		}
		template = template.replace('%name%', name);
		template = template.replace('%username%', username);
		$(template).appendTo(list).each(function() {
			var url = $(this).attr('url');
			var item = this;
			$.ajax({
				url: url,
				method: 'PUT'
			}).success(function() {
				$(item).children('.roles-user-loading').hide();
			});
		});
	});
	$('body').on('click', '.roles-user-role-remove', function(e) {
		e.preventDefault();
		var parent = $(this).parent();
		var url= $(parent).attr('url');
		$(parent).children('.roles-user-loading').show();
		$.ajax({
			url: url,
			method: 'DELETE'
		}).success(function() {
			$(parent).remove();
		});
	});
});