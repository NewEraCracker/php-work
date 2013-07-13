<?php
/*
  Helps checking compatibility with IP.Board and other scripts
  @author  NewEraCracker
  @version 3.0.7
  @date    2013/05/08
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
$CONFIG['mysql_enabled'] = true;
$CONFIG['mysql_host']    = '127.0.0.1';
$CONFIG['mysql_port']    = '3306';
$CONFIG['mysql_user']    = '';
$CONFIG['mysql_pass']    = '';

// Session settings
$CONFIG['session_enable_extended_check'] = true;

/* --------------------------
   Functions for calculations
   -------------------------- */

/**
 * Converts text to suit the correct delimiter
 */
function cleanup_crlf($text)
{
	$text = str_replace("\r\n", "\n", $text);
	$text = str_replace("\r", "\n", $text);
	$text = str_replace("\n", "\r\n", $text);

	return $text;
}

/**
 * Returns the absolute value of integer
 */
function intAbs($number)
{
	return (int)str_replace('-', '', (string)$number);
}

/**
 * Return the value of a PHP configuration option
 */
function improvedIniGet($varname)
{
	if(function_exists('ini_get'))
		return @ini_get($varname);

	if(function_exists('get_cfg_var'))
		return @get_cfg_var($varname);
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
	for($i = 0; $i < strlen($value); $i++)
	{
		// Found a number ?
		if(is_numeric($value[$i]))
		{
			$found = true;
			$new .= $value[$i];
		}
		elseif($found)
		{
			// We already have numbers and we don't like trash.
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
	$version = explode('.', $version);
	$version = array_map('improvedIntVal', $version);
	$version = $version[0] * 10000 + $version[1] * 100 + $version[2];
	return $version;
}

/**
 * Returns the MySQL version string from a MySQL version integer
 */
function mySqlVersionIntToString($version)
{
	$version_string = '';
	$version_remain = (int)($version);

	foreach(array(10000, 100, 1) as $value)
	{
		$version_bit     = (int)($version_remain / $value);
		$version_remain  = (int)($version_remain - ($version_bit * $value));
		$version_string .= $version_bit.'.';
	}

	return rtrim($version_string, '.');
}

/* ---------------
   Classes for tests
   --------------- */

/**
 * Test for PHP bug #50394 (PHP 5.3.x conversion to null only, not 5.2.x)
 * @see http://bugs.php.net/bug.php?id=50394
 */
class RefCallBugTester
{
	public $bad = true;

	public function __call($name, $args)
	{
		$old = error_reporting(E_ALL & ~E_WARNING);
		call_user_func_array(array($this, 'checkForBrokenRef'), $args);
		error_reporting($old);
	}

	public function checkForBrokenRef(&$var)
	{
		if($var)
			$this->bad = false;
	}

	public function execute()
	{
		$var = true;
		call_user_func_array(array($this, 'foo'), array(&$var));
	}
}

/**
 * Test for PHP+libxml2 bug which breaks XML input subtly with certain versions.
 * Known fixed with PHP 5.2.9 + libxml2-2.7.3
 * @see http://bugs.php.net/bug.php?id=45996
 */
class XmlBugTester
{
	private $parsedData = '';
	public $bad = true;

	public function __construct()
	{
		$charData = '<b>c</b>';
		$xml = '<a>'.htmlspecialchars($charData).'</a>';

		$parser = xml_parser_create();
		xml_set_character_data_handler($parser, array($this, 'chardata'));
		$parsedOk = xml_parse($parser, $xml, true);
		$this->bad = (!$parsedOk || ($this->parsedData != $charData));
	}

	public function chardata($parser, $data)
	{
		$this->parsedData .= $data;
	}
}

/**
 * Class for compatibility checker
 */
class Compatibility_Checker
{
	private $disabled_functions;
	private $rec_mem_limit_val;
	private $rec_mem_limit_set;
	private $warnings = array();

	/**
	 * Object constructor
	 */
	public function __construct()
	{
		// Recommended memory limit
		$this->rec_mem_limit_val = (128 * 1024 * 1024);
		$this->rec_mem_limit_set = '128M';

		// Build disabled functions array
		$tmp = array();

		foreach(array('disable_functions', 'suhosin.executor.eval.blacklist', 'suhosin.executor.func.blacklist') as $var)
			$tmp = array_merge($tmp, array_map('trim', explode(',', improvedIniGet($var))));

		$this->disabled_functions = array_unique($tmp);
	}

	/**
	 * Executor for all tests
	 */
	public function run()
	{
		// Call each test to fill warnings array
		foreach(get_class_methods($this) as $method)
		{
			if(strpos($method, 'test_') === 0)
				call_user_func(array($this, $method));
		}

		// Dirty hack to remove prefix from array keys
		$tmp = array();
		foreach($this->warnings as $type => $warn)
		{
			$type = substr($type, strlen(__CLASS__.'::test_'));
			$tmp[$type] = $warn;
		}
		$this->warnings = $tmp;
	}

	/**
	 * Method to output results
	 */
	public function output()
	{
		if(isset($_SERVER['HTTP_USER_AGENT']))
			$this->output_web();
		else
			$this->output_cli();
	}

	/**
	 * Method to output results when checker is ran from the web
	 */
	private function output_web()
	{
		header('Cache-Control: no-cache');
		header('Content-Type: text/html');

		// Head
		ob_start('cleanup_crlf');
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>check_compatibility.php</title>
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<style type="text/css"><!--
body {background-color: #ffffff; color: #000000;}
body, td, th, h1, h2 {font-family: sans-serif;}
pre {margin: 0px; font-family: monospace;}
table {border-collapse: collapse;}
td, th { border: 1px; font-size: 75%; vertical-align: baseline;}
h1 {font-size: 150%;}
h2 {font-size: 125%;}
.center {text-align: center;}
.warntitle {text-align: center; margin: 2ex 0; border: 1px solid #660000; padding: 1ex 1em; background-color: #FFEEEE; color: #660000;}
.warntext {text-align: left; margin: 2ex 0; border: 1px solid #660000; padding: 1ex 1em; background-color: #FDF5E6; color: #660000;}
.success {text-align: center; margin: 2ex 0; border: 1px solid #006600; padding: 1ex 1em; background-color: #EEFFEE; color: #000000;}
//--></style>
</head>
<body>';
		ob_end_flush();

		// Body
		if(count($this->warnings))
		{
			// Errors
			echo '<div class="center"><h2>The following issues have been found, please ask your host to fix them:</h2></div>'."\r\n";
			foreach($this->warnings as $type => $warn)
			{
				echo '<table border="0" cellpadding="3" width="100%"><tr><th class="warntitle">Test: '.htmlspecialchars($type).'</th></tr>'."\r\n";

				foreach($warn as $key => $message)
				{
					echo '<tr><td class="warntext">'.htmlspecialchars($message).'</td></tr>'."\r\n";
				}

				echo '</table><br />'."\r\n";
			}
		}
		else
		{
			// Balls to you!
			echo '<div class="success"><h2>Congratulations</h2><br />No problems have been detected.<br /><br /></div>';
		}

		// Footer
		echo '</body></html>';
	}

	/**
	 * Method to output results when checker is ran from cli
	 */
	private function output_cli()
	{
		if(isset($_SERVER['REMOTE_ADDR']))
		{
			// We are not in cli it seems, lets send headers
			header('Cache-Control: no-cache');
			header('Content-Type: text/plain');
		}

		if(count($this->warnings))
		{
			// Errors
			echo 'The following issues have been found, please ask your host to fix them:'."\r\n";
			foreach($this->warnings as $type => $warn)
			{
				echo "\r\nTest: ".$type."\r\n";

				foreach($warn as $key => $message)
				{
					echo "\t".$message."\r\n";
				}
			}
		}
		else
		{
			// No problem, sir.
			echo 'Congratulations, no problems have been detected.';
		}
	}

	/**
	 * Detects if float handling is problematic
	 */
	private function test_bug_float()
	{
		$num1 = 2009010200.01;
		$num2 = 2009010200.02;

		if((string)$num1 === (string)$num2 || $num1 === $num2 || $num2 <= (string)$num1)
			$this->warnings[__METHOD__][] = 'Detected unexpected problem in handling of PHP float numbers.';
	}

	/**
	 * Test for PHP bug #50394 (PHP 5.3.x conversion to null only, not 5.2.x)
	 * @see http://bugs.php.net/bug.php?id=50394
	 */
	private function test_bug_ref_call()
	{
		$refCallBugTester = new RefCallBugTester();
		$refCallBugTester->execute();

		if($refCallBugTester->bad)
			$this->warnings[__METHOD__][] = 'A regression (bug #50394) has been detected in your PHP version. Please upgrade or downgrade your PHP installation.';
	}

	/**
	 * Check for required PHP extensions
	 */
	private function test_core_extensions()
	{
		$required_extensions = array(
			array('ctype', 'Ctype'),
			array('curl', 'cURL'),
			array('dom', 'Document Object Model'),
			array('iconv', 'Iconv'),
			array('gd', 'GD Library'),
			array('json', 'JSON'),
			array('mbstring', 'Multibyte String'),
			array('mysql', 'MySQL'),
			array('mysqli', 'MySQLi'),
			array('openssl', 'OpenSSL'),
			array('pcre', 'Perl-Compatible Regular Expressions'),
			array('reflection', 'Reflection Class'),
			array('session', 'Session'),
			array('spl', 'SPL'),
			array('xml', 'XML Parser'),
			array('zip', 'Zip'),
			array('zlib', 'Zlib'),
		);

		foreach($required_extensions as $test)
		{
			if(!extension_loaded($test[0]))
				$this->warnings[__METHOD__][] = 'The required PHP extension "'.$test[1].'" could not be found. You need to install/enable this extension.';
		}
	}

	/**
	 * Check important PHP functions
	 */
	private function test_core_functions()
	{
		// Functions available since PHP 4
		$functions_required = array('base64_decode', 'crypt', 'fpassthru', 'get_cfg_var', 'ini_get', 'ini_set', 'parse_ini_file', 'php_uname');

		// Function available since PHP 5.3
		if(version_compare(PHP_VERSION, '5.3') >= 0)
			$functions_required = array_merge($functions_required, array('parse_ini_string'));

		foreach($functions_required as $test)
		{
			if(!function_exists($test) || in_array($test, $this->disabled_functions))
				$this->warnings[__METHOD__][] = 'Function '.$test.' is required to be enabled in PHP.';
		}
	}

	/**
	 * Detects if input variables handling is problematic
	 */
	private function test_core_max_input_vars()
	{
		$tmp = improvedIniGet('max_input_vars');
		if($tmp !== FALSE && $tmp < 4096) // This setting was added in PHP 5.3.7 but some distros backported it to older PHP versions
			$this->warnings[__METHOD__][] = 'Problematic max_input_vars setting detected, please set it to 4096 or higher.';
	}

	/**
	 * Check memory limit
	 */
	private function test_core_memory_limit()
	{
		if($tmp = improvedIniGet('memory_limit'))
		{
			$tmp = trim($tmp);
			$last = strtolower($tmp[strlen($tmp)-1]);
			switch($last)
			{
				case 'g':
					$tmp *= 1024;
				case 'm':
					$tmp *= 1024;
				case 'k':
					$tmp *= 1024;
			}

			if($tmp < $this->rec_mem_limit_val)
				$this->warnings[__METHOD__][] = 'Memory Limit: '.$this->rec_mem_limit_set.' is required. Please increase this setting.';
		}
	}

	/**
	 * Check PHP configuration
	 */
	private function test_core_settings()
	{
		$php_checks = array(
			array(in_array('eval',$this->disabled_functions), 'Language construct eval is required to be enabled in PHP.'),
			array(improvedIniGet('magic_quotes_gpc') || @get_magic_quotes_gpc(), 'Setting magic_quotes_gpc has been found enabled in your php.ini. Disable it for better functionality.'),
			array(improvedIniGet('safe_mode'), 'PHP must not be running in safe_mode. Disable the PHP safe_mode setting.'),
			array(improvedIniGet('output_handler') == 'ob_gzhandler', 'PHP must not be running with output_handler set to ob_gzhandler. Disable this setting.'),
			array(improvedIniGet('zlib.output_compression'), 'PHP must not be running with zlib.output_compression enabled. Disable this setting.'),
			array(improvedIniGet('zend.ze1_compatibility_mode'), 'zend.ze1_compatibility_mode is set to On. This may cause some strange problems. It is strongly suggested to turn this value to Off.'),
		);

		foreach($php_checks as $test)
		{
			if($test[0])
				$this->warnings[__METHOD__][] = $test[1];
		}
	}

	/**
	 * Check upload dir
	 */
	private function test_core_uploads()
	{
		$upload_dir = improvedIniGet('upload_tmp_dir') ? improvedIniGet('upload_tmp_dir') : @sys_get_temp_dir();
		if(!empty($upload_dir) && !is_writable($upload_dir)) // Make sure upload dir is writable
			$this->warnings[__METHOD__][] = 'Your upload temporary directory '.$upload_dir.' is not writable. Please fix this issue.';
	}

	/**
	 * Check PHP version
	 */
	private function test_core_version()
	{
		// Check for lower than 5.2.9
		if(version_compare(PHP_VERSION, '5.2.9') < 0)
			$this->warnings[__METHOD__][] = 'PHP 5.2.9 or newer is required. '.PHP_VERSION.' does not meet this requirement.';

		// If 5.4, check for lower than 5.4.5
		elseif(version_compare(PHP_VERSION, '5.4') >= 0 && version_compare(PHP_VERSION, '5.4.5') < 0)
			$this->warnings[__METHOD__][] = 'PHP 5.4.5 or newer is required. '.PHP_VERSION.' does not meet this requirement.';

		// If 5.3, check for lower than 5.3.5
		elseif(version_compare(PHP_VERSION, '5.3') >= 0 && version_compare(PHP_VERSION, '5.3.5') < 0)
			$this->warnings[__METHOD__][] = 'PHP 5.3.5 or newer is required. '.PHP_VERSION.' does not meet this requirement.';
	}

	/**
	 * Check PHP's crypt
	 */
	private function test_crypt()
	{
		if(function_exists('crypt'))
		{
			$hash = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
			$test = crypt('password', $hash);

			if($test !== $hash)
				$this->warnings[__METHOD__][] = 'A problem has been found in crypt()\'s Blowfish functionality. Please upgrade to PHP 5.3.7 or higher.';
		}
	}

	/**
	 * Check cURL
	 */
	private function test_ext_curl()
	{
		if(extension_loaded('curl'))
		{
			$curlFound = 'The required PHP extension "cURL" was found, but ';

			// Some hosts have cURL but disable its functions. Lets check for that.
			$curlFuctions = array(
				'curl_close', 'curl_copy_handle', 'curl_errno', 'curl_error', 'curl_exec', 'curl_getinfo',
				'curl_init', 'curl_multi_add_handle', 'curl_multi_close', 'curl_multi_exec', 'curl_multi_getcontent',
				'curl_multi_info_read', 'curl_multi_init', 'curl_multi_remove_handle', 'curl_multi_select',
				'curl_setopt_array', 'curl_setopt', 'curl_version',
				);
			foreach($curlFuctions as $test)
				if(!function_exists($test) || in_array($test, $this->disabled_functions))
					$this->warnings[__METHOD__][] = $curlFound.'function '.$test.' is disabled. Please enable it.';

			// We need SSL and ZLIB support
			if($curlVersion = @curl_version())
			{
				$curlBitFields = array('CURL_VERSION_SSL', 'CURL_VERSION_LIBZ');
				$curlBitFriendly = array('SSL', 'ZLIB');

				foreach($curlBitFields as $arr_key => $feature)
				{
					$test = $curlBitFriendly[$arr_key];
					if(!($curlVersion['features'] && constant($feature)))
						$this->warnings[__METHOD__][] = $curlFound.$test.' support is missing. Please add support for '.$test.' in cURL.';
				}
			}
		}
	}

	/**
	 * Detect if timezone handling is problematic
	 */
	private function test_ext_date()
	{
		// check if date is loaded and DateTimeZone class exists
		if(extension_loaded('date') && class_exists('DateTimeZone'))
		{
			$error_message = 'Invalid or empty date.timezone setting detected.';

			if($tmp = improvedIniGet('date.timezone'))
			{
				try
				{
					$tz = new DateTimeZone($tmp);
				}
				catch(Exception $e)
				{
					// timezone is invalid
					$this->warnings[__METHOD__][] = $error_message;
				}
			}
			else
			{
				// timezone is empty
				$this->warnings[__METHOD__][] = $error_message;
			}
		}
	}

	/**
	 * Check eAccelerator version
	 */
	private function test_ext_eaccelerator()
	{
		if(function_exists('eaccelerator_info'))
		{
			/*
			$ea_info = eaccelerator_info();

			if(version_compare($ea_info['version'], '0.9.9') <= 0)
			{
				// Only 1.0-dev are known to work
				$this->warnings[__METHOD__][] = 'You have an old version of eAccelerator (earlier than 1.0) which is known to cause problems.';
			}
			*/
			$this->warnings[__METHOD__][] = 'You appear to be using the eAccelerator. Be aware that it has limitations which cause problems. Switching to another PHP optimizer such as Zend OPcache is recommended.';
		}
	}

	/**
	 * Check GD
	 */
	private function test_ext_gd()
	{
		if(function_exists('gd_info'))
		{
			$gdFound = 'The required PHP extension "GD Library" was found, but ';

			// We need GIF, JPEG and PNG support
			$required_gd = array(
				array('imagecreatefromgif', 'GIF'),
				array('imagecreatefromjpeg', 'JPEG'),
				array('imagecreatefrompng', 'PNG'),
			);

			foreach($required_gd as $test)
				if(!function_exists($test[0]))
					$this->warnings[__METHOD__][] = $gdFound.$test[1].' support is missing. Please add support for '.$test[1].' images in GD Library.';

			// We need GD 2 and freetype support
			$gdInfo = @gd_info();

			if(@$gdInfo["GD Version"] && !strstr($gdInfo["GD Version"], '2.'))
				$this->warnings[__METHOD__][] = $gdFound.'GD Version is older than v2. Please fix this issue.';

			if(!@$gdInfo['FreeType Support'])
				$this->warnings[__METHOD__][] = $gdFound.'FreeType support is missing. Please add support for this.';
		}
	}

	/**
	 * Detect if mbstring configuration is problematic
	 */
	private function test_ext_mbstring()
	{
		if(extension_loaded('mbstring') && improvedIniGet('mbstring.func_overload'))
			$this->warnings[__METHOD__][] = 'Extension Multibyte String may break data when mbstring.func_overload setting is enabled. Please disable this setting.';
	}

	/**
	 * Check MySQL version
	 */
	private function test_ext_mysql()
	{
		global $CONFIG;

		if($CONFIG['mysql_enabled'])
		{
			// Just to be sure
			$mysql_port = (int)$CONFIG['mysql_port'];

			if(function_exists('mysqli_connect'))
			{
				$mysqli = @mysqli_connect($CONFIG['mysql_host'], $CONFIG['mysql_user'], $CONFIG['mysql_pass'], '', $mysql_port);

				if(!$mysqli)
				{
					$this->warnings[__METHOD__][] = 'Unable to connect to MySQLi: '.mysqli_connect_error();
				}
				else
				{
					$client_version = mySqlVersionStringToInt(mysqli_get_client_info());
					$server_version = mySqlVersionStringToInt(mysqli_get_server_info($mysqli));
					mysqli_close($mysqli);
				}
			}
			elseif(function_exists('mysql_connect'))
			{
				$mysql_host = $CONFIG['mysql_host'].':'.$mysql_port;
				$mysql = @mysql_connect($mysql_host, $CONFIG['mysql_user'], $CONFIG['mysql_pass']);

				if(!$mysql)
				{
					$this->warnings[__METHOD__][] = 'Unable to connect to MySQL: '.mysql_error();
				}
				else
				{
					$client_version = mySqlVersionStringToInt(mysql_get_client_info());
					$server_version = mySqlVersionStringToInt(mysql_get_server_info($mysql));
					mysql_close($mysql);
				}
			}

			if(isset($server_version) && isset($client_version))
			{
				if($server_version < 50100)
					$this->warnings[__METHOD__][] = 'You are running MySQL '.mySqlVersionIntToString($server_version).'. We recommend upgrading to at least MySQL 5.1.';

				if(intAbs($server_version - $client_version) >= 1000)
					$this->warnings[__METHOD__][] = 'Your PHP MySQL library version ('.mySqlVersionIntToString($client_version).') does not match MySQL Server version ('.mySqlVersionIntToString($server_version).').';
			}
		}
	}

	/**
	 * Check session and its settings
	 */
	private function test_ext_session()
	{
		global $CONFIG;

		if(extension_loaded('session'))
		{
			$sessionFound = 'The required PHP extension "Session" was found, but ';

			$session_path = @session_save_path();
			if(FALSE !== ($tmp = strpos($session_path, ';'))) // http://www.php.net/manual/en/function.session-save-path.php#50355
				$session_path = substr($session_path, $tmp+1);
			if(!empty($session_path) && !is_writable($session_path)) // Make sure session path is writable
				$this->warnings[__METHOD__][] = $sessionFound.'your session path '.$session_path.' is not writable. Please fix this issue.';

			if(improvedIniGet('session.use_trans_sid'))
				$this->warnings[__METHOD__][] = $sessionFound.'session.use_trans_sid is enabled. Please disable this setting for security reasons.';

			if(!improvedIniGet('session.use_only_cookies'))
				$this->warnings[__METHOD__][] = $sessionFound.'session.use_only_cookies is disabled. Please enable this setting for security reasons.';

			if($CONFIG['session_enable_extended_check'])
			{
				if(improvedIniGet('session.hash_bits_per_character') < 5)
					$this->warnings[__METHOD__][] = $sessionFound.'session.hash_bits_per_character is set to a low value. Please set it to 5 for security reasons.';

				if(!improvedIniGet('session.hash_function'))
					$this->warnings[__METHOD__][] = $sessionFound.'session.hash_function is set to a weak value. Please set it to 1 for security reasons.';
			}
		}
	}

	/**
	 * Check suhosin settings
	 */
	private function test_ext_suhosin()
	{
		if(extension_loaded('suhosin'))
		{
			// Value has to be false or zero to pass tests
			$test_false = array(
				'suhosin.executor.disable_eval',
				'suhosin.mail.protect',
				'suhosin.sql.bailout_on_error',
				'suhosin.cookie.encrypt',
				'suhosin.session.encrypt'
			);

			// Value has to be the same or higher to pass tests
			$test_values = array(
				array('suhosin.cookie.max_name_length', 64),
				array('suhosin.cookie.max_totalname_length', 256),
				array('suhosin.cookie.max_value_length', 10000),
				array('suhosin.get.max_name_length', 512),
				array('suhosin.get.max_totalname_length', 512),
				array('suhosin.get.max_value_length', 2048),
				array('suhosin.post.max_array_index_length', 256),
				array('suhosin.post.max_name_length', 512),
				array('suhosin.post.max_totalname_length', 8192),
				array('suhosin.post.max_vars', 4096),
				array('suhosin.post.max_value_length', 1000000),
				array('suhosin.request.max_array_index_length', 256),
				array('suhosin.request.max_totalname_length', 8192),
				array('suhosin.request.max_vars', 4096),
				array('suhosin.request.max_value_length', 1000000),
				array('suhosin.request.max_varname_length', 512)
			);

			// Value has to be zero (protection disabled), equal or higher than x to pass
			$test_zero_or_higher_than_value = array(
				array('suhosin.executor.max_depth', 10000),
				array('suhosin.executor.include.max_traversal', 6),
				array('suhosin.memory_limit', $this->rec_mem_limit_val)
			);

			foreach($test_false as $test)
			{
				if(improvedIniGet($test))
					$this->warnings[__METHOD__][] = $test.' is required to be set to '.(($test == 'suhosin.mail.protect') ? '0 (Zero)' : 'Off').'in php.ini. Your server does not meet this requirement.';
			}

			foreach($test_values as $test)
			{
				if(isset($test[0]) && isset($test[1]))
				{
					if(improvedIniGet($test[0]) < $test[1])
						$this->warnings[__METHOD__][] = 'It is required that '.$test[0].' is set to '.$test[1].' or higher.';
				}
			}

			foreach($test_zero_or_higher_than_value as $test)
			{
				if($tmp = improvedIniGet($test[0]))
				{
					if($test[0] == 'suhosin.memory_limit')
					{
						$last = strtolower($tmp[strlen($tmp)-1]);
						switch($last)
						{
							case 'g':
								$tmp *= 1024;
							case 'm':
								$tmp *= 1024;
							case 'k':
								$tmp *= 1024;
						}
					}

					if($tmp < $test[1])
						$this->warnings[__METHOD__][] = 'It is required that '.$test[0].' is set to either 0 (Zero), '.(($test[0] == 'suhosin.memory_limit') ? $this->rec_mem_limit_set : $test[1]).' or higher.';
				}
			}
		}
	}

	/**
	 * Test for PHP+libxml2 bug which breaks XML input subtly with certain versions.
	 * Known fixed with PHP 5.2.9 + libxml2-2.7.3
	 * @see http://bugs.php.net/bug.php?id=45996
	 */
	private function test_ext_xml()
	{
		if(extension_loaded('xml'))
		{
			$xmlBugTester = new XmlBugTester();

			if($xmlBugTester->bad)
				$this->warnings[__METHOD__][] = 'A bug has been detected in PHP+libxml2 which breaks XML input.';
		}
	}

	/**
	 * Check Ioncube Loader version
	 */
	private function test_loader_ioncube()
	{
		if(extension_loaded('ionCube Loader') && function_exists('ioncube_loader_version'))
		{
			if(!function_exists('ioncube_loader_iversion'))
				$this->warnings[__METHOD__][] = 'You have a VERY old version of IonCube Loader which is known to cause problems.';

			elseif(ioncube_loader_iversion() < 40400 && version_compare(PHP_VERSION, '5.4') >= 0)
				$this->warnings[__METHOD__][] = 'You have an old version of IonCube Loader (earlier than 4.4.0) which is known to cause problems with PHP 5.4.';

			elseif(ioncube_loader_iversion() < 40007)
				$this->warnings[__METHOD__][] = 'You have an old version of IonCube Loader (earlier than 4.0.7) which is known to cause problems with PHP scripts.';
		}
		else
		{
			$this->warnings[__METHOD__][] = 'You do not seem to have IonCube Loader installed.';
		}
	}

	/**
	 * Check NuSphere PhpExpress version
	 */
	private function test_loader_phpexpress()
	{
		if(extension_loaded('NuSphere PhpExpress') && function_exists('phpexpress'))
		{
			// Fetch NuSphere PhpExpress information
			ob_start();
			@phpexpress();
			$content = ob_get_clean();

			// Attempt to find version offset
			if(FALSE !== ($tmp = strpos($content, 'Version ')))
			{
				$tmp += strlen('Version ');

				// Build version string
				$version = '';
				for($i = $tmp; $i < ($tmp+10); $i++)
				{
					if($content[$i] == '<')
					{
						// If we reach this, it means version is set
						if(version_compare($version, '3.0.7') < 0)
							$this->warnings[__METHOD__][] = 'You have an old version of NuSphere PhpExpress (earlier than 3.0.7) which is known to cause problems with PHP scripts.';

						break;
					}
					$version .= $content[$i];
				}
			}
		}
	}

	/**
	 * Check Zend Optimizer version
	 */
	private function test_loader_zend()
	{
		if(extension_loaded('Zend Optimizer') && function_exists('zend_optimizer_version'))
		{
			if(version_compare(zend_optimizer_version(), '3.3.3') < 0)
				$this->warnings[__METHOD__][] = 'You have an old version of Zend Optimizer (earlier than 3.3.3) which is known to cause problems with PHP scripts.';
		}
	}
}

// Build and call compatibility checker
$compatibility_checker = new Compatibility_Checker();
$compatibility_checker->run();

// Output compatibility checker results
$compatibility_checker->output();
?>