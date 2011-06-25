<?php
// Author: NewEraCracker
// License: Public Domain

//@link: http://php.net/manual/en/function.inet-pton.php
if ( !function_exists('inet_pton'))
{
	function inet_pton($ip)
	{
		if(strpos($ip, '.') !== FALSE)
		{
			// Pack ipv4
			$ip = trim($ip,':f');
			$ip = pack('N',ip2long($ip));
		}
		elseif(strpos($ip, ':') !== FALSE)
		{
			// Expand ipv6
			$_count = count(explode(':', $ip));
			while($_count<=8)
			{
				$ip = str_replace('::',':0::',$ip);
				$_count++;
			}
			unset($_count);
			$ip = str_replace('::',':',$ip);

			// Pack ipv6
			$ip = explode(':', $ip);
			$res = str_pad('', (4*(8-count($ip))), '0000', STR_PAD_LEFT);
			foreach ($ip as $seg)
			{
				$res .= str_pad($seg, 4, '0', STR_PAD_LEFT);
			}
			$ip = pack('H'.strlen($res), $res);
		}
		else
		{
			return false;
		}
		
		if(strlen($ip)==4 || strlen($ip)==16)
		{
			return $ip;
		}
		else
		{
			return false;
		}
		
	}
}

//@link: http://php.net/manual/en/function.inet-ntop.php
if ( !function_exists('inet_ntop'))
{
	function inet_ntop($ip){
		if(strlen($ip)==4)
		{
			// Unpack ipv4
			list(,$ip)=unpack('N',$ip);
			$ip=long2ip($ip);
		}
		elseif(strlen($ip)==16)
		{
			// Unpack ipv6
			$ip=bin2hex($ip);
			$ip=substr(chunk_split($ip,4,':'),0,-1);
			$ip=explode(':',$ip);
			$res='';
			foreach($ip as $seg)
			{
				while($seg{0}=='0') $seg=substr($seg,1);
				if($seg!='')
				{
					$res.=($res==''?'':':').$seg;
				}
				else
				{
					if (strpos($res,'::')===false) {
							if (substr($res,-1)==':') continue;
							$res.=':';
							continue;
					}
					$res.=($res==''?'':':').'0';
				}
			}
			$ip=$res;
		}
		else
		{
			return false;
		}

		return $ip;
	}
}

function ipRangeCalculate($ip,$range)
{
	$ip_address = (string)  @$ip;
	$ip_range   = (integer) @$range;
	
	if($ip_address != $ip || $ip_range != $range)
	{
		// We got wrong types of information
		return false;
	}
	else
	{
		// Validate IP address
		$ip_address = inet_ntop( inet_pton( $ip_address ) );
		
		if(!$ip_address)
		{
			// Not a good IP
			return false;
		}
	}

	// Pack IP, Set some vars
	$ip_pack       = inet_pton($ip_address);
	$ip_pack_size  = strlen($ip_pack);
	$ip_bits_size  = $ip_pack_size*8;

	// IP bits (lots of 0's and 1's)
	$ip_bits = "";
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
	
	return array("lower"=>inet_ntop($ip_lower_pack),"higher"=>inet_ntop($ip_higher_pack));
}

// Sample useage
if( $range = ipRangeCalculate(@$_GET['ip'],@$_GET['range']) )
{
	echo "<pre>";
	print_r( $range );
	echo "</pre>";
}