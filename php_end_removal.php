<?php
/**
 * Author: NewEraCracker
 * License: Public Domain
 * Version: 2016.0204.1
 *
 * Will remove PHP ending tag if code conforms certain specifications.
 *
 * https://secure.php.net/manual/en/language.basic-syntax.phptags.php
 * https://secure.php.net/manual/en/language.basic-syntax.instruction-separation.php
 *
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
	if(!is_file($file) || !is_readable($file) || !is_writable($file))
	{
		return false;
	}

	// Grab content and trim ending whitespace
	$contents = rtrim(file_get_contents($file));
	$stripped = rtrim(php_strip_whitespace($file));

	// Check if content was grabbed
	if(!$contents || !$stripped)
	{
		return false;
	}

	// Verify both strings have begin and ending tags. Ignore otherwise.
	// Important! Be very strict when checking offset! Keep this line where it is!
	if(substr($stripped, 0, 5) !== '<?php' || substr($stripped, -2) !== '?>' ||
	   substr($contents, 0, 5) !== '<?php' || substr($contents, -2) !== '?>')
	{
		return false;
	}

	// Convert line endings to LF
	$contents = str_replace("\r", "\n", str_replace("\r\n", "\n", $contents));

	// Count the number of opening tags for full & short types.
	// We'll count from contents to ensure we get accurate values.
	$php_tags_no   = substr_count($contents, '<?php');
	$short_tags_no = substr_count($contents, '<?') - $php_tags_no;

	// Dynamic fix depending if file is pure PHP code or not.
	// Important! File must not have short tags neither have more than one opening tag!
	if($remove_close_tag && $short_tags_no == 0 && $php_tags_no == 1)
	{
		$stripped     = rtrim(substr($stripped, 0, -2));
		$stripped_len = strlen($stripped);

		// Test the stripped file validity without the tag
		if($stripped[$stripped_len-1] === ';' || $stripped[$stripped_len-1] === '}')
		{
			// Ending tag removal is safe.
			// Act on the contents themselves and add a final EOL.
			$contents = rtrim(substr($contents, 0, -2))."\n";
		}
	}

	// Write the new file
	return file_put_contents($file, $contents);
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