$(document).ready(function() {
	/* show yes/no dialog before accepting user action */
	$('body').on('click', '.dialog-yes-no', function(e) {
		var options = {
			title: $(this).attr('title'),
			message: $(this).attr('message'),
			url: $(this).attr('href'),
			callbacks: {
				pre: $(this).attr('pre'),
				post: $(this).attr('post'),
			},
			event: e,
		};
		dialogYesNo($(this), options);
	});
	/* basic visibility toggle */
	$('body').on('click', '.toggle-visibility', function(e) {
		// e.preventDefault();
		var object = $(this).attr('toggle-target');
		var source = this;
		$(object).toggle('fast', function() {
			var class_visible = $(source).attr('toggle-class-visible');
			var class_hidden = $(source).attr('toggle-class-hidden');
			if ($(object).is(':visible')) {
				$(source).addClass(class_visible);
				$(source).removeClass(class_hidden);
				$(source).find('[toggle-class-visible]').each(function() {
					class_visible = $(this).attr('toggle-class-visible');
					class_hidden = $(this).attr('toggle-class-hidden');
					$(this).addClass(class_visible);
					$(this).removeClass(class_hidden);
				});
			} else {
				$(source).removeClass(class_visible);
				$(source).addClass(class_hidden);
				$(source).find('[toggle-class-visible]').each(function() {
					class_visible = $(this).attr('toggle-class-visible');
					class_hidden = $(this).attr('toggle-class-hidden');
					$(this).removeClass(class_visible);
					$(this).addClass(class_hidden);
				});
			}
		});
	});
	/* load image later after page has been shown */
	$('.img-load-late').each(function() {
		loadImageLate(this, 'url');
	});
	/* late load with lazyloader */
	$('.img-lazyload').lazyload();
	/* sortable table */
	$('body').on('click', '.th-sortable', tableSortableSort);
	/* search timer */
	setInterval(function() {
		$('.table-search-input').each(function() {
			var current = $(this).val();
			var previous = $(this).attr('search-previous');
			if (previous == undefined) {
				$(this).attr('search-previous', current);
			} else if (current != previous) {
				var target = $(this).attr('search-target');
				tableSearchableSearch(target, current);
				$(this).attr('search-previous', current);
			}
		});
		$('.ul-search-input').each(function() {
			var current = $(this).val();
			var previous = $(this).attr('search-previous');
			if (previous == undefined) {
				$(this).attr('search-previous', current);
			} else if (current != previous) {
				var target = $(this).attr('search-target');
				ulSearchableSearch(target, current);
				$(this).attr('search-previous', current);
			}
		});
	}, 333);
	$('body').on('keydown', '.ul-addable-input', function(e) {
		if (event.which == 13) {
			e.preventDefault();
			ulAddableInputSubmit(this);
		}
	});
	$('body').on('click', '.ul-addable-item-remove', function(e) {
		e.preventDefault();
		$(this).parents('.ul-addable-item').remove();
	});
	$('body').on('click', '.ul-addable-item-remove-ask', function(e) {
		dialogYesNo(null, {
			remove: $(this).parents('.ul-addable-item'),
			event: e,
		});
	});
	$('body').on('click', '.ul-addable-input-submit', function(e) {
		e.preventDefault();
		var target = $(this).attr('target');
		if (target !== undefined)
		{
			ulAddableInputSubmit(target);
		}
	});
});
/* show basic yes/no dialog */
function dialogYesNo(object, options) {
	/* continue with default behaviour is set so */
	if ($(object).attr('dialog-yes-no-continue') == 'yes') {
		$(object).removeAttr('dialog-yes-no-continue');
		return;
	}
	/* disable default if event is set */
	if (options.event !== undefined) {
		options.event.preventDefault();
	}
	
	var defaults = {
		title: "{{ tr('title/dialog-yes-no') }}",
		message: "{{ tr('content/dialog-yes-no') }}",
		url: null,
		remove: null,
		callbacks: {
			pre: null,
			post: null,
		},
	};
	$.extend(defaults, options);
	BootstrapDialog.show({
		title: defaults.title,
		message: defaults.message,
		buttons: [{
			label: "{{ tr('actions/action-yes') }}",
			icon: 'fa fa-check',
			cssClass: 'btn btn-primary',
			action: function(dialog) {
				dialog.close();
				var ret = true;
				var fn_pre = defaults.callbacks.pre;
				if (typeof fn_pre != 'function') {
					fn_pre = window[defaults.callbacks.pre];
				}
				if (typeof fn_pre == 'function') {
					ret = fn_pre(this, options);
				}

				if (ret === false) {
					/* do not let action continue */
				} else if (defaults.remove) {
					$(defaults.remove).remove();
				} else if (defaults.url) {
					window.location.assign(defaults.url);
				} else {
					/* continue with default click behaviour by triggering it again */
					$(object).attr('dialog-yes-no-continue', 'yes');
					$(object).trigger('click');
				}
			}
		}, {
			label: "{{ tr('actions/action-no') }}",
			icon: 'fa fa-stop',
			cssClass: 'btn btn-default',
			action: function(dialog) {
				/* do not let action continue */
				dialog.close();
			}
		}]
	});
}
/* show modal load dialog that is not closable */
function messageReloadAfterActionComplete() {
	BootstrapDialog.show({
		closable: false,
		title: "{{ tr('title/please-wait') }}",
		message: "{{ tr('content/reload-after-action-complete') }}",
	});
	return true;
}
/* default handler for failed ajax requests */
$(document).ajaxComplete(function(event, jqxhr, settings, thrownError) {
	try {
		var data = JSON.parse(jqxhr.responseText);
		if (data.success == false) {
			BootstrapDialog.show({
				title: jqxhr.status + ' ' + jqxhr.statusText,
				message: data.msg
			});
		}
	} catch (err) {
		BootstrapDialog.show({
			title: jqxhr.status + ' ' + jqxhr.statusText,
			message: "{{ tr('msg/error/ajax-request-failed') }}"
		});
	}
});
/* load images example only when they are visible */
function loadImageLate(object_id, url_attr) {
	var obj = $(object_id);
	var img = new Image();
	$(img).attr({
		src: obj.attr(url_attr)
	});
	if (img.complete || img.readyState === 4) {
		obj.attr('src', img.src);
	} else {
		$(img).load(function(response, status, xhr) {
			if (status != 'error') {
				obj.attr('src', img.src);
			}
		});
	}
}
/* search from a table */
function tableSearchableSearch(target, search) {
	$('#' + target + ' tbody tr td[searchable="yes"]').each(function() {
		var text = $(this).attr('search-value');
		if (text == undefined) {
			text = $(this).text();
		}
		var n = text.toLowerCase().indexOf(search.toLowerCase());
		if (n >= 0) {
			$(this).parent().show();
		} else {
			$(this).parent().hide();
		}
	});
}
/* sort a table */
function tableSortableSort() {
	var field = $(this).attr('sort-id');
	if (field == undefined) {
		return;
	}
	var order = $(this).attr('sort-order');
	if (order == undefined) {
		order = 1;
	} else {
		order = -order;
	}
	if (order != 1 && order != -1) {
		order = 1;
	}
	$(this).attr('sort-order', order);
	var table = $(this).parents('.table-sortable');
	var numeric = $(this).attr('sort-type') == 'number';
	$(table).children('tbody').each(function() {
		var tbody = this;
		$(this).children('tr').sort(function(a, b) {
			var a_field = $(a).children('td[sort-id="' + field + '"]');
			var b_field = $(b).children('td[sort-id="' + field + '"]');
			if (a_field.length < 1 || b_field.length < 1) {
				return 0;
			}
			var a_val = $(a_field).attr('sort-value');
			var b_val = $(b_field).attr('sort-value');
			if (a_val == undefined) {
				a_val = $(a_field).text();
			}
			if (b_val == undefined) {
				b_val = $(b_field).text();
			}
			if (numeric) {
				a_val = parseFloat(a_val);
				b_val = parseFloat(b_val);
			}
			if (a_val < b_val) {
				return -order;
			}
			if (a_val > b_val) {
				return order;
			}
			for (var i = 1; i < 10; i++) {
				a_field = $(a).children('td[sort-order="' + i + '"]');
				b_field = $(b).children('td[sort-order="' + i + '"]');
				if (a_field.length < 1 || b_field.length < 1) {
					break;
				}
				a_val = $(a_field).attr('sort-value');
				b_val = $(b_field).attr('sort-value');
				if (a_val == undefined) {
					a_val = $(a_field).text();
				}
				if (b_val == undefined) {
					b_val = $(b_field).text();
				}
				// if (numeric) {
				// 	a_val = parseFloat(a_val);
				// 	b_val = parseFloat(b_val);
				// }
				if (a_val < b_val) {
					return -1;
				}
				if (a_val > b_val) {
					return 1;
				}
			}
			a_field = $(a).children('td:first');
			b_field = $(b).children('td:first');
			if (a_field.length < 1 || b_field.length < 1) {
				return 0;
			}
			a_val = $(a_field).attr('sort-value');
			b_val = $(b_field).attr('sort-value');
			if (a_val == undefined) {
				a_val = $(a_field).text();
			}
			if (b_val == undefined) {
				b_val = $(b_field).text();
			}
			// if (numeric) {
			// 	a_val = parseFloat(a_val);
			// 	b_val = parseFloat(b_val);
			// }
			if (a_val < b_val) {
				return -1;
			}
			if (a_val > b_val) {
				return 1;
			}
			return 0;
		}).each(function() {
			$(tbody).append($(this));
		});
	});
}
/* search from a ul */
function ulSearchableSearch(target, search) {
	var count = 0;
	var max = $('#' + target).attr('search-max');
	if (max == undefined) {
		max = 0;
	}
	$('#' + target + ' li').each(function() {
		var searchable = $(this).attr('searchable');
		if (searchable == 'no') {
			return;
		}
		var text = $(this).attr('search-value');
		if (text == undefined) {
			text = $(this).text();
		}
		var n = text.toLowerCase().indexOf(search.toLowerCase());
		if (n >= 0 && (count < max || max == 0)) {
			$(this).show();
			count++;
		} else {
			$(this).hide();
		}
	});
}
/* submit addable list item */
function ulAddableInputSubmit(object)
{
	var value = $(object).val().trim().replace(/"/g, '&quot;');
	if (value.length > 0) {
		var parent = null;
		var target = $(object).attr('target');
		if (target === undefined) {
			parent = $(object).parents('.ul-addable');
		} else {
			parent = target;
		}
		var template = $(parent).attr('template');
		if (template === undefined) {
			template = '<li class="ul-addable-item">' + value + '</li>';
		} else {
			template = $(template).html();
			template = template.replace('%value%', value);
			template = template.replace('%value%', value);
		}
		$(parent).append(template);
		$(object).val('');
		if (target === undefined) {
			$(parent).append(object);
		}
		$(object).focus();
	}
}
