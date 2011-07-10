<?php
// Author: NewEraCracker
// License: Public Domain
?>
SiMPLE PHP ENCODER BY NEWERACRACKER [v0.0.7]<br/>
<br/>
Encoding: base64_encode(gzdeflate($text,9));<br/>
Decoding: gzinflate(base64_decode($text));<br/>
<br/>
<form action="" method="post">
	<textarea name="text" cols=100 rows=20></textarea>
	<br/>Encode <input type="radio" name="type" value="encode" checked> | <input type="radio" name="type" value="decode"> Decode
	<br/><input type="submit" value="Go !" name="submitcmd"/>
</form><br/>

<?php
if (isset($_POST['text']) && isset($_POST['type']))
{
	$text = get_magic_quotes_gpc() ? stripslashes( (string) $_POST['text']) : (string) $_POST['text'];

	if ($_POST['type']=='encode')
	{
		$encoded = @base64_encode(gzdeflate($text,9));
		echo 'DECODED: <br/><textarea cols=100 rows=5>'.htmlspecialchars($text).'</textarea><br/>';
		echo 'ENCODED: <br/><textarea cols=100 rows=5>'.htmlspecialchars($encoded).'</textarea><br/>';
	}
	elseif ($_POST['type']=='decode')
	{
		$decoded = @gzinflate(base64_decode($text));
		echo 'ENCODED: <br/><textarea cols=100 rows=5>'.htmlspecialchars($text).'</textarea><br/>';
		echo 'DECODED: <br/><textarea cols=100 rows=5>'.htmlspecialchars($decoded).'</textarea><br/>';
	}
}
?>