<?php
require 'is_ajax.php';
if (!isAjax()) {return;}

session_start();

if ($_POST['action'] === 'logout') {
	unset($_SESSION['USER']);
}

header('Content-Type: application/json; charset=UTF-8');
if (isset($_SESSION['USER'])) {
	require 'class.User_Authorization.php';
	$user = unserialize($_SESSION['USER']);
	if ($user->isAuthorization()) {
		echo json_encode(array('event' => 'Auth', 'message' => array('username' => $user->getUsername(),'fullname' => $user->getFullName())));
		return;
	}
}
echo json_encode(array('event' => 'Login', 'message' => 'มีข้อผิดพลากับการยืนยันสิทธิ์การใช้งาน'));
?>