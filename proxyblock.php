<?php
/*
	--------------------
	Proxy Block Script
	--------------------

	Created by NewEraCracker
	Date    2013/08/04
	Version 1.1.0

	Requirements:
	= PHP 5.2 or higher
	= MySQL 5 or higher

	License: CC BY-SA 3.0
*/

function check_proxy()
{
	/*---------------------
	 * Configuration start
	 *--------------------*/

	// Database information
	$db_hostname  = 'localhost';
	$db_database  = 'proxydb';
	$db_username  = 'username';
	$db_password  = 'password';
	$db_installed = false; // change to true after executing 1st time

	// Ports to check
	$check_ports = true;
	$ports = array(3128,8080);

	// Proxy headers
	$check_headers = true;
	$headers = array('HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED', 'HTTP_CLIENT_IP', 'HTTP_FORWARDED_FOR_IP', 'VIA', 'X_FORWARDED_FOR', 'FORWARDED_FOR', 'X_FORWARDED', 'FORWARDED', 'CLIENT_IP', 'FORWARDED_FOR_IP', 'HTTP_PROXY_CONNECTION');

	// Banned
	$banned_ips = array('193.200.150.');
	$banned_useragents = array();

	// Allowed
	$allowed_ips = array('127.0.0.');
	$allowed_useragents = array('Googlebot','msnbot','Slurp');

	// Notes:
	// You are able to ban/allow an IP range such as 1.0.0.0 -> 1.0.0.255
	// by banning/allowing the IP "1.0.0."

	/*---------------------
	 * Configuration end
	 *--------------------*/

	// Init
	error_reporting(0);
	$userip    = (string) $_SERVER['REMOTE_ADDR'];
	$useragent = (string) $_SERVER['HTTP_USER_AGENT'];
	$proxy     = false;

	// Fix configuration
	if(!$check_ports)
		$ports = array();

	if(!$check_headers)
		$headers = array();

	// Ban certain IPs
	if( count($banned_ips) )
	{
		foreach($banned_ips as $ip)
		{
			$test = strpos($userip,$ip);

			if($test !== false && $test == 0)
				return true;
		}
		unset($ip);
	}

	// Ban certain User-Agents
	if( count($banned_useragents) )
	{
		foreach($banned_useragents as $ua)
		{
			$test = strpos($useragent,$ua);

			if($test !== false)
				return true;
		}
		unset($ua);
	}

	// Allow certain IPs
	if( count($allowed_ips) )
	{
		foreach($allowed_ips as $ip)
		{
			$test = strpos($userip,$ip);

			if($test !== false && $test == 0)
				return false;
		}
		unset($ip);
	}

	// Allow certain User-Agents
	if( count($allowed_useragents) )
	{
		foreach($allowed_useragents as $ua)
		{
			$test = strpos($useragent,$ua);

			if($test !== false)
				return false;
		}
		unset($ua);
	}

	// Check for proxy
	if( count($ports) || count($headers) )
	{	
		// Connect and select database
		$db_link = mysql_connect($db_hostname,$db_username,$db_password) or die(mysql_error());
		mysql_select_db($db_database) or die(mysql_error());

		$db_setup = 'CREATE TABLE IF NOT EXISTS `proxyblock` ( `ip` varchar(40) CHARACTER SET latin1 NOT NULL, `proxy` tinyint(1) unsigned NOT NULL, `time` DATETIME NOT NULL, UNIQUE KEY `ip` (`ip`) ) ENGINE=MyISAM DEFAULT CHARSET=latin1;';
		$db_query = sprintf( "SELECT * FROM `proxyblock` WHERE `ip`='%s'",mysql_real_escape_string($userip) );

		// To select records created in the last 30 minutes
		$db_query .= " AND `time` > DATE_SUB( NOW(), INTERVAL 30 MINUTE)";

		// Has database been initialized?
		if( !$db_installed )
			mysql_query($db_setup) or die(mysql_error());

		// Now query for the IP address
		$db_result = mysql_query($db_query) or die(mysql_error());

		// Have we found it?
		while ($row = mysql_fetch_assoc($db_result))
		{
			// No need for a port scan or check for headers here
			return $row['proxy'];
		}

		// Check for proxy headers
		if( count($headers) )
		{
			foreach ($headers as $header)
			{
				if( isset($_SERVER[$header]) )
				{
					$proxy = true;
					break;
				}
			}
		}

		// Do a port scan
		if( !$proxy && count($ports) )
		{
			foreach($ports as $port)
			{
				if($test = @fsockopen($userip,$port,$errno,$errstr,0.5))
				{
					fclose($test);
					$proxy = true;
					break;
				}
			}
		}

		// Delete older result and insert new
		$proxy = intval($proxy);
		$db_delete_ip = sprintf( "DELETE FROM `proxyblock` WHERE `ip`='%s'",mysql_real_escape_string($userip) );
		$db_insert_ip = sprintf( "INSERT INTO `proxyblock` VALUES ('%s','{$proxy}',NOW())",mysql_real_escape_string($userip) );
		mysql_query($db_delete_ip) or die(mysql_error());
		mysql_query($db_insert_ip) or die(mysql_error());
	}

	// Return result
	return $proxy;
}

if( check_proxy() )
{
	die("<title>403: Forbidden</title>Oops... A proxy");
}
?>