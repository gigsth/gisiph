<?php
session_start();
require 'class.User_Authorization.php';
require 'configure.database.php';

class Maps
{
	private $mysql;

	public function __construct()
	{
		$this->mysql = new MySQL_Connection();
		$this->mysql->connect(HOSTNAME, USERNAME, PASSWORD, DBNAME);
		$this->mysql->charset = 'utf8';
	}

	public function __destruct() {
		$this->mysql->close();
	}

	public function getHouses($chronics, $colors)
	{
		if(empty($chronics) || empty($colors)) return array();

		$houses = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`gisiph_gps_house`.`hcode` AS `house_id`,
				CONCAT_WS(
					'  ',
					`house`.`hno`,
					CONCAT('หมู่', CAST(`village`.`villno` AS CHAR)),
					CONCAT('บ้าน', `village`.`villname`),
					CONCAT('ต.', `place`.`subdistname`),
					CONCAT('อ.', `place`.`distname`),
					CONCAT('จ.', `place`.`provname`)
				) AS `address`,
				`gisiph_gps_house`.`latitude`,
				`gisiph_gps_house`.`longitude`
			FROM
				`jhcisdb`.`gisiph_gps_house`,
				`jhcisdb`.`house`,
				`jhcisdb`.`village`,
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
				) AS `place`
			WHERE
				`gisiph_gps_house`.`status` <> 'DELETE' AND
				`gisiph_gps_house`.`hcode` <> 0 AND
				`village`.`villcode` <> 0 AND
				`gisiph_gps_house`.`hcode` = `house`.`hcode` AND
				`house`.`villcode` = `village`.`villcode` AND
				SUBSTRING(`village`.`villcode`, 1, 6) = `place`.`place_code`
			"
		);

		foreach ($houses as $ind => &$house)
		{
			$house['latitude'] = (double)$house['latitude'];
			$house['longitude'] = (double)$house['longitude'];

			$house['persons'] = $this->getPersons($house['house_id']);
			$house['color_level'] = (int)-1;
			$house['photo'] = $this->getPhotoHouses($house['house_id']);

			foreach ($house['persons'] as $key => &$person)
			{
				if (empty($person['chronics']['diabetes']) && empty($person['chronics']['hypertension']))
				{
					if ( ((empty($person['last_pressure']['systolic']) || empty($person['last_pressure']['diastolic'])) && in_array('01', $chronics))
					   || (empty($person['last_sugarblood']) && in_array('10', $chronics)) ) {
						$person['color_level'] = (int)-1;
					}
					elseif (in_array('10', $chronics)) // diabetes
					{
						$person['color_level'] = $this->calcColor($person['last_sugarblood'], FALSE);
					}
					elseif (in_array('01', $chronics)) // hypertension
					{
						$person['color_level'] = $this->calcColor($person['last_pressure'], FALSE);
					}
				}
				else
				{
					if ($person['incurrent']) {
						$person['color_level'] = (int)6;
					}
					elseif (!empty($person['chronics']['diabetes']) && in_array('10', $chronics)) // diabetes
					{
						$person['color_level'] = $this->calcColor($person['last_sugarblood']);
					}
					elseif (!empty($person['chronics']['hypertension']) && in_array('01', $chronics)) // hypertension
					{
						$person['color_level'] = $this->calcColor($person['last_pressure']);
					}
				}

				// Fillter Colors
				if (!in_array($person['color_level'], $colors)) {
					unset($house['persons'][$key]);
					continue;
				}

				if ($person['color_level'] > $house['color_level'])
				{
					$house['color_level'] = $person['color_level'];
				}
			}
			$house['persons'] = array_values($house['persons']);

			if (empty($houses[$ind]['persons'])) {
				unset($houses[$ind]);
			}
		}
		$houses = array_values($houses);

		return $houses;
	}

	public function getPersons($house_id)
	{
		$persons = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`person`.`pid` AS `person_id`,
				CONCAT(
					`ctitle`.`titlenamelong`,
					`person`.`fname`,
					' ',
					`person`.`lname`
				) AS `name`,
				TIMESTAMPDIFF(YEAR, `person`.`birth`, CURRENT_DATE)  AS `age`,
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
				`ceducation`.`educationname` AS `education`,
				`cnation`.`nationname` AS `nation`,
				`corigin`.`nationname` AS `origin`
			FROM
				`jhcisdb`.`person`,
				`jhcisdb`.`ctitle`,
				`jhcisdb`.`ceducation`,
				`jhcisdb`.`cnation`,
				`jhcisdb`.`cnation` AS `corigin`
			WHERE
				`person`.`hcode` = %n[HOUSE_ID] AND
				`person`.`hcode` <> 0 AND
				`person`.`prename` = `ctitle`.`titlecode` AND
				`person`.`educate` = `ceducation`.`educationcode` AND
				`person`.`nation` = `cnation`.`nationcode` AND
				`person`.`origin` = `corigin`.`nationcode` AND
				TIMESTAMPDIFF(YEAR, `person`.`birth`, CURRENT_DATE) BETWEEN 15 AND 65
			",
			array(
				'HOUSE_ID' => $house_id
			)
		);

		$person = array();
		foreach ($persons as $index => $list)
		{
			if ($person[$index]['person_id'] !== $list['person_id'])
			{
				$incurrent = $this->getIncurrent($list['person_id']);
				$visited = $this->getVisited($list['person_id']);
				$chronic = $this->getChronics($list['person_id']);

				$pressure = explode("/", $visited['last_pressure']);

				$person[$index]['person_id'] = $list['person_id'];
				$person[$index]['name'] = $list['name'];
				$person[$index]['age'] = (int)$list['age'];
				$person[$index]['birth'] = $list['birth'];
				$person[$index]['sex'] = $list['sex'];
				$person[$index]['idcard'] = $list['idcard'];
				$person[$index]['education'] = $list['education'];
				$person[$index]['nation'] = $list['nation'];
				$person[$index]['origin'] = $list['origin'];
				
				$person[$index]['color_level'] = (int)0;
				$person[$index]['last_pressure'] = array('systolic' => (int)$pressure[0], 'diastolic' => (int)$pressure[1]);
				$person[$index]['last_sugarblood'] = (int)$visited['last_sugarblood'];

				//if (empty($chronic)) continue;
				$person[$index]['chronics'] = $chronic;
				$person[$index]['incurrent'] = $incurrent;
			}
			
		}

		return $person;
	}

	public function getPhotoHouses($house_id)
	{
		$photo = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`gisiph_photo_house`.`phcode` AS `photo_id`,
				`gisiph_photo_house`.`path` AS `file`,
				`gisiph_photo_house`.`timestamp`
			FROM
				`jhcisdb`.`gisiph_photo_house`
			WHERE
				`gisiph_photo_house`.`hcode` = %n[HOUSE_ID]
				AND
				`gisiph_photo_house`.`status` <> 'DELETE'
			",
			array(
				'HOUSE_ID' => $house_id
			)
		);

		return $photo;
	}

	public function getPhotoChronics($person_id, $code)
	{
		$photo = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`gisiph_photo_pchronic`.`pccode` AS `photo_id`,
				`gisiph_photo_pchronic`.`path` AS `file`,
				`gisiph_photo_pchronic`.`timestamp`
			FROM
				`jhcisdb`.`gisiph_photo_pchronic`
			WHERE
				`gisiph_photo_pchronic`.`pid` = %n[PERSON_ID] AND
				`gisiph_photo_pchronic`.`chroniccode` = %s[CODE] AND
				`gisiph_photo_pchronic`.`status` <> 'DELETE'
			",
			array(
				'PERSON_ID' => $person_id,
				'CODE' => $code
			)
		);

		return $photo;
	}

	public function getChronics($person_id)
	{
		$chronics = $this->mysql->queryAndFetchAll(
			"
			SELECT
				CASE `cdiseasechronic`.`groupcode`
					WHEN '01' THEN 'hypertension'
					WHEN '10' THEN 'diabetes'
				END AS `chronic`,
				`personchronic`.`chroniccode`,
				`cdisease`.`diseasenamethai`,
				CONCAT(
					DATE_FORMAT(`personchronic`.`datefirstdiag`, '%e '),
					CASE DATE_FORMAT(`personchronic`.`datefirstdiag`, '%c')
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
					CAST(DATE_FORMAT(`personchronic`.`datefirstdiag`, '%Y') + '543' AS CHAR)
				) AS `date`
			FROM
				`jhcisdb`.`personchronic`,
				`jhcisdb`.`cdisease`,
				`jhcisdb`.`cdiseasechronic`
			WHERE
				`personchronic`.`pid` = %n[PERSON_ID] AND
				`personchronic`.`chroniccode` = `cdisease`.`diseasecode` AND
				`cdisease`.`codechronic` = `cdiseasechronic`.`groupcode` AND
				`cdiseasechronic`.`groupcode` IN ('01', '10') /*01=hypertension, 10=diabetes*/
			ORDER BY
				`personchronic`.`datefirstdiag` DESC
			",
			array(
				'PERSON_ID' => $person_id
			)
		);

		$chronic = array(
			'diabetes' => array(),
			'hypertension' => array()
		);
		foreach ($chronics as $values)
		{
			array_push($chronic[ $values['chronic'] ], array(
				'code' => $values['chroniccode'],
				'date' => $values['date'],
				'diseasenamethai' => $values['diseasenamethai'],
				'photo' => $this->getPhotoChronics($person_id, $values['chroniccode'])
			));
		}

		return $chronic;
	}

	public function getIncurrent($person_id)
	{
		$incurrents = $this->mysql->queryAndFetch(
			"
			SELECT
				IFNULL(
					`incurrents`.`incurrent`,
					FALSE
				) AS `incurrent`
			FROM
				`jhcisdb`.`person`
			LEFT JOIN
				(
					SELECT
						`personchronic`.`pid`,
						TRUE AS `incurrent`
					FROM
						`jhcisdb`.`cdiseasechronic`,
						`jhcisdb`.`cdisease`,
						`jhcisdb`.`personchronic`
					WHERE
						`cdiseasechronic`.`groupcode` NOT IN ('01', '10') AND /*01=hypertension, 10=diabetes*/
						`cdiseasechronic`.`groupcode` = `cdisease`.`codechronic` AND
						`cdisease`.`diseasecode` = `personchronic`.`chroniccode`
					GROUP BY
						`personchronic`.`pid`
				) AS `incurrents`
			ON
				`person`.`pid` = `incurrents`.`pid`
			WHERE
				`person`.`pid` = %n[PERSON_ID]
			GROUP BY
				`person`.`pid`
			",
			array(
				'PERSON_ID' => $person_id
			),
			'incurrent'
		);
		return (bool)$incurrents;
	}

	public function getVisited($person_id)
	{
		$visited = $this->mysql->queryAndFetch(
			"
			SELECT
				`pressure`.`last_pressure`,
				`sugarblood`.`last_sugarblood`
			FROM
				(
					SELECT
						`visit`.`pid`,
						`visit`.`pressure` AS `last_pressure`
					FROM
						`jhcisdb`.`visit`
					WHERE
						`visit`.`pid` = %n[PERSON_ID] AND
						`visit`.`pressure` IS NOT NULL
					ORDER BY
						`visit`.`pid`,
						`visit`.`visitdate` DESC
				) AS `pressure`,
				(
					SELECT
						`visit`.`pid`,
						`visitlabsugarblood`.`sugarnumdigit` AS `last_sugarblood`
					FROM
						`jhcisdb`.`visit`,
						`jhcisdb`.`visitlabsugarblood`
					WHERE
						`visit`.`pid` = %n[PERSON_ID] AND
						`visit`.`visitno` = `visitlabsugarblood`.`visitno` AND
						`visitlabsugarblood`.`sugarnumdigit` IS NOT NULL
					ORDER BY
						`visit`.`pid`,
						`visit`.`visitdate` DESC
				) AS `sugarblood`
			WHERE
				`pressure`.`pid` = `sugarblood`.`pid`
			GROUP BY
				`pressure`.`pid`
			",
			array(
				'PERSON_ID' => $person_id
			)
		);
		return $visited;
	}

	public function calcColor($value, $is_patient=true)
	{
		$color_level = 0;
		$disease = is_array($value) ? 'hypertension' : 'diabetes';

		if ($disease === 'hypertension')
		{
			if (!$is_patient)
			{
				if ($value['systolic'] < 120 && $value['diastolic'] < 80) 		$color_level = 0;
				else 															$color_level = 1;
				
			}
			else
			{
				if ($value['systolic'] < 140 && $value['diastolic'] < 90) 		$color_level = 2;
				elseif ($value['systolic'] < 160 && $value['diastolic'] < 100) 	$color_level = 3;
				elseif ($value['systolic'] < 180 && $value['diastolic'] < 110) 	$color_level = 4;
				else 															$color_level = 5;
			}
		}
		elseif ($disease === 'diabetes')
		{
			if (!$is_patient)
			{
				if ($value < 100) 	$color_level = 0;
				else 				$color_level = 1;
			}
			else
			{
				if ($value < 126) 		$color_level = 2;
				elseif ($value < 155) 	$color_level = 3;
				elseif ($value < 183) 	$color_level = 4;
				else 					$color_level = 5;
			}
		}

		return (int)$color_level;
	}

	function prettyPrint($json)
	{
		$result = '';
		$level = 0;
		$prev_char = '';
		$in_quotes = false;
		$ends_line_level = NULL;
		$json_length = strlen($json);

		for($i = 0; $i < $json_length; $i++)
		{
			$char = $json[$i];
			$new_line_level = NULL;
			$post = "";
			if($ends_line_level !== NULL)
			{
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if($char === '"' && $prev_char != '\\')
			{
				$in_quotes = !$in_quotes;
			}
			else if(!$in_quotes)
			{
				switch($char)
				{
					case '}': case ']':
					$level--;
					$ends_line_level = NULL;
					$new_line_level = $level;
					break;

					case '{': case '[':
					$level++;
					case ',':
					$ends_line_level = $level;
					break;

					case ':':
					$post = " ";
					break;

					case " ": case "\t": case "\n": case "\r":
					$char = "";
					$ends_line_level = $new_line_level;
					$new_line_level = NULL;
					break;
				}
			}
			if($new_line_level !== NULL)
			{
				$result .= "\n".str_repeat("  ", $new_line_level);
			}
			$result .= $char.$post;
			$prev_char = $char;
		}
		return $result;
	}
}
?>