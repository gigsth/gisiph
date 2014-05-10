var id, map, myLatLng,marker, origin, currentAddress;
var menu, group;
var ctrlDown = false;

$(document).ready(function() {
	// Initlization page
	menu = 'Home';
	getDataToTable({
		menu: menu,
		page: 1
	});

	// Initial alertify
	alertifyReset();

	// Clicked menu
	$('.menu').click(function(e) {
		e.preventDefault();
		menu = $(this).data('menu');
		getDataToTable({
			menu: menu,
			page: 1
		});
		$('.menu').removeClass('active');
		$(this).addClass('active');
	});

	// Submited form
	$('form').submit(function(e) {
		e.preventDefault();
		beautifulKeyword();
		getDataToTable({
			menu: menu,
			search: $("#keyword").val(),
			page: 1
		});
	});

	// Clicked datatable ( Opened Modal )
	$(document).on("click",".datatable", function() {

		$('#save').attr('data-save', $(this).attr('id'));
		id = parseInt($(this).attr('id'));
		$('#address').html($(this).text());
		
		if (menu === 'Home') {
			$('.modal-title').html('<span class="glyphicon glyphicon-home"></span> ข้อมูลที่พักอาศัย');
			$('#rowHome').removeClass('hide');
			$('#rowPatient').addClass('hide');
			downloadImage({
				hcode: id,
				menu: $('.menu.active').data('menu')
			});
			$('#gpsAdd').hide();
			$('#gpsDel').hide();
			
			if (!$(this).find('span').first().hasClass('glyphicon-map-marker')) {
				myLatLng = new google.maps.LatLng(0, 0);
				$('#gpsAdd').show();
				$('#latitude, #longitude').attr('readonly', 'readonly');
			}
			else {
				myLatLng = new google.maps.LatLng($(this).data('lat'), $(this).data('lng'));
				$('#gpsDel').show();
				$('#latitude, #longitude').removeAttr('readonly');
			}

			var lt = (myLatLng.lat() === 0)? '' : myLatLng.lat().toFixed(6).toString();
			var ln = (myLatLng.lng() === 0)? '' : myLatLng.lng().toFixed(6).toString();

			$('#latitude').val(lt);
			$('#longitude').val(ln);

			// Person
			$.ajax({
				type: 'POST',
				url: './dist/php/manage.php',
				data: {
					scope: 'Person',
					menu: $('.menu.active').data('menu'),
					hcode: id
				},
				contentType: "application/x-www-form-urlencoded;charset=utf-8",
				success: function(data) {
					if (data['event'] === 'Success') {
						$('#peopleInHouse').empty();
						var table = $('<table></table>').addClass('table');
						var thead = $('<thead  style="font-weight:bold;"></thead>');
						var tbody = $('<tbody style="bg-color: gray;opacity: .8;"></tbody>');
						$(thead).append(
							$('<tr></tr>').append(
								$('<th></th>').text('ชื่อ-สกุล'),
								$('<th></th>').text('อายุ'),
								$('<th></th>').text('การศึกษา')
							)
						);
						$.each(data['data'], function(i,item) {
							$(tbody).append(
								$('<tr></tr>').append(
									$('<td></td>').text(item.name),
									$('<td></td>').text(item.age),
									$('<td></td>').text(item.educate)
								)
							);
						});
						$(table).append(
							$(thead),
							$(tbody)
						);
						$('#peopleInHouse').append(table);
					}
					else if(data['event'] === 'Error') {
						alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
					}
				}
			});
		}
		else if (menu === 'Patient') {
			$('.modal-title').html('<span class="glyphicon glyphicon-user"></span> ข้อมูลผู้ป่วย');
			$('#rowPatient').removeClass('hide');
			$('#rowHome').addClass('hide');

			$('#name').val($(this).data('name'));
			$('#age').val($(this).data('age'));
			$('#birth').val($(this).data('birth'));
			$('#sex').val($(this).data('sex'));
			$('#idcard').val($(this).data('idcard'));
			$('#origin').val($(this).data('origin'));
			$('#nation').val($(this).data('nation'));
			$('#educate').val($(this).data('educate'));

			$('#historyChronic').empty();
			// Chronic
			$.ajax({
				type: 'POST',
				url: './dist/php/manage.php',
				data: {
					scope: 'Chronic',
					menu: $('.menu.active').data('menu'),
					pid: id
				},
				contentType: "application/x-www-form-urlencoded;charset=utf-8",
				success: function(data) {
					if (data['event'] === 'Success') {
						if (data['data'].length === 0) $('#historyChronic').html('<label class="col-lg-12">ไม่มีประวัติการตรวจพบ</label>');
						$.each(data['data'], function(i,item) {
							var s = $('<a href="#" id="'+item.code+'" class="list-group-item"></a>').append(
								'<span class="badge"><strong>'+item.date+'</strong></span>',
								'<p class="list-group-item-text">'+item.chronic+'</p>'
							);
							$('#historyChronic').append(s);
							$(s).click(function(e) {
								e.preventDefault();
								$('#photoPatient').empty();
								$('.list-group-item').removeClass('active');
								$(this).addClass('active');

								$('#phototitle').text(item.chronic);
								$('#photogroup').attr('data-group', item.group);
								$('#photogroup').attr('data-ccode', item.code);

								downloadImage({
									hcode: id,
									ccode: $(this).attr('id'),
									menu: $('.menu.active').data('menu')
								});
							});
						});
					}
					else if(data['event'] === 'Error') {
						alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
					}
				}
			});
			// Visit
			var visitno;
			$.ajax({
				type: 'POST',
				url: './dist/php/manage.php',
				data: {
					scope: 'Visit',
					menu: $('.menu.active').data('menu'),
					pid: id
				},
				contentType: "application/x-www-form-urlencoded;charset=utf-8",
				success: function(data) {
					if (data['event'] === 'Success') {
						$('#height').val(data['data'].height);
						$('#weight').val(data['data'].weight);
						$('#waist').val(data['data'].waist);
						$('#ass').val(data['data'].ass);
						$('#pressure').val(data['data'].pressure);
						$('#visitdate').text('บัททึกเมื่อ '+data['data'].visitdate);
					}
					else if(data['event'] === 'Error') {
						alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
					}
				}
			}).done(function() {
				// Sugar
				$.ajax({
					type: 'POST',
					url: './dist/php/manage.php',
					data: {
						scope: 'Sugar',
						menu: $('.menu.active').data('menu'),
						visitno: visitno
					},
					contentType: "application/x-www-form-urlencoded;charset=utf-8",
					success: function(data) {
						if (data['event'] === 'Success') {
							$('#sugar').val(data['data']);
						}
						else if(data['event'] === 'Error') {
							alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
						}
					}
				});
			});
		}

		$('#manage').modal({
			show: true,
			backdrop: true
		});
	});

	// Shown modal
	$('#manage').on('shown.bs.modal', function () {
		if (menu === 'Home') {
			setMap();
			google.maps.event.trigger(map, 'resize');
		}
		else if (menu === 'Patient') {
			$('#historyChronic > a:first-child').click();
		}
	});

	// Hidden modal
	$('#manage').on('hidden.bs.modal', function () {
		$('#latitude').val(null);
		$('#longitude').val(null);
		$('.stockImg').empty();

		$('#height').val('');
		$('#weight').val('');
		$('#waist').val('');
		$('#ass').val('');
		$('#pressure').val('');
		$('#visitdate').text('');
		$('#sugar').val('');
	});

	// Clicked add pgs
	$('#gpsAdd').click(function() {
		setMarker();
		$('#latitude, #longitude').removeAttr('readonly');
	});

	// Clicked delete pgs
	$('#gpsDel').click(function() {
		clearMarker();
		$('#latitude, #longitude').attr('readonly', 'readonly');
	});

	// Get Ctrl is down
	$(document).keydown(function(e) {
		if (e.keyCode == 17) ctrlDown = true;
	});

	// Edited lat or lng
	$('#latitude, #longitude').keyup(function(e) {
		if (!ctrlDown) {
			myLatLng = new google.maps.LatLng($('#latitude').val(), $('#longitude').val());
			setMap();
			saveMarker({
				scope: 'Save',
				menu: $('.menu.active').data('menu'),
				id: id,
				latitude: parseFloat($('#latitude').val()).toFixed(6),
				longitude: parseFloat($('#longitude').val()).toFixed(6)
			});
		}
		ctrlDown = false;
		
	});

	// Changed lat or lng
	$('#latitude, #longitude').change(function(e) {
		if ($(this).val() !== parseFloat($(this).val()).toFixed(6).toString()) {
			$(this) .val(parseFloat($(this).val()).toFixed(6).toString());
		}

	});

	// Resized window
	$(window).resize(function() {
		if (typeof(map) !== 'undefined') {
			map.setCenter(myLatLng);
		}
	});

	$("#imgInput").change(function(){
		if (typeof(this) !== 'undefined') {
			//readImg(this);
			uploadImgage({
				files: $('#imgInput'),
				menu: $('.menu.active').data('menu'),
				hcode: id,
				ccode: $('#photogroup').data('ccode')
			});
			$(this).parent('form').get(0).reset();
		}
	});

	$('.btnPhoto').click(function() {
		$('#imgInput').click();
	});

	$('.menu[data-menu="Home"]').click(function() {
		$('.menu[data-menu="Patient"].btn').removeClass('active');
		$('.menu[data-menu="Home"].btn').addClass('active');

		$('.menu[data-menu="Patient"]').parent('li').removeClass('active');
		$('.menu[data-menu="Home"]').parent('li').addClass('active');

		$('.showTitle').text('ข้อมูลที่พักอาศัย');
		$('#keyword').val('');
	});

	$('.menu[data-menu="Patient"]').click(function() {
		$('.menu[data-menu="Home"].btn').removeClass('active');
		$('.menu[data-menu="Patient"].btn').addClass('active');

		$('.menu[data-menu="Home"]').parent('li').removeClass('active');
		$('.menu[data-menu="Patient"]').parent('li').addClass('active');

		$('.showTitle').text('ข้อมูลผู้ป่วย');
		$('#keyword').val('');
	});
});

