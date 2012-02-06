<?php
/* Author: NewEraCracker
 * License: Public Domain
 */

function readdir_recursive($dir)
{
	return explode("\r\n", readdir_recursive_string($dir));
}

function readdir_recursive_string($dir, $dir_len=null)
{
	// Be sure about dir
	$dir = realpath($dir);

	// Calculate the length if we don't know it
	if($dir_len == null) $dir_len = strlen($dir);

	// Fun begins
	ob_start();

	// Open the dir
	$dh = opendir($dir);

	// Read the dir
	while ( ( false !== ( $file = readdir($dh) ) ) && $dh )
	{
		if( $file != '.' && $file != '..')
		{
			$file = realpath($dir."/".$file);
			if( is_dir($file) )
			{
				// Read recursively another dir below the original dir
				// We pass $dir_len here so the path is relative to the 'mother' dir
				echo readdir_recursive_string($file, $dir_len);
			}
			else
			{
				// Just echo the filename
				echo "\r\n".substr($file, $dir_len);
			}
		}
	}

	// Get the result
	$contents = ob_get_contents();

	// Clean the buffer
	ob_end_clean();

	// Return our result
	return ltrim($contents);
}
?>