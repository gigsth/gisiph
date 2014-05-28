<?php
session_start();
require 'class.User_Authorization.php';
require 'configure.database.php';

class Manage_Home
{
	private $mysql;

	public function __construct()
	{
		$this->mysql = new MySQL_Connection();
		$this->mysql->connect(HOSTNAME, USERNAME, PASSWORD, DBNAME, PORT);
		$this->mysql->charset = 'utf8';
	}

	public function __destruct() {
		$this->mysql->close();
	}
	
	public function getCurrentPage($search='', $page=1)
	{
		$query = $this->mysql->queryAndFetchAll(
			"
			SELECT SQL_CALC_FOUND_ROWS
				`house`.`id`,
				`house`.`address`,
				`gisiph_gps_house`.`latitude`,
				`gisiph_gps_house`.`longitude`,
				`gisiph_gps_house`.`uedit` AS `username`,
				IF(`gisiph_gps_house`.`status` IS NOT NULL AND `gisiph_gps_house`.`status` <> 'DELETE', 'map-marker', '') AS `glyphicon`,
				`gisiph_gps_house`.`timestamp`
			FROM
				(
				SELECT 	
					`house`.`hcode` AS `id`,
					CONCAT(
						CAST(`house`.`hno` AS CHAR),
						' หมู่ ',
						CAST(`village`.`villno` AS CHAR),
						' บ้าน',
						`village`.`villname`,
						' ต.',
						`subselect`.`subdistname`,
						' อ.',
						`subselect`.`distname`,
						' จ.',
						`subselect`.`provname`
					) AS address

				FROM 	
					`jhcisdb`.`house`, 
					`jhcisdb`.`village`,
					(
					SELECT	
						CONCAT(
							`cprovince`.`provcode`,
							`cdistrict`.`distcode`,
							`csubdistrict`.`subdistcode`
						) AS ref,
						`csubdistrict`.`subdistname`,
						`cdistrict`.`distname`,
						`cprovince`.`provname`

						FROM
							`jhcisdb`.`csubdistrict`,
							`jhcisdb`.`cdistrict`,
							`jhcisdb`.`cprovince`

						WHERE
							`csubdistrict`.`distcode` = `cdistrict`.`distcode`
							AND
							`cdistrict`.`provcode` = `cprovince`.`provcode`
							AND
							`csubdistrict`.`provcode` = `cprovince`.`provcode`
					) AS `subselect`

				WHERE
					`house`.`villcode` = `village`.`villcode`
					AND
					SUBSTRING(`village`.`villcode`,1,6) = `subselect`.`ref`
					AND
					`house`.`hcode` <> '0'
					AND
					`village`.`villno` <> '0'
				) AS `house`

			LEFT JOIN
				`jhcisdb`.`gisiph_gps_house`

			ON
				`house`.`id` = `gisiph_gps_house`.`hcode`

			WHERE
				`house`.`address` LIKE %s[SEARCH]
				

			LIMIT %n[PAGE], 10
			",
			array(
				'SEARCH' => '%'.$search.'%',
				'PAGE' => ($page - 1) * 10
			)
		);

		return array(
			'data' => $query,
			'totalPage' => $this->mysql->queryValue("SELECT CEILING(FOUND_ROWS()/10)")
		);
	}

	public  function setGpsHouse($id, $latitude, $longitude)
	{
		$user = unserialize($_SESSION['USER']);
		$query = $this->mysql->query(
			"
			INSERT INTO
				`jhcisdb`.`gisiph_gps_house`
					(
						`gisiph_gps_house`.`hcode`,
						`gisiph_gps_house`.`latitude`,
						`gisiph_gps_house`.`longitude`,
						`gisiph_gps_house`.`uedit`,
						`gisiph_gps_house`.`status`
					) 
			VALUES
				(
					%n[HCODE],
					%n[LATTITUDE],
					%n[LONGITUDE],
					%s[UEDIT],
					'INSERT'
				) 
			ON DUPLICATE KEY 
				UPDATE
					`gisiph_gps_house`.`latitude` = VALUES(`latitude`),
					`gisiph_gps_house`.`longitude` = VALUES(`longitude`),
					`gisiph_gps_house`.`uedit` = VALUES(`uedit`),
					`gisiph_gps_house`.`status` = 'UPDATE',
					`gisiph_gps_house`.`timestamp` = CURRENT_TIMESTAMP
			",
			array(
				'HCODE' => $id,
				'LATTITUDE' => $latitude,
				'LONGITUDE' => $longitude,
				'UEDIT' => $user->getUsername()
			)
		);
		return $query;
	}

