<?php
/**
 * @author  NewEraCracker
 * @version 1.0.9
 * @date    2014/07/02
 * @license Public Domain
 */

/** @Link : http://php.net/manual/en/function.inet-pton.php */
if ( !function_exists('inet_pton'))
{
	function inet_pton($ip)
	{
		if(strpos($ip, '.') !== false)
		{
			// Pack IPv4
			$ip = trim($ip, ':f');
			$ip = pack('N', ip2long($ip));

			return $ip;
		}
		elseif(strpos($ip, ':') !== false)
		{
			// Expand IPv6
			$ip = explode(':', $ip);
			$res = '';
			$expand = true;
			foreach($ip as $seg)
			{
				if($seg == '' && $expand)
				{
					// This will expand a compacted IPv6
					$res .= str_pad('', (((8 - count($ip)) + 1) * 4), '0', STR_PAD_LEFT);

					// We only expand once, else it would cause troubles with ::1 or ffff::
					$expand = false;
				}
				else
				{
					// This will pad to ensure each IPv6 part has 4 digits.
					$res .= str_pad($seg, 4, '0', STR_PAD_LEFT);
				}
			}

			// Pack IPv6
			$ip = pack('H'.strlen($res), $res);

			return $ip;
		}

		return false;
	}
}

/** @Link : http://php.net/manual/en/function.inet-ntop.php */
if ( !function_exists('inet_ntop'))
{
	function inet_ntop($ip)
	{
		if(strlen($ip) == 4)
		{
			// Unpack IPv4
			list(, $ip) = unpack('N', $ip);
			$ip = long2ip($ip);
		}
		elseif(strlen($ip) == 16)
		{
			// Unpack IPv6
			$ip = bin2hex($ip);
			$ip = substr(chunk_split($ip, 4, ':'), 0, -1);

			// Compact IPv6
			$ip  = explode(':',$ip);
			$res = '';
			for($i = (count($ip)-1); $i >= 0; $i--)
			{
				$seg = ltrim($ip[$i], '0');

				if($seg != '')
				{
					$res = $seg.($res==''?'':':').$res;
				}
				else
				{
					if(strpos($res, '::') === false)
					{
						// @Hack : Check iteration number to make sure ::1 case is handled
						if($res != '' && $res[0] == ':' && $i > 0)
						{
							continue;
						}
						$res = ':'.$res;
						continue;
					}
					$res = '0'.($res==''?'':':').$res;
				}
			}
			$ip = $res;
		}
		else
		{
			return false;
		}

		return $ip;
	}
}

/*
	==================================================
	Calculates an IP range from an IP and a range code
	==================================================

	----

	Example:

	To calculate the range of:
	127.0.0.1/8

	You would pass 127.0.0.1 as the $ip and 8 as the $range

	----

	The function returns an array with the elements
	'lower' and 'higher' or, in case of failure, false.
*/
function ipRangeCalculate( $ip, $range )
{
	$ip_address = (string)  @$ip;
	$ip_range   = (integer) @$range;

	if( empty($ip_address) || empty($ip_range) )
	{
		// Something is wrong
		return false;
	}
	else
	{
		// Validate IP address
		$ip_address = @inet_ntop( @inet_pton( $ip_address ) );

		if( !$ip_address )
		{
			// Not a good IP
			return false;
		}
	}

	// Pack IP, Set some vars
	$ip_pack	  = inet_pton($ip_address);
	$ip_pack_size = strlen($ip_pack);
	$ip_bits_size = $ip_pack_size*8;

	// IP bits (lots of 0's and 1's)
	$ip_bits = '';
	for($i=0; $i < $ip_pack_size; $i=$i+1)
	{
		$bit = decbin( ord($ip_pack[$i]) );
		$bit = str_pad($bit, 8, '0', STR_PAD_LEFT);
		$ip_bits .= $bit;
	}

	// Significative bits (from the ip range)
	$ip_bits = substr($ip_bits,0,$ip_range);

	// Some calculations
	$ip_lower_bits  = str_pad($ip_bits, $ip_bits_size, '0', STR_PAD_RIGHT);
	$ip_higher_bits = str_pad($ip_bits, $ip_bits_size, '1', STR_PAD_RIGHT);

	// Lower IP
	$ip_lower_pack  = "";
	for($i=0; $i < $ip_bits_size; $i=$i+8)
	{	$chr = substr($ip_lower_bits,$i,8);
		$chr = chr( bindec($chr) );
		$ip_lower_pack .= $chr;
	}

	// Higher IP
	$ip_higher_pack = "";
	for($i=0; $i < $ip_bits_size; $i=$i+8)
	{	$chr = substr($ip_higher_bits,$i,8);
		$chr = chr( bindec($chr) );
		$ip_higher_pack .= $chr;
	}

	return array( 'lower'=>inet_ntop($ip_lower_pack), 'higher'=>inet_ntop($ip_higher_pack));
}

