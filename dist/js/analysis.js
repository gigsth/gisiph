google.load('visualization', '1', {packages: ["corechart"]});

var cDiscover, cChronics, cVillage, cColorFromHypertension, cColorFromDiabetes,
	cColorFromHypertensionVillage, cColorFromDiabetesVillage,
	cColorStackFromHypertensionVillage, cColorStackFromDiabetesVillage;

$(window).on("resize", function(event) {
	drawCharts();
});

$(document).ready(function() {
	google.setOnLoadCallback(drawCharts);
	getNameVillage();
});

$('a.menu,  a.sub-menu').click(function() {
	setTimeout(function() {
		drawCharts();
	},200);
});

$('#tab2-selection, #tab3-selection').on('change', function() {
	setTimeout(function() {
		drawCharts();
	},200);
});

function drawCharts() {

	var tab_id = $('.tab-pane.view.active').attr('id');

	$(function() {
		$('.progress-bar').show();
		if (tab_id === 'tab1') {
			var sub_tab_id = $('#tab1 .tab-pane.active').attr('id');

			if (sub_tab_id === 'tab1-1') {
				callJSON({request: 'chronics'}, drawChronics);
			} else if (sub_tab_id === 'tab1-2') {
				callJSON({request: 'village'}, drawVillage);
			} else if (sub_tab_id === 'tab1-3') {
				callJSON({request: 'discover'}, drawDiscover);
			};
		} else if (tab_id === 'tab2') {
			var sub_tab_id = $('#tab2 .tab-pane.active').attr('id');

			if (sub_tab_id === 'tab2-1') {
				callJSON({request: 'colorFromHypertension', selection: $('#tab2-selection').val()}, drawColorFromHypertension);
			} else if (sub_tab_id === 'tab2-2') {
				callJSON({request: 'colorStackFromHypertension'}, drawColorStackFromHypertension);
			};
		}else if (tab_id === 'tab3') {
			var sub_tab_id = $('#tab3 .tab-pane.active').attr('id');

			if (sub_tab_id === 'tab3-1') {
				callJSON({request: 'colorFromDiabetes', selection: $('#tab3-selection').val()}, drawColorFromDiabetes);
			} else if (sub_tab_id === 'tab3-2') {
				callJSON({request: 'colorStackFromDiabetes'}, drawColorStackFromDiabetes);
			};
		};
	}).promise().done(function() {
		var date = new Date(),
			yyyy = (date.getFullYear()+543).toString(),
			mm = (date.getMonth()+1).toString(),
			dd  = date.getDate().toString();
			$('#print_date').html((dd[1]?dd:"0"+dd[0])+'/'+(mm[1]?mm:"0"+mm[0])+'/'+yyyy);
		$('.progress-bar').delay(100).fadeOut(400);
	});
}

function drawChronics(chronics) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'โรค');
	data.addColumn('number', 'จำนวน');
	data.addRows(getValues(chronics));console.log(getValues(chronics).join(','));
	cChronics = new google.visualization.PieChart(document.getElementById('chronics'));
	cChronics.draw(data, {
			title: 'สัดส่วนของผู้ป่วยโรคเรื้อรัง',
			width: '100%',
			height: '100%'
	});

	$('#print-header').html('แผนภูมิอัตราผู้ป่วยโรคเรื้อรัง <small>(เบาหวานและความดันโลหิตสูง)</small>');
	prepareTable(['โรคเรื้อรัง', 'จำนวน(คน)'], $.map(chronics, function(value, index) {
		return [[value.disease, value.count]];
	}));
}

function drawVillage(village) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'หมู่บ้าน');
	data.addColumn('number', 'ความดันโลหิตสูง');
	data.addColumn('number', 'เบาหวาน');
	data.addColumn('number', 'เบาหวานและความดันโลหิตสูง');
	data.addRows(getValues(village));
	cVillage = new google.visualization.ColumnChart(document.getElementById('village'));
	cVillage.draw(data, {
			title: 'สัดส่วนของผู้ป่วยโรคเรื้อรังในแต่ละหมู่บ้าน',
			width: '100%',
			height: '100%'
	});

	$('#print-header').html('แผนภูมิอัตราผู้ป่วยโรคเรื้อรัง <small>(เบาหวานและความดันโลหิตสูง)</small>');
	prepareTable(['หมู่บ้าน', 'โรคความดันโลหิตสูง(คน)','โรคเบาหวาน(คน)',  'โรคเบาหวานและความดันโลหิตสูง(คน)'], $.map(village, function(value, index) {
		return [[value.villname, value.diabetes, value.hypertension, value.both]];
	}));
}

