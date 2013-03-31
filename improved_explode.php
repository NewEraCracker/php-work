<?php
/*
 Author: NewEraCracker
 License: Public Domain
*/

function improved_explode($delimiter,$string,$escape=null)
{
	$exploded = explode($delimiter,$string);

	if($escape !== null)
	{
		// Fix exploded array to comply with escape char
		foreach($exploded as $key => $value)
		{
			if ($value != '' && isset($exploded[$key]) )
			{
				if( stripos( $value.$delimiter, $escape.$delimiter) !== false )
				{
					// Merge $key and $key+1
					$exploded[$key] = $exploded[$key] . $delimiter . ( isset($exploded[$key+1]) ? $exploded[$key+1] : '' );

					// Fix $key
					$exploded[$key] = str_replace( $escape.$delimiter , $delimiter , $exploded[$key]);

					// Unset $key+1
					if( isset($exploded[$key+1]) ) unset($exploded[$key+1]);
				}
			}
		}
		unset($key, $value);

		// Fix array keys
		$i = 0;
		$new = array();
		foreach($exploded as $key => $value)
		{
			$new[$i]=$exploded[$key];
			$i++;
		}
		unset($key, $value);
		$exploded = $new;
	}

	return $exploded;
}

// Example usage
$mystring = "test=2\\=2";
var_dump( improved_explode('=',$mystring,'\\') );

?>