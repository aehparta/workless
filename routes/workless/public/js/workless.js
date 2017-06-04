$(document).ready(function() {
	$('.workless-files .img-lazyload').on('load', function() {
		var src = $(this).attr('src');
		if (src[0] == '/') {
			$(this).css('visibility', 'visible').next().hide();
		}
	})
	$('.workless-folder-create').click(function(e) {
		e.preventDefault();
		var message = "<label>{{ tr('content/folder-create') }}:</label>";
		message += '<div id="workless-folder-invalid-name" class="alert alert-danger" style="display: none;">{{ tr("msg/error/folder-invalid-character") }}</div>';
		message += '<input id="workless-folder-create-name" class="form-control" type="text" />';
		var url = $(this).attr('href');
		BootstrapDialog.show({
			title: "{{ tr('title/folder-create') }}",
			message: message,
			buttons: [{
				label: "{{ tr('actions/create') }}",
				icon: 'glyphicon glyphicon-ok',
				cssClass: 'btn btn-primary workless-folder-create-accept',
				action: function(dialog) {
					var name = $('#workless-folder-create-name').val().trim();
					if (name.indexOf('/') > -1) {
						$('#workless-folder-invalid-name').show();
					} else if (name.length > 0) {
						dialog.close();
						url += name + '/';
						$.post(url, function(data) {
							if (data.success) {
								location.reload();
							}
						});
					}
				}
			}, {
				label: "{{ tr('actions/cancel') }}",
				icon: 'glyphicon glyphicon-stop',
				cssClass: 'btn btn-default',
				action: function(dialog) {
					dialog.close();
				}
			}]
		});
	});
	$('body').on('keypress', '#workless-folder-create-name', function(e) {
		if (e.keyCode == 13) {
			$('.workless-folder-create-accept').click();
		}
	});
	$('body').on('click', '.workless-file-remove', function(e) {
		e.preventDefault();
		var url = $(this).attr('url');
		if (!url) {
			url = $(this).attr('href');
		}
		if (!url) {
			return;
		}
		var name = $(this).attr('title');
		var options = {
			title: "{{ trJs('title/delete-confirm', 'name') }}",
			message: "{{ trJs('content/delete-confirm', 'name') }}",
			callbacks: {
				pre: function() {
					$.ajax({
						url: url,
						method: 'DELETE'
					}).done(function() {
						location.reload();
					});
					return false;
				},
			},
		};
		dialogYesNo(null, options);
	});
	$('body').on('click', '#worksite-document-save', function() {
		$('#worksite-document').submit();
	});
	$('body').on('click', '.workless-project-user-list', function(e) {
		e.preventDefault();
		var name = $(this).attr('name');
		var username = $(this).attr('username');
		var list = $(this).parents('.workless-project-users-list');
		var template = $('.workless-project-user-template').html();
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
				$(item).children('.workless-project-user-loading').hide();
			});
		});
	});
	$('body').on('click', '.workless-project-user-role-remove', function(e) {
		e.preventDefault();
		var parent = $(this).parent();
		var url = $(parent).attr('url');
		$(parent).children('.roles-user-loading').show();
		$.ajax({
			url: url,
			method: 'DELETE'
		}).success(function() {
			$(parent).remove();
		});
	});
	{% if authorize('role:foreman') and not data.confirmed %}
		$('.work-progress-items').sortable({
			connectWith: '.work-progress-items'
		}).disableSelection();
		$('.work-progress-items').on('sortreceive', function(e, ui) {
			selector = 'started';
			if ($(this).is('#work-progress-items-unfinished')) {
				selector = 'unfinished';
			}
			if ($(this).is('#work-progress-items-finished')) {
				selector = 'finished';
			}
			var value = $(this).html();
			value = value.replace('data[work][progress][started][]', 'data[work][progress][' + selector + '][]');
			value = value.replace('data[work][progress][unfinished][]', 'data[work][progress][' + selector + '][]');
			value = value.replace('data[work][progress][finished][]', 'data[work][progress][' + selector + '][]');
			$(this).html(value);
		});
	{% endif %}
});

function worklessCalendarDayClick(date, event, view) {
	{% if authorize('role:foreman') %}
	window.location.href = $('#fullcalendar').attr('url') + date.format('YYYY/M/D/');
	{% endif %}
}

function worklessCalendarEvents(start, end, timezone, callback) {
	var url = $('#fullcalendar').attr('url') + start.format('YYYY/M/D/') + end.format('YYYY/M/D') + '.json';
	var year = start.format('YYYY');
	var month = start.format('M');
	var day = start.format('D');
	if (day > 1) {
		day = 1;
		month++;
		if (month > 12) {
			year++;
			month = 1;
		}
	}
	if (history.replaceState) {
		history.replaceState(null, null, $('#fullcalendar').attr('url') + year + '/' + month + '/');
	}
	$.get(url, function(data) {
		if (data.success) {
			var events = [];
			var calendar = $('#fullcalendar').fullCalendar('getCalendar');
			for (var i = 0; i < data.data.events.length; i++) {
				var e = data.data.events[i];
				var classes = ['workless-calendar-event'];
				if (e.accepted) {
					classes.push('workless-calendar-accepted');
				}
				if (e.confirmed) {
					classes.push('workless-calendar-confirmed');
				}
				var event = {
					url: e.url,
					title: e.title,
					start: calendar.moment(e.start),
					className: classes,
				};
				events.push(event);
			}
			callback(events);
		}
	});
}

function worklessUploadProgress(progress, total, file_progress, file_total, name, i, count) {
	$('#workless-files-upload .caption').hide();
	$('#workless-files-upload .progress').show();
	var percent = Math.floor(progress * 100.0 / total) + '%';
	$('#workless-files-upload .progress-bar').css('width', percent);
	$('#workless-files-upload .progress-bar').html(percent + ' (' + i + ' / ' + count + ')');
}

function worklessUploadFinished(total, count) {
	$('#workless-files-upload .caption').show();
	$('#workless-files-upload .progress').hide();
	location.reload();
}

