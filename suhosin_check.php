<?php
/**
* Suhosin Configuration Checker v0.4
* @author NewEraCracker
* @date 24-02-2011
* @license Public Domain
*/

//
// Configuration
//

// Value has to be the same or higher to pass tests
$test_values = array(
array( 'suhosin.get.max_name_length', 350 ),
array( 'suhosin.post.max_array_index_length', 256 ),
array( 'suhosin.post.max_totalname_length', 8192 ),
array( 'suhosin.post.max_vars', 4096 ),
array( 'suhosin.post.max_value_length', 1000000 ),
array( 'suhosin.request.max_array_index_length', 256 ),
array( 'suhosin.request.max_totalname_length', 8192 ),
array( 'suhosin.request.max_vars', 4096 ),
array( 'suhosin.request.max_value_length', 1000000 ),
array( 'suhosin.request.max_varname_length', 350 ),
);

// Value has to be false to pass tests
$test_false = array(
'suhosin.sql.bailout_on_error',
'suhosin.cookie.encrypt',
'suhosin.session.encrypt',
);

//
// Main code
//

ob_start();

if( extension_loaded('suhosin') )
{
	$problems = 0;
	echo "<b>Suhosin installation detected!</b>".PHP_EOL;

	foreach($test_false as $test)
	{
		if( ini_get($test) != false )
		{
			echo "Please ask your host to <b>disable (turn off) {$test}</b> in php.ini".PHP_EOL;
			$problems++;
		}
	}
	foreach($test_values as $test)
	{
		if( isset($test['0']) && isset($test['1']) )
		{
			if( ini_get($test['0']) < $test['1'])
			{
				echo "Please ask your host to set <b>{$test['0']}</b> in php.ini to <b>{$test['1']}</b> or higher".PHP_EOL;
				$problems++;
			}
		}
	}
	if($problems == 0)
	{
		echo "<b>No problems detected!</b>".PHP_EOL;
	}
}
else
{
	echo "<b>There is no Suhosin in here :)</b>".PHP_EOL;
}

$output = ob_get_contents();
ob_end_clean();

$output = str_replace(PHP_EOL, PHP_EOL.PHP_EOL ,$output);
$output = nl2br($output);
echo $output;

?>