$(document).ready(function() {
	var options = {
		weekNumbers: true,
		height: 'auto',
	};
	var lang = $('#fullcalendar').attr('lang');
	if (lang !== undefined) {
		options.lang = lang;
	}
	var select = $('#fullcalendar').attr('select');
	if (select !== undefined) {
		options.select = window[select];
		options.selectable = true;
	}
	var click = $('#fullcalendar').attr('dayClick');
	if (click !== undefined) {
		options.dayClick = window[click];
	}
	var click = $('#fullcalendar').attr('eventClick');
	if (click !== undefined) {
		options.eventClick = window[click];
	}
	var events = $('#fullcalendar').attr('events');
	if (events !== undefined) {
		options.events = window[events];
	}
	var defaultDate = $('#fullcalendar').attr('defaultDate');
	if (defaultDate !== undefined) {
		if (defaultDate.length > 0) {
			options.defaultDate = $.fullCalendar.moment(defaultDate);
		}
	}
	$('#fullcalendar').fullCalendar(options);
});