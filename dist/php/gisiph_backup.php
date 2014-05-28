<?php
/*
 * Set execution time to 0 if in case your site is huge and will take much time 
 * for back-up as default execution time for PHP is 30 seconds.
 */
ini_set("max_execution_time", 0);


/*
 * By using below code we are creating a directory in which you’re going to stored your zip. 
 * In my case my directory name is ‘site-backup-gisiph’.
 */
$dir = "../backup";
if(!(file_exists($dir))) {
	mkdir($dir, 0777);
}


/*
 * Set your site credentials.
 */
$host = "localhost"; //host name
$username = "root"; //username
$password = "toor"; // your password
$dbname = "jhcisdb"; // database name
$tables = array(
	"gisiph_gps_house",
	"gisiph_photo_house",
	"gisiph_photo_pchronic"
);


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
echo "Archive name $zipname is created.<br/>";
// initialize an iterator
// pass it the directory to be processed
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("../../uploads/"));
// iterate over the directory
// add each file found to the archive
foreach ($iterator as $key=>$value) {
	$value = substr(str_replace('\\', '/', $value), 3);
	$zip->addFile(realpath($key), $value) or die ("ERROR: Could not add file: $key");
	echo 'Add file '.$value.' to archive.<br/>';
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
		$tables = array();
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
	foreach($tables as $table) {
		$result = mysql_query('SELECT * FROM '.$table);
		$num_fields = mysql_num_fields($result);
		$num_rows = mysql_num_rows($result);
		$index = 0;
		$return.= 'DROP TABLE `'.$table.'`;';
		$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
		$return.= "\n\n".$row2[1].";\n\n";

		$return.= 'INSERT INTO `'.$table."` VALUES\n";
		while($row = mysql_fetch_row($result)) {
			$return .= '(';
			for($j=0; $j<$num_fields; $j++) {
				$row[$j] = addslashes($row[$j]);
				if (isset($row[$j])) {
					$return.= '"'.$row[$j].'"' ;
				} else {
					$return.= '""';
				}
				if ($j<($num_fields-1)) {
					$return.= ',';
				}
			}
			$return.= ")";
			if ($index < $num_rows) {
				$return .= ",\n";
			}
			++$index;
		}
		$return.="\n\n\n";
		echo 'Re-Engineer table `'.$table.'` to sql code.<br/>';
	}
	
	//save file
	echo 'Prepare SQL code...<br/>';
	$handle = fopen('createdb.sql', 'w+');
	echo 'File createdb.sql is created.<br/>';
	fwrite($handle, $return);
	echo 'Push SQL code to createdb.sql file.<br/>';
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
			echo 'Add file '.$arr_file[$j].' to archive.<br/>';
		}
	}
}
echo 'Backup process is completed.<br/>';

?>