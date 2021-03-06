<?php
//type octet-stream. make sure apache does not gzip this type, else it would get buffered
header('Content-Type: text/octet-stream');
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.


/*
 * Set execution time to 0 if in case your site is huge and will take much time 
 * for back-up as default execution time for PHP is 30 seconds.
 */
ini_set("max_execution_time", 0);


require 'configure.database.php';


/*
 * Set your site credentials.
 */
$host = HOSTNAME; //host name
$username = USERNAME; //username
$password = PASSWORD; // your password
$dbname = DBNAME; // database name


/**
 *	Send a partial message
 */
function send_percent($current, $max) {
	echo floor(($current*100)/$max).'%';

	//PUSH THE data out by all FORCE POSSIBLE
	ob_flush();
	flush();
}


$file = $_FILES['zip'];


if ($file['error'] !== UPLOAD_ERR_OK) {
	die("Upload failed.");
}


$zip = new ZipArchive;
if ($zip->open($file['tmp_name']) === TRUE) {
	$zip->extractTo('./');
	$zip->close();

	$result = restore_tables($host, $username, $password, $dbname, './');
	deleteDirectory('../../uploads');
	rename('uploads', '../../uploads');
} else {
	echo 'failed';
}



/*
 * Define the function restore_tables which will create a database sql file.
 */
function restore_tables($host, $user, $pass, $name, $path){
	global $CURRENT_DATA;
	global $MAX_DATA;
	
	$con = mysql_connect($host,$user,$pass);
	mysql_select_db($name,$con);

	//load file
	$commands = file_get_contents($path . '/createdb.sql');

	//delete comments
	$lines = explode("\n",$commands);
	$commands = '';
	foreach($lines as $line){
		$line = trim($line);
		if( $line && !startsWith($line,'--') ){
			$commands .= $line;
			$MAX_DATA++;
		}
	}

	//convert to array
	$commands = explode(";", $commands);

	//run commands
	$total = $success = 0;
	foreach($commands as $k => $command){
		if(trim($command)){
			$status = @mysql_query($command)==false ? 0 : 1;
			$success += $status;
			$total += 1;
		}
		send_percent(++$CURRENT_DATA, $MAX_DATA);
	}


	//return number of successful queries and total number of queries found
	return array(
		"success" => $success,
		"total" => $total
	);
}


// Here's a startsWith function
function startsWith($haystack, $needle){
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}


// Here's a deleteDirectory function
function deleteDirectory($dir) {
	if (!file_exists($dir)) {
		return true;
	}

	if (!is_dir($dir)) {
		return unlink($dir);
	}

	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..') {
			continue;
		}

		if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
			return false;
		}

	}

	return rmdir($dir);
}
?>