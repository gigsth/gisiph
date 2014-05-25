google.load('visualization', '1', {packages: ["corechart"]});

var cDiscover, cChronics, cVillage, cColorFromHypertension, cColorFromDiabetes,
 	cColorFromHypertensionVillage, cColorFromDiabetesVillage;

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
			callJSON({request: 'colorFromHypertension', selection: $('#tab2-selection').val()}, drawColorFromHypertension);
		}else if (tab_id === 'tab3') {
			callJSON({request: 'colorFromDiabetes', selection: $('#tab3-selection').val()}, drawColorFromDiabetes);
		};
	}).promise().done(function() {
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
	prepareTable(['หมู่บ้าน', 'โรคเบาหวาน(คน)', 'โรคความดันโลหิตสูง(คน)', 'โรคเบาหวานและความดันโลหิตสูง(คน)'], $.map(village, function(value, index) {
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
			title: 'จำนวนผู้ป่วยโรคเรื้อรังในแต่ละปี',
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

function callJSON(options, callback) {
	$.ajax({
		type: 'POST',
		url: './dist/php/analysis.php',
		data: options,
		contentType: "application/x-www-form-urlencoded;charset=utf-8",
		success: function(data) {
			if (data.response == 'success') {
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
			$(tr).append('<td>'+rows[i][j]+'</td>');
		}
		$('#prepare_table tbody').append(tr);
	};
}
