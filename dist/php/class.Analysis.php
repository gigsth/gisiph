<?php
session_start();
require 'class.User_Authorization.php';
require 'configure.database.php';

class Analysis
{
	private $mysql;

	public function __construct() {
		$this->mysql = new MySQL_Connection();
		$this->mysql->connect(HOSTNAME, USERNAME, PASSWORD, DBNAME);
		$this->mysql->charset = 'utf8';
	}

	public function __destruct() {
		$this->mysql->close();
	}

	public function getChronics() {
		$chronics_groupcode = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`personchronic`.`pid` AS `pid`, 
				`cdiseasechronic`.`groupcode` AS `groupcode`
			FROM
				`jhcisdb`.`personchronic`,
				`jhcisdb`.`cdisease`,
				`jhcisdb`.`cdiseasechronic`
			WHERE
				`personchronic`.`chroniccode` = `cdisease`.`diseasecode` AND
				`cdisease`.`codechronic` = `cdiseasechronic`.`groupcode` AND
				`cdiseasechronic`.`groupcode` IN ('01', '10') 
			GROUP BY 
				`personchronic`.`pid`,
				`cdiseasechronic`.`groupcode`
			ORDER BY 
				`personchronic`.`pid`
			"
		);

		$person_disease = array();
		foreach ($chronics_groupcode as $key => &$value) {
			if(!isset($person_disease[$value['pid']])) {
				if($value['groupcode'] === '01') {
					$person_disease[$value['pid']] = 0;
				}
				elseif ($value['groupcode'] === '10') {
					$person_disease[$value['pid']] = 1;
				}
			}
			else {
				$person_disease[$value['pid']] = 2;
			}

		}

		$chronics = array(
			array(
				'disease' => 'ความดันโลหิตสูง',
				'count' => 0
			),
			array(
				'disease' => 'เบาหวาน',
				'count' => 0
			),
			array(
				'disease' => 'เบาหวานและความดันโลหิตสูง',
				'count' => 0
			)
		);

		foreach ($person_disease as $key => &$value) {
			$chronics[$value]['count']++;
		}

