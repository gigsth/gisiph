<?php
require 'is_ajax.php';
if (!isAjax()) {return;}

try {
	header('Content-Type: application/json; charset=UTF-8');

	require 'class.User_Authorization.php';
	require 'configure.database.php';

	$mysql = new MySQL_Connection();
	$mysql->connect(HOSTNAME, USERNAME, PASSWORD, DBNAME, PORT);
	$mysql->charset = 'utf8';

	$query = $mysql->queryAndFetch(
		"
		SELECT
			`username`,
			`password`,
			`fname`,
			`lname`
		FROM
			`user`
		WHERE
			`username` = %s
			AND
			`password` = %s
		",
		array(
			$_POST['username'],
			$_POST['password']
		)
	);

	if (!is_null($query)) {
		session_start();
		$user = new User_Authorization($query);
		$_SESSION['USER'] = serialize($user);

		echo json_encode(array('event' => 'Success', 'message' => ''));
	}
	else{
		throw new Exception('โปรดตรวจสอบชื่อผู้ใช้และรหัสผ่านว่าถูกต้องหรือไม่');
	}
}
catch(Exception $e) {
	echo json_encode(array('event' => 'Error', 'message' => '<strong>ผิดพลาด!</strong> '.$e->getMessage()));
}
?>