// NOT USED
function readImg(input) {
	$.each(input.files, function(i, item) {
		if (item) {
			var reader = new FileReader();
			reader.readAsDataURL(item);
		}
	});
}

function confirmDelete(img, parent) {
	var message = '<h3>คุณต้องการลบภาพนี้ ใช่หรือไม่ ?</h3><hr>'
	message +='<img src="'+img.attr('src')+'" alt="รูปที่ต้องการลบ" class="img-thumbnail" style="max-height: 280px;"><hr>';
	alertify.confirm(message, function (e) {
		if (e) {
			deleteImage({
				hcode: id,
				menu: $('.menu.active').data('menu'),
				phcode: $(img).data('key')
			});
			$(img).parents(parent+' > div').remove();
		} 
	});
}

function renderImage(render, item) {
	var img = $('<img data-key="'+item.key+'" style="height: 159px;">').attr('src', item.file);
	$(render).append(
		$('<div class="col-xs-12 col-sm-12 col-md-6"></div>').append(
			$('<a href="#" class="thumbnail img-responsive"></a>').append(img)
		)
	);
	$(img).parents(render+' > div > a').on('click', function() {
		confirmDelete($(img), render);
	});
}

function uploadImgage(option) {
	var data = new FormData();
	data.append('scope', 'AddPhoto');
	data.append('menu', option.menu);
	data.append('hcode', option.hcode);
	data.append('ccode', option.ccode);

	var c = 0;
	$.each(option.files, function(i, field) {
		$.each(field.files, function(j, file) {
			data.append('file_'+c, file);
			c = c + 1;
		});
	});
	
	$.ajax({
		url: './dist/php/manage.php',
		data: data,
		cache: false,
		contentType: false,
		processData: false,
		type: 'POST',
		success: function(data){
			if (data['event'] === 'Success') {
				$.each(data['data'], function(i,item) {
					renderImage('#photo'+option.menu, item);
					iconControl('IMG', 'Save', option.hcode);
				})
			}
			else if(data['event'] === 'Error') {
				alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
			}
		}
	});
}

