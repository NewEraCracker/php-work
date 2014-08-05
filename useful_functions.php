<?php
/*
 Author: NewEraCracker
 License: Public Domain
*/

/** Array with the paths a dir contains */
function readdir_recursive($dir='.', $show_dirs=false)
{
	return explode("\n", ltrim(readdir_recursive_string($dir, $show_dirs)));
}

/** String with the paths a dir contains */
function readdir_recursive_string($dir='.', $show_dirs=false)
{
	// Fun begins
	$ok = ob_start(null);
	if( !$ok )
	{
		trigger_error('Unable to start output buffering', E_USER_WARNING);
		return '';
	}

	// Open and read dir
	$dh = opendir($dir);
	while( $dh && (false !== ($path = readdir($dh))) )
	{
		if( $path != '.' && $path != '..')
		{
			$path = $dir.'/'.$path;
			if( is_dir($path) )
			{
				// If $show_dirs is true, dir names will also be listed
				if( $show_dirs )
				{
					echo "\n".$path;
				}

				// Read recursively another dir below the original dir
				echo readdir_recursive_string($path, $show_dirs);
			}
			elseif( is_file($path) )
			{
				// Echo filename
				echo "\n".$path;
			}
		}
	}
	closedir($dh);

	// Get and return our result
	return ob_get_clean();
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