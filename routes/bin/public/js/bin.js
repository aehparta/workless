$(document).ready(function() {
	$('body').on('change', '.bin-upload-files', function(e) {
		var url = $(this).attr('url');
		var progress = window[$(this).attr('progress')];
		var finished = window[$(this).attr('finished')];
		var files = this.files;
		var total = 0;
		for (var i = 0; i < files.length; i++) {
			var file = files[i];
			total += file.size;
		}
		binSendFiles(url, files, 0, total, 0, progress, finished);
	});
});

function binSendFiles(url, files, current, total, transferred, progress, finished) {
	var fd = new FormData();
	var xhr = new XMLHttpRequest();
	xhr.open('POST', url, true);
	xhr.onloadend = function(e) {
		transferred += files[current].size;
		current++;
		if (current < files.length) {
			binSendFiles(url, files, current, total, transferred, progress, finished);
		} else {
			if (finished !== undefined) {
				finished(total, files.length);
			}
		}
	};
	xhr.upload.onprogress = function(e) {
		if (progress !== undefined) {
			progress(transferred + e.loaded, total, e.loaded, e.total, files[current].name, current + 1, files.length);
		}
	};
	fd.append('file' + current, files[current]);
	xhr.send(fd);
}