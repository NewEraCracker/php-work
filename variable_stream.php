<?php

class VariableStream
{
	var $position;
	var $varname;

	function stream_open($path, $mode, $options, &$opened_path)
	{
		$url = parse_url($path);
		$this->varname = $url['host'];
		$this->position = 0;
		return true;
	}

	function stream_read($count)
	{
		$ret = substr($GLOBALS[$this->varname], $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	function stream_write($data)
	{
		if(! isset($GLOBALS[$this->varname]))
		{
			$GLOBALS[$this->varname] = '';
		}

		$left = substr($GLOBALS[$this->varname], 0, $this->position);
		$right = substr($GLOBALS[$this->varname], $this->position + strlen($data));
		$GLOBALS[$this->varname] = $left . $data . $right;
		$this->position += strlen($data);
		return strlen($data);
	}

	function stream_tell()
	{
		return $this->position;
	}

	function stream_eof()
	{
		return $this->position >= strlen($GLOBALS[$this->varname]);
	}

	function stream_seek($offset, $whence)
	{
		if($whence === SEEK_SET && $offset < strlen($GLOBALS[$this->varname]) && $offset >= 0)
		{
			 $this->position = $offset;
			 return true;
		}

		if($whence === SEEK_CUR && $offset >= 0)
		{
			 $this->position += $offset;
			 return true;
		}

		if($whence === SEEK_END && strlen($GLOBALS[$this->varname]) + $offset >= 0)
		{
			 $this->position = strlen($GLOBALS[$this->varname]) + $offset;
			 return true;
		}

		return false;
	}

	function stream_metadata($path, $option, $var)
	{
		if($option === STREAM_META_TOUCH)
		{
			$url = parse_url($path);
			$varname = $url['host'];

			if(! isset($GLOBALS[$varname]))
			{
				$GLOBALS[$varname] = '';
			}

			return true;
		}

		return false;
	}

	function stream_stat()
	{
		return array(
			0, // device number
			0, // inode number
			0, // inode protection mode
			0, // number of links
			getmyuid(), // userid of owner
			getmygid(), // groupid of owner
			0, // device type, if inode device
			strlen($GLOBALS[$this->varname]), // size in bytes
			time(), // atime - time of last access
			time(), // mtime - time of last modification
			time(), // ctime - time of creation
			-1, // block size of filesystem I/O
			-1 // number of 512-byte blocks allocated
		);
	}
}

stream_wrapper_register('var', 'VariableStream')
	or die('Failed to register protocol');

// Tests!
header('Content-Type: text/plain');

echo 'parse_ini_file test:'."\n";	
$ini_file = "test_string = test_value";
var_dump(parse_ini_file('var://ini_file'));

echo "\n".'php_strip_whitespace test:'."\n";
$php_code = <<<RAW
<?php
	echo 'testing1';
	echo 'testing2';
?>
RAW;
var_dump(php_strip_whitespace('var://php_code'));

echo "\n".'file_put_contents test:'."\n";
file_put_contents('var://test', 'test');
var_dump($test);