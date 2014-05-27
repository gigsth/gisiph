<?php
require 'is_ajax.php';
if (!isAjax()) {return;}

try {
	header('Content-Type: application/json; charset=UTF-8');

	$myclass = array(
		'Home',
		'Patient'
	);

	if (!in_array($_POST['menu'], $myclass)) {
		throw new Exception('ไม่มีเมนูที่คุณเรียกใช้');
	}

	$classname = 'Manage_'.$_POST['menu'];
	require 'class.'.$classname.'.php';
	$manage = new $classname();

	// Get current page
	if ($_POST['scope'] === 'Datatable') {
		if (!$q = $manage->getCurrentPage($_POST['search'], $_POST['page'])) {
			throw new Exception('ไม่สามารถเรียกดูข้อมูลได้');
		}
		echo json_encode(
			array(
				'event' => 'Success',
				'totalPage' => $q['totalPage'],
				'currentPage' => $_POST['page'],
				'data' => $q['data']
			)
		);
	}
	// Insert or Update row
	else if ($_POST['scope'] === 'Save') {
		if (!$manage->setGpsHouse($_POST['id'], $_POST['latitude'], $_POST['longitude'])) {
			throw new Exception('ไม่สามารถบันทึกพิกัดได้');
		}
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => array('id' => $_POST['id'], 'latitude' => $_POST['latitude'], 'longitude' => $_POST['longitude'])
			)
		);
	}
	// Delete or Update row
	else if ($_POST['scope'] === 'Delete') {
		if (!$manage->delGpsHouse($_POST['id'])) {
			throw new Exception('ไม่สามารถลบพิกัดได้');
		}
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => array('id' => $_POST['id'], 'latitude' => '', 'longitude' => '')
			)
		);
	}
	// Select Person
	else if ($_POST['scope'] === 'Person') {
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => $manage->getPerson($_POST['hcode'])
			)
		);
	}
	// Select Photo
	else if ($_POST['scope'] === 'SelPhoto') {
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => $manage->selPhoto($_POST['hcode'], $_POST['ccode'])
			)
		);
	}
	// Insert Photo
	else if ($_POST['scope'] === 'AddPhoto') {
		require_once('class.Image_Manipulator.php');
		$data = array();
		foreach ($_FILES as $fname => $file) {
			$validExtensions = array('.jpg', '.jpeg');
			$fileExtension = strrchr($file['name'], ".");
			if (in_array(strtolower($fileExtension), $validExtensions)) {
				usleep(1);
				$newName = md5(microtime()). '.jpg';
				$lastInsert = $manage->addPhoto($_POST['hcode'], $newName, $_POST['ccode']);
				if (!$lastInsert) {
					throw new Exception('ไม่สามารถเพิ่มรูปได้');
				}
				$manipulator = new ImageManipulator($file['tmp_name']);
				$dir = '../../uploads/';
					if(!(file_exists($dir))) {
					mkdir($dir, 0777);
				}
				$manipulator->save($dir . $newName);
				array_push(
					$data,
					array(
						'key' => $lastInsert,
						'file' => './uploads/' . $newName
					)
				);
			}
			
		}
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => $data
			)
		);
		
	}
	// Delete Photo
	else if ($_POST['scope'] === 'DelPhoto') {
		if (!$q = $manage->delPhoto($_POST['phcode'])) {
			throw new Exception('ไม่สามารถลบรูปภาพได้');
		}
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => $q
			)
		);
	}
	// Get patient in house (On model open)
	else if ($_POST['scope'] === 'Chronic') {
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => $manage->getChronic($_POST['pid'])
			)
		);
		
	}
	// Get detail of 'personchronic' table (On model open)
	else if ($_POST['scope'] === 'Visit') {
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => $manage->getVisit($_POST['pid'])
			)
		);
		
	}
	// Get detail of 'personchronic' table (On model open)
	else if ($_POST['scope'] === 'Sugar') {
		echo json_encode(
			array(
				'event' => 'Success',
				'data' => $manage->getSugar($_POST['visitno'])
			)
		);
		
	}
	// Invalid $_POST['scope']
	else {
		throw new Exception('ขอบเขตการเรียกดูข้อมูลของคุณไม่ถูกต้อง');
	}

	unset($manage);
}
catch(Exception $e) {
	echo json_encode(array('event' => 'Error', 'message' => $e->getMessage()));
}
?>