<?php
require '../configure.database.php';

class Database
{
	private static $connection = null;
	private function __construct()
	{
		//Thou shalt not construct that which is unconstructable!
	}
	private function __clone()
	{
		//Me not like clones! Me smash clones!
	}
	
	public static function getConnection()
	{
		if (!isset(Database::$connection)) {
			Database::$connection = new MySQL_Connection();
			Database::$connection->connect(HOSTNAME, USERNAME, PASSWORD, DBNAME);
			Database::$connection->charset = 'utf8';
		}
		return Database::$connection;
	}
}
?>