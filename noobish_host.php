<?php
/* iS MA HOS' NOOBISH? v2.4
 * @author   NewEraCracker
 * @date     22-02-2011
 * @license  Public Domain
 * @notes    newfags can't triforce
 */

header('Content-Type: text/plain');
error_reporting(0);

// config
$noobishPointz = 0;
$functionsToBeDisabled = array('link', 'symlink', 'system', 'shell_exec', 'passthru', 'exec', 'popen', 'proc_close', 'proc_get_status', 'proc_nice', 'proc_open', 'proc_terminate');
$functionsToBeEnabled = array('php_uname', 'base64_decode', 'fpassthru');

// functions to be disabled
foreach ($functionsToBeDisabled as $test) {
    if (function_exists($test) && !(in_array($test, explode(', ', ini_get('disable_functions'))))) {
        $noobishPointz++;
    }
}
unset($test);

// functions to be enabled
foreach ($functionsToBeEnabled as $test) {
    if (!function_exists($test) || in_array($test, explode(', ', ini_get('disable_functions')))) {
        $noobishPointz++;
    }
}
unset($test);

// dl (in)security
if (function_exists('dl') && !(in_array('dl', explode(', ', ini_get('disable_functions'))))) {
        if (ini_get('enable_dl')) {
            $noobishPointz++;
        }
}

// safe_mode?
if (ini_get('safe_mode')) { $noobishPointz++; }

// magic_quotes_gpc?
if (ini_get('magic_quotes_gpc')) { $noobishPointz++; }

// ini_alter vs ini_set crap?
if (function_exists('ini_alter') && (in_array('ini_alter', explode(', ', ini_get('disable_functions'))))) {
        if (function_exists('ini_set') && !(in_array('ini_set', explode(', ', ini_get('disable_functions'))))) {
            $noobishPointz++;
        }
}

// output results
if ($noobishPointz==0) { echo('Host is not noobish! Ready for use!' . PHP_EOL); }
else { echo('Your host scored '.$noobishPointz.', check php.ini if you don\'t mind!'. PHP_EOL); }

?>