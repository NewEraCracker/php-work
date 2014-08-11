<?php
/*
 Author: NewEraCracker
 License: Public Domain
*/

/* Stop unwanted access */
if(PHP_SAPI != 'cli'){ die("This script must be ran from CLI.\n"); }

/* Database details */
$dbhost = '127.0.0.1';
$dbuser = 'root';
$dbpass = '****';
$dbname = 'wordpress';

/* Database globals */
$dblink = null;
$dbtype = extension_loaded('mysqli') ? 'mysqli' : (extension_loaded('mysql') ? 'mysql' : null);

/* Function: Connect and select the database */
function db_connect()
{
	global $dbhost, $dbuser, $dbpass, $dbname, $dbtype, $dblink;

	if( $dbtype == 'mysql' )
	{
		$dblink = mysql_connect($dbhost, $dbuser, $dbpass);
		mysql_select_db($dbname, $dblink);
	}
	else if( $dbtype == 'mysqli' )
	{
		$dblink = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
	}

	if( !$dblink )
	{
		die("Connection to database failed!\n");
	}
}

/* Function: Query the database */
function db_query($query)
{
	global $dbtype, $dblink;

	if( $dbtype == 'mysql' )
	{
		return mysql_query($query, $dblink);
	}
	else if( $dbtype == 'mysqli' )
	{
		return mysqli_query($dblink, $query);
	}
}

/* Function: Fetch from the result */
function db_fetch($result)
{
	global $dbtype;

	if( $dbtype == 'mysql' )
	{
		return mysql_fetch_array($result);
	}
	else if( $dbtype == 'mysqli' )
	{
		return mysqli_fetch_array($result);
	}
}

/* Connect */
db_connect();

/* Grab the tables */
$show_tables_result = db_query('SHOW TABLES');

while( $tables = db_fetch($show_tables_result) )
{
	foreach( $tables as $table )
	{
		/* Change each table collation */
		db_query("OPTIMIZE TABLE `{$table}`");
	}
}

echo "The database has been successfully optimized!\n";
?>