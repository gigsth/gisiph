$(document).ready(function() {
	// Inited page
	$('body').hide();

	// Loaded page
	$.ajax({
		type: 'POST',
		url: './dist/php/authorization.php',
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data['event'] === 'Auth') {
				window.location.replace('./maps.html');
			}
			else if(data['event'] === 'Error') {
				console.log(data['message']);
			}
		}
	});
	$('body').fadeIn(800);


	// Closed alert
	$('.alert > button').click(function(){
		$('.alert').fadeOut(500);
	});

	// Clicked login button
	$('#loginForm').submit(function(e) {
		e.preventDefault();
		$.ajax({
			type: "POST",
			url: './dist/php/login.php',
			data: {
				username: $("#username").val(),
				password: $("#password").val()
			},
			contentType: "application/x-www-form-urlencoded;charset=utf-8",
			success: function(data) {
				if (data['event'] === 'Success') {
					window.location.replace('./maps.html');
				}
				else {
					$('.alert').removeClass('alert-danger');
					$('.alert').removeClass('alert-warning');
					if (data['event'] === 'Warning') {
						$('.alert').addClass('alert-warning');
					}
					else if (data['event'] === 'Error') {
						$('.alert').addClass('alert-danger');
					}
					else {
						return false;
					}
					$('#message').html(data['message']);
					$('.alert').addClass('hide');
					$('.alert').fadeIn(500).removeClass('hide');
				}
			}
		});
		$("#password").val('');
		$('#username').focus();
	});

	// Pressed enter button
	$("#username").keyup(function(event) {
		if (event.keyCode === 13) {
			$('#password').focus();
		}
	});
	$("#password").keyup(function(event) {
		if (event.keyCode === 13) {
			$("#login").click();
		}
	});

	// Focused username textbox
	$('#username').focus();

	$('#tranfer_setting_btn').click(function(e) {
		e.preventDefault();
		$.ajax({
			type: "POST",
			url: './dist/php/tranfer_setting.php',
			data: {
				HOSTNAME: $("#dbhost").val(),
				USERNAME: $("#dbuser").val(),
				PASSWORD: $("#dbpass").val(),
				PORT: $("#dbport").val()
			},
			contentType: "application/x-www-form-urlencoded;charset=utf-8",
			success: function(data) {
				console.log('Config done..');
				$('#tranfer_setting').modal('hide');
			}
		});
	});
});