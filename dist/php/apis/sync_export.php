<?php
try {
	$callback = isset($_GET['callback']) ? preg_replace('[^a-zA-Z0-9$_.]', '', $_GET['callback']) : false;
	header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
	header('Access-Control-Allow-Origin: *');
	require_once 'class.Database.php';

	function array_column($array, $column)
	{
		$ret = array();
		foreach ($array as $row) $ret[] = $row[$column];
		return $ret;
	}

	$update_data = json_decode(stripslashes($_POST['update']), true);
	$return = array();

	switch($_POST['request'])
	{
		case 'gisiph_gps_house':
			foreach ($update_data as $key => $value) {
				Database::getConnection()->query(
					"
					INSERT INTO
						`jhcisdb`.`gisiph_gps_house`
							(
								`gisiph_gps_house`.`hcode`,
								`gisiph_gps_house`.`latitude`,
								`gisiph_gps_house`.`longitude`,
								`gisiph_gps_house`.`uedit`,
								`gisiph_gps_house`.`status`,
								`gisiph_gps_house`.`timestamp`
							) 
					VALUES
						(
							%n[HOUSE_ID],
							%n[LATTITUDE],
							%n[LONGITUDE],
							%s[UEDIT],
							%s[STATUS],
							%s[TIMESTAMP]
						) 
					ON DUPLICATE KEY 
						UPDATE
							`gisiph_gps_house`.`latitude` = VALUES(`latitude`),
							`gisiph_gps_house`.`longitude` = VALUES(`longitude`),
							`gisiph_gps_house`.`uedit` = VALUES(`uedit`),
							`gisiph_gps_house`.`status` = VALUES(`status`),
							`gisiph_gps_house`.`timestamp` = VALUES(`timestamp`)
					",
					array(
						'HOUSE_ID' => $value['house_id'],
						'LATTITUDE' => $value['lattitude'],
						'LONGITUDE' => $value['longitude'],
						'UEDIT' => $value['uedit'],
						'STATUS' => $value['status'],
						'TIMESTAMP' => $value['timestamp']
					)
				);

			}

			$return = array_column($update_data, 'house_id');
		break;

		case 'gisiph_photo_house':
			foreach ($update_data as $key => $value) {
				if (empty($value['ref_id'])) {
					usleep(1);
					$filename = md5(microtime()). '.jpg';
					$path = './uploads/';
					$real_path = '../../.'.$path;
					if(!(file_exists($real_path))) {
						mkdir($real_path, 0777);
					}
					$real_path .= $filename;
					$src = substr($value['src'], 1 + strrpos($value['src'], ','));
					$result = Database::getConnection()->query(
						"
						INSERT INTO
							`jhcisdb`.`gisiph_photo_house`
								(
									`gisiph_photo_house`.`hcode`,
									`gisiph_photo_house`.`path`,
									`gisiph_photo_house`.`uedit`,
									`gisiph_photo_house`.`status`,
									`gisiph_photo_house`.`timestamp`
								)
						VALUES
							(
								%n[HOUSE_ID],
								%s[PATH],
								%s[UEDIT],
								%s[STATUS],
								%s[TIMESTAMP]
							)
						",
						array(
							'HOUSE_ID' => $value['house_id'],
							'PATH' => $path,
							'UEDIT' => $value['uedit'],
							'STATUS' => $value['status'],
							'TIMESTAMP' => $value['timestamp']
						)
					);

					if ($result) {
						// Output Buffering
						ob_start();
						header('Content-Type: image/jpeg');
						file_put_contents($real_path, base64_decode($src));
						ob_end_clean();
					}

				} else {
					Database::getConnection()->query(
						"
						UPDATE
							`jhcisdb`.`gisiph_photo_house`
						SET
							`gisiph_photo_house`.`uedit` = %s[UEDIT],
							`gisiph_photo_house`.`status` = %s[STATUS],
							`gisiph_photo_house`.`timestamp` = %s[TIMESTAMP]
						WHERE
							`gisiph_photo_house`.`phcode` = %n[PHOTO_ID]
						",
						array(
							'UEDIT' => $value['uedit'],
							'STATUS' => $value['status'],
							'TIMESTAMP' => $value['timestamp'],
							'PHOTO_ID' => $value['ref_id']
						)
					);
					
				}
			}

			$return = array_column($update_data, 'photo_id');
		break;

		case 'gisiph_photo_pchronic':
			foreach ($update_data as $key => $value) {
				if (empty($value['ref_id'])) {
					usleep(1);
					$filename = md5(microtime()). '.jpg';
					$path = './uploads/';
					$real_path = '../../.'.$path;
					if(!(file_exists($real_path))) {
						mkdir($real_path, 0777);
					}
					$real_path .= $filename;
					$src = substr($value['src'], 1 + strrpos($value['src'], ','));
					$result = Database::getConnection()->query(
						"
						INSERT INTO
							`jhcisdb`.`gisiph_photo_pchronic`
								(
									`gisiph_photo_pchronic`.`pid`,
									`gisiph_photo_pchronic`.`chroniccode`,
									`gisiph_photo_pchronic`.`path`,
									`gisiph_photo_pchronic`.`uedit`,
									`gisiph_photo_pchronic`.`status`,
									`gisiph_photo_pchronic`.`timestamp`
								)
						VALUES
							(
								%n[PERSON_ID],
								%s[CHRONICCODE],
								%s[PATH],
								%s[UEDIT],
								%s[STATUS],
								%s[TIMESTAMP]
							)
						",
						array(
							'PERSON_ID' => $value['person_id'],
							'CHRONICCODE' => $value['chroniccode'],
							'PATH' => $path,
							'UEDIT' => $value['uedit'],
							'STATUS' => $value['status'],
							'TIMESTAMP' => $value['timestamp']
						)
					);

					if ($result) {
						// Output Buffering
						ob_start();
						header('Content-Type: image/jpeg');
						file_put_contents($real_path, base64_decode($src));
						ob_end_clean();
					}

				} else {
					Database::getConnection()->query(
						"
						UPDATE
							`jhcisdb`.`gisiph_photo_pchronic`
						SET
							`gisiph_photo_pchronic`.`uedit` = %s[UEDIT],
							`gisiph_photo_pchronic`.`status` = %s[STATUS],
							`gisiph_photo_pchronic`.`timestamp` = %s[TIMESTAMP]
						WHERE
							`gisiph_photo_pchronic`.`pccode` = %n[PHOTO_ID]
						",
						array(
							'UEDIT' => $value['uedit'],
							'STATUS' => $value['status'],
							'TIMESTAMP' => $value['timestamp'],
							'PHOTO_ID' => $value['ref_id']
						)
					);
					
				}
			}

			$return = array_column($update_data, 'photo_id');
		break;
	}

	$response = array(
		'prop' => 'success',
		'data' => $return,
		'format' => ltrim(str_repeat(', ?', count($return)), ', ')
	);

	// Output the end result
	echo ($callback ? $callback . '(' : '') . json_encode($response) . ($callback ? ')' : '');

} catch(Exception $e) {
	echo json_encode(
		array(
			'prop' => 'fail',
			'data' => $e->getMessage() //'Empty data..'
		)
	);
}
?>