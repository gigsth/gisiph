<?php
require 'is_ajax.php';
//if (!isAjax()) {return;}
/*$_POST['chronics']['hypertension'] = true;
$_POST['chronics']['diabetes']  = true;

$_POST['colors']['unseen']  = true;
$_POST['colors']['level_0'] = true;
$_POST['colors']['level_1'] = true;
$_POST['colors']['level_2'] = true;
$_POST['colors']['level_3'] = true;
$_POST['colors']['level_4'] = true;
$_POST['colors']['level_5'] = true;
$_POST['colors']['level_6'] = true;

$_POST['request'] = 'houses';*/

try {
	header('Content-Type: application/json; charset=UTF-8');

	require 'class.Maps.php';
	$maps = new Maps();

	switch($_POST['request'])
	{
		case 'houses':
			$chronics = array();
			if ($_POST['chronics']['hypertension'] == true) array_push($chronics, '01');
			if ($_POST['chronics']['diabetes'] == true) array_push($chronics, '10');

			$colors = array();
			if($_POST['colors']['unseen']  == true) array_push($colors, (int)-1);
			if($_POST['colors']['level_0'] == true) array_push($colors, (int)0);
			if($_POST['colors']['level_1'] == true) array_push($colors, (int)1);
			if($_POST['colors']['level_2'] == true) array_push($colors, (int)2);
			if($_POST['colors']['level_3'] == true) array_push($colors, (int)3);
			if($_POST['colors']['level_4'] == true) array_push($colors, (int)4);
			if($_POST['colors']['level_5'] == true) array_push($colors, (int)5);
			if($_POST['colors']['level_6'] == true) array_push($colors, (int)6);

			echo json_output(
				array(
					'response' => 'success',
					'values' => $maps->getHouses(
						$chronics,
						$colors
					)
				)
			);
			break;
			
		default: 
			throw new Exception('ขอบเขตการเรียกดูข้อมูลของคุณไม่ถูกต้อง');
			break;
	}

	unset($maps);
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