<?php
require 'is_ajax.php';
//if (!isAjax()) {return;}

//$_POST['request'] = 'nameVillage';

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
					'values' => $analysis->getColorFromHypertension()
				)
			);
			break;
		case 'colorFromHypertensionVillage':
			echo json_output(
				array(
					'response' => 'success',
					'values' => $analysis->getColorFromHypertensionVillage()
				)
			);
			break;
		case 'colorFromDiabetesVillage':
			echo json_output(
				array(
					'response' => 'success',
					'values' => $analysis->getColorFromDiabetesVillage()
				)
			);
			break;			
		case 'colorFromDiabetes':
			echo json_output(
				array(
					'response' => 'success',
					'values' => $analysis->getColorFromDiabetes()
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