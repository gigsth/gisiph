$(document).ready(function() {
	// Inited page
	$('.main').hide();

	// Loaded page
	$.ajax({
		type: 'POST',
		url: './dist/php/authorization.php',
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data['event'] === 'Login') {
				window.location.replace('./login.html');
			}
			else if (data['event'] === 'Auth') {
				$("#username").html(data['message']['fullname']);
			}
			else if(data['event'] === 'Error') {
				console.log(data['message']);
			}
		}
	});
	$('.main').fadeIn(800);

	// Clicked logout button
	$('#logout').click(function() {
		$.ajax({
			type: "POST",
			url: './dist/php/authorization.php',
			data: {
				action: 'logout'
			},
			contentType: "application/x-www-form-urlencoded;charset=utf-8",
			success: function(data) {
				if (data['event'] === 'Login') {
					window.location.replace('./login.html');
				}
				else if(data['event'] === 'Error') {
					console.log(data['message']);
				}
			}
		});
		$("#username").val('');
		$("#password").val('');
	});
});