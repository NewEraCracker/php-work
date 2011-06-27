<?php
/* 
	Helps checking compatibility with IP.Board (and other scripts)
	@author  NewEraCracker
	@version 0.5.0
	@date    27/06/2011
	@license Public Domain

	Inspired by all noobish hosting companies around the world

	Greetz to:
	 - ForumScriptz Team
	 - Matt Mecham
	 - Xenforo Developers
*/

/* -------------
   Configuration
   ------------- */

$mysqlHostname = '127.0.0.1';
$mysqlPortnum  = '3306';
$mysqlUsername = '';
$mysqlPassword = '';
$mysqlEnabled  = true;

/* ---------
   Functions
   --------- */

function improvedIntVal($value)
{
	$value = (string)$value;
	$new   = "";
	$found = false;

	// Build
	for( $i=0; $i<strlen($value); $i++ )
	{	
		// Found a number ?		
		if( is_numeric($value[$i]) )
		{
			$found = true;
			$new .= $value[$i];
		}
		elseif($found)
		{
			// We already have numbers
			// and we don't like trash.
			break;
		}
	}
	$value = $new;
	
	// Return the result
	return (int)$value;
}

function mySqlVersionStringToInt($version)
{
	$version = explode('.',$version);
	$version = array_map("improvedIntVal",$version);
	$version = $version[0]*10000 + $version[1]*100 + $version[2];
	return $version;
}

function mySqlVersionIntToString($version)
{
	$version_remain = (int)($version);

	// Major  [?.x.x]
	$version_major  = (int)($version_remain/10000) ;
	$version_remain = (int)($version_remain-($version_major*10000));

	// Medium [x.?.x]
	$version_medium = (int)($version_remain/100);
	$version_remain = (int)($version_remain-($version_medium*100));

	// Lower  [x.x.?]
	$version_lower  = (int)($version_remain);

	return "{$version_major}.{$version_medium}.{$version_lower}";
}

/* -----------
   PHP Version
   ----------- */

$phpVersion = phpversion();

// Check for lower than 5.2.4
if( version_compare($phpVersion, '5.2.4', '<') )
{
	$errors[] = "PHP 5.2.4 or newer is required. {$phpVersion} does not meet this requirement.";
}

// If 5.3, check for lower than 5.3.4
if( version_compare($phpVersion, '5.3', '>=') && version_compare($phpVersion, '5.3.4', '<') )
{
	$errors[] = "PHP 5.3.4 or newer is required. {$phpVersion} does not meet this requirement.";
}

/* ------------
   PHP Settings
   ------------ */

// Functions to be enabled
$disabledFunctions    = array_map("trim", explode(",",@ini_get("disable_functions")));
$functionsToBeEnabled = array('php_uname', 'base64_decode', 'fpassthru', 'ini_set', 'ini_get');

foreach( $functionsToBeEnabled as $test )
{
	if (!function_exists($test) || in_array($test, $disabledFunctions))
	{
		$errors[] = "Function ".$test." is required to be enabled in PHP!";
	}
}

// Magic Quotes
if ( @ini_get('magic_quotes_gpc') || @get_magic_quotes_gpc() )
{
	$errors[] = "magic_quotes_gpc is enabled in your php.ini! Please ask your host to disable it for better functionality.";
}

// Safe Mode
if( @ini_get('safe_mode') )
{
	$errors[] = 'PHP must not be running in safe_mode. Please ask your host to disable the PHP safe_mode setting.';
}

// Check PHP extensions
$required_extensions = array(

array(	'prettyname'		=> "Ctype",
		'extensionname'	=> "ctype" ),

array(	'prettyname'		=> "Document Object Model",
		'extensionname'	=> "dom" ),

array(	'prettyname'		=> "Iconv",
		'extensionname'	=> "iconv" ),

array(	'prettyname'		=> "GD Library",
		'extensionname'	=> "gd" ),

array(	'prettyname'		=> "MySQL",
		'extensionname'	=> "mysql" ),

array(	'prettyname'		=> "MySQLi",
		'extensionname'	=> "mysqli" ),

array(	'prettyname'		=> "Perl-Compatible Regular Expressions",
		'extensionname'	=> "pcre" ),

array(	'prettyname'		=> "Reflection Class",
		'extensionname'	=> "reflection" ),

array(	'prettyname'		=> "XML Parser",
		'extensionname'	=> "xml" ),

array(	'prettyname'		=> "SPL",
		'extensionname'	=> "spl" ),

array(	'prettyname'		=> "OpenSSL",
		'extensionname'	=> "openssl" ),

array(	'prettyname'		=> "JSON",
		'extensionname'	=> "json" ),

);

foreach( $required_extensions as $test )
{
	if ( !extension_loaded($test['extensionname']) )
	{
		$errors[] = "The required PHP extension \"{$test['prettyname']}\" could not be found. Please ask your host to install this extension.";
	}
}

