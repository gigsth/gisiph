var origin, map, mapOrigin;
var default_location = {}, current_position;
var markerOrigin;
var markersArray = [], infowindowArray = [];
var pins = [ 'FFFFFF', '00FF00', '007700', 'FFFF00', 'FF7F00', 'FF0000', '000000' ];
pins[-1] = 'CCCCCC';
var person_type = [
	'กลุ่มปกติ',
	'กลุ่มเสี่ยง',
	'กลุ่มผู้ป่วยอาการระดับ 0',
	'กลุ่มผู้ป่วยอาการระดับ 1',
	'กลุ่มผู้ป่วยอาการระดับ 2',
	'กลุ่มผู้ป่วยอาการระดับ 3',
	'กลุ่มผู้ป่วยที่มีภาวะแทรกซ้อน'
];
person_type[-1] = 'กลุ่มที่ยังไม่ได้รับการตรวจ';
var cbxChronics, cbxColors;

var objHouses;

$(document).ready(function() {
	var init_location = {
			lat: 13.997928,
			lon: 101.310393
		},
		parse_val = JSON.parse(localStorage.getItem('default_location'))
	;

	default_location = parse_val || init_location;
	initialize(default_location);
	setting();
});

function initialize(data) {
	origin = new google.maps.LatLng(data.lat, data.lon);

	map = new google.maps.Map(
		document.getElementById('mapCanvas'),
		{
			zoom: 14,
			center: origin,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		}
	);

	mapOrigin = new google.maps.Map(
		document.getElementById('mapOrigin'),
		{
			zoom: 14,
			center: origin,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		}
	);

	markerOrigin = new google.maps.Marker({
		position: mapOrigin.getCenter(),
		map: mapOrigin,
		draggable: true
	});

	// Resized window
	$(window).resize(function() {
		if (typeof(mapOrigin) !== 'undefined') {
			mapOrigin.setCenter(current_position);
		}
	});

	$('a[href="#origin_setting"').on('shown.bs.tab', function(e) {
		google.maps.event.trigger(mapOrigin, "resize");
		mapOrigin.setCenter(markerOrigin.getPosition());
	});

	google.maps.event.addListener(markerOrigin, 'dragend', function() {
		current_position = markerOrigin.getPosition();
		default_location.lat = current_position.lat();
		default_location.lon = current_position.lng();
	});
}

function setOrigin(options) {
	if (typeof(options) === 'undefined') return false;
	localStorage.setItem('default_location', JSON.stringify(options));
}

function setMaps(option) {
	$('.progress-bar').show();
	$.ajax({
		type: 'POST',
		url: './dist/php/maps.php',
		data: {
			request: 'houses',
			chronics: option.chronics,
			colors: option.colors
		},
		contentType: 'application/x-www-form-urlencoded;charset=utf-8',
		success: function(houses) {
			if (houses.response === 'success') {
				objHouses = houses.values;
				$.each(houses.values, function(index, house) {
					var marker = new google.maps.Marker({
						position: new google.maps.LatLng(house.latitude, house.longitude),
						map: map,
						icon: 'http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=home|' + pins[house.color_level]
					});
					markersArray.push(marker);

					var house_content = '<h4 class="house" onclick="house('+house.house_id+')" role="button">' 
						+ '<span class="person-color" style="background-color: #'+ pins[house.color_level] +';">'
						+ '&nbsp;</span>&nbsp;' + house.address + '</h4><hr><ul class="persons">';

					$.each(house.persons, function(index, person) {
						house_content += '<li class="person" onclick="person(' + person.person_id + ')" role="button">'
						+ '<span class="person-color" style="background-color: #'+ pins[person.color_level] +';">'
						+ '&nbsp;</span>&nbsp;' + person.name + '</li>';
					});

					house_content += '</ul>';

					var infowindow = new google.maps.InfoWindow({
						content: house_content,
						maxWidth: 550
					});
					infowindowArray.push(infowindow);

					google.maps.event.addListener(marker, 'click', function() {
						closeAllInfoWindow();
						infowindow.open(map, this);
					});
				});
			}
			else if (houses.response === 'error') {
				console.log(houses.values);
			}
		}
	}).done(function() {
		$('.progress-bar').delay(100).fadeOut(400);
		$('#setting').show();
	});	
}

function closeAllInfoWindow()
{
	for (var i = 0; i < infowindowArray.length; i++) {
		infowindowArray[i].close();
	}
}

function clearOverlays() {
	for (var i = 0; i < infowindowArray.length; i++ ) {
		infowindowArray[i] = null;
	}
	infowindowArray.length = 0;

	for (var i = 0; i < markersArray.length; i++ ) {
		markersArray[i].setMap(null);
	}
	markersArray.length = 0;
}

