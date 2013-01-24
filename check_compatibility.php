<?php
/*
	Helps checking compatibility with IP.Board and other scripts
	@author  NewEraCracker
	@version 1.3.2
	@date    2013/01/24
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

// MySQL settings
$mysql_enable_check = true;
$mysql_host = '127.0.0.1';
$mysql_port = '3306';
$mysql_username = '';
$mysql_password = '';

// Session settings
$session_enable_extended_check = true;

/* --------------------------
   Functions for calculations
   -------------------------- */

/**
 * Returns the absolute value of integer
 */
function intAbs($number)
{
	return (int)str_replace('-','',(string)$number);
}

/**
 * Get the integer value of a variable
 */
function improvedIntVal($value)
{
	$value = (string)$value;
	$new   = '';
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

/**
 * Returns the MySQL version integer from a MySQL version string
 */
function mySqlVersionStringToInt($version)
{
	$version = explode('.',$version);
	$version = array_map('improvedIntVal',$version);
	$version = $version[0]*10000 + $version[1]*100 + $version[2];
	return $version;
}

/**
 * Returns the MySQL version string from a MySQL version integer
 */
function mySqlVersionIntToString($version)
{
	$version_string = '';
	$version_remain = (int)($version);

	foreach( array(10000,100,1) as $value)
	{
		$version_bit     = (int)($version_remain/$value);
		$version_remain  = (int)($version_remain-($version_bit*$value));
		$version_string .= $version_bit.'.';
	}

	return rtrim($version_string,'.');
}

/* -------------------
   Functions for tests
   ------------------- */

/**
 * Detects if float handling is problematic
 */
function is_float_problem()
{
    $num1 = 2009010200.01;
    $num2 = 2009010200.02;

    return ((string)$num1 === (string)$num2 || $num1 === $num2 || $num2 <= (string)$num1);
}

/**
 * Detects if timezone handling is problematic
 */
function is_timezone_problem()
{
	// check if date is loaded and DateTimeZone class exists (this should be true since 5.2.0)
	if( extension_loaded('date') && class_exists('DateTimeZone') )
	{
		$status = ini_get('date.timezone');

		if( !empty($status) )
		{
			try
			{
				$tz = new DateTimeZone($status);
			}
			catch(Exception $e)
			{
				// timezone is invalid
				return true;
			}

			// timezone is set and working
			return false;
		}

		// timezone is empty
		return true;
	}

	// extension isn't loaded so we don't check
	return false;
}

/**
 * Detects if input variables handling is problematic
 */
function is_max_input_vars_problem()
{
	// max_input_vars was introduced in PHP 5.3.9
	if( version_compare(PHP_VERSION, '5.3.9') >= 0 )
	{
		return (@ini_get('max_input_vars') < 4096);
	}

	// less than PHP 5.3.9, no problem here
	return false;
}

/**
 * Test for PHP+libxml2 bug which breaks XML input subtly with certain versions.
 * Known fixed with PHP 5.2.9 + libxml2-2.7.3
 * @see http://bugs.php.net/bug.php?id=45996
 */
function phpXmlBugTester()
{
	if( extension_loaded('xml') )
	{
		class XmlBugTester
		{
			private $parsedData = '';
			public $bad = true;

			public function __construct()
			{
				$charData = '<b>c</b>';
				$xml = '<a>' . htmlspecialchars( $charData ) . '</a>';

				$parser = xml_parser_create();
				xml_set_character_data_handler( $parser, array( $this, 'chardata' ) );
				$parsedOk = xml_parse( $parser, $xml, true );
				$this->bad = ( !$parsedOk || ($this->parsedData != $charData) );
			}
			public function chardata( $parser, $data )
			{
				$this->parsedData .= $data;
			}
		}
		$xmlBugTester = new XmlBugTester();
		return $xmlBugTester->bad;
	}

	// extension isn't loaded so we don't check
	return false;
}

/**
 * Test for PHP bug #50394 (PHP 5.3.x conversion to null only, not 5.2.x)
 * @see http://bugs.php.net/bug.php?id=50394
 */
function phpRefCallBugTester()
{
	class RefCallBugTester
	{
		public $bad = true;

		function __call( $name, $args )
		{
			$old = error_reporting( E_ALL & ~E_WARNING );
			call_user_func_array( array( $this, 'checkForBrokenRef' ), $args );
			error_reporting( $old );
		}
		function checkForBrokenRef( &$var )
		{
			if( $var )
				$this->bad = false;
		}
		function execute()
		{
			$var = true;
			call_user_func_array( array( $this, 'foo' ), array( &$var ) );
		}
	}
	$refCallBugTester = new RefCallBugTester();
	$refCallBugTester->execute();
	return $refCallBugTester->bad;
}

/* ---------
   Check PHP
   --------- */

// Check for lower than 5.2.9
if(version_compare(PHP_VERSION, '5.2.9') < 0)
	$errors[] = 'PHP 5.2.9 or newer is required. '.PHP_VERSION.' does not meet this requirement.';

// If 5.4, check for lower than 5.4.5
elseif(version_compare(PHP_VERSION, '5.4') >= 0 && version_compare(PHP_VERSION, '5.4.5') < 0)
	$errors[] = 'PHP 5.4.5 or newer is required. '.PHP_VERSION.' does not meet this requirement.';

// If 5.3, check for lower than 5.3.5
elseif(version_compare(PHP_VERSION, '5.3') >= 0 && version_compare(PHP_VERSION, '5.3.5') < 0)
	$errors[] = 'PHP 5.3.5 or newer is required. '.PHP_VERSION.' does not meet this requirement.';

// Functions to be enabled
$disabledFunctions = array_map('trim', explode(',',@ini_get('disable_functions')) );
$disabledFunctions = array_merge($disabledFunctions, array_map('trim', explode(',',@ini_get('suhosin.executor.func.blacklist'))) );
$functionsToBeEnabled = array('php_uname', 'base64_decode', 'fpassthru', 'ini_set', 'ini_get');
foreach( $functionsToBeEnabled as $test )
{
	if( !function_exists($test) || in_array($test, $disabledFunctions) )
		$errors[] = 'Function '.$test.' is required to be enabled in PHP.';
}

// Settings
$php_checks = array(
	array( is_float_problem(), 'Detected unexpected problem in handling of PHP float numbers.'),
	array( is_timezone_problem(), 'Invalid or empty date.timezone setting detected.'),
	array( is_max_input_vars_problem(), 'Problematic max_input_vars setting detected, please set it to 4096 or higher.'),
	array( phpXmlBugTester(), 'A bug has been detected in PHP+libxml2 which breaks XML input.'),
	array( phpRefCallBugTester(), 'A regression (bug #50394) has been detected in your PHP version. Please upgrade or downgrade your PHP installation.'),
	array( in_array('eval',$disabledFunctions), 'Language construct eval is required to be enabled in PHP.'),
	array( @ini_get('magic_quotes_gpc') || @get_magic_quotes_gpc(), 'Setting magic_quotes_gpc has been found enabled in your php.ini. Disable it for better functionality.'),
	array( @ini_get('safe_mode'), 'PHP must not be running in safe_mode. Disable the PHP safe_mode setting.'),
	array( @ini_get('output_handler') == 'ob_gzhandler', 'PHP must not be running with output_handler set to ob_gzhandler. Disable this setting.'),
	array( @ini_get('zlib.output_compression' ), 'PHP must not be running with zlib.output_compression enabled. Disable this setting.'),
	array( @ini_get('zend.ze1_compatibility_mode'), 'zend.ze1_compatibility_mode is set to On. This may cause some strange problems. It is strongly suggested to turn this value to Off.'),
);

foreach( $php_checks as $fail )
	if( $fail[0] )
		$errors[] = $fail[1];

// Upload dir
$upload_dir = @ini_get('upload_tmp_dir') ? @ini_get('upload_tmp_dir') : @sys_get_temp_dir();
if( !empty($upload_dir) && !is_writable($upload_dir) ) // Make sure upload dir is writable
	$errors[] = 'Your upload temporary directory '.htmlspecialchars($upload_dir).' is not writable. Please fix this issue.';

// Session path
$sessionpath = @session_save_path();
if( strpos($sessionpath, ';') !== false ) // http://www.php.net/manual/en/function.session-save-path.php#50355
	$sessionpath = substr($sessionpath, strpos($sessionpath, ';')+1);	
if( !empty($sessionpath) && !is_writable($sessionpath) ) // Make sure session path is writable
	$errors[] = 'Your session path '.htmlspecialchars($sessionpath).' is not writable. Please fix this issue.';

// Check PHP extensions
$required_extensions = array(
	array( 'ctype', 'Ctype' ),
	array( 'curl', 'cURL' ),
	array( 'dom', 'Document Object Model' ),
	array( 'iconv', 'Iconv' ),
	array( 'gd', 'GD Library' ),
	array( 'json', 'JSON' ),
	array( 'mbstring', 'Multibyte String' ),
	array( 'mysql', 'MySQL'  ),
	array( 'mysqli', 'MySQLi' ),
	array( 'openssl', 'OpenSSL'  ),
	array( 'pcre', 'Perl-Compatible Regular Expressions' ),
	array( 'reflection', 'Reflection Class' ),
	array( 'session', 'Session' ),
	array( 'spl', 'SPL' ),
	array( 'xml', 'XML Parser' ),
	array( 'zip', 'Zip' ),
	array( 'zlib', 'Zlib' ),
);

foreach( $required_extensions as $test )
{
	if( !extension_loaded($test[0]) )
		$errors[] = 'The required PHP extension "'.$test[1].'" could not be found. You need to install/enable this extension.';
}

// Check cURL
if( extension_loaded('curl') )
{
	$curlFound = 'The required PHP extension "cURL" was found';

	// Some hosts have cURL but disable its functions. Lets check for that.
	$curlFuctions = array(
		'curl_close', 'curl_copy_handle', 'curl_errno', 'curl_error', 'curl_exec', 'curl_getinfo',
		'curl_init', 'curl_multi_add_handle', 'curl_multi_close', 'curl_multi_exec', 'curl_multi_getcontent',
		'curl_multi_info_read', 'curl_multi_init', 'curl_multi_remove_handle', 'curl_multi_select',
		'curl_setopt_array', 'curl_setopt', 'curl_version',
		);
	foreach( $curlFuctions as $test )
		if(!function_exists($test) || in_array($test, $disabledFunctions))
			$errors[] = $curlFound.', but function '.$test.' is disabled. Please enable it.';

	// We need SSL and ZLIB support
	if( $curlVersion = @curl_version() )
	{
		$curlBitFields = array( 'CURL_VERSION_SSL', 'CURL_VERSION_LIBZ' );
		$curlBitFriendly = array( 'SSL', 'ZLIB' );

		foreach($curlBitFields as $arr_key => $feature)
		{
			$test = $curlBitFriendly[$arr_key];
			if( !($curlVersion['features'] && constant($feature)) )
				$errors[] = $curlFound.', but '.$test.' support is missing. Please add support for '.$test.' in cURL.';
		}
	}
}

// Check GD
if( function_exists('gd_info') )
{
	$gdFound = 'The required PHP extension "GD Library" was found';

	// We need GIF, JPEG and PNG support
	$required_gd = array(
		array( 'imagecreatefromgif', 'GIF' ),
		array( 'imagecreatefromjpeg', 'JPEG' ),
		array( 'imagecreatefrompng', 'PNG' ),
	);

	foreach( $required_gd as $test )
		if( !function_exists($test[0]) )
			$errors[] = $gdFound.', but '.$test[1].' support is missing. Please add support for '.$test[1].' images in GD Library.';

	// We need GD 2 and freetype support
	$gdInfo = @gd_info();

	if( @$gdInfo["GD Version"] && !strstr($gdInfo["GD Version"],'2.') )
		$errors[] = $gdFound.', but GD Version is older than v2. Please fix this issue.';

	if( ! @$gdInfo['FreeType Support'] )
		$errors[] = $gdFound.', but FreeType support is missing. Please add support for this.';
}

// Check Session
if( extension_loaded('session') )
{
	$sessionFound = 'The required PHP extension "Session" was found';

	if( @ini_get('session.use_trans_sid') )
		$errors[] = $sessionFound.', but session.use_trans_sid is enabled. Please disable this setting for security reasons.';

	if( ! @ini_get('session.use_only_cookies') )
		$errors[] = $sessionFound.', but session.use_only_cookies is disabled. Please enable this setting for security reasons.';

	if( $session_enable_extended_check )
	{
		if( @ini_get('session.hash_bits_per_character') < 5 )
			$errors[] = $sessionFound.', but session.hash_bits_per_character is set to a low value. Please set it to 5 for security reasons.';

		if( ! @ini_get('session.hash_function') )
			$errors[] = $sessionFound.', but session.hash_function is set to a weak value. Please set it to 1 for security reasons.';
	}
}

// Check Ioncube
if( function_exists('ioncube_loader_version') )
{
	if( !function_exists('ioncube_loader_iversion') )
		$errors[] = 'You have a VERY old version of IonCube Loaders which is known to cause problems.';

	elseif(ioncube_loader_iversion() < 40202 && version_compare(PHP_VERSION, '5.4') >= 0)
		$errors[] = 'You have an old version of IonCube Loaders (4.2.1 or earlier) which is known to cause problems with PHP 5.4.';

	elseif(ioncube_loader_iversion() < 40007 && version_compare(PHP_VERSION, '5.3') >= 0)
		$errors[] = 'You have an old version of IonCube Loaders (4.0.6 or earlier) which is known to cause problems with PHP 5.3.';
}
else
{
	$errors[] = 'You do not seem to have IonCube Loaders installed.';
}

// Check eAccelerator if installed
if( function_exists('eaccelerator_info') )
{
	$ea_info = eaccelerator_info();

	if(version_compare($ea_info['version'], '0.9.9') <= 0)
	{
		// Only 1.0-dev are known to work
		$errors[] = 'You have an old version of eAccelerator (earlier than 1.0) which is known to cause problems.';
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
		$errors[] = 'Memory Limit: 128M is required. Please increase this setting.';
}

/* ----------------
   Suhosin Settings
   ---------------- */
if( extension_loaded('suhosin') )
{
	// Value has to be false or zero to pass tests
	$test_false = array(
		'suhosin.mail.protect',
		'suhosin.sql.bailout_on_error',
		'suhosin.cookie.encrypt',
		'suhosin.session.encrypt'
	);

	// Value has to be the same or higher to pass tests
	$test_values = array(
		array( 'suhosin.cookie.max_name_length', 64),
		array( 'suhosin.cookie.max_totalname_length', 256),
		array( 'suhosin.cookie.max_value_length', 10000),
		array( 'suhosin.get.max_name_length', 512 ),
		array( 'suhosin.get.max_totalname_length', 512 ),
		array( 'suhosin.get.max_value_length', 2048 ),
		array( 'suhosin.post.max_array_index_length', 256 ),
		array( 'suhosin.post.max_name_length', 512 ),
		array( 'suhosin.post.max_totalname_length', 8192 ),
		array( 'suhosin.post.max_vars', 4096 ),
		array( 'suhosin.post.max_value_length', 1000000 ),
		array( 'suhosin.request.max_array_index_length', 256 ),
		array( 'suhosin.request.max_totalname_length', 8192 ),
		array( 'suhosin.request.max_vars', 4096 ),
		array( 'suhosin.request.max_value_length', 1000000 ),
		array( 'suhosin.request.max_varname_length', 512 )
	);
	
	// Value has to be zero (protection disabled) or higher than x to pass
	$test_zero_or_higher_than_value = array(
		array( 'suhosin.executor.max_depth', 10000),
		array( 'suhosin.executor.include.max_traversal', 6),
	);

	foreach($test_false as $test)
	{
		if( @ini_get($test) )
		{
			if( $test == 'suhosin.mail.protect' )
				$errors[] = $test.' is required to be set to 0 (zero) in php.ini. Your server does not meet this requirement.';
			else
				$errors[] = $test.' is required to be set to <b>off</b> in php.ini. Your server does not meet this requirement.';
		}
	}

	foreach($test_values as $test)
	{
		if( isset($test['0']) && isset($test['1']) )
		{
			if( @ini_get($test['0']) < $test['1'] )
				$errors[] = 'It is required that <b>'.$test['0'].'</b> is set to <b>'.$test['1'].'</b> or higher.';
		}
	}

	foreach($test_zero_or_higher_than_value as $test)
	{
		if( @ini_get($test['0']) )
		{
			if( @ini_get($test['0']) < $test['1'] )
				$errors[] = 'It is required that <b>'.$test['0'].'</b> is set to either 0 (zero), <b>'.$test['1'].'</b> or higher.';
		}
	}
}

/* -------------
   MySQL Version
   ------------- */
if( $mysql_enable_check )
{
	// Just to be sure :)
	$mysql_port = (int)$mysql_port;

	if( function_exists('mysqli_connect') )
	{
		$mysqli = @mysqli_connect($mysql_host,$mysql_username,$mysql_password,'',$mysql_port);

		if(!$mysqli)
		{
			$errors[] = 'Unable to connect to MySQLi: '.mysqli_connect_error();
		}
		else
		{
			$client_version = mySqlVersionStringToInt( mysqli_get_client_info() );
			$server_version = mySqlVersionStringToInt( mysqli_get_server_info($mysqli) );
			mysqli_close($mysqli);
		}
	}
	elseif( function_exists('mysql_connect') )
	{
		$mysql_host = $mysql_host.':'.$mysql_port;
		$mysql = @mysql_connect($mysql_host,$mysql_username,$mysql_password);

		if(!$mysql)
		{
			$errors[] = 'Unable to connect to MySQL: '.mysql_error();
		}
		else
		{
			$client_version = mySqlVersionStringToInt( mysql_get_client_info() );
			$server_version = mySqlVersionStringToInt( mysql_get_server_info($mysql) );
			mysql_close($mysql);
		}
	}

	if( isset($server_version) && isset($client_version) )
	{
		if($server_version < 50100)
			$errors[] = 'You are running MySQL '.mySqlVersionIntToString($server_version).'. We recommend upgrading to at least MySQL 5.1.';

		if( intAbs($server_version-$client_version) >= 1000 )
			$errors[] = 'Your PHP MySQL library version ('.mySqlVersionIntToString($client_version).') does not match MySQL Server version ('.mySqlVersionIntToString($server_version).').';
	}
}

/* ------
   Output
   ------ */

// Header
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>check_compatibility.php</title>
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
</head>
<body>';

// Body
if( isset($errors) && count($errors) )
{
	// Errors
	echo 'The following issues have been found, please ask your host to fix them:<br /><br />';
	foreach($errors as $error)
		echo $error.'<br />';
}
else
{
	// Balls to you!
	echo 'Congratulations, no problems have been detected.';
}

// Footer
echo '</body>
</html>';
?>