// Check RAM limits
if( $memLimit = @ini_get('memory_limit') )
{
	$memLimit = trim($memLimit);
	$last = strtolower($memLimit[strlen($memLimit)-1]);
	switch($last) {
		case 'g':
			$memLimit *= 1024;
		case 'm':
			$memLimit *= 1024;
		case 'k':
			$memLimit *= 1024;
	}

	$recLimit = (128*1024*1024);
	if($memLimit < $recLimit)
	{
		$errors[] = "Memory Limit: {$recLimit}M is required. Please ask your host to increase this setting.";
	}

}

/* ----------------
   Suhosin Settings
   ---------------- */
if( extension_loaded('suhosin') )
{
	// Value has to be the same or higher to pass tests
	$test_values = array(
	array( 'suhosin.get.max_name_length', 350 ),
	array( 'suhosin.post.max_array_index_length', 256 ),
	array( 'suhosin.post.max_totalname_length', 8192 ),
	array( 'suhosin.post.max_vars', 4096 ),
	array( 'suhosin.post.max_value_length', 1000000 ),
	array( 'suhosin.request.max_array_index_length', 256 ),
	array( 'suhosin.request.max_totalname_length', 8192 ),
	array( 'suhosin.request.max_vars', 4096 ),
	array( 'suhosin.request.max_value_length', 1000000 ),
	array( 'suhosin.request.max_varname_length', 350 ),
	);

	// Value has to be false to pass tests
	$test_false = array(
	'suhosin.sql.bailout_on_error',
	'suhosin.cookie.encrypt',
	'suhosin.session.encrypt',
	);

	foreach($test_false as $test)
	{
		if( @ini_get($test) != false )
		{
			$errors[] = "{$test} being off in php.ini is required. Your host does not meet this requirement.";
		}
	}

	foreach($test_values as $test)
	{
		if( isset($test['0']) && isset($test['1']) )
		{
			if( @ini_get($test['0']) < $test['1'])
			{
				$errors[] = "It is required that <b>{$test['0']}</b> is set to <b>{$test['1']}</b> or higher.";
			}
		}
	}
}

/* -------------
   MySQL Version
   ------------- */
if( $mysqlEnabled )
{
	// Just to be sure :)
	$mysqlPortnum = (int)$mysqlPortnum;

	if( function_exists('mysqli_connect') )
	{
		$mysqli = @mysqli_connect($mysqlHostname,$mysqlUsername,$mysqlPassword,"",$mysqlPortnum);

		if(!$mysqli)
		{
			$errors[] = "Unable to connect to MySQLi: ".mysqli_connect_error();
		}
		else
		{
			$client_version = mySqlVersionStringToInt( mysqli_get_client_info() );
			$server_version = mySqlVersionStringToInt( mysqli_get_server_info($mysqli) );
			
			if($server_version < 50000)
			{
				$errors[] = "Your MySQL Version (".mySqlVersionIntToString($server_version).") is end-of-life. Please ask your host to upgrade MySQL!";
			}
			elseif($server_version < 50100)
			{
				$errors[] = "You are running MySQL ".mySqlVersionIntToString($server_version).", please ask your host to upgrade to MySQL 5.1!";
			}
			if( ($server_version-$client_version)>=1000 )
			{
				$errors[] = "Your PHP MySQL library version (".mySqlVersionIntToString($client_version).") does not match MySQL Server version (".mySqlVersionIntToString($server_version).")! Please ask your host to fix this issue";
			}

			mysqli_close($mysqli);
		}
	}
	elseif( function_exists('mysql_connect') )
	{
		$mysqlHostname = "{$mysqlHostname}:{$mysqlPortnum}";
		$mysql = @mysql_connect($mysqlHostname,$mysqlUsername,$mysqlPassword);

		if(!$mysql)
		{
			$errors[] = "Unable to connect to MySQL: ".mysql_error();
		}
		else
		{
			$client_api = mySqlVersionStringToInt( mysql_get_client_info() );
			$server_api = mySqlVersionStringToInt( mysql_get_server_info($mysql) );
			
			if($server_version < 50000)
			{
				$errors[] = "Your MySQL Version (".mySqlVersionIntToString($server_version).") is end-of-life. Please ask your host to upgrade MySQL!";
			}
			elseif($server_version < 50100)
			{
				$errors[] = "You are running MySQL ".mySqlVersionIntToString($server_version).", please ask your host to upgrade to MySQL 5.1!";
			}
			if( ($server_version-$client_version)>=1000 )
			{
				$errors[] = "Your PHP MySQL library version (".mySqlVersionIntToString($client_version).") does not match MySQL Server version (".mySqlVersionIntToString($server_version).")! Please ask your host to fix this issue";
			}

			mysql_close($mysql);
		}
	}
}

/* ---------------------
   Output problems found
   --------------------- */
echo "<pre>";
// Errors ?
if( isset($errors) && count($errors) )
{
	// Output them!
	foreach($errors as $error)
	{
		echo $error."\r\n";
	}
}
else
{
	// Balls to you!
	echo "Congratulations, no problems have been detected.";
}
echo "</pre>";