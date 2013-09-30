<?php
/**
 *   Haik Install main
 *   -------------------------------------------
 *   installer.php
 *
 *   Copyright (c) 2012 hokuken
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
 
define('APP_NAME', 'haik');
define('APP_DOWNLOAD_URL', 'http://ensmall.net/dev/gethaik/download.php');
define('APP_VERSION_URL', 'https://ensmall.net/dev/gethaik/version');


// ! 関数定義

// Get absolute-URI of this script
function get_url()
{
	static $url;

	// Get
	if (isset($url)) return $url;

	// Set automatically

	$url  = ($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://'); // scheme
	$url .= $_SERVER['SERVER_NAME'];	// host
	$url .= (($_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443) ? '' : ':' . $_SERVER['SERVER_PORT']);  // port

	// SCRIPT_NAME が'/'で始まっていない場合(cgiなど) REQUEST_URIを使ってみる
	$path    = $_SERVER['SCRIPT_NAME'];
	if ($path{0} != '/') {
		if (! isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI']{0} != '/')
			die_message($msg);

		// REQUEST_URIをパースし、path部分だけを取り出す
		$parse_url = parse_url($script . $_SERVER['REQUEST_URI']);
		if (! isset($parse_url['path']) || $parse_url['path']{0} != '/')
			die_message($msg);

		$path = $parse_url['path'];
	}
	$url .= $path;

	return $url;
}



if (!function_exists("json_encode")) {
   function json_encode($var, /*emu_args*/$obj=FALSE) {
   
      #-- prepare JSON string
      $json = "";
      
      #-- add array entries
      if (is_array($var) || ($obj=is_object($var))) {

         #-- check if array is associative
         if (!$obj) foreach ((array)$var as $i=>$v) {
            if (!is_int($i)) {
               $obj = 1;
               break;
            }
         }

         #-- concat invidual entries
         foreach ((array)$var as $i=>$v) {
            $json .= ($json ? "," : "")    // comma separators
                   . ($obj ? ("\"$i\":") : "")   // assoc prefix
                   . (json_encode($v));    // value
         }

         #-- enclose into braces or brackets
         $json = $obj ? "{".$json."}" : "[".$json."]";
      }

      #-- strings need some care
      elseif (is_string($var)) {
         if (!utf8_decode($var)) {
            $var = utf8_encode($var);
         }
         $var = str_replace(array("\\", "\"", "/", "\b", "\f", "\n", "\r", "\t"), array("\\\\", "\\\"", "\\/", "\\b", "\\f", "\\n", "\\r", "\\t"), $var);
         $var = json_encode_string($var);
         $json = '"' . $var . '"';
         //@COMPAT: for fully-fully-compliance   $var = preg_replace("/[\000-\037]/", "", $var);
      }

      #-- basic types
      elseif (is_bool($var)) {
         $json = $var ? "true" : "false";
      }
      elseif ($var === NULL) {
         $json = "null";
      }
      elseif (is_int($var) || is_float($var)) {
         $json = "$var";
      }

      #-- something went wrong
      else {
         trigger_error("json_encode: don't know what a '" .gettype($var). "' is.", E_USER_ERROR);
      }
      
      #-- done
      return($json);
   }
}


function json_encode_string($in_str) {
	//fb($in_str, "json_encode_string");
	$debug = 'before:'."\n" . $in_str;
	mb_internal_encoding("UTF-8");
	$convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
	$str = "";
	for($i=mb_strlen($in_str)-1; $i>=0; $i--)
	{
		$mb_char = mb_substr($in_str, $i, 1);
		if(mb_ereg("&#(\\d+);", mb_encode_numericentity($mb_char, $convmap, "UTF-8"), $match))
		{
			$str = sprintf("\\u%04x", $match[1]) . $str;
		}
		else
		{
			$str = $mb_char . $str;
		}
	}
	$debug .= "\n\nafter:\n" . $str;
	//file_put_contents("debug.txt", $debug);
	return $str;
}

function h($string, $flags = ENT_QUOTES, $charset = 'UTF-8')
{
	return htmlspecialchars($string, $flags, $charset);
}


// ! セッション開始


$vals = parse_url(get_url());

$domain = $vals['host'];

if(isset($vals['port']))
{
	$domain .= ':'.$vals['port'];
}
$dir = str_replace('\\', '', dirname( $vals['path'] ));
$ckpath = ($dir=='/') ? '/' : $dir.'/';
			
