<?php
session_start();
header("Content-type: application/zip"); 
header("Content-Disposition: attachment; filename={$_SESSION['zip']}"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
readfile("../backup/{$_SESSION['zip']}");
exit;
?>