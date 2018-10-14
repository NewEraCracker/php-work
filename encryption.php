<?php
/*
 Author: NewEraCracker
 License: Public Domain
*/

function __decode($txt, $ml = 10)
{
	/* I truly hate magic constants, so code will be as generic as possible */
	$ml  = /* Sanitize multiplier */ ($ml < 10 || $ml > 32) ? 10 : $ml;
	$dml = /*  Double multiplier  */ ($ml * 2);
	$nml = /* Negative multiplier */ (0 - $ml);

	/* Powerful magic to unhide */
	$alph = '8qQpro+ABfT4aXOK7kb5u3Sm=VWJLw6E1GY0xDteyl/ZPid2M9NzcnCRFIUhHgsvj';
	$from = array('!', '@', '#', '$', '%', '^', '&', '*', '(', ')');
	$to   = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j');
	$txt  = base64_decode($txt);

	/* Reverse bits, ain't that fun? */
	for($i=0; $i < strlen($txt); $i++) {
		$v       = decbin(ord($txt[$i]));
		$pad     = str_pad($v, 8, '0', STR_PAD_LEFT);
		$rev     = strrev($pad);
		$bin     = bindec($rev);
		$txt[$i] = chr($bin);
	}

	/* Uncompress, rotate, grab the pass, remove pass from text */
	$txt  = gzuncompress($txt);
	$txt  = str_replace($from, $to, $txt);
	$pass = substr($txt, 0, $ml) . substr($txt, $nml);
	$txt  = substr($txt, $ml, $nml);

	/* I call this the NewEraVigenère decryption */
	for($t = '', $i = 0, $tl = strlen($txt), $al = strlen($alph); $i < $tl; $i++)
	{
		$k  = strpos($alph, $txt[$i]);
		$z  = ord($pass[$i % $dml]) % $al;
		$t .= $alph[($al + $k - $z) % $al];
	}
	$txt = base64_decode($t); /* This is extremely important */

	/* Hashes are cool, let's give them some use for integrity checks */
	$sha = substr($txt, -40);
	$txt = substr($txt, 0, -40);

	/* ALL YOUR BASE MIGHT BELONG TO YOU */
	return (sha1($txt) === $sha ? $txt : false);
}
function __encode($txt, $ml = 10)
{
	/* I truly hate magic constants, so code will be as generic as possible */
	$ml  = /* Sanitize multiplier */ ($ml < 10 || $ml > 32) ? 10 : $ml;
	$dml = /*  Double multiplier  */ ($ml * 2);
	$nml = /* Negative multiplier */ (0 - $ml);

	/* Powerful magic to hide */
	$alph = '8qQpro+ABfT4aXOK7kb5u3Sm=VWJLw6E1GY0xDteyl/ZPid2M9NzcnCRFIUhHgsvj';
	$from = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j');
	$to   = array('!', '@', '#', '$', '%', '^', '&', '*', '(', ')');
	$txt  = base64_encode($txt . sha1($txt)); /* Hashes are cool, let's give them some use for integrity checks */
	$pass = substr(str_shuffle($alph), 0, $dml);

	/* I call this the NewEraVigenère encyption */
	for($t = '', $i = 0, $tl = strlen($txt), $al = strlen($alph); $i < $tl; $i++)
	{
		$k  = strpos($alph, $txt[$i]);
		$z  = ord($pass[$i % $dml]) % $al;
		$t .= $alph[($k + $z) % $al];
	}
	$txt = substr($pass, 0, $ml) . $t . substr($pass, $nml);

	/* Rotate, and compress */
	$txt = str_replace($from, $to, $txt);
	$txt = gzcompress($txt);

	/* Reverse bits, ain't that fun? */
	for($i=0; $i < strlen($txt); $i++) {
		$v       = decbin(ord($txt[$i]));
		$pad     = str_pad($v, 8, '0', STR_PAD_LEFT);
		$rev     = strrev($pad);
		$bin     = bindec($rev);
		$txt[$i] = chr($bin);
	}

	/* ALL YOUR BASE ARE BELONG TO US */
	return base64_encode($txt);
}

var_dump(__decode(__encode('A cat jumps over the lazy dog', 20), 20));
?>