function drawDiscover(discover) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'ปี');
	data.addColumn('number', 'เบาหวาน');
	data.addColumn('number', 'ความดันโลหิตสูง');
	data.addRows(getValues(discover));
	cDiscover = new google.visualization.LineChart(document.getElementById('discover'));
	cDiscover.draw(data, {
			title: 'จำนวนผู้ป่วยโรคเรื้อรังที่เพิ่มขึ้นในแต่ละปี',
			width: '100%',
			height: '100%'
	});

	$('#print-header').html('แผนภูมิอัตราผู้ป่วยโรคเรื้อรัง <small>(เบาหวานและความดันโลหิตสูง)</small>');
	prepareTable(['ปี(พ.ศ.)', 'โรคเบาหวาน(คน)', 'โรคความดันโลหิตสูง(คน)'], $.map(discover, function(value, index) {
		return [[value.year, value.diabetes, value.hypertension]];
	}));
}

function drawColorFromHypertension(colorFromHypertension) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'ระดับความรุนแรง');
	data.addColumn('number', 'จำนวน');
	data.addColumn({type: 'string', role: 'style'});
	data.addRows(getValues(colorFromHypertension));
	var view = new google.visualization.DataView(data);
	view.setColumns(
		[
			0, 
			1,
			{ 
				calc: "stringify",
				sourceColumn: 1,
				type: "string",
				role: "annotation" 
			},
			2
		]
	);
	cColorFromHypertension = new google.visualization.ColumnChart(document.getElementById('colorFromHypertension'));
	cColorFromHypertension.draw(view, {
			title: 'จำนวนของผู้ป่วยโรคความดันโลหิตสูงจำแนกตามระดับความรุนแรง',
			width: '100%',
			height: '100%',
			bar: {groupWidth: "70%"},
			legend: { position: "none" }
	});

	$('#print-header').html('แผนภูมิผู้ป่วยโรคความดันโลหิตสูง <small>('+$('#tab2-selection option:selected').text()+')</small>');
	prepareTable(['กลุ่มของอาการ', 'จำนวน(คน)'], $.map(colorFromHypertension, function(value, index) {
		return [[value.name, value.count]];
	}));
}

function drawColorStackFromHypertension(colorStackFromHypertension) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'หมู่บ้าน');
	data.addColumn('number', 'กลุ่มปกติ');
	data.addColumn({type: 'string', role: 'style'});
	data.addColumn('number', 'กลุ่มเสี่ยง');
	data.addColumn('number', 'กลุ่มผู้ป่วยระดับ 0');
	data.addColumn('number', 'กลุ่มผู้ป่วยระดับ 1');
	data.addColumn('number', 'กลุ่มผู้ป่วยระดับ 2');
	data.addColumn('number', 'กลุ่มผู้ป่วยระดับ 3');
	data.addColumn('number', 'กลุ่มผู้ป่วยมีโรคแทรกซ้อน');
	data.addRows(getValues(colorStackFromHypertension));
	cColorStackFromHypertensionVillage = new google.visualization.ColumnChart(document.getElementById('colorStackFromHypertension'));
	cColorStackFromHypertensionVillage.draw(data, {
			title: 'จำนวนของผู้ป่วยโรคความดันโลหิตสูงจำแนกตามระดับความรุนแรงและหมู่บ้าน',
			width: '100%',
			height: '100%',
			isStacked: true,
			colors: [
				'#FFFFFF',
				'#00FF00',
				'#007700',
				'#FFFF00',
				'#FF7F00',
				'#FF0000',
				'#000000'
			]
	});

	$('#print-header').html('แผนภูมิผู้ป่วยโรคความดันโลหิตสูง');
	prepareTable(['หมู่บ้าน', 'กลุ่มปกติ(คน)', 'กลุ่มเสี่ยง(คน)', 'กลุ่มผู้ป่วยระดับ 0(คน)', 'กลุ่มผู้ป่วยระดับ 1(คน)', 'กลุ่มผู้ป่วยระดับ 2(คน)', 
		'กลุ่มผู้ป่วยระดับ 3(คน)', 'กลุ่มผู้ป่วยมีโรคแทรกซ้อน(คน)'], $.map(colorStackFromHypertension, function(value, index) {
		return [[value.villname, value.level_0, value.level_1, value.level_2, value.level_3, value.level_4, value.level_5, value.level_6]];
	}));
}

