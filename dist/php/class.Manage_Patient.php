<?php
session_start();
require 'class.User_Authorization.php';
require 'configure.database.php';

class Manage_Patient
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
				`personchronic`.*,
				IF(`photo`.`pccode` IS NOT NULL, 'picture', '') AS `glyphicon`
			FROM
				(
					SELECT
						`person`.`pid` AS `id`,
						`person`.`hcode` AS `hcode`,
						CONCAT(
							`ctitle`.`titlenamelong`,
							`person`.`fname`,
							' ',
							`person`.`lname`
						) AS `name`,
						CONCAT(
							TIMESTAMPDIFF(YEAR, `person`.`birth`, CURRENT_DATE),
							' ปี'
						) AS `age`,
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
						`jhcisdb`.`cnation` AS `corigin`,
						(
							SELECT
								`personchronic`.`pid`
							FROM
								`jhcisdb`.`personchronic`,
								`jhcisdb`.`cdisease`
							WHERE
								`personchronic`.`chroniccode` = `cdisease`.`diseasecode`
								AND
								`cdisease`.`codechronic` IN ('01', '10')
							GROUP BY
								`personchronic`.`pid`
						) AS `chronic`
					WHERE
						`person`.`pid` = `chronic`.`pid`
						AND
						`person`.`prename` = `ctitle`.`titlecode`
						AND
						`person`.`educate` = `ceducation`.`educationcode`
						AND
						`person`.`occupa` = `coccupa`.`occupacode`
						AND
						`person`.`nation` = `cnation`.`nationcode`
						AND
						`person`.`origin` = `corigin`.`nationcode`
						AND
						`person`.`hcode` <> '0'
						AND
						TIMESTAMPDIFF(YEAR, `person`.`birth`, CURRENT_DATE) BETWEEN 15 AND 65
				) AS `personchronic`
			LEFT JOIN
				(
					SELECT
						*
					FROM
						`jhcisdb`.`gisiph_photo_pchronic`
					WHERE
						`gisiph_photo_pchronic`.`status` <> 'DELETE'
					GROUP BY
						`gisiph_photo_pchronic`.`pid`
				) AS `photo`
			ON
				`personchronic`.`id` = `photo`.`pid`
			WHERE
				`personchronic`.`name` LIKE %s[SEARCH]
				OR
				`personchronic`.`age` LIKE %s[SEARCH]
				OR
				`personchronic`.`birth` LIKE %s[SEARCH]
				OR
				`personchronic`.`sex` LIKE %s[SEARCH]
				OR
				REPLACE(`personchronic`.`idcard`, '-', '') LIKE %s[SEARCH]
				OR
				`personchronic`.`educate` LIKE %s[SEARCH]
				OR
				`personchronic`.`occupa` LIKE %s[SEARCH]
				OR
				`personchronic`.`nation` LIKE %s[SEARCH]
				OR
				`personchronic`.`origin` LIKE %s[SEARCH]
			LIMIT 
				%n[PAGE], 10
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

	public function addPhoto($pid, $filename, $ccode)
	{
		$user = unserialize($_SESSION['USER']);
		$query = $this->mysql->query(
			"
			INSERT INTO
				`jhcisdb`.`gisiph_photo_pchronic`
					(
						`gisiph_photo_pchronic`.`pid`,
						`gisiph_photo_pchronic`.`chroniccode`,
						`gisiph_photo_pchronic`.`path`,
						`gisiph_photo_pchronic`.`uedit`,
						`gisiph_photo_pchronic`.`status`
					)
			VALUES
				(
					%n[PID],
					%s[CCODE],
					%s[PATH],
					%s[UEDIT],
					'INSERT'
				)
			",
			array(
				'PID' => $pid,
				'CCODE' => $ccode,
				'PATH' => './uploads/'.$filename,
				'UEDIT' => $user->getUsername()
			)
		);
		return $this->mysql->queryValue("SELECT LAST_INSERT_ID()");
	}

	public function selPhoto($pcode, $ccode)
	{
		$query = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`gisiph_photo_pchronic`.`pccode` AS `key`,
				`gisiph_photo_pchronic`.`path` AS `file`
			FROM
				`jhcisdb`.`gisiph_photo_pchronic`
			WHERE
				`gisiph_photo_pchronic`.`pid` = %n[PCODE]
				AND
				`gisiph_photo_pchronic`.`chroniccode` = %s[CCODE]
				AND
				`gisiph_photo_pchronic`.`status` <> 'DELETE'
			",
			array(
				'PCODE' => $pcode,
				'CCODE' => $ccode
			)
		);
		return $query;
	}

	public function delPhoto($phcode)
	{
		$query = $this->mysql->query(
			"
			UPDATE
				`jhcisdb`.`gisiph_photo_pchronic`
			SET
				`gisiph_photo_pchronic`.`status` = 'DELETE'
			WHERE
				`gisiph_photo_pchronic`.`pccode` = %n[PHCODE]
			",
			array(
				'PHCODE' => $phcode
			)
		);
		return $query;
	}

	public function getChronic($pid)
	{
		$query = $this->mysql->queryAndFetchAll(
			"
			SELECT
				`personchronic`.`pid`,
				`cdiseasechronic`.`groupcode` AS `group`,
				`personchronic`.`chroniccode` AS `code`,
				`cdisease`.`diseasenamethai` AS `chronic`,
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
				`jhcisdb`.`cdiseasechronic`,
				`jhcisdb`.`cdisease`,
				`jhcisdb`.`personchronic`
			WHERE
				`cdiseasechronic`.`groupcode` IN ('01', '10')
				AND
				`cdiseasechronic`.`groupcode` = `cdisease`.`codechronic`
				AND
				`cdisease`.`diseasecode` = `personchronic`.`chroniccode`
				AND
				`personchronic`.`pid` = %n[PID]

			ORDER BY
				`personchronic`.`datefirstdiag` DESC
			",
			array(
				'PID' => $pid
			)
		);
		return $query;
	}

	public function getVisit($pid = '')
	{
		$query = $this->mysql->queryAndFetch(
			"
			SELECT
				`visit`.`visitno`,
				CONCAT(
					DATE_FORMAT(`visit`.`visitdate`, '%e '),
					CASE DATE_FORMAT(`visit`.`visitdate`, '%c')
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
					CAST(DATE_FORMAT(`visit`.`visitdate`, '%Y') + '543' AS CHAR)
				) AS `visitdate`,
				`visit`.`weight`,
				`visit`.`height`,
				`visit`.`pressure`,
				`visit`.`waist`,
				`visit`.`ass`
			FROM
				`jhcisdb`.`visit`
			WHERE
				`visit`.`pid` = %n[PID]
			GROUP BY
				`visit`.`pid`,
				`visit`.`visitdate` DESC
			ORDER BY
				`visit`.`pid`
			",
			array(
				'PID' => $pid
			)
		);
		return $query;
	}

	public function getSugar($visitno = 0)
	{
		$query = $this->mysql->queryAndFetch(
			"
			SELECT
				`visitlabsugarblood`.`sugarnumdigit` AS `sugar`
			FROM
				`jhcisdb`.`visitlabsugarblood`
			WHERE
				`visitlabsugarblood`.`visitno` = %n[VISITNO]
			",
			array(
				'VISITNO' => $visitno
			)
		);
		return $query;
	}
}
?>