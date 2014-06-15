<?php
try {
	$callback = isset($_GET['callback']) ? preg_replace('[^a-zA-Z0-9$_.]', '', $_GET['callback']) : false;
	header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
	header('Access-Control-Allow-Origin: *');
	require_once 'class.Database.php';
// $_POST['villcodes'] = 25060601;
	$_VILLCODES_ = split(',', $_POST['villcodes']);

	// $starttime = microtime(true);
	$villages = Database::getConnection()->queryAndFetchAll(
		"
			SELECT
				`village`.`villcode`,
				`village`.`villname`
			FROM
				`jhcisdb`.`village`
			WHERE
				`village`.`villcode` IN (%n[VILLCODES])
		",
		array(
			'VILLCODES' => $_VILLCODES_
		)
	);

	$houses = Database::getConnection()->queryAndFetchAll(
		"
		SELECT
			`houses`.`hcode` AS `house_id`,
			`houses`.`villcode`,
			CONCAT_WS(
				'  ',
				`houses`.`hno`,
				CONCAT('หมู่', CAST(`village`.`villno` AS CHAR)),
				CONCAT('บ้าน', `village`.`villname`),
				CONCAT('ต.', `place`.`subdistname`),
				CONCAT('อ.', `place`.`distname`),
				CONCAT('จ.', `place`.`provname`)
			) AS `address`,
			`gps`.`latitude`,
			`gps`.`longitude`,
			`gps`.`uedit`,
			`gps`.`status`,
			DATE_FORMAT(DATE_ADD(`gps`.`timestamp`, INTERVAL 543 YEAR), '%d/%m/%Y %T') AS `timestamp`
		FROM
			(
				SELECT	
					CONCAT(
						`cprovince`.`provcode`,
						`cdistrict`.`distcode`,
						`csubdistrict`.`subdistcode`
					) AS `place_code`,
					`csubdistrict`.`subdistname`,
					`cdistrict`.`distname`,
					`cprovince`.`provname`
				FROM
					`jhcisdb`.`csubdistrict`,
					`jhcisdb`.`cdistrict`,
					`jhcisdb`.`cprovince`
				WHERE
					`csubdistrict`.`distcode` = `cdistrict`.`distcode` AND
					`cdistrict`.`provcode` = `cprovince`.`provcode` AND
					`csubdistrict`.`provcode` = `cprovince`.`provcode`
			) AS `place`,
			`jhcisdb`.`village`,
			(
				SELECT
					`house`.`hcode`,
					`house`.`hno`,
					`house`.`villcode`,
					SUBSTRING(`house`.`villcode`, 1, 6) AS `place_code`
				FROM
					`jhcisdb`.`house`
				WHERE
					`house`.`villcode` IN (%n[VILLCODES])
			) AS `houses`
		LEFT JOIN
			(
				SELECT
					`gisiph_gps_house`.`hcode`,
					`gisiph_gps_house`.`latitude`,
					`gisiph_gps_house`.`longitude`,
					`gisiph_gps_house`.`uedit`,
					`gisiph_gps_house`.`status`,
					`gisiph_gps_house`.`timestamp`
				FROM
					`jhcisdb`.`gisiph_gps_house`
				WHERE
					`gisiph_gps_house`.`status` <> 'DELETE'
			) AS `gps`
		ON
			`houses`.`hcode` = `gps`.`hcode`
		WHERE
			`houses`.`place_code` = `place`.`place_code` AND
			`houses`.`villcode` = `village`.`villcode`
		",
		array(
			'VILLCODES' => $_VILLCODES_
		)
	);

	$houses_id = array();
	foreach ($houses as $key => $value) {
		$houses[$key]['house_id'] = (int)$value['house_id'];
		$houses[$key]['latitude'] = (double)$value['latitude'];
		$houses[$key]['longitude'] = (double)$value['longitude'];
		$houses_id[] = $value['house_id'];
	}

	$photos_house = Database::getConnection()->queryAndFetchAll(
		"
		SELECT
			`gisiph_photo_house`.`phcode` AS `photo_id`,
			`gisiph_photo_house`.`hcode` AS `house_id`,
			`gisiph_photo_house`.`path` AS `src`,
			`gisiph_photo_house`.`uedit`,
			`gisiph_photo_house`.`status`,
			DATE_FORMAT(DATE_ADD(`gisiph_photo_house`.`timestamp`, INTERVAL 543 YEAR), '%d/%m/%Y %T') AS `timestamp`
		FROM
			`jhcisdb`.`gisiph_photo_house`
		WHERE
			`gisiph_photo_house`.`status` <> 'DELETE'
		"
	);

	$path = 'http://'.$_SERVER['HTTP_HOST'].'/gisiph/';
	foreach ($photos_house as $key => $value) {
		$image = $path.substr($value['src'], 2);
		if(!file_exists('../../.'.$value['src'])){
			$photos_house[$key]['photo_id'] = (int)$value['photo_id'];
			$photos_house[$key]['house_id'] = (int)$value['house_id'];
			$photos_house[$key]['src'] = 'static/images/connection_fail.png';
			continue;
		}
		$imageData = base64_encode(file_get_contents($image));
		$src = 'data: image/jpeg;base64,' . $imageData;
		$width = 300;

		// Loading the image and getting the original dimensions
		$image = imagecreatefromjpeg($src);
		$orig_width = imagesx($image);
		$orig_height = imagesy($image);

		// Calc the new height
		$height = (($orig_height * $width) / $orig_width);

		// Create new image to display
		$new_image = imagecreatetruecolor($width, $height);

		// Create new image with change dimensions
		imagecopyresized($new_image, $image,
			0, 0, 0, 0,
			$width, $height,
			$orig_width, $orig_height);

		// Output Buffering
		ob_start();
		imagejpeg($new_image);
		$data = ob_get_contents();
		ob_end_clean();

		$photos_house[$key]['photo_id'] = (int)$value['photo_id'];
		$photos_house[$key]['house_id'] = (int)$value['house_id'];
		$photos_house[$key]['src'] = 'data: image/jpeg;base64,' . base64_encode($data);
	}

	$persons = Database::getConnection()->queryAndFetchAll(
		"
		SELECT
			`person`.`pid` AS `person_id`,
			`person`.`hcode` AS `house_id`,
			CONCAT(
				`ctitle`.`titlenamelong`,
				`person`.`fname`,
				' ',
				`person`.`lname`
			) AS `fullname`,
			TIMESTAMPDIFF(YEAR, `person`.`birth`, CURRENT_DATE) AS `age`,
			CONCAT(
				DATE_FORMAT(`person`.`birth`, '%e '),
				CASE DATE_FORMAT(`person`.`birth`, '%c')
					WHEN '1' THEN 'มกราคม'
					WHEN '2' THEN 'กุมภาพันธ์'
					WHEN '3' THEN 'มีนาคม'
					WHEN '4' THEN 'เมษายน'
					WHEN '5' THEN 'พฤษภาคม'
					WHEN '6' THEN 'มิถุนายน'
					WHEN '7' THEN 'กรกฏาคม'
					WHEN '8' THEN 'สิงหาคม'
					WHEN '9' THEN 'กันยายน'
					WHEN '10' THEN 'ตุลาคม'
					WHEN '11' THEN 'พฤศจิกายน'
					WHEN '12' THEN 'ธันวาคม'
				END,
				' ',
				CAST(DATE_FORMAT(`person`.`birth`, '%Y') + '543' AS CHAR)
			) AS `birth`,
			CASE `person`.`sex`
				WHEN '1' THEN 'ชาย'
				WHEN '2' THEN 'หญิง'
			END AS `sex`,
			CONCAT_WS(
				'-',
				SUBSTRING(`person`.`idcard`, '1', '1'),
				SUBSTRING(`person`.`idcard`, '2', '4'),
				SUBSTRING(`person`.`idcard`, '6', '5'),
				SUBSTRING(`person`.`idcard`, '11', '2'),
				SUBSTRING(`person`.`idcard`, '12', '1')
			) AS `idcard`,
			`ceducation`.`educationname` AS `educate`,
			`coccupa`.`occupaname` AS `occupa`,
			`cnation`.`nationname` AS `nation`,
			`corigin`.`nationname` AS `origin`
		FROM
			`jhcisdb`.`person`,
			`jhcisdb`.`ctitle`,
			`jhcisdb`.`ceducation`,
			`jhcisdb`.`coccupa`,
			`jhcisdb`.`cnation`,
			`jhcisdb`.`cnation` AS `corigin`
		WHERE
			`person`.`hcode` IN (%n[HOUSES_ID]) AND
			`person`.`prename` = `ctitle`.`titlecode` AND
			`person`.`educate` = `ceducation`.`educationcode` AND
			`person`.`occupa` = `coccupa`.`occupacode` AND
			`person`.`nation` = `cnation`.`nationcode` AND
			`person`.`origin` = `corigin`.`nationcode` AND
			(YEAR(CURRENT_DATE) + 543) - (YEAR(`person`.`birth`) + 543) BETWEEN 15 AND 65
		",
		array(
			'HOUSES_ID' => $houses_id
		)
	);

	$persons_id = array();
	foreach ($persons as $key => $value) {
		$persons_id[] = $value['person_id'];
	}

	$chronics = Database::getConnection()->queryAndFetchAll(
		"
		SELECT
			`personchronic`.`pid` AS `person_id`,
			CASE `cdiseasechronic`.`groupcode`
				WHEN '01' THEN 'hypertension'
				WHEN '10' THEN 'diabetes'
			END AS `disease`,
			`cdisease`.`diseasenamethai` AS `detail`,
			`personchronic`.`chroniccode`,
			DATE_FORMAT(DATE_ADD(`personchronic`.`datefirstdiag`, INTERVAL 543 YEAR), '%d/%m/%Y') AS `datefirstdiag`
		FROM
			`jhcisdb`.`personchronic`,
			`jhcisdb`.`cdisease`,
			`jhcisdb`.`cdiseasechronic`
		WHERE
			`personchronic`.`pid` IN (%n[PERSONS_ID]) AND
			`personchronic`.`chroniccode` = `cdisease`.`diseasecode` AND
			`cdisease`.`codechronic` = `cdiseasechronic`.`groupcode` AND
			`cdiseasechronic`.`groupcode` IN ('01', '10')
		",
		array(
			'PERSONS_ID' => $persons_id
		)
	);

	$photos_chronic = Database::getConnection()->queryAndFetchAll(
		"
		SELECT
			`gisiph_photo_pchronic`.`pccode` AS `photo_id`,
			`gisiph_photo_pchronic`.`pid` AS `person_id`,
			`gisiph_photo_pchronic`.`chroniccode`,
			`gisiph_photo_pchronic`.`path` AS `src`,
			`gisiph_photo_pchronic`.`uedit`,
			`gisiph_photo_pchronic`.`status`,
			DATE_FORMAT(DATE_ADD(`gisiph_photo_pchronic`.`timestamp`, INTERVAL 543 YEAR), '%d/%m/%Y %T') AS `timestamp`
		FROM
			`jhcisdb`.`gisiph_photo_pchronic`
		WHERE
			`gisiph_photo_pchronic`.`status` <> 'DELETE'
		"
	);

	$path = 'http://'.$_SERVER['HTTP_HOST'].'/gisiph/';
	foreach ($photos_chronic as $key => $value) {
		$image = $path.substr($value['src'], 2);
		if(!file_exists('../../.'.$value['src'])) continue;
		$imageData = base64_encode(file_get_contents($image));
		$src = 'data: image/jpeg;base64,' . $imageData;
		$width = 300;

		// Loading the image and getting the original dimensions
		$image = imagecreatefromjpeg($src);
		$orig_width = imagesx($image);
		$orig_height = imagesy($image);

		// Calc the new height
		$height = (($orig_height * $width) / $orig_width);

		// Create new image to display
		$new_image = imagecreatetruecolor($width, $height);

		// Create new image with change dimensions
		imagecopyresized($new_image, $image,
			0, 0, 0, 0,
			$width, $height,
			$orig_width, $orig_height);

		// Output Buffering
		ob_start();
		imagejpeg($new_image);
		$data = ob_get_contents();
		ob_end_clean();

		$photos_chronic[$key]['photo_id'] = (int)$value['photo_id'];
		$photos_chronic[$key]['person_id'] = (int)$value['person_id'];
		$photos_chronic[$key]['src'] = 'data: image/jpeg;base64,' . base64_encode($data);
	}

	$visited = Database::getConnection()->queryAndFetchAll(
		"
		SELECT
			`pressure`.`pid` AS `person_id`,
			`pressure`.`last_pressure`,
			`sugarblood`.`last_sugarblood`,
			`pressure`.`visitdate`,
			`incurrents`.`incurrent`
		FROM
			
			(
				SELECT
					`visit`.`pid`,
					`visitlabsugarblood`.`sugarnumdigit` AS `last_sugarblood`
				FROM
					`jhcisdb`.`visit`,
					`jhcisdb`.`visitlabsugarblood`
				WHERE
					`visit`.`pid` IN (%n[PERSONS_ID]) AND
					`visit`.`visitno` = `visitlabsugarblood`.`visitno` AND
					`visitlabsugarblood`.`sugarnumdigit` IS NOT NULL
				ORDER BY
					`visit`.`pid`,
					`visit`.`visitdate` DESC
			) AS `sugarblood`,
			(
				SELECT
					`visit`.`pid`,
					`visit`.`pressure` AS `last_pressure`,
					DATE_FORMAT(DATE_ADD(`visit`.`visitdate`, INTERVAL 543 YEAR), '%d/%m/%Y') AS `visitdate`
				FROM
					`jhcisdb`.`visit`
				WHERE
					`visit`.`pid` IN (%n[PERSONS_ID]) AND
					`visit`.`pressure` IS NOT NULL
				ORDER BY
					`visit`.`pid`,
					`visit`.`visitdate` DESC
			) AS `pressure`
		LEFT JOIN
			(
				SELECT
					`personchronic`.`pid`,
					CASE WHEN `personchronic`.`pid` THEN TRUE ELSE FALSE END AS `incurrent`
				FROM
					`jhcisdb`.`personchronic`,
					`jhcisdb`.`cdisease`,
					`jhcisdb`.`cdiseasechronic`
				WHERE
					`personchronic`.`pid` IN (%n[PERSONS_ID]) AND
					`personchronic`.`chroniccode` = `cdisease`.`diseasecode` AND
					`cdisease`.`codechronic` = `cdiseasechronic`.`groupcode` AND
					`cdiseasechronic`.`groupcode` NOT IN ('01', '10')
				GROUP BY
					`personchronic`.`pid`
			) AS `incurrents`
		ON `pressure`.`pid` = `incurrents`.`pid`
		WHERE
			`pressure`.`pid` = `sugarblood`.`pid`
		GROUP BY
			`pressure`.`pid`
		",
		array(
			'PERSONS_ID' => $persons_id
		)
	);
	/*$endtime = microtime(true);
	$duration = $endtime - $starttime;*/
	$response = array(
		'prop' => !empty($houses) ? 'success' : 'fail',
		'data' => array(
			'villages' => $villages,
			'houses' => $houses,
			'photos_house' => $photos_house,
			'persons' => $persons,
			'chronics' => $chronics,
			'photos_chronic' => $photos_chronic,
			'visited' => $visited/*,
			'query_time' => $duration*/
		)
	);

	// Output the end result
	echo ($callback ? $callback . '(' : '') . json_encode($response) . ($callback ? ')' : '');

} catch(Exception $e) {
	echo json_encode(
		array(
			'prop' => 'fail',
			'data' => 'Empty data..'.$e->getMessage()
		)
	);
}
?>