		return $chronics;
	}

	public function getVillage() {
		$village_groupcode = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`person`.`pid` AS `pid`,
				`village`.`villname` AS `villname`,				
				`cdiseasechronic`.`groupcode` AS `groupcode`
			FROM
				`jhcisdb`.`village`,
				`jhcisdb`.`house`,
				`jhcisdb`.`person`,
				`jhcisdb`.`personchronic`,
				`jhcisdb`.`cdisease`,
				`jhcisdb`.`cdiseasechronic`
			WHERE
				`village`.`villcode` = `house`.`villcode` AND
				`house`.`hcode` = `person`.`hcode` AND
				`person`.`pid` = `personchronic`.`pid` AND
				`personchronic`.`chroniccode` = `cdisease`.`diseasecode` AND
				`cdisease`.`codechronic` = `cdiseasechronic`.`groupcode` AND
				`cdiseasechronic`.`groupcode` IN ('01', '10')
			GROUP BY 
				`person`.`pid`,
				`cdiseasechronic`.`groupcode`
				ORDER BY 
				`village`.`villcode`,
				`person`.`pid`,
				`cdiseasechronic`.`groupcode`
			"
		);

		$village_disease = array();
		foreach ($village_groupcode as $key => &$value) {
			if(!isset($village_disease[$value['pid']])) {
				if($value['groupcode'] === '01') {
					$village_disease[$value['pid']] = array($value['villname'], 'diabetes');
				}
				elseif ($value['groupcode'] === '10') {
					$village_disease[$value['pid']] = array($value['villname'], 'hypertension');
				}
			}
			else {
				$village_disease[$value['pid']] = array($value['villname'], 'both');
			}
		}

		$village_prepare = array();
		foreach ($village_disease as $key => $value) {
			if (!isset($village_prepare[$value[0]])) {
				$village_prepare[$value[0]] = array(
					'villname' => $value[0],
					'diabetes' => 0,
					'hypertension' => 0,
					'both' => 0
					);
			}
			$village_prepare[$value[0]][$value[1]]++;
		}
		$village = array();
		foreach ($village_prepare as $key => $value) {
			$village[] = $value;
		}
		
		return $village;


	}

	public function getDiscover() {
		$discover = $this->mysql->queryAndFetchAll(
			"
			SELECT
				YEAR(`personchronic`.`datefirstdiag`) + 543 AS `year`,
				COUNT(
					CASE WHEN `cdiseasechronic`.`groupcode` = '10' THEN 1 END
				) AS `diabetes`,
				COUNT(
					CASE WHEN `cdiseasechronic`.`groupcode` = '01' THEN 1 END
				) AS `hypertension`
			FROM
				`jhcisdb`.`personchronic`,
				`jhcisdb`.`cdisease`,
				`jhcisdb`.`cdiseasechronic`
			WHERE
				`personchronic`.`chroniccode` = `cdisease`.`diseasecode` AND
				`cdisease`.`codechronic` = `cdiseasechronic`.`groupcode`
			GROUP BY
				YEAR(`personchronic`.`datefirstdiag`)
			"
		);

		foreach ($discover as $key => &$value) {
			$value['diabetes'] = (int)$value['diabetes'];
			$value['hypertension'] = (int)$value['hypertension'];
		}

		return $discover;
	}

	public function getColorFromHypertension() {
		$colorFromHypertension = $this->mysql->queryAndFetchAll(
			"
			SELECT 
				`pressure_pidgroup`.`pid`,
				`pressure_pidgroup`.`top_pressure`,
				`pressure_pidgroup`.`down_pressure`,
				`pressure_pidgroup`.`visitdate`,
				`cdisease`.`codechronic` AS `person_codechronic`
			
			FROM
			(
				SELECT `pressure`.*			
				FROM
					(SELECT
						`visit`.`pid` AS `pid`,
						SUBSTRING(`visit`.`pressure`,1,INSTR(`visit`.`pressure`,'/')-1) AS `top_pressure`,
						SUBSTRING(`visit`.`pressure`,INSTR(`visit`.`pressure`,'/')+1, CHAR_LENGTH(`visit`.`pressure`)) AS `down_pressure`,
						`visit`.`visitdate`
					FROM
						`jhcisdb`.`visit` 
					WHERE
						`visit`.`pressure` IS NOT NULL
					ORDER BY
						`visit`.`pid`,
					`visit`.`visitdate` DESC
					) AS `pressure`

				GROUP BY 
					`pressure`.`pid`) AS `pressure_pidgroup`
				
				LEFT JOIN `personchronic` ON `pressure_pidgroup`.`pid` = `personchronic`.`pid`
				LEFT JOIN `cdisease` ON `personchronic`.`chroniccode` = `cdisease`.`diseasecode` 

			GROUP BY 
				`pressure_pidgroup`.`pid`,
				`cdisease`.`codechronic`
			"
		);
		return $this->calcColorFromHypertension($colorFromHypertension);
	}
	
	public function calcColorFromHypertension($colorFromHypertension) {
		$person_color = array();
		$has_disease = array();
		foreach ($colorFromHypertension as $key => &$value) {
			$value['top_pressure'] = (int)$value['top_pressure'];
			$value['down_pressure'] = (int)$value['down_pressure'];

			if (($value['person_codechronic'] === NULL || $value['person_codechronic'] !== '01') && !$has_disease[$value['pid']]) {
				if ($value['top_pressure'] >= 120 && $value['down_pressure'] >= 80 && (int)$person_color[$value['pid']] <= 1) {
					$person_color[$value['pid']] = 1;
				}
				elseif ((int)$person_color[$value['pid']] === 0) {
					$person_color[$value['pid']] = 0;
				}
			}
			elseif ($value['person_codechronic'] === '01' && !$has_disease[$value['pid']]) {
				$has_disease[$value['pid']] = true;
				if ($value['top_pressure'] >= 180 && $value['down_pressure'] >= 110) {
					$person_color[$value['pid']] = 5;
				}
				elseif ($value['top_pressure'] >= 160 && $value['down_pressure'] >= 100) {
					$person_color[$value['pid']] = 4;
				}
				elseif ($value['top_pressure'] >= 140 && $value['down_pressure'] >= 90) {
					$person_color[$value['pid']] = 3;
				}
				else {
					$person_color[$value['pid']] = 2;
				}
			}
			elseif ($value['person_codechronic'] !== NULL && $value['person_codechronic'] !== '01') {
				$has_disease[$value['pid']] = true;
				$person_color[$value['pid']] = 6;
			}
		}

		$color = array(
			array(
				'name' => 'กลุ่มปกติ',
				'count' => 0,
				'style' => 'color: #FFFFFF; stroke-color: #000; stroke-width: .5;'
			),
			array(
				'name' => 'กลุ่มเสี่ยง',
				'count' => 0,
				'style' => 'color: #00FF00'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยระดับ 0',
				'count' => 0,
				'style' => 'color: #007700'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยระดับ 1',
				'count' => 0,
				'style' => 'color: #FFFF00'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยระดับ 2',
				'count' => 0,
				'style' => 'color: #FF7F00'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยระดับ 3',
				'count' => 0,
				'style' => 'color: #FF0000'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยมีโรคแทรกซ้อน',
				'count' => 0,
				'style' => 'color: #000000'
			)
		);

		foreach ($person_color as $key => $value) {
			$color[$value]['count']++;
		}

		return $color;
	}
	
	public function getColorFromDiabetes() {
		$colorFromDiabetes = $this->mysql->queryAndFetchAll(
			"
			SELECT 
				`sugarblood_grouppid`.`pid`,
				`sugarblood_grouppid`.`sugarnumdigit`,
				`cdisease`.`codechronic` AS 'person_codechronic'
			FROM
				(
					SELECT 
						`sugarblood`.* 
					FROM
						(
							SELECT 
								`visit`.`pid`,
								`visitlabsugarblood`.`sugarnumdigit`,
								`visit`.`visitdate`
							FROM
								`jhcisdb`.`visit`
							JOIN
								`jhcisdb`.`visitlabsugarblood`
							ON 
								`visitlabsugarblood`.`visitno` = `visit`.`visitno`
							ORDER BY 
								`visit`.`pid`,
								`visit`.`visitdate` DESC
						)AS `sugarblood`
					GROUP BY 
						`sugarblood`.`pid`
				) AS `sugarblood_grouppid`
			LEFT JOIN 
				`jhcisdb`.`personchronic`
			ON 
				`sugarblood_grouppid`.`pid` = `personchronic`.`pid`
			LEFT JOIN 
				`jhcisdb`.`cdisease`
			ON 
				`personchronic`.`chroniccode` = `cdisease`.`diseasecode`

			GROUP BY 
				`sugarblood_grouppid`.`pid`,
				`cdisease`.`codechronic` DESC
			"
		);
		return $this->calcColorFromDiabetes($colorFromDiabetes);
	}

	public function calcColorFromDiabetes($colorFromDiabetes) {
		$person_color = array();
		$has_disease = array();
		foreach ($colorFromDiabetes as $key => &$value) {
			$value['sugarnumdigit'] = (int)$value['sugarnumdigit'];

			if (($value['person_codechronic'] === NULL || $value['person_codechronic'] !== '10') && !$has_disease[$value['pid']]) {
				if ($value['sugarnumdigit'] >= 100 && (int)$person_color[$value['pid']] <= 1) {
					$person_color[$value['pid']] = 1;
				}
				elseif ((int)$person_color[$value['pid']] === 0) {
					$person_color[$value['pid']] = 0;
				}
			}
			elseif ($value['person_codechronic'] === '10' && !$has_disease[$value['pid']]) {
				$has_disease[$value['pid']] = true;
				if ($value['sugarnumdigit'] >= 183) {
					$person_color[$value['pid']] = 5;
				}
				elseif ($value['sugarnumdigit'] >= 155) {
					$person_color[$value['pid']] = 4;
				}
				elseif ($value['sugarnumdigit'] >= 126) {
					$person_color[$value['pid']] = 3;
				}
				else {
					$person_color[$value['pid']] = 2;
				}
			}
			elseif ($value['person_codechronic'] !== NULL && $value['person_codechronic'] !== '10') {
				$has_disease[$value['pid']] = true;
				$person_color[$value['pid']] = 6;
			}
		}

		$color = array(
			array(
				'name' => 'กลุ่มปกติ',
				'count' => 0,
				'style' => 'color: #FFFFFF; stroke-color: #000; stroke-width: .5;'
			),
			array(
				'name' => 'กลุ่มเสี่ยง',
				'count' => 0,
				'style' => 'color: #00FF00'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยระดับ 0',
				'count' => 0,
				'style' => 'color: #007700'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยระดับ 1',
				'count' => 0,
				'style' => 'color: #FFFF00'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยระดับ 2',
				'count' => 0,
				'style' => 'color: #FF7F00'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยระดับ 3',
				'count' => 0,
				'style' => 'color: #FF0000'
			),
			array(
				'name' => 'กลุ่มผู้ป่วยมีโรคแทรกซ้อน',
				'count' => 0,
				'style' => 'color: #000000'
			)
		);

		foreach ($person_color as $key => $value) {
			$color[$value]['count']++;
		}

		return $color;
	}

	public function getColorFromHypertensionVillage($villcode){
		$colorFromHypertension = $this->mysql->queryAndFetchAll(
			"
			SELECT 
				`pressure_pidgroup`.`pid`,
				`pressure_pidgroup`.`top_pressure`,
				`pressure_pidgroup`.`down_pressure`,
				`cdisease`.`codechronic` AS `person_codechronic`
				
			FROM
				(	SELECT 
						`person`.`pid` 
					FROM 
						`person`,`house`
					WHERE 
						`person`.`hcode` = `house`.`hcode` 
					AND
						`house`.`villcode` = %s[VILLCODE]
					ORDER BY `person`.`pid`
				) AS `personvillage` 

			JOIN

				(
					SELECT `pressure`.*			
					FROM
						(
							SELECT
								`visit`.`pid` AS `pid`,
								SUBSTRING(`visit`.`pressure`,1,INSTR(`visit`.`pressure`,'/')-1) AS `top_pressure`,
								SUBSTRING(`visit`.`pressure`,INSTR(`visit`.`pressure`,'/')+1, CHAR_LENGTH(`visit`.`pressure`)) AS `down_pressure`,
								`visit`.`visitdate` AS `date_pressure`
							FROM
								`jhcisdb`.`visit` 
							WHERE
								`visit`.`pressure` IS NOT NULL
							ORDER BY
								`visit`.`pid`,
							`visit`.`visitdate` DESC
						) AS `pressure`

					GROUP BY 
						`pressure`.`pid`
				) AS `pressure_pidgroup` ON `personvillage`.`pid` = `pressure_pidgroup`.`pid`
					
			LEFT JOIN `personchronic` ON `pressure_pidgroup`.`pid` = `personchronic`.`pid`
			LEFT JOIN `cdisease` ON `personchronic`.`chroniccode` = `cdisease`.`diseasecode`
				
			GROUP BY 
				`pressure_pidgroup`.`pid`,
				`cdisease`.`codechronic`
			",
			array(
				'VILLCODE' => $villcode
			)
		);

		return $this->calcColorFromHypertension($colorFromHypertension);
	}

	public function getColorFromDiabetesVillage($villcode) {

		$colorFromDiabetes = $this->mysql->queryAndFetchAll(
			"
			SELECT 
				`sugarblood_grouppid`.*,
				`cdisease`.`codechronic` AS 'person_codechronic'
			FROM
			(
				SELECT 
					`person`.`pid` 
				FROM 
					`person`,`house`
				WHERE 
					`person`.`hcode` = `house`.`hcode` 
				AND 
					`house`.`villcode` = %n[VILLCODE]
				ORDER BY `person`.`pid`
			) AS `personvillage` 
			JOIN
			(
				SELECT 
					`sugarblood`.* 
				FROM
					(
						SELECT 
							`visit`.`pid`,
							`visitlabsugarblood`.`sugarnumdigit`,
							`visit`.`visitdate`
						FROM 
							`jhcisdb`.`visitlabsugarblood` 
						LEFT JOIN 
							`jhcisdb`.`visit`
						ON 
							`visitlabsugarblood`.`visitno` = `visit`.`visitno`
						ORDER BY 
							`visit`.`pid`,
							`visit`.`visitdate` DESC
					)AS `sugarblood`
				GROUP BY 
					`sugarblood`.`pid`
			) AS `sugarblood_grouppid`
			ON `personvillage`.`pid` = `sugarblood_grouppid`.`pid`
			LEFT JOIN 
				`jhcisdb`.`personchronic`
			ON 
				`sugarblood_grouppid`.`pid` = `personchronic`.`pid`
			LEFT JOIN 
				`jhcisdb`.`cdisease`
			ON 
				`personchronic`.`chroniccode` = `cdisease`.`diseasecode`

			GROUP BY 
				`sugarblood_grouppid`.`pid`,
				`cdisease`.`codechronic` DESC
			",
			array(
				'VILLCODE' => $villcode
			)
		);

		return $this->calcColorFromDiabetes($colorFromDiabetes);
	}

	public function getNameVillage() {
		$village = $this->mysql->queryAndFetchAll(
			" 
			SELECT 
				`village`.`villcode` AS `villcode`,
				`village`.`villname` AS `villname`
			FROM `village`
			"
		);
		return $village;
	}


}
?>