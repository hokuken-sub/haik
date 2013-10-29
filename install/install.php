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
define('APP_INSTALLER_URL', 'http://ensmall.net/gethaik/install/installer.php');
define('APP_MANUAL_URL', 'http://toiee.jp/haik/help/index.php');
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

	//致命的な設定の上書きを試行する
	ini_set('safe_mode', 'Off');
	ini_set('allow_url_fopen', 'On');
	
	//check php.ini
	if (ini_get('safe_mode'))
	{
		die_message(
			'safe_mode が有効な環境ではインストールできません。',
			'<strong>Runtime Error</strong><br>
			PHP の safe_mode が有効（On）になっているため、インストールできません。<br>
			php.ini を編集して、safe_mode を無効（Off）にしてください。',
			'PHP_Safe_Mode'
		);
	}
	if ( ! ini_get('allow_url_fopen'))
	{
		die_message(
			'allow_url_fopen が無効な環境ではインストールできません。',
			'<strong>Runtime Error</strong><br>
			PHP の allow_url_fopen が無効（Off）になっているため、インストールできません。<br>
			php.ini を編集して、allow_url_fopen を無効（On）にしてください。',
			'PHP_Allow_Url_Fopen'
		);
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

/**
 * @param string $title: page title
 * @param string $msg : error messages
 * @param string $link_to : page name of the manual site | URL
 */
function die_message($title, $msg, $link_to = '')
{
	$link = '';
	if ($link_to)
	{
		if ( ! preg_match('/^https?:\/\//', $link_to))
		{
			$link_to = APP_MANUAL_URL . '?' . rawurlencode($link_to);
		}
		$link = '<a href="'.$link_to.'" class="btn btn-info btn-large">詳しくはこちら</a>';
	}

	$html = <<< EOH
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>$title</title>
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
</head>
<body>
<div class="jumbotron">
	<div class="container">
	<h2>$title</h2>

	<div class="alert alert-danger">
		$msg
	</div>
	
	$link
	</div>
	
</div>
</body>
</html>


EOH;
	die($html);
}
