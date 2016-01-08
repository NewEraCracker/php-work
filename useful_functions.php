<?php
/*
 Author: NewEraCracker
 License: Public Domain
*/

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
function normalize_text_file($f)
{
	// Check if we can work with the file
	if( is_file($f) && is_readable($f) && is_writable($f) )
	{
		// Grab content
		$content = file($f);

		// Check if content was grabbed
		if(count($content))
		{
			// Detect line ending (Windows CRLF or Unix LF)
			$eol = ( (strpos($content[0], "\r\n") !== false) ? "\r\n" : "\n" );

			$new = '';

			// Build new content
			foreach($content as $line)
			{
				$new .= rtrim($line).$eol;
			}

			$new = trim($new);

			// Some files may have intentional whitespace so we'll keep it
			if(strlen($new) > 0)
			{
				// Write the new content
				file_put_contents($f,$new);
			}
		}
	}
}
?>