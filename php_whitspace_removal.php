<?php
/**
 * Author: NewEraCracker
 * License: Public Domain
 */

# Basic configuration
$path = './';
$php_types = array('php','inc');

# Functions

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

/** Normalize a text file by trimming extra whitespace */
function normalize_php_file($file)
{
	// Check if we can work with the file
	if( is_file($file) && is_readable($file) && is_writable($file) )
	{
		// Grab content
		$length  = filesize($file);
		$content = ($length ? file($file) : false);
		$lineno  = (is_array($content) ? count($content) : false);

		// Check if content was grabbed
		if($lineno)
		{
			// Remove WS after closing PHP tag
			if(strpos($content[$lineno-1], '?>') !== false)
			{
				$content[$lineno-1] = rtrim($content[$lineno-1]);
			}

			$new = implode($content);

			// Write the file if the size changes
			if(strlen($new) < $length)
			{
				file_put_contents($file, $new);
			}
		}
	}
}

# Main

// Check
if( !is_dir($path) || !is_readable($path) || !is_writable($path) )
	exit($path.' is invalid');

// Fix
$path = rtrim($path, '/');

// Walk
foreach(readdir_recursive($path) as $f)
{
	// Grab extension
	$ext = pathinfo($f,PATHINFO_EXTENSION);

	// Normalize PHP files
	if(in_array($ext, $php_types))
	{
		normalize_php_file($f);
	}
}
?>