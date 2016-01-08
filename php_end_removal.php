<?php
/**
 * Author: NewEraCracker
 * License: Public Domain
 * Version: 2016.0108.4
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

/** Normalize a PHP file by trimming extra whitespace/tags and changing EOL to LF */
function normalize_php_file($file, $remove_close_tag = true)
{
	// Check if we can work with the file
	if( is_file($file) && is_readable($file) && is_writable($file) )
	{
		// Grab content
		$size = filesize($file);
		$new  = ($size ? file_get_contents($file) : false);

		// Check if content was grabbed
		if($size && $new)
		{
			// Convert line endings to LF and remove WS before EOF
			$new = str_replace("\r\n", "\n", $new);
			$new = str_replace("\r", "\n", $new);
			$new = rtrim($new);
			$len = strlen($new);

			// Count the number of opening tags for full & short types
			$php_tags_no   = substr_count($new, '<?php');
			$short_tags_no = substr_count($new, '<?') - $php_tags_no;

			// Has ending tag? If not, we simply don't care
			// Important! Be very strict when checking offset! Keep this line where it is!
			if(substr($new, -2) !== '?>')
				return;

			// Dynamic fix depending if file is (almost) pure PHP code or not
			// Important! File must not have short tags neither have more than one opening tag!
			if($remove_close_tag && $short_tags_no == 0 && $php_tags_no == 1)
			{
				// Remove ending tag, trailing WS and insert final newline
				$new = rtrim(substr($new, 0, -2))."\n";
				$len = strlen($new);
			}

			// Write the file if the size changes
			if($len > 0 && $len < $size)
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