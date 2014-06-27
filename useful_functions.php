<?php
/*
 Author: NewEraCracker
 License: Public Domain
*/

/** Array with the paths a dir contains */
function readdir_recursive($dir, $show_dirs=false)
{
	return explode("\n", readdir_recursive_string($dir, $show_dirs));
}

/** String with the paths a dir contains */
function readdir_recursive_string($dir, $show_dirs=false, $dir_len=null)
{
	// Be sure about dir
	$dir = realpath($dir);

	// Calculate the length if we don't know it
	if($dir_len == null) $dir_len = strlen($dir);

	// Fun begins
	$ok = ob_start(null);
	if( !$ok )
	{
		trigger_error('Unable to start output buffering', E_USER_WARNING);
		return '';
	}

	// Open the dir
	$dh = opendir($dir);

	// Read the dir
	while( $dh && (false !== ($file = readdir($dh))) )
	{
		if( $file != '.' && $file != '..')
		{
			$file = realpath($dir.'/'.$file);
			if( is_dir($file) )
			{
				// If $show_dirs is true, dir names will also be listed
				if( $show_dirs )
				{
					echo "\n".substr($file, $dir_len);
				}

				// Read recursively another dir below the original dir
				// We pass $dir_len here so the path is relative to the 'mother' dir
				echo "\n".readdir_recursive_string($file, $show_dirs, $dir_len);
			}
			else
			{
				// Just echo the filename
				echo "\n".substr($file, $dir_len);
			}
		}
	}

	// Get and return our result
	return ltrim(ob_get_clean());
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