if( function_exists('ini_set') ){
	ini_set('session.use_trans_sid',0);
	ini_set('session.name', strtoupper(APP_NAME) . '_SSID'.strlen($ckpath));
	ini_set('session.use_only_cookies', 1);
	ini_set('session.cookie_path', $ckpath);
	ini_set('session.cookie_domain', $domain);
	ini_set('session.cookie_lifetime', 0);
}

session_start();


// ! ライブラリ読み込み

$lib_path = dirname(APP_INSTALLER_URL).'/lib/';

$download_files = array(
	'ftp'       => $lib_path . 'Ftp.php',
	'local'     => $lib_path . 'LocalAdapter.php',
	'unzip'     => $lib_path . 'Unzip.php',
	'template'  => $lib_path . 'Template.php',
	'logo'      => $lib_path . 'haiklogo.jpg',
);

if (DEBUG) {
	$_SESSION = array();
}

// ライブラリファイルのダウンロード
if ( ! isset($_SESSION['files']))
{
	$files = array();
	foreach($download_files as $key => $file)
	{
		$str = file_get_contents($file);
		$tmpdir = sys_get_temp_dir();
		$tmpfile = tempnam($tmpdir, "haik_");
		file_put_contents($tmpfile, $str);
		
		$files[$key] = $tmpfile;
	}
	$_SESSION['files'] = $files;
}
else
{
	$files = $_SESSION['files'];
}

require($files['ftp']);
require($files['local']);
require($files['unzip']);


// ! メイン処理
$title = 'もう少しです。';
$installer = new Installer();

// FTP接続が必要かチェック
if ( ! isset($_SESSION['is_writable']))
{
	$installer->local_is_writable = $_SESSION['is_writable'] = local_is_writable();
}
else
{
	$installer->local_is_writable = $_SESSION['is_writable'];
}

// パッケージをダウンロード済みならセット
if (isset($_SESSION['package_file']))
{
	$installer->package_file = $_SESSION['package_file'];
}

// !リクエストを解析
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$mode = trim($_POST['mode']);

	$ftp_error = FALSE;
	
	$json = array();
	
	switch ($mode)
	{
		case 'download':
			if ($installer->package_file)
			{
				$json['message'] = 'Already Downloaded!';
			}
			else if ($installer->download())
			{
				$json['message'] = 'Download Success!';
				$_SESSION['package_file'] = $installer->package_file;
			}
			else
			{
				$json['error'] = $installer->errmsg;
				$json['errorCode'] = $installer->errcode;
			}
			break;
		case 'ftp_connect':
			$ftp_config = $_POST['ftp'];
			if ( ! $installer->ftp_connect($ftp_config))
			{
				$ftp_error = TRUE;
				$json['error'] = $installer->errmsg;
				$json['errorCode'] = $installer->errcode;
				break;
			}
		case 'install':
			$to_url = $installer->install();
			$json['message'] = 'Install Success!';
			$json['redirect'] = $to_url;
			
			//ftp_config だけ引き継ぐ
			$_SESSION = array(
				'ftp_config' => $_SESSION['ftp_config'],
			);
			
			//ライブラリファイルの削除
			foreach ($files as $key => $filepath)
			{
				unlink($filepath);
			}
			
			//その他終了処理
			$installer->finish();
	}

	//JSON 出力
	header("Content-Type: application/json; charset=utf-8");
	header("X-Content-Type: nosniff");

	echo json_encode($json);
	exit;
	
}


/* ---------------------
// ! 表示
 */

$ftp_config = $installer->ftp_config;

include($files['template']);


class Installer {
	
	public $ftp_config;
	public $local_is_writable = FALSE;
	
	public function __construct()
	{
		$this->ftp_config = array(
			'hostname' => 'localhost',
			'username' => get_current_user(),
			'password' => '',
			'dir'      => INSTALL_DIR,
		);
	}
	
	public function get_adapter()
	{
		if (isset($this->adapter)) return $this->adapter;
		
		$this->adapter = new LocalAdapter();
		return $this->adapter;
	}

