<?php
//type octet-stream. make sure apache does not gzip this type, else it would get buffered
header('Content-Type: text/octet-stream');
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
session_start();


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
$tables = array(
	"gisiph_gps_house",
	"gisiph_photo_house",
	"gisiph_photo_pchronic"
);


/**
 *	Send a partial message
 */
function send_message($message) {
	echo $message . PHP_EOL . '&gt;';

	//PUSH THE data out by all FORCE POSSIBLE
	ob_flush();
	flush();
}


/*
 * By using below code we are creating a directory in which you’re going to stored your zip. 
 * In my case my directory name is ‘site-backup-gisiph’.
 */
$dir = "../backup";
if(!(file_exists($dir))) {
	mkdir($dir, 0777);
}


/*
 * We are going to save our backup in zip format so create an object of zip.
 */
$zip = new ZipArchive();


/*
 * Create a name for zip file. I have created it based on today’s date so that 
 * we can easily find date of last backup. Also I have append ‘gisiph-’ to a name of 
 * zip file which we use in next procedures.
 */
$zipname = 'backup-'.date("Ymd-His");
$zipname = 'gisiph-'.$zipname.'.zip';
$zipname = str_replace("/", "-", $zipname);


/*
 * Add all files from uploads folder in newly created zip file.
 */
// open archive
if ($zip->open('../backup/'.$zipname, ZIPARCHIVE::CREATE) !== TRUE) {
	die ("Could not open archive");
}
send_message("Archive name $zipname is created.");
// initialize an iterator
// pass it the directory to be processed
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("../../uploads/"));
// iterate over the directory
// add each file found to the archive
foreach ($iterator as $key=>$value) {
	$value = substr(str_replace('\\', '/', $value), 6);
	$zip->addFile(realpath($key), $value) or die ("ERROR: Could not add file: $key");
	send_message('Add file '.$value.' to archive.');
}
// close and save archive
$zip->close();


/*
 * Call the function backup_tables.
 */
backup_tables($host, $username, $password, $dbname, $tables);


/*
 * Define the function backup_tables which will create a database sql file.
 */
function backup_tables($host,$user,$pass,$name,$tables = '*') {
	/* backup the db OR just a table */
	$con = mysql_connect($host,$user,$pass);
	mysql_select_db($name,$con);
	
	//get all of the tables
	if($tables == '*') {
		$tables = '*';
		$result = mysql_query('SHOW TABLES');
		while($row = mysql_fetch_row($result)) {
			$tables[] = $row[0];
		}
	}
	else {
		$tables = is_array($tables) ? $tables : explode(',',$tables);
	}
	$return = "";
			
	//cycle through
	send_message('Prepare SQL code...');
	foreach($tables as $table) {
		$result = mysql_query('SELECT * FROM '.$table);
		$num_fields = mysql_num_fields($result);
		$num_rows = mysql_num_rows($result);
		$return.= 'DROP TABLE `'.$table.'`;';
		$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
		$return.= "\n\n".$row2[1].";\n\n";

		while($row = mysql_fetch_row($result)) {
			$line = 'INSERT INTO `'.$table."` VALUES(";
			for($j=0; $j<$num_fields; $j++) {
				$row[$j] = addslashes($row[$j]);
				$row[$j] = ereg_replace("\n","\\n",$row[$j]);
				if (isset($row[$j])) {
					/*if (is_numeric($row[$j]))
						$sql = $row[$j];
					else*/
						$sql = '"'.$row[$j].'"' ;
					$line.= $sql;
				} else {
					$line.= '""';
				}
				if ($j<($num_fields-1)) {
					$line.= ',';
				}
			}
			$line.= ");";
			$return.= $line."\n";
			send_message('Add '.$line);
		}
		$return.="\n\n\n";
	}

	// index
	$return .= "ALTER TABLE `jhcisdb`.`visit` DROP KEY `gisiph_pid`;\n";
	$return .= "ALTER TABLE `jhcisdb`.`visit` ADD KEY `gisiph_pid` (`pid`);\n\n";
	$return .= "ALTER TABLE `jhcisdb`.`visit` DROP KEY `gisiph_visitno`;\n";
	$return .= "ALTER TABLE `jhcisdb`.`visit` ADD KEY `gisiph_visitno` (`visitno`);\n\n";
	$return .= "ALTER TABLE `jhcisdb`.`visit` DROP KEY `gisiph_pid_visitno`;\n";
	$return .= "ALTER TABLE `jhcisdb`.`visit` ADD KEY `gisiph_pid_visitno` (`pid`, `visitno`);\n\n";
	$return .= "ALTER TABLE `jhcisdb`.`visitlabsugarblood` DROP KEY `gisiph_visitno`;\n";
	$return .= "ALTER TABLE `jhcisdb`.`visitlabsugarblood` ADD KEY `gisiph_visitno` (`visitno`);\n\n";
	
	//save file
	$handle = fopen('createdb.sql', 'w+');
	send_message('File createdb.sql is created.');
	fwrite($handle, $return);
	send_message('Push SQL code to createdb.sql file.');
	fclose($handle);
}


/*
 * Convert .sql file in .sql.zip file and remove the .sql file
 */
if (glob("*.sql") != false) {
	$filecount = count(glob("*.sql"));
	$arr_file = glob("*.sql");
	
	for($j=0;$j<$filecount;$j++) {
		$res = $zip->open('../backup/'.$zipname, ZipArchive::CREATE);
		if ($res === TRUE) {
			$zip->addFile($arr_file[$j]);
			$zip->close();
			unlink($arr_file[$j]);
			send_message('Add file '.$arr_file[$j].' to archive.');
		}
	}
}
send_message('Compression already successful.');
$_SESSION['zip'] = $zipname;
send_message('Done...');

?>