<?php
//dfghbjjhbhjbhyuhihihii
//require 'class.Maps.php';

//$q = new Maps();

header('Content-Type: application/json; charset=UTF-8');

require 'class.Database.php';
echo Database::getConnection()->ping();
//echo $q->prettyPrint(json_encode($_POST));
/*echo $q->prettyPrint(
	json_encode(
		$q->getHouses(
			array(
				'hypertension' => '01',
				'diabetes' => '10'
			)
		)
	)
);*/
/*echo $q->prettyPrint(
	json_encode(
		$q->getPersonsDetail(2)
	)
);*/

/*require 'class.Database.php';
require 'is_ajax.php';header('Content-Type: application/json; charset=UTF-8');
$maps = new Maps();
echo json_output($maps->getPersons());
switch($_POST['request'])
{
	case 'houses': break; // [house_id, address, latitude, longitude, color_level]
	case 'persons': break; // [person_id, name, color_level]
	case 'person': break; // detail of person
	//default: throw new Exception('ขอบเขตการเรียกดูข้อมูลของคุณไม่ถูกต้อง');
}

class Maps
{
	private $houses;
	private $persons;

	public function __construct() {}

	private function selectHouses()
	{
		$this->houses = Database::getConnection()->queryAndFetchAll(
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
	}

	private function selectPersons($house_id)
	{
		$this->persons = Database::getConnection()->queryAndFetchAll(
			"
			SELECT
				`person`.`pid` AS `person_id`,
				CONCAT(
					`ctitle`.`titlenamelong`,
					`person`.`fname`,
					' ',
					`person`.`lname`
				) AS `name`
			FROM
				`jhcisdb`.`person`,
				`jhcisdb`.`ctitle`
			WHERE
				`person`.`hcode` = %n[HOUSE_ID] AND
				`person`.`hcode` <> 0 AND
				`person`.`prename` = `ctitle`.`titlecode`
			",
			array(
				'HOUSE_ID' => $house_id
			)
		);
	}

	private function selectPerson($person_id)
	{}

	public function getHouses($chronics, $colors)
	{
		$this->selectHouses();

		foreach ($houses as &$house)
		{
			$house['latitude'] = (double)$house['latitude'];
			$house['longitude'] = (double)$house['longitude'];

			$this->getPersons($house['house_id'], $chronics);

			$house['color_level'] = (int)0;
		}
	}

	public function getPersons($house_id, $chronics)
	{
		$this->selectPersons($house_id);
		foreach ($this->persons as &$person)
		{
			//$person['color_level']
		}
		return $this->persons;
	}
}*/
?>