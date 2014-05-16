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
			console.log($('#tab2-selection').val());
			if($('#tab2-selection').val() === -1){
				callJSON({request: 'colorFromHypertension'}, drawColorFromHypertension);
			}else{
				callJSON({request: 'colorFromHypertensionVillage', selection: $('#tab2-selection').val()},
					drawColorFromHypertensionVillage);
			};
		}else if (tab_id === 'tab3') {
			console.log($('#tab3-selection').val());
			if($('#tab2-selection').val() === -1){
				callJSON({request: 'colorFromDiabetes'}, drawColorFromDiabetes);
			}else{
				callJSON({request: 'colorFromDiabetesVillage', selection: $('#tab3-selection').val()},
					drawColorFromDiabetesVillage);
			};
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
}

function drawVillage(village) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'หมู่บ้าน');
	data.addColumn('number', 'เบาหวาน');
	data.addColumn('number', 'ความดันโลหิตสูง');
	data.addRows(getValues(village));
	cVillage = new google.visualization.ColumnChart(document.getElementById('village'));
	cVillage.draw(data, {
			title: 'สัดส่วนของผู้ป่วยโรคเรื้อรังในแต่ละหมู่บ้าน',
			width: '100%',
			height: '100%'
	  });
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

}

function drawColorFromHypertensionVillage(colorFromHypertensionVillage) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'ระดับความรุนแรง');
	data.addColumn('number', 'จำนวน');
	data.addColumn({type: 'string', role: 'style'});
	data.addRows(getValues(colorFromHypertensionVillage));
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
	cColorFromHypertensionVillage = new google.visualization.ColumnChart(document.getElementById('colorFromHypertensionVillage'));
	cColorFromHypertensionVillage.draw(view, {
			title: 'graph test',
			width: '100%',
			height: '100%',
			bar: {groupWidth: "70%"},
			legend: { position: "none" }
	  });

}

function drawColorFromDiabetes(colorFromDiabetes) {//console.log(colorFromDiabetes);
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
}

function drawColorFromDiabetesVillage(colorFromDiabetesVillage) {//console.log(colorFromDiabetes);
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'ระดับความรุนแรง');
	data.addColumn('number', 'จำนวน');
	data.addColumn({type: 'string', role: 'style'});
	data.addRows(getValues(colorFromDiabetesVillage));
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
	cColorFromDiabetesVillage = new google.visualization.ColumnChart(document.getElementById('colorFromDiabetesVillage'));
	cColorFromDiabetesVillage.draw(view, {
			title: 'testGraphDiabetesVillage',
			width: '100%',
			height: '100%',
			bar: {
				groupWidth: '70%'
			},
			legend: {
				position: 'none'
			}
	  });
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
