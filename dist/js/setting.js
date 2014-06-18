$(document).ready(function() {
	$('#backup-btn').on('click', backup);
	$('#restore-btn').on('click', restore);

	$('.fake-backup-btn').on('click', function() {
		$('#zip_input').click();
		$('#restore-btn').removeClass('disabled');
	});
	$('#zip_input').change(zipChange);

	$('.menu').on('click', function() {
		$('#show_title').text($(this).data('title-text'));
	});
});

function clearPregressBar(pre_id) {
	$(pre_id).hide().width('0%').show();
}

function zipChange() {
	$('#zip_name').text(this.files[0].name);
}

function beforeProcess(btn) {
	$('.btn').addClass('disabled');
	$(btn).find('.glyphicon-flash').removeClass('glyphicon-flash').addClass('glyphicon-refresh').addClass('glyphicon-spin');
}

function afterProcess(btn) {
	$('.btn').removeClass('disabled');
	$(btn).find('.glyphicon-spin').removeClass('glyphicon-spin').addClass('glyphicon-flash').removeClass('glyphicon-spin');
	$('#download-btn').removeClass('hide');
}

function backup() {
	if (!window.XMLHttpRequest) {
		console.log("Your browser does not support the native XMLHttpRequest object.");
		return;
	}
	try {
		var xhr = new XMLHttpRequest();
		var new_response, result, backup_area;
		xhr.previous_text = '';
		beforeProcess('#backup-btn');
		clearPregressBar('#backup-area');
		xhr.onload = function() {
			document.getElementById('download-btn').classList.remove('hide');
			afterProcess('#backup-btn');
			$('#backup-area').width('100%');
			console.log('done...');
		};
		xhr.onerror = function() {
			console.log("[XHR] Fatal Error.");
		};
		xhr.onreadystatechange = function() {
			try {
				if (xhr.readyState > 2) {
					new_response = xhr.responseText.substring(xhr.previous_text.length);
					result = new_response;
					$('#backup-area').width(result);
					xhr.previous_text = xhr.responseText;
				}
			}
			catch (e) {
				//log_message("<b>[XHR] Exception: " + e + "</b>");
			}
		};
		xhr.open("GET", "./dist/php/gisiph_backup.php", true);
		xhr.send();      
	}
	catch(e) {
		console.log("<b>[XHR] Exception: " + e + "</b>");
	}
}

function restore() {
	if (!window.XMLHttpRequest) {
		console.log("Your browser does not support the native XMLHttpRequest object.");
		return;
	}
	try {
		var xhr = new XMLHttpRequest();  
		xhr.previous_text = '';
		beforeProcess('#restore-btn');
		clearPregressBar('#restore-area');
		xhr.onload = function() {
			afterProcess('#restore-btn');
			$('#restore-area').width('100%');
			console.log('done...');
		};
		xhr.onerror = function() {
			console.log("[XHR] Fatal Error.");
		};
		xhr.onreadystatechange = function() {
			try {
				if (xhr.readyState > 2) {
					var new_response = xhr.responseText.substring(xhr.previous_text.length);
					var result = new_response;
					$('#restore-area').width(result);
					xhr.previous_text = xhr.responseText;
				}
			}
			catch (e) {
				//log_message("<b>[XHR] Exception: " + e + "</b>");
			}
		};
		var data = new FormData();
		data.append('zip', zip_input = $('#zip_input')[0].files[0]);
		xhr.open("POST", "./dist/php/gisiph_restore.php", true);
		xhr.send(data);      
	}
	catch(e) {
		console.log("<b>[XHR] Exception: " + e + "</b>");
	}
}
