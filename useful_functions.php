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
				echo "\r\n".readdir_recursive_string($file, $dir_len);
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

function normalize_text_file($f)
{
	// Bail out on error
	if( !is_file($f) || !is_readable($f) || !is_writable($f) )
		return;

	// Grab contents and normalize them
	$c = rtrim(convert_to_crlf(file_get_contents($f)));

	// Explode the normalized contents
	$c = explode("\r\n",$c);

	// Build new contents while normalizing them
	$new = '';
	for($i=0;$i<count($c);$i++)
		$new .= rtrim($c[$i])."\r\n";
	$new = rtrim($new);

	// Write the new contents to the file
	file_put_contents($f,$new);
}

function convert_to_crlf($text)
{
	// First, we take care of CRLF itself
	$text = str_replace("\r\n","\n",$text);

	// Then, we take care of any CR left
	$text = str_replace("\r","\n",$text);

	// Finally, we convert the LF back to CRLF
	$text = str_replace("\n","\r\n",$text);

	return $text;
}
?>