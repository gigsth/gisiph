$(document).ready(function() {
	$('#backup-btn').on('click', backup);
	$('#restore-btn').on('click', restore);

	$('.fake-backup-btn').on('click', function() {
		$('#zip_input').click();
	});
	$('#zip_input').change(zipChange);

	$('.menu').on('click', function() {
		$('#show_title').text($(this).data('title-text'));
	});
});

function removeCursor(pre_id) {
	$(pre_id).find('div.cursor').remove();
}

function appendCursor(pre_id) {
	$(pre_id).append($('<div class="cursor">&nbsp;</div>'));
}

function zipChange() {
	$('#zip_name').text(this.files[0].name);
}

function beforeProcess(btn) {
	$('.btn').attr('disabled', true);
	$(btn).find('.glyphicon-flash').removeClass('glyphicon-flash').addClass('glyphicon-refresh').addClass('glyphicon-spin');
}

function afterProcess(btn) {
	$('.btn').attr('disabled', false);
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
		removeCursor('#backup-area');
		xhr.onload = function() {
			document.getElementById('download-btn').classList.remove('hide');
			afterProcess('#backup-btn');
		};
		xhr.onerror = function() {
			console.log("[XHR] Fatal Error.");
		};
		xhr.onreadystatechange = function() {
			try {
				if (xhr.readyState > 2) {
					new_response = xhr.responseText.substring(xhr.previous_text.length);
					result = new_response;
					backup_area = document.getElementById("backup-area");
					removeCursor('#backup-area');
					$('#backup-area').append(result);
					appendCursor('#backup-area');
					backup_area.scrollTop = backup_area.scrollHeight;
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
		removeCursor('#restore-area');
		xhr.onload = function() {
			afterProcess('#restore-btn');
		};
		xhr.onerror = function() {
			console.log("[XHR] Fatal Error.");
		};
		xhr.onreadystatechange = function() {
			try {
				if (xhr.readyState > 2) {
					var new_response = xhr.responseText.substring(xhr.previous_text.length);
					var result = new_response;
					var restore_area = document.getElementById("restore-area");
					removeCursor('#restore-area');
					$('#restore-area').append(result);
					appendCursor('#restore-area');
					restore_area.scrollTop = restore_area.scrollHeight;
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
