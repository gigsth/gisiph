<?php
session_start();

$_SESSION['HOSTNAME'] = empty($_POST['HOSTNAME']) ? NULL : $_POST['HOSTNAME'];
$_SESSION['USERNAME'] = empty($_POST['USERNAME']) ? NULL : $_POST['USERNAME'];
$_SESSION['PASSWORD'] = empty($_POST['PASSWORD']) ? NULL : $_POST['PASSWORD'];
$_SESSION['PORT'] = empty($_POST['PORT']) ? NULL : $_POST['PORT'];
?>