function drawColorFromDiabetes(colorFromDiabetes) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'ระดับความรุนแรง');
	data.addColumn('number', 'จำนวน');
	data.addColumn({type: 'string', role: 'style'});
	data.addRows(getValues(colorFromDiabetes));
	var view = new google.visualization.DataView(data);
	view.setColumns(
		[
			0, 
			1,
			{ 
				calc: "stringify",
				sourceColumn: 1,
				type: "string",
				role: "annotation" 
			},
			2
		]
	);
	cColorFromDiabetes = new google.visualization.ColumnChart(document.getElementById('colorFromDiabetes'));
	cColorFromDiabetes.draw(view, {
		title: 'จำนวนของผู้ป่วยโรคเบาหวานจำแนกตามระดับความรุนแรง',
		width: '100%',
		height: '100%',
		bar: {
			groupWidth: '70%'
		},
		legend: {
			position: 'none'
		}
	});

	$('#print-header').html('แผนภูมิผู้ป่วยโรคเบาหวาน <small>('+$('#tab3-selection option:selected').text()+')</small>');
	prepareTable(['กลุ่มของอาการ', 'จำนวน(คน)'], $.map(colorFromDiabetes, function(value, index) {
		return [[value.name, value.count]];
	}));
}

function drawColorStackFromDiabetes(colorStackFromDiabetesVillage) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'หมู่บ้าน');
	data.addColumn('number', 'กลุ่มปกติ');
	data.addColumn({type: 'string', role: 'style'});
	data.addColumn('number', 'กลุ่มเสี่ยง');
	data.addColumn('number', 'กลุ่มผู้ป่วยระดับ 0');
	data.addColumn('number', 'กลุ่มผู้ป่วยระดับ 1');
	data.addColumn('number', 'กลุ่มผู้ป่วยระดับ 2');
	data.addColumn('number', 'กลุ่มผู้ป่วยระดับ 3');
	data.addColumn('number', 'กลุ่มผู้ป่วยมีโรคแทรกซ้อน');
	data.addRows(getValues(colorStackFromDiabetesVillage));
	cColorStackFromDiabetesVillage = new google.visualization.ColumnChart(document.getElementById('colorStackFromDiabetesVillage'));
	cColorStackFromDiabetesVillage.draw(data, {
			title: 'จำนวนของผู้ป่วยโรคเบาหวานจำแนกตามระดับความรุนแรงและหมู่บ้าน',
			width: '100%',
			height: '100%',
			isStacked: true,
			colors: [
				'#FFFFFF',
				'#00FF00',
				'#007700',
				'#FFFF00',
				'#FF7F00',
				'#FF0000',
				'#000000'
			]
	});

	$('#print-header').html('แผนภูมิผู้ป่วยโรคเบาหวาน');
	prepareTable(['หมู่บ้าน', 'กลุ่มปกติ(คน)', 'กลุ่มเสี่ยง(คน)', 'กลุ่มผู้ป่วยระดับ 0(คน)', 'กลุ่มผู้ป่วยระดับ 1(คน)', 'กลุ่มผู้ป่วยระดับ 2(คน)', 
		'กลุ่มผู้ป่วยระดับ 3(คน)', 'กลุ่มผู้ป่วยมีโรคแทรกซ้อน(คน)'], $.map(colorStackFromDiabetesVillage, function(value, index) {
		return [[value.villname, value.level_0, value.level_1, value.level_2, value.level_3, value.level_4, value.level_5, value.level_6]];
	}));
}

function callJSON(options, callback) {
	$.ajax({
		type: 'POST',
		url: './dist/php/analysis.php',
		data: options,
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data.response == 'success') {
				$('#modify_date').html(data.modify);
				callback(data.values);
			}
		}
	});
}

function getValues(v) {
	var rows = [];
	$.each(v, function(i, v) {
		var row = [];
		$.each(v, function(i, v) {
			row.push(v);
		});
		rows.push(row);
	});
	return rows;
}

function getNameVillage() {
	$.ajax({
		type: 'POST',
		url: './dist/php/analysis.php',
		data: {
			request: 'nameVillage'
		},
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data.response == 'success') {
				$('.name-village').empty();
				$('.name-village').append('<option value=-1>ทั้งหมด</option>');
				$.each(data.values, function(i, village) {
					$('.name-village').append('<option value="'+village.villcode+'">'+village.villname+'</option>');
				});
			}
		}
	});
}

function prepareTable(columns, rows) {
	var tr;
	$('#prepare_table thead tr').empty();
	$('#prepare_table tbody').empty();
	for (var i = 0, len = columns.length; i < len; i++) {
		$('#prepare_table thead tr').append('<th>'+columns[i]+'</th>');
	};
	for (var i = 0, len_i = rows.length; i < len_i; i++) {
		tr = $('<tr></tr>');
		for (var j = 0, len_j = rows[i].length; j < len_j; j++) {
			$(tr).append('<td class="text-'+(j !== 0 ? 'right' : 'left')+'">'+(j !== 0 ? number_format(rows[i][j]) : rows[i][j])+'</td>');
		}
		$('#prepare_table tbody').append(tr);
	};
}

function number_format(n, currency, fixed) {
	return (currency || '') + n.toFixed(fixed).replace(/./g, function(c, i, a) {
		return i > 0 && c !== "." && (a.length - i) % 3 === 0 ? "," + c : c;
	});
}
