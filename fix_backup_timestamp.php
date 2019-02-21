<?php
/*
 Author: NewEraCracker
 License: Public Domain
*/
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
	
	$original_path = 'D:';
	$backup_path = 'F:';

	$original_len  = strlen($original_path);
	$original_list = readdir_recursive($original_path);

	if(!file_exists($original_path) || !file_exists($backup_path))
	{
		echo 'Do not be a dumbass !'.PHP_EOL;
		exit(1);
	}

	foreach($original_list as $filename)
	{
		$filename_time  = filemtime($filename);
		$backup_filename = $backup_path . substr($filename, $original_len);

		if($filename_time > 0 && file_exists($backup_filename) && is_readable($backup_filename) && is_writable($backup_filename))
		{
			if(filemtime($backup_filename) != $filename_time)
			{
				$file_md5    = md5_file($filename);
				$backup_md5  = md5_file($backup_filename);
				$file_sha1   = sha1_file($filename);
				$backup_sha1 = sha1_file($backup_filename);

				if($file_md5 == $backup_md5 && $file_sha1 == $backup_sha1) {
					touch($filename, $filename_time, $filename_time);
					touch($backup_filename, $filename_time, $filename_time);
					echo 'Fixing '.$filename.PHP_EOL;
					echo '> '.$backup_filename.PHP_EOL;
				}
			}
		}
	}

	exit(0);