/*
	=======================================================
	Calculates if a certain IP is within a certain IP range
	=======================================================

	It currently supports single IPs (x.x.x.x),
	ranges (x.x.x.x-y.y.y.y and x.x.x.x/n) and wildcards (x.x.x.*)

	----

	Example:

	$ip = 8.8.8.8
	$range = 8.8.4.4/16

	This function would return true for that case

	----

	Be aware this function also returns false in case of error,
	so you better validate the input ;)
*/
function isInIpRange( $ip, $range )
{
	/* -------------------------
	   Convert IP to packed form
	   ------------------------- */
	$ip = @inet_pton( $ip );

	if( !$ip )
	{
		// This is not right
		return false;
	}

	/* --------------------
	   Calculate the range
	   -------------------- */

	if( strpos($range,'/') !== false )
	{
		// Explode
		$range = explode( '/', $range );

		// Calculate and validate
		if( $range = ipRangeCalculate( @$range[0], @$range[1] ) )
		{
			$ipRangeLower  = $range['lower'];
			$ipRangeHigher = $range['higher'];
		}
		else
		{
			// This is not right
			return false;
		}
	}
	elseif( strpos($range,'*') !== false )
	{
		// Calculate and validate
		if( strpos($range,'.') !== false )
		{
			// IPv4
			$ipRangeLower = str_replace('*','0',$range);
			$ipRangeHigher = str_replace('*','255',$range);
		}
		elseif( strpos($range,':') !== false )
		{
			// IPv6
			$ipRangeLower = str_replace('*','0',$range);
			$ipRangeHigher = str_replace('*','ffff',$range);
		}
		else
		{
			// This is not right
			return false;
		}
	}
	elseif( strpos($range,'-') !== false )
	{
		// Calculate
		$range		 = explode( '-', $range );
		$ipRangeLower  = @$range[0];
		$ipRangeHigher = @$range[1];

		// Validate
		if( empty($ipRangeLower) || empty($ipRangeHigher) )
		{
			// This is not right
			return false;
		}
	}
	else
	{
		// Validate
		$range = @inet_ntop( @inet_pton( $range ) );

		if( !$range )
		{
			// This is not right
			return false;
		}

		// Calculate
		$ipRangeHigher = $ipRangeLower = $range;
	}

	/* -------------------------------------------
	   Convert the calculated range to packed form
	   ------------------------------------------- */

	$ipRangeHigher = inet_pton( $ipRangeHigher );
	$ipRangeLower = inet_pton( $ipRangeLower );

	/* --------------
	   Validate again
	   -------------- */

	if( strlen($ipRangeHigher) != strlen($ipRangeLower)
	|| strlen($ipRangeLower) != strlen($ip) )
	{
		// This is not right
		return false;
	}

	/* -------
	   Compare
	   ------- */

	// Compare IP to ranges extremes
	if( $ipRangeHigher == $ip || $ipRangeLower == $ip )
	{
		// Thats the IP we are looking for
		return true;
	}

	for( $i=0; $i<strlen($ip); $i++)
	{
		if( ord($ipRangeLower[$i]) <= ord($ip[$i]) && ord($ip[$i]) <= ord($ipRangeHigher) )
		{
			// This IP bit is into IP Range
			continue;
		}
		else
		{
			// We didn't found that IP bit into IP range
			// So, the IP we are looking for is not this one
			return false;
		}
	}

	/* --------
	   Found it
	   -------- */

	return true;
}