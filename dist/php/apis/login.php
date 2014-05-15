<?php
$callback = isset($_GET['callback']) ? preg_replace('[^a-zA-Z0-9$_.]', '', $_GET['callback']) : false;
header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
header('Access-Control-Allow-Origin: *');
require_once 'class.Database.php';

$user = Database::getConnection()->queryAndFetch(
	"
	SELECT
		`user`.`username`,
		CONCAT(`user`.`fname`, ' ', `user`.`lname`) AS `fullname`,
		MD5(CONCAT(`user`.`username`, `user`.`password`)) AS `hash`,
		CURRENT_TIMESTAMP AS `timestamp`
	FROM
		`jhcisdb`.`user`
	WHERE
		`user`.`username` = %s[USERNAME] AND
		`user`.`password` = %s[PASSWORD]
	",
	array(
		'USERNAME' => $_POST['username'],
		'PASSWORD' => $_POST['password']
	)
);

$districts = Database::getConnection()->queryAndFetchAll(
	"
	SELECT
		`village`.`villname` AS `display`,
		'districts' AS `name`,
		`village`.`villcode` AS `value`,
		'' AS `checked`,
		'' AS `disabled`
	FROM
		`jhcisdb`.`village`
	/*WHERE
		`village`.`villno` <> 0*/
	"
);

$response = array(
	'prop' => !empty($user) ? 'success' : 'fail',
	'data' => array(
		'user' => $user,
		'districts' => !empty($user) ? $districts : array()
	)
);

// Output the end result
echo ($callback ? $callback . '(' : '') . json_encode($response) . ($callback ? ')' : '');
?>