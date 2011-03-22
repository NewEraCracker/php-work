<?php
/* iS MA HOS' NOOBISH? v2.5.1
 * @author   NewEraCracker
 * @date     22-03-2011
 * @license  Public Domain
 * @notes    newfags can't triforce
 */

// config
$functionsToBeDisabled = array('link', 'symlink', 'system', 'shell_exec', 'passthru', 'exec', 'pcntl_exec', 'popen', 'proc_close', 'proc_get_status', 'proc_nice', 'proc_open', 'proc_terminate');
$functionsToBeEnabled = array('php_uname', 'base64_decode', 'fpassthru', 'ini_set');

// init
header('Content-Type: text/plain');
error_reporting(0);
$crlf = "\r\n";
$issues = '';
$noobishPointz = 0;

// functions to be disabled
foreach ($functionsToBeDisabled as $test)
{
    if (function_exists($test) && !(in_array($test, explode(', ', ini_get('disable_functions')))))
    {
        $noobishPointz++;
		$issues .= "Issue: Function ".$test." should be disabled!".$crlf;
    }
}
unset($test);

// functions to be enabled
foreach ($functionsToBeEnabled as $test)
{
    if (!function_exists($test) || in_array($test, explode(', ', ini_get('disable_functions'))))
    {
        $noobishPointz++;
		$issues .= "Issue: Function ".$test." should be enabled!".$crlf;
    }
}
unset($test);

// dl (in)security
if (function_exists('dl') && !(in_array('dl', explode(', ', ini_get('disable_functions')))))
{
        if (ini_get('enable_dl'))
        {
            $noobishPointz++;
			$issues .= "Issue: enable_dl should be Off!".$crlf;
        }
}

// safe_mode?
if (ini_get('safe_mode')) { $noobishPointz++; $issues .= "Issue: safe_mode is On!".$crlf;}

// magic_quotes_gpc?
if (ini_get('magic_quotes_gpc')) { $noobishPointz++; $issues .= "Issue: magic_quotes_gpc is On!"; }

// output results
if ($noobishPointz==0)
{
    echo('Host is not noobish! Ready for use!'.$crlf);
}
else
{
    echo('Your host scored '.$noobishPointz.' noobish points!'.$crlf.$crlf.$issues); 
}
?>