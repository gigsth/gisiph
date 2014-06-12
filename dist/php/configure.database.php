<?php
session_start();
require 'class.MySQL_Connection.php';

define('HOSTNAME', !empty($_SESSION['HOSTNAME']) ? $_SESSION['HOSTNAME'] : '127.0.0.1');
define('USERNAME', !empty($_SESSION['USERNAME']) ? $_SESSION['USERNAME'] : 'root');
define('PASSWORD', !empty($_SESSION['PASSWORD']) ? $_SESSION['PASSWORD'] : 'toor');
define('DBNAME', 'jhcisdb');
define('PORT', !empty($_SESSION['PORT']) ? $_SESSION['PORT'] : '3306'); // Optional Default is 3306
?>