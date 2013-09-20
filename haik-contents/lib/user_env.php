<?php
/**
 *   User Environment
 *   -------------------------------------------
 *   user_env.php
 *   
 *   Copyright (c) 2011 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-07-04
 *   modified :
 *   
 *   ユーザーの環境を取得、整形します。
 *   ユーザーエージェントやOSなど。
 *   
 *   Usage :
 *   
 */

class UserEnvironment {

	public $ipaddress = '';
	public $hostname = '';
	
	public $ua = '';
	public $browser = '';
	public $browser_version = '';
	public $os = '';
	public $os_version = '';
	

	function __construct($ua = '')
	{
		$this->init($ua);
	}

	function init($ua)
	{
		if (trim($ua) === '')
		{
			$ua = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT']: '';
		}
		$this->ua = $ua;
		$this->ipaddress = isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR']: '';
		$this->hostname = gethostbyaddr($this->ipaddress);
		
		$br = $ua;
		$ver = '';
		if (preg_match('/MSIE (\d+\.\d+)/', $ua, $mts))
		{
			$br = 'Internet Explorer';
			$ver = $mts[1];
		}
		else if (preg_match('/Chrome\/([\d.]+)/', $ua, $mts))
		{
			$br = 'Google Chrome';
			$ver = $mts[1];
		}
		else if (preg_match('/Lunascape ([\d.]+)/', $ua, $mts))
		{
			$br = 'Lunascape';
			$ver = $mts[1];
		}
		else if (preg_match('/Firefox\/([\d.]+)/', $ua, $mts))
		{
			$br = 'Mozilla Firefox';
			$ver = $mts[1];
		}
		else if (preg_match('/Version\/([\d.]+) (Mobile )?Safari/', $ua, $mts))
		{
			if ($mts[2])
			{
				$br = 'Mobile Safari';
			}
			else
			{
				$br = 'Safari';
			}
			$ver = $mts[1];
		}
		else if (preg_match('/Opera[\/ ]([\d.]+)/', $ua, $mts))
		{
			$br = 'Opera';
			$ver = $mts[1];
		}
		else if (preg_match('/(iPhone|iPod|iPad)/', $ua, $mts))
		{
			$br = $mts[0];
		}
		else if (preg_match('/AppleWebKit\/([\d.]+)/', $ua, $mts))
		{
			$br = 'Apple Webkit';
			$ver = $mts[1];
		}
		
		$this->browser = $br;
		$this->browser_version = $ver;

		//OS
		$os = '';
		$os_ver = '';
		if (preg_match('/Android/', $ua))
		{
			$os = 'Android';
		}
		else if (preg_match('/iPhone OS ([\d_]+)/', $ua, $mts))
		{
			$os = 'iOS';
			$os_ver = $mts[1];
		}
		else if (preg_match('/Windows (NT ([\d.]+)|98)/', $ua, $mts))
		{
			//http://ja.wikipedia.org/wiki/Windows_NT%E7%B3%BB
			$os = 'Windows';
			if (isset($mts[2]))
			{
				$nt_ver = $mts[2];
				if ($nt_ver == '5.0')
				{
					$os_ver = '2000';
				}
				else if ($nt_ver == '5.1')
				{
					$os_ver = 'XP';
				}
				else if ($nt_ver == '5.2')
				{
					$os_ver = 'XP 64bit';
				}
				else if ($nt_ver == '6.0')
				{
					$os_ver = 'Vista';
				}
				else if ($nt_ver == '6.1')
				{
					$os_ver = '7';
				}
				else
				{
					$os_ver = $mts[1];
				}
			}
			else
			{
				$os_ver = $mts[1];
			}

		}
		else if (preg_match('/Mac OS X ([._\d]+)/', $ua, $mts))
		{
			$os = 'Mac OS X';
			$os_ver = $mts[1];
		}
		else if (preg_match('/Macintosh/', $ua, $mts))
		{
			$os = 'Macintosh';
		}
		
		$this->os = $os;
		$this->os_version = $os_ver;
		
	}
	
	/**
	 * get version
	 *
	 * @param integer $depth depth of version string (delim is . or _)
	 * @param boolean $browser flag of get browser version.  if the flag is false, return os version
	 * @return string version string
	 */
	public function getVersion($depth = 1, $browser = true)
	{
		$version = $browser? $this->browser_version: $this->os_version;
		
		
		if ($depth < 1)
		{
			return str_replace('_', '.', $version);
		}
		else
		{
			$vers = preg_split('/[._]/', $version);
			$vers = array_slice($vers, 0, $depth);
			return join('.', $vers);
		}
	}
	
	public function toString()
	{
		$fmt = 'host: %s (%s), env: %s %s (%s %s), ua: %s';//host: hoge.com (1.1.1.1), env: MSIE 9.0 (Windows 7), ua: HTTP_USER_AGENT
		$str = sprintf($fmt, $this->hostname, $this->ipaddress, $this->browser, $this->browser_version, $this->os, $this->os_version, $this->ua);
		return $str;
	}

}


