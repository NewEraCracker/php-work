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

/** Normalize a PHP file by trimming extra whitespace/tags and changing EOL to LF */
function normalize_php_file($file, $remove_close_tag = true)
{
	// Check if we can work with the file
	if( is_file($file) && is_readable($file) && is_writable($file) )
	{
		// Grab content
		$len = filesize($file);
		$new = ($len ? file_get_contents($file) : false);

		// Check if content was grabbed
		if($len && $new)
		{
			// Convert line endings to LF
			$new = str_replace("\r\n", "\n", $new);
			$new = str_replace("\r", "\n", $new);

			// Remove WS before EOF
			$new = rtrim($new);

			// Count the number of opening tags
			$pno = substr_count($new, '<?');

			// Convert to an array of lines and count them
			$new = explode("\n", $new);
			$lno = count($new);

			// Has ending tag? If not, we simply don't care
			if(strpos($new[$lno-1], '?>') === false)
				return;

			// Dynamic fix depending if file is pure PHP code or not
			// Important! Always confirm the line ONLY contains the closing tag and nothing else!
			if($remove_close_tag && $pno == 1 && $new[$lno-1] === '?>')
			{
				// Remove ending tag
				$lno = $lno-1;
				$new[$lno] = '';
				unset($new[$lno]);

				// Implode, remove trailing WS and insert final newline
				$new = rtrim(implode("\n", $new))."\n";
			} else {
				// Implode and keep no final newline
				$new = implode("\n", $new);
			}

			// Write the file if the size changes
			if(strlen($new) > 0 && strlen($new) < $len)
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