	public function ftp_connect($ftp_config)
	{
	
		if ($this->local_is_writable)
		{
			return TRUE;
		}
		
		$this->ftp_config = array_merge($this->ftp_config, $ftp_config);
		
		$this->ftp_config = array_map('trim', $this->ftp_config);
		
/*
		if (isset($_POST['ftp_hostname']))
		{
			$ftp_config['hostname'] = trim($_POST['ftp_hostname']);
		}
		if (isset($_POST['ftp_username']))
		{
			$ftp_config['username'] = trim($_POST['ftp_username']);
		}
		if (isset($_POST['install_dir']))
		{
			$ftp_config['dir'] = trim($_POST['install_dir']);
		}

		$ftp_config['password'] = trim($_POST['ftp_password']);
*/

		$config = array(
			'ftp_config' => $this->ftp_config
		);
		$adapter = new LocalAdapter($config);
		$this->adapter = $adapter;
		
		// FTP login
		if ($adapter->ftp->connect($this->ftp_config))
		{
			// ftp_config[dir]に移動
			$adapter->ftp->dir = $ftp_config['dir']; 
			$adapter->changeidir();
			
			// ! urlの取得
			$url = get_url();
			
			$tempdir = sys_get_temp_dir();
			$adapter->ftp->putChecker($tempdir);
			$result = $adapter->ftp->request_checker($url);
			$adapter->ftp->removeChecker();
			if ($result)
			{
				if ($adapter->ftp->serverTest())
				{
					//FTP接続情報を保存
					$_SESSION['ftp_config'] = $ftp_config;
//					$adapter->ftp->close();
					
					return TRUE;

				}
				else
				{
					$this->errmsg = $adapter->ftp->errmsg;
					$this->errcode = '10003';//server test failure
				}
			}
			else
			{
				$this->errmsg = $adapter->ftp->errmsg;
				$this->errcode = '10002';//dir is wrong
			}

			// invalid dir
			$adapter->ftp->close();
		}
		else
		{
			$this->errmsg = $adapter->ftp->errmsg;
			$this->errcode = '10001';//ftp connect error
		}

		return FALSE;
	}	
	
	public function install()
	{
		// haik.zip のダウンロード
		if ( ! isset($this->package_file))
			$this->download();
		
		// haik.zip の解凍
		$this->unzip();
		
		// haikを起動
		$to_url = dirname(get_url()) . '/index.php?cmd=app_start&mode=set_auth';

		return $to_url;
	}
	
	public function download()
	{
		$tmpfile = tempnam(sys_get_temp_dir(), 'haik-pack-');
		
		if (file_put_contents($tmpfile, file_get_contents(APP_DOWNLOAD_URL)))
		{
			$this->package_file = $tmpfile;
			return $this->package_file;
		}
		else
		{
			$this->errmsg = 'haik のダウンロードに失敗しました。';
			$this->errcode = '20001';
			return FALSE;
		}
		
	}
	
	public function get_version()
	{
		
		$version = file_get_contents(APP_VERSION_URL);
		
		return $version;
		
	}
	
	public function unzip()
	{
		$tmpdir = rtrim(sys_get_temp_dir(), '/');
		
		$unzip = new Unzip();
		$file_locations = $unzip->extract($this->package_file, $tmpdir);
		
		unlink($this->package_file);
		
		$version = trim($this->get_version());
		$update_dir = $tmpdir . '/haik-' . $version;
		
		
		$adapter = $this->get_adapter();
		
		foreach ($file_locations as $filepath)
		{
			$adapter->changeidir();
			
			$name = basename($filepath);
			$orgpath = dirname($filepath);
	
			$path = preg_replace('/^'. preg_quote($update_dir, '/').'/', '', $orgpath);
			$path = ltrim($path . '/', '/');
	
			$realpath = $adapter->pwd() . '/' . $path;
			
			//存在しないディレクトリを作成
			if ( ! $adapter->is_exists($realpath))
			{
				$adapter->mkdir($realpath, 0755, TRUE);
			}
	
			$adapter->changeidir();
			
			//ファイルをコピー
			$rempath = $realpath . $name;
			$locpath = $orgpath . '/' . $name;
			
//			echo $locpath , ' -> ', $rempath, PHP_EOL;
			
			$adapter->upload($locpath, $rempath);
			
		}
	
		// cache を消す
		
		if ( ! $adapter->delete_dir($update_dir))
		{
			rmdir($update_dir);
		}
		$adapter->changeidir();
	
			
	}
	
	public function finish()
	{

	}
	
}
