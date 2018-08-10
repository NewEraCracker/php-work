<?php
/*
 Author: NewEraCracker
 License: Public Domain
*/

function blob2dat($fn_read, $fn_write)
{
	if(!file_exists($fn_read) || file_exists($fn_write) || !is_readable($fn_read))
		return 0;

	$count = 0;
	$mtime = filemtime($fn_read);

	$fp_write = fopen($fn_write /*'uc_intel.dat'*/, 'wb');

	if(!$fp_write) {
		return 0;
	}

	$fp_read = fopen($fn_read /*'uc_intel.bin'*/, 'rb');

	do {
		$bytes = fread($fp_read, 4);

		if(!strlen($bytes))
			break;

		$text = '0x' . bin2hex($bytes[3] . $bytes[2]) . bin2hex($bytes[1] . $bytes[0]) . ',';

		$count++;

		if($count % 4) {
			$text .= ' ';
		} else {
			$text .= "\n";
		}

		fwrite($fp_write, $text);
		fflush($fp_write);
	}
	while (!feof($fp_read));
	
	fclose($fp_read);
	fclose($fp_write);

	touch($fn_write, $mtime, $mtime);

	return $count;
}
function dat2blob($fn_read, $fn_write)
{
	if(!file_exists($fn_read) || file_exists($fn_write) || !is_readable($fn_read))
		return 0;

	$count = 0;
	$mtime = filemtime($fn_read);

	$fp_write = fopen($fn_write /*'uc_intel.bin'*/, 'wb');

	if(!$fp_write) {
		return 0;
	}

	$fl_read = @file($fn_read /*'uc_intel.dat'*/, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	foreach($fl_read as $l)
	{
		if($l[0] == '/')
			continue;

		$wrt = '';
		$hex = array_map('trim', explode(',', $l));

		foreach($hex as $h) {
			$bytes = str_split($h, 2);
			$hex0x = array_shift($bytes);
			
			if($hex0x == '0x') {
				for($i = count($bytes) - 1; $i >= 0; $i--) {
					$wrt .= pack('H*' , $bytes[$i]);
				}
				$count++;
			}
		}
		
		fwrite($fp_write, $wrt);
		fflush($fp_write);
	}

	fclose($fp_write);

	touch($fn_write, $mtime, $mtime);

	return $count;
}

($_SERVER['argc'] == 3) || die('Usage: ' . $_SERVER['argv'][0] . ' microcode.dat microcode.bin' . PHP_EOL);

$fn_read  = $_SERVER['argv'][1];
$fn_write = $_SERVER['argv'][2];

$ex_read  = pathinfo($fn_read, PATHINFO_EXTENSION);
$ex_write = pathinfo($fn_write, PATHINFO_EXTENSION);

if($ex_read == 'dat' && $ex_write == 'bin')
	dat2blob($fn_read, $fn_write);

if($ex_read == 'bin' && $ex_write == 'dat')
	blob2dat($fn_read, $fn_write);