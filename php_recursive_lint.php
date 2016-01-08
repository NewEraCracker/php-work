<?php
/**
 * Author: NewEraCracker
 * License: Public Domain
 * Version: 2016.0108.1
 */

# Basic configuration
define('PHP_EXE_PATH', 'C:/WherePHPis/php.exe');
$path = './';
$php_types = array('php','inc');

/** Array with the paths a dir contains */
function readdir_recursive($dir='.', $show_dirs=false, $ignored=array())
{
	// Set types for stack and return value
	$stack = $result = array();

	// Initialize stack
	$stack[] = $dir;

	// Pop the first element of stack and evaluate it (do this until stack is fully empty)
	while($dir = array_shift($stack))
	{
		$dh = opendir($dir);
		while($dh && (false !== ($path = readdir($dh))))
		{
			if($path != '.' && $path != '..')
			{
				// Prepend dir to current path
				$path = $dir.'/'.$path;

				if(is_dir($path))
				{
					// Check ignored dirs
					if(is_array($ignored) && count($ignored) && in_array($path.'/', $ignored))
						continue;

					// Add dir to stack for reading
					$stack[] = $path;

					// If $show_dirs is true, add dir path to result
					if($show_dirs)
						$result[] = $path;
				}
				elseif(is_file($path))
				{
					// Check ignored files
					if(is_array($ignored) && count($ignored) && in_array($path, $ignored))
						continue;

					// Add file path to result
					$result[] = $path;
				}
			}
		}
		closedir($dh);
	}

	// Sort the array using simple ordering
	sort($result);

	// Now we can return it
	return $result;
}

if(isset($_SERVER['REQUEST_METHOD']) || !isset($_SERVER['argc']) || !isset($_SERVER['argv']))
{
	// We are not in CLI - Send header to set our content as plain text
	header('Content-Type: text/plain');
}

// Check
if(!is_executable(PHP_EXE_PATH))
	exit('PHP executable not found.');

if(!is_dir($path) || !is_readable($path) || !is_writable($path))
	exit($path.' is invalid');

// Fix
$path = rtrim($path, '/');

foreach(readdir_recursive($path) as $file)
{
	// Grab extension
	$ext = pathinfo($file, PATHINFO_EXTENSION);

	// Check and execute lint
	if(in_array($ext, $php_types))
	{
		passthru(escapeshellarg(PHP_EXE_PATH).' -n -l '.escapeshellarg($file));
	}
}
?>