function house(house_id) {
	$.each(objHouses, function(key, house) {
		if (house.house_id == house_id) {
			$('#house .modal-title').html('<span class="glyphicon glyphicon-home"></span> รายละเอียดที่อยู่');
			$('#house_type').html('<span class="person-color" style="background-color: #' 
					+ pins[house.color_level] +';">&nbsp;</span> '+house.address);

			$('#address').val(house.address);
			$('#count').val(house.persons.length);
			$('#latitude').val(house.latitude);
			$('#longitude').val(house.longitude);
			$('#house_photo').empty();

			$.each(house.photo, function(key, photo) {
				$('#house_photo').append(
					$('<div class="item"></div>').append(
						$('<img src="'+photo.file+'" alt="..." class="thumbnail img-responsive">')
					)
				);
			});
			$('#house').modal('show');
		}
	});
}

function person(person_id) {
	$.each(objHouses, function(key, house) {
		$.each(house.persons, function(key, person) {
			if (person.person_id == person_id) {
				$('#person .modal-title').html('<span class="glyphicon glyphicon-user"></span> รายละเอียดบุคคล<small> ('
					+ person_type[person.color_level] + ')</small>');
				$('#person_type').html('<span class="person-color" style="background-color: #' 
					+ pins[person.color_level] +';">&nbsp;</span> '+person.name);

				$('#name').val(person.name);
				$('#sex').val(person.sex);
				$('#idcard').val(person.idcard);
				$('#age').val(person.age);
				$('#birth').val(person.birth);
				$('#nation').val(person.nation);
				$('#origin').val(person.origin);
				$('#education').val(person.education);
				$('#systolic').val(person.last_pressure.systolic);
				$('#diastolic').val(person.last_pressure.diastolic);
				$('#sugarblood').val(person.last_sugarblood);

				$('#disease').empty().append($('<option disabled selected> -- โปรดเลือกประวัติโรคเรื้อรัง -- </option>'));
				$('#disease').attr('data-pid', person_id);
				$('#code').val('');
				$('#date').val('');
				$('#person_photo').empty();

				$.each(person.chronics, function(key, chronic) {
					$.each(chronic, function(key, disease) {
						$('#disease').append(
							$('<option></option>')
								.attr('data-code', disease.code)
								.attr('data-date', disease.date)
								.text(disease.diseasenamethai)
						); 
					});
				});

				$('#person_tab li:eq(0) a').tab('show');
				$('#person').modal('show');
			}
		});
	});
}

$('#disease').change(function() {
	$('#code').val($('#disease option:selected').attr('data-code'));
	$('#date').val($('#disease option:selected').attr('data-date'));

	$('#person_photo').empty();
	$.each(objHouses, function(key, house) {
		$.each(house.persons, function(key, person) {
			if (person.person_id == $('#disease').attr('data-pid')) {
				$.each(person.chronics, function(key, chronic) {
					$.each(chronic, function(key, disease) {
						if (disease.code == $('#disease option:selected').attr('data-code')) {
							$.each(disease.photo, function(key, photo) {
								$('#person_photo').append(
									$('<div class="item"></div>').append(
										$('<img src="'+photo.file+'" alt="..." class="thumbnail img-responsive">')
									)
								);
							});
						}
					});
				});
			}
		});
	});
});

// save setting
function setting()
{
	cbxChronics = {
		hypertension: +$('#hypertension').prop('checked'),
		diabetes: +$('#diabetes').prop('checked')
	};

	cbxColors = {
		unseen: +$('#unseen').prop('checked'),
		level_0: +$('#level_0').prop('checked'),
		level_1: +$('#level_1').prop('checked'),
		level_2: +$('#level_2').prop('checked'),
		level_3: +$('#level_3').prop('checked'),
		level_4: +$('#level_4').prop('checked'),
		level_5: +$('#level_5').prop('checked'),
		level_6: +$('#level_6').prop('checked')
	};
	$('#setting_modal').modal('hide');
	clearOverlays();
	setMaps({
		chronics: cbxChronics,
		colors: cbxColors
	});

	setOrigin(default_location);
}

$('#setting_modal').on('hidden.bs.modal', function() {
	$('#hypertension').prop('checked', cbxChronics.hypertension);
	$('#diabetes').prop('checked', cbxChronics.diabetes);

	$('#unseen').prop('checked', cbxColors.unseen);
	$('#level_0').prop('checked', cbxColors.level_0);
	$('#level_1').prop('checked', cbxColors.level_1);
	$('#level_2').prop('checked', cbxColors.level_2);
	$('#level_3').prop('checked', cbxColors.level_3);
	$('#level_4').prop('checked', cbxColors.level_4);
	$('#level_5').prop('checked', cbxColors.level_5);
	$('#level_6').prop('checked', cbxColors.level_6);
});