	public function delGpsHouse($id)
	{
		$user = unserialize($_SESSION['USER']);
		$query = $this->mysql->query(
			"
			UPDATE
				`jhcisdb`.`gisiph_gps_house`
			SET 
				`gisiph_gps_house`.`uedit` = %s[UEDIT],
				`gisiph_gps_house`.`status` = 'DELETE'
			WHERE
				`gisiph_gps_house`.`hcode` = %n[HCODE]
			",
			array(
				'HCODE' => $id,
				'UEDIT' => $user->getUsername()
			)
		);
		return $query;
	}

	public function addPhoto($hcode, $filename, $empty='')
	{
		$user = unserialize($_SESSION['USER']);
		$query = $this->mysql->query(
			"
			INSERT INTO
				`jhcisdb`.`gisiph_photo_house`
					(
						`gisiph_photo_house`.`hcode`,
						`gisiph_photo_house`.`path`,
						`gisiph_photo_house`.`uedit`,
						`gisiph_photo_house`.`status`
					)
			VALUES
				(
					%n[HCODE],
					%s[PATH],
					%s[UEDIT],
					'INSERT'
				)
			",
			array(
				'HCODE' => $hcode,
				'PATH' => './uploads/'.$filename,
				'UEDIT' => $user->getUsername()
			)
		);
		return $this->mysql->queryValue("SELECT LAST_INSERT_ID()");
	}

	public function selPhoto($hcode, $empty='')
	{
		$query = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`gisiph_photo_house`.`phcode` AS `key`,
				`gisiph_photo_house`.`path` AS `file`
			FROM
				`jhcisdb`.`gisiph_photo_house`
			WHERE
				`gisiph_photo_house`.`hcode` = %n[HCODE]
				AND
				`gisiph_photo_house`.`status` <> 'DELETE'
			",
			array(
				'HCODE' => $hcode
			)
		);
		return $query;
	}

	public function delPhoto($phcode)
	{
		$query = $this->mysql->query(
			"
			UPDATE
				`jhcisdb`.`gisiph_photo_house`
			SET
				`gisiph_photo_house`.`status` = 'DELETE'
			WHERE
				`gisiph_photo_house`.`phcode` = %n[PHCODE]
			",
			array(
				'PHCODE' => $phcode
			)
		);
		return $query;
	}

	public function getPerson($hcode = 0)
	{
		$query = $this->mysql->queryAndFetchAll(
			"
			SELECT
				CONCAT(
					`ctitle`.`titlenamelong`,
					`person`.`fname`,
					' ',
					`person`.`lname`
				) AS `name`,
				CAST(CONCAT(
					TIMESTAMPDIFF(YEAR, `person`.`birth`, CURRENT_DATE),
					' ปี'
				) AS CHAR) AS `age`,
				`ceducation`.`educationname` AS `educate`
			FROM
				`jhcisdb`.`house`,
				`jhcisdb`.`person`,
				`jhcisdb`.`ctitle`,
				`jhcisdb`.`ceducation`
			WHERE
				`house`.`hcode` = `person`.`hcode`
				AND
				`person`.`prename` = `ctitle`.`titlecode`
				AND
				`person`.`educate` = `ceducation`.`educationcode`
				AND
				`house`.`hcode` = %n[HCODE]
			",
			array(
				'HCODE' => $hcode
			)
		);
		return $query;
	}
}
?>