function downloadImage(option) {
	$.ajax({
		type: 'POST',
		url: './dist/php/manage.php',
		data: {
			scope: 'SelPhoto',
			menu: option.menu,
			hcode: option.hcode,
			ccode: option.ccode
		},
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data['event'] === 'Success') {
				$.each(data['data'], function(i,item) {
					renderImage('#photo'+option.menu, item);
				});
			}
			else if(data['event'] === 'Error') {
				alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
			}
		}
	});
}

function deleteImage(option) {
	$.ajax({
		type: 'POST',
		url: './dist/php/manage.php',
		data: {
			scope: 'DelPhoto',
			phcode: option.phcode,
			menu: option.menu
		},
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data['event'] === 'Success') {
				iconControl('IMG', 'Delete', option.hcode);
			}
			else if(data['event'] === 'Error') {
				alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
			}
		}
	});
}

function iconControl(menu, scope, id) {
	if (menu === 'GPS') {
		if (scope === 'Save') {
			$('.datatable[id='+id+'] > td > span').addClass('glyphicon-map-marker').addClass('glyphicon');
			alertify.log('<span class="glyphicon glyphicon-floppy-disk"></span> บันทึกพิกัดเรียบร้อยแล้ว');
		}
		else if (scope === 'Delete') {
			$('.datatable[id='+id+'] > td > span').removeClass('glyphicon-map-marker').removeClass('glyphicon');
			alertify.log('<span class="glyphicon glyphicon-trash"></span> ลบพิกัดเรียบร้อยแล้ว');
			
		}
	}
	else if (menu === 'IMG') {
		if (scope === 'Save') {
			$('.datatable[id='+id+'] > td > span').addClass('glyphicon-picture').addClass('glyphicon');
			alertify.log('<span class="glyphicon glyphicon-floppy-disk"></span> บันทึกรูปภาพเรียบร้อยแล้ว');
		}
		else if (scope === 'Delete') {
			$('.datatable[id='+id+'] > td > span').removeClass('glyphicon-picture').removeClass('glyphicon');
			alertify.log('<span class="glyphicon glyphicon-trash"></span> ลบรูปภาพเรียบร้อยแล้ว');
		}
	}
}

function saveMarker(option) {
	$.ajax({
		type: 'POST',
		url: './dist/php/manage.php',
		data: {
			scope: option.scope,
			menu: option.menu,
			id: option.id,
			latitude: option.latitude,
			longitude: option.longitude
		},
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data['event'] === 'Success') {
				$('.datatable[id='+data['data']['id']+']').attr('data-lat', data['data']['latitude']);
				$('.datatable[id='+data['data']['id']+']').attr('data-lng', data['data']['longitude']);
				iconControl('GPS', option.scope, option.id);
			}
			else if(data['event'] === 'Error') {
				alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
			}
		}
	});
}

function setMarker() {
	var markerImg = 'markers='+map.getCenter().lat()+','+map.getCenter().lng(),
	mapImg = 'http://maps.googleapis.com/maps/api/staticmap?'+markerImg+'&zoom=15&size=800x400&scale=1&maptype=roadmap&sensor=false&visual_refresh=true';
	var message = '<h3>คุณต้องการเพิ่มพิกัดนี้ ใช่หรือไม่ ? </h3><hr>';
	message +='<img src="'+mapImg+'" alt="พิกัดที่ต้องการเพิ่ม" class="img-thumbnail"><hr>';

	alertify.confirm(message, function (e) {
		if (e) {
			myLatLng = map.getCenter();
			marker.setAnimation(google.maps.Animation.DROP);
			marker.setPosition(myLatLng);
			$('#latitude').val(marker.getPosition().lat().toFixed(6).toString());
			$('#longitude').val(marker.getPosition().lng().toFixed(6).toString());
			$('#gpsAdd').slideUp(100);
			$('#gpsDel').delay(100).slideDown(100);

			saveMarker({
				scope: 'Save',
				menu: $('.menu.active').data('menu'),
				id: id,
				latitude: parseFloat($('#latitude').val()).toFixed(6),
				longitude: parseFloat($('#longitude').val()).toFixed(6)
			});
		} 
	});
	$('#alertify-ok').removeClass('btn-danger').addClass('btn-success').html('<big>ใช่ ฉันต้องการเพิ่ม</big>');
}

function clearMarker() {
	var markerImg = 'markers='+$('#latitude').val()+','+$('#longitude').val(),
	mapImg = 'http://maps.googleapis.com/maps/api/staticmap?'+markerImg+'&zoom=15&size=800x400&scale=1&maptype=roadmap&sensor=false&visual_refresh=true';
	var message = '<h3>คุณต้องการลบพิกัดนี้ ใช่หรือไม่ ? </h3><hr>';
	message +='<img src="'+mapImg+'" alt="พิกัดที่ต้องการลบ" class="img-thumbnail"><hr>';

	alertify.confirm(message, function (e) {
		if (e) {
			marker.setAnimation(null);
			marker.setPosition(null);
			$('#latitude').val(null);
			$('#longitude').val(null);
			$('#gpsAdd').delay(100).slideDown(100);
			$('#gpsDel').slideUp(100);

			saveMarker({
				scope: 'Delete',
				menu: $('.menu.active').data('menu'),
				id: id
			});
		} 
	});
}

function setMap() {
	if (typeof(map) === 'undefined') {
		origin = new google.maps.LatLng(13.997928, 101.310393); // Bangpaung, Prachin-Buri

		map = new google.maps.Map(
			document.getElementById('mapCanvas'),
			{
				zoom: 15,
				center: origin,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			}
		);

		marker = new google.maps.Marker({
			position: map.getCenter(),
			map: map,
			draggable: true
		});

		google.maps.event.addListener(marker, 'drag', function() {
			$('#latitude').val(marker.getPosition().lat().toFixed(6).toString());
			$('#longitude').val(marker.getPosition().lng().toFixed(6).toString());
		});

		google.maps.event.addListener(marker, 'dragend', function() {
			myLatLng = marker.getPosition();
			saveMarker({
				scope: 'Save',
				menu: $('.menu.active').data('menu'),
				id: id,
				latitude: parseFloat($('#latitude').val()).toFixed(6),
				longitude: parseFloat($('#longitude').val()).toFixed(6)
			});
		});

		google.maps.event.addListener(map, 'zoom_changed', function() {
			$('#zoomScale').text('ระดับการซูม : '+map.zoom);
		});
	}

	marker.setPosition(myLatLng);

	if (myLatLng.toString() === '(0, 0)') {
		myLatLng = origin;
	}

	map.setCenter(myLatLng);
	map.setZoom(15); // 1(cm) : 500(m)
	
}

function beautifulKeyword() {
	var t = $("#keyword").val().split(' ');
	var r = ' ';
	t.forEach(function(s) {
		if (s !== '') {
			r += s.trim() + ' ';
		};
		
	});
	$("#keyword").val(r.trim());
}

function getDataToTable(option) {
	$('.progress-bar').show();
	$('#pageSelection').hide();
	$('table').hide();
	$('tbody').empty();
	$.ajax({
		type: 'POST',
		url: './dist/php/manage.php',
		data: {
			scope: 'Datatable',
			menu: option.menu,
			search: option.search,
			page: option.page
		},
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data['event'] === 'Success') {
				$('#pageSelection').unbind('page');
				$('#pageSelection').empty();
				$('#pageSelection').bootpag({
					total: parseInt(data.totalPage),
					page: parseInt(data.currentPage),
					maxVisible: parseInt(5)
				}).on("page", function(event, page) {
					//if (currentPage != data.currentPage) {
						getDataToTable({
							menu: option.menu,
							search: option.search,
							page: parseInt(page)
						});
					//}
				});

				if (data['data'].length === 0) {
					$('tbody').append('<tr><td colspan="2">ไม่พบข้อมูล</td></tr>')
				}

				var cls;
				$.each(data['data'], function(i,item) {
					cls = $('<span></span>');
					if (item['glyphicon'] === 'map-marker') { 
						cls = $('<span class="glyphicon glyphicon-map-marker"></span>'); 
					}
					else if (item['glyphicon'] === 'picture') {
						cls = $('<span class="glyphicon glyphicon-picture"></span>'); 
					}


					if (option.menu === 'Home') {
						$('tbody').append(
							$('<tr id="'+item['id']+'" class="datatable" data-lat="'+item['latitude']+'" data-lng="'+item['longitude']+'"></tr>').append(
								$('<td></td>').append(cls),
								$('<td></td>').text(item['address'])
							)
						);
					}
					else if (option.menu === 'Patient') {
						var td = '<tr id="'+item['id']+'" data-hcode="'+item['hcode']+'" data-name="'+item['name']+'" data-age="'+item['age']+'" ';
						td += 'data-birth="'+item['birth']+'" data-sex="'+item['sex']+'" data-idcard="'+item['idcard']+'" data-educate="'+item['educate']+'" ';
						td += 'data-occupa="'+item['occupa']+'" data-origin="'+item['origin']+'" data-nation="'+item['nation']+'" ';
						td += 'class="datatable"></tr>';
						$('tbody').append(
							$(td).append(
								$('<td></td>').append(cls),
								$('<td></td>').text(item['name'])
							)
						);
					}
				});
				$('.progress-bar').delay(100).fadeOut(400);

				$('#pageSelection > ul > li:not(.prev):not(.next)').removeClass('active');
				$('#pageSelection > ul > li[data-lp="'+option.page+'"]:not(.prev):not(.next)').addClass('active').removeClass('disabled');
				$('#pageSelection').fadeIn(100);

				$('table').fadeIn(100);
			}
			else if(data['event'] === 'Error') {
				alertify.error('<span class="glyphicon glyphicon-warning-sign"></span><strong> ผิดพลาด!</strong><br>'+data['message']);
			}
		}
	});
}

function alertifyReset () {
	alertify.set({
		labels : {
			ok     : "<big>ใช่ ฉันต้องการลบ</big>",
			cancel : "<big>ยกเลิก</big>"
		},
		delay : 5000,
		buttonReverse : true
	});
}