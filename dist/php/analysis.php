<?php
require 'is_ajax.php';
//if (!isAjax()) {return;}

// $_POST['request'] = 'village';
// $_POST['selection'] = 25060600;

try {
	header('Content-Type: application/json; charset=UTF-8');

	require 'class.Analysis.php';
	$analysis = new Analysis();

	switch($_POST['request'])
	{
		case 'chronics':
			echo json_output(
				array(
					'response' => 'success',
					'values' => $analysis->getChronics()
				)
			);
			break;
		case 'village':
			echo json_output(
				array(
					'response' => 'success',
					'values' => $analysis->getVillage()
				)
			);
			break;
		case 'colorFromHypertension':
			echo json_output(
				array(
					'response' => 'success',
					'values' => (isset($_POST['selection']) && $_POST['selection'] != '-1') ? 
						$analysis->getColorFromHypertensionVillage($_POST['selection']) : 
						$analysis->getColorFromHypertension()
				)
			);
			break;
		case 'colorFromDiabetes':
			echo json_output(
				array(
					'response' => 'success',
					'values' => (isset($_POST['selection']) && $_POST['selection'] != '-1') ? 
						$analysis->getColorFromDiabetesVillage($_POST['selection']) : 
						$analysis->getColorFromDiabetes()
				)
			);
			break;
		case 'discover':
			echo json_output(
				array(
					'response' => 'success',
					'values' => $analysis->getDiscover()
				)
			);
			break;
		case 'nameVillage':
			echo json_output(
				array(
					'response' => 'success',
					'values' => $analysis->getNameVillage()
				)
			);
			break;
		default: 
			throw new Exception('ขอบเขตการเรียกดูข้อมูลของคุณไม่ถูกต้อง');
			break;
	}
}
catch(Exception $e) {
	echo json_output(
		array(
			'response' => 'error',
			'values' => $e->getMessage()
		)
	);
}
?>