<?php
/* IPv6 to IPv4, a dirty hack
 *
 * This will create fake IPv4 for IPv6 users based on the 64 first bits of their IP.
 * As most currently existing IPv6 providers are assigning /64 classes to their customers,
 * banning the generated IPv4 effectively bans the whole IPv6.
 *
 * Also this script generates a 32bits IPv4 from 64bits of IPv6 using XOR.
 * While this means people using IPv6 might share the same generated IPv4 (quite unlikely),
 * it is usually impossible for someone to obtain a different generated IPv4 without access
 * to more than a /64 (I believe only system administrators have this kind of thing).
 *
 * Author: NewEraCracker
 * License: Public Domain
 */

//@link: http://php.net/manual/en/function.inet-pton.php
if ( !function_exists('inet_pton')) {
        function inet_pton($ip){
                //ipv4
                if (strpos($ip, '.') !== FALSE) {
                        $ip = trim($ip,':f');
                        $ip = pack('N',ip2long($ip));
                }
                //ipv6
                elseif (strpos($ip, ':') !== FALSE) {

                        //Short ipv6 fix by NewEraCracker
                        $_count = count(explode(':', $ip));
                        while($_count<=8) {
                                $ip = str_replace('::',':0::',$ip);
                                $_count++;
                        }
                        unset($_count);
                        $ip = str_replace('::',':',$ip);
                        //Newfags can't triforce!

                        $ip = explode(':', $ip);
                        $res = str_pad('', (4*(8-count($ip))), '0000', STR_PAD_LEFT);
                        foreach ($ip as $seg) {
                                $res .= str_pad($seg, 4, '0', STR_PAD_LEFT);
                        }
                        $ip = pack('H'.strlen($res), $res);
                }
                return $ip;
        }
}

//@link: http://php.net/manual/en/function.inet-ntop.php
if ( !function_exists('inet_ntop')){
        function inet_ntop($ip){
                if (strlen($ip)==4){
                        //ipv4
                        list(,$ip)=unpack('N',$ip);
                        $ip=long2ip($ip);
                }
                elseif(strlen($ip)==16){
                        //ipv6
                        $ip=bin2hex($ip);
                        $ip=substr(chunk_split($ip,4,':'),0,-1);
                        $ip=explode(':',$ip);
                        $res='';
                        foreach($ip as $seg) {
                                while($seg{0}=='0') $seg=substr($seg,1);
                                if ($seg!='') {
                                        $res.=($res==''?'':':').$seg;
                                } else {
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
                return $ip;
        }
}

//@link: http://blog.magicaltux.net/2010/02/18/invision-power-board-and-ipv6-a-dirty-hack/
$encoded_ip = inet_pton($_SERVER['REMOTE_ADDR']);
if (strlen($encoded_ip) == 16) {
    $ipv4 = '';
    for($i = 0; $i < 8; $i += 2) $ipv4 .= chr(ord($encoded_ip[$i]) ^ ord($encoded_ip[$i+1]));
    $_SERVER['REMOTE_ADDR'] = inet_ntop($ipv4);
}

?>