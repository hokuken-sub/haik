<?php
/**
 *   Haik Installer
 *   -------------------------------------------
 *   install.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/09/26
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
define('DEBUG', FALSE);
define('APP_INSTALLER_URL', 'http://ensmall.net/haik-inst/installer.php');
define('INSTALL_DIR', dirname(__FILE__) . '/');


if (DEBUG)
{
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}
else
{
	//haik のインストールが完了していたらリダイレクトする。
	if (file_exists('haik-contents') && is_dir('haik-contents'))
	{
		header('Location: ./index.php');
		exit;
	}

	ini_set('display_errors', 'Off');
	error_reporting(E_ERROR | E_PARSE);
}


if ( ! function_exists('sys_get_temp_dir'))
{
	function sys_get_temp_dir()
	{ 
		if ( ! empty($_ENV['TMP']))
		{
			return realpath($_ENV['TMP']);
		}
		if ( ! empty($_ENV['TMPDIR']))
		{
			return realpath( $_ENV['TMPDIR']);
		} 
		if ( ! empty($_ENV['TEMP']))
		{
			return realpath( $_ENV['TEMP']); 
		}

		$tempfile=tempnam(__FILE__,''); 
		if (file_exists($tempfile))
		{
			unlink($tempfile); 
			return realpath(dirname($tempfile)); 
		}
		
		return null; 
	}
}


printd("haik install start !!!!");

$str = file_get_contents(APP_INSTALLER_URL);

$tmpdir = sys_get_temp_dir();
$fname = tempnam($tmpdir, "haik_");

file_put_contents($fname, $str);

printd("fname : " . $fname);
printd("======= remote data ==============");

require($fname);

unlink($fname);


function printd($msg)
{
	if (DEBUG)
	{
		echo $msg . "<br>\n";
	}
}