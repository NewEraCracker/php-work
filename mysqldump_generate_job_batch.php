<?php
// Stop unwanted access
if(PHP_SAPI != 'cli'){ exit(); }

// Define MySQL database root
define('DB_BIN_PATH', 'C:/server/bin/mysql/bin');
define('DB_FILES_ROOT', 'C:/server/bin/mysql/data');
define('DB_ROOT_PASS', '****');
define('DB_BAK_PATH', 'C:/Users/Jorge/Desktop');

$dh = opendir(DB_FILES_ROOT);

while((false !== ($file = readdir($dh))) && $dh)
{
	if(!in_array($file, array('.', '..', 'mysql', 'performance_schema')))
	{
		$path = realpath(DB_FILES_ROOT.'/'.$file);
		if( is_dir($path) )
		{
			echo DB_BIN_PATH.'/mysqldump.exe -u root -p'.escapeshellarg(DB_ROOT_PASS).' '.escapeshellarg($file).' > '.escapeshellarg(DB_BAK_PATH.'/'.$file.'.sql')."\n";
		}
	}
}

closedir($dh);