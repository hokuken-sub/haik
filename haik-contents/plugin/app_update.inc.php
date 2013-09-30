<?php
/**
 *   App Updater
 *   -------------------------------------------
 *   /app/haik-contents/plugin/app_update.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2012-06-12
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

require_once(LIB_DIR . 'Ftp.php');
require_once(LIB_DIR . 'qhm_fs.php');
require_once(LIB_DIR . 'Unzip.php');

define('APP_PACKAGE_URL_FORMAT', 'https://ensmall.net/gethaik/download.php?v=%s');
define('APP_VERSION_URL', 'https://ensmall.net/gethaik/version');

function plugin_app_update_init()
{
	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}
	
	if ( ! is_writable(CACHE_DIR))
	{
		die(sprintf(__('キャッシュフォルダ %1$s に書き込み権限がありません。<br>ユーザー %2$s へ書き込みを許可してください。'), CACHE_DIR, get_current_user()));
	}
	
	if ( ! file_exists(CONFIG_DIR . 'perms.ini.php'))
	{
		die(sprintf(__('必要な設定ファイルがありません。[%s]'), CONFIG_DIR . 'perms.ini.php'));
	}

	
}

function plugin_app_update_action()
{
	global $script, $vars, $style_name;
	global $no_proxy, $proxy_port;
	global $local_ftp, $defaultpage;
	
	//アップデート後FrontPageに戻す
	$vars['refer'] = $defaultpage;
	
	$qt = get_qt();
	$qt->setv('template_name', 'filer');

	if ( ! isset($_SESSION['app_update']))
	{
		$_SESSION['app_update'] = array();
	}

	//実行ユーザーで書き込みが可能かチェック
	$is_writable = $_SESSION['app_update']['is_writable'] = local_is_writable();

	$qt->appendv('plugin_script', '<script src="'.PLUGIN_DIR.'app_update/app_update.js"></script>');


	$mode = isset($vars['mode']) ? $vars['mode'] : '';
	$config = isset($vars['config']) ? TRUE : FALSE;

	$func_name = 'plugin_app_update_' . $mode . '_';
	
	if (function_exists($func_name))
	{
		return $func_name();
	}


	// 1. アップデート確認
	$title = sprintf(__('%sの更新'), APP_NAME);

	$tmpl_file = PLUGIN_DIR . 'app_update/index.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);
}

function plugin_app_update_confirm_()
{
	global $vars, $script;

	if ( ! $_SESSION['app_update']['is_writable'])
	{
		if ( ! isset($_SESSION['ftp_config']))
		{
			return plugin_app_update_ftp_connect_();
			exit;
		}
	}

	$config = isset($vars['config']) ? TRUE : FALSE;

	$title = sprintf(__('%sの更新'), APP_NAME);
	
	$version = plugin_app_update_get_version();

	$tmpl_file = PLUGIN_DIR . 'app_update/confirm.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);
}

function plugin_app_update_ftp_connect_()
{
	global $vars, $script;
	
	if (isset($_SESSION['app_update']['is_writable']) && $_SESSION['app_update']['is_writable'])
	{
		redirect($script . '?cmd=app_update');
		exit;
	}
	
	$config = isset($vars['config']) ? TRUE : FALSE;
	
	$ftp_config = array(
		'hostname'=>'localhost',
		'username' => get_current_user(),
		'dir' => '',
	);
	
	$title = sprintf(__('%sの更新'), APP_NAME);
	$ftp_type = 'default';
	

	if (isset($vars['ftp_connect']))
	{
		
		if (isset($vars['ftp_hostname']))
		{
			$ftp_config['hostname'] = $vars['ftp_hostname'];
		}
		if (isset($vars['ftp_username']))
		{
			$ftp_config['username'] = $vars['ftp_username'];
		}
		if (isset($vars['install_dir']))
		{
			$ftp_config['dir'] = $vars['install_dir'];
		}
	
		$ftp_config['password'] = $vars['ftp_password'];
		
		
		$config = array(
			'ftp_config' => $ftp_config
		);
		$fs = new QHM_FS($config);
	
		// FTP login
		if ($fs->ftp->connect($ftp_config))
		{
			plugin_app_update_clean_tmpfile();
			$tmp_file = realpath(tempnam(CACHE_DIR, 'app_update-'));
			chmod($tmp_file, 0666);
			
			$rand_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-#!"$%&\'()^~|@{}[]*:;?/><.,'), 0, 16);
			file_put_contents($tmp_file, $rand_str);
			
			if (($abpath = $fs->ftp->get_ftp_ab_path($tmp_file)) === FALSE)
			{
				// display web dir form
				$fs->ftp->check_web_dir($tmp_file, $ftp_config['dir']);
			}
			else
			{
				//haik-contents/cache を削除
				$fs->ftp->dir = substr($fs->ftp->dir, 0, strrpos($fs->ftp->dir, CACHE_DIR));
			}

			unlink($tmp_file);

			// 設置先フォルダをセット
			$ftp_config['dir'] = $fs->ftp->dir;
			
	
			if ($fs->ftp->serverTest())
			{
				//FTP接続情報を保存
				$_SESSION['ftp_config'] = $ftp_config;
				$fs->ftp->close();
				
//				return plugin_app_update_update_();
				redirect($script . '?cmd=app_update&mode=confirm');
				exit;

			}
			else
			{
				// invalid dir
				$error = $fs->ftp->errmsg;
				$ftp_type = 'full';
				$title = __('FTP 接続：エラー');
			}
			
			$fs->ftp->close();
		}
		else
		{
			// cannot connect
			$error = $fs->ftp->errmsg;
			$ftp_type = 'default';
			$title = __('FTP 接続：エラー');
		}
	}


	$tmpl_file = PLUGIN_DIR . 'app_update/ftp_connect.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);

}

function plugin_app_update_update_()
{
	global $script;
	
	$title = sprintf(__('%sの更新'), APP_NAME);

	$config = array();
	if (isset($_SESSION['ftp_config']))
	{
		$_SESSION['ftp_config']['connect'] = TRUE;
		$config['ftp_config'] = $_SESSION['ftp_config'];
	}
	
	$fs = new QHM_FS($config);
	
	$errmsg = '';
	$errmsg = plugin_app_update_update_files($fs);

	if ( ! is_null($fs->ftp))
	{
		$fs->ftp->close();
	}
	
	if ($errmsg)
	{
		$tmpl_file = PLUGIN_DIR . 'app_update/error.html';
	}
	else
	{
		$tmpl_file = PLUGIN_DIR . 'app_update/complete.html';
	}
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);
	
	exit;

}

function plugin_app_update_cancel_()
{
	global $script, $vars;
	
	unset($_SESSION['app_update']);
	unset($_SESSION['ftp_config']);
	
	$url = $script;
	if (isset($vars['config']))
	{
		$url = $url."?cmd=app_config";
	}
	
	redirect($url);
}

function plugin_app_update_clean_tmpfile()
{
	$files = glob(CACHE_DIR . 'app_update-*');
	foreach ($files as $file)
	{
		unlink($file);
	}
}



function plugin_app_update_download_package()
{

	clearstatcache();
	
	$version = plugin_app_update_get_version();
	$get_url = sprintf(APP_PACKAGE_URL_FORMAT, rawurlencode($version));
	$tmp_file = tempnam(CACHE_DIR, 'app_update-');
	file_put_contents($tmp_file, file_get_contents($get_url));
	
	if (filesize($tmp_file) > 0)
	{
		return $tmp_file;
	}
	
	return FALSE;
	
}


/**
* ファイルの更新
* 
* @param class QHM_FS
* @return string error
*/
function plugin_app_update_update_files($fs)
{
	$mode = 'update';
	$error = '';

	// package archive file(.zip) download
	$package = plugin_app_update_download_package();
	if ( ! $package)
	{
		return __('アップロードファイルが取得できませんでした');
	}

	$unzip = new Unzip();
	$file_locations = $unzip->extract($package, rtrim(CACHE_DIR, '/'));
	
	unlink($package);
	
	$version = trim(plugin_app_update_get_version());
	$update_dir = CACHE_DIR . 'haik-' . $version;
	
	
	foreach ($file_locations as $filepath)
	{
		$fs->changeidir();
		
		$name = basename($filepath);
		$orgpath = dirname($filepath);

		$path = preg_replace('/^'. preg_quote($update_dir, '/').'/', '', $orgpath);
		$path = $path . '/';

		$realpath = $fs->pwd() . '/' . $path;
		
		//存在しないディレクトリを作成
		if ( ! $fs->is_exists($realpath))
		{
			$fs->mkdir($realpath, 0755, TRUE);
		}

		$fs->changeidir();
		
		//ファイルをコピー
		$rempath = $realpath . $name;
		$locpath = $orgpath . '/' . $name;
		
		$fs->upload($locpath, $rempath);
		
	}

	// cache を消す
	
	if ( ! $fs->delete_dir($update_dir))
	{
		rmdir($update_dir);
	}
	$fs->changeidir();


	//permission
	
	include(CONFIG_DIR . 'perms.ini.php');
	
	foreach ($perms as $dir => $perm)
	{
		$fs->chmod($dir, $perm);
		if ($perm === 0777)
		{
			// ディレクトリ内のファイルは、666にする
			// .htaccess は 644
			$files = $fs->lsR($dir);
			foreach ($files as $file)
			{
				if ($fs->is_dir($file))
				{
					$fs->chmod($file, $perm);
				}
				else
				{
					if (basename($file) === '.htaccess')
					{
						$fs->chmod($file, 0644);
					}
					else
					{
						$fs->chmod($file, 0666);
					}
				}
			}
		}
	}

	//version キャッシュの削除
	$version_cache_file = CACHE_DIR . 'version.dat';
	if (file_exists($version_cache_file))
		unlink($version_cache_file);

	return $error;
}

/**
 *   最新バージョンの取得
 * @return string error
 *   
 */
function plugin_app_update_get_version()
{
	static $version;
	
	if ( ! isset($version)) $version = file_get_contents(APP_VERSION_URL);
	
	return $version;
}

function plugin_app_update_check_version_()
{
	$version_cache_file = CACHE_DIR . 'version.dat';
	
	//最新バージョン番号はキャッシュし、1日に一度までしかチェックしない
	if (file_exists($version_cache_file) && (filemtime($version_cache_file) + 86400) > time() )
	{
		$new_version = file_get_contents($version_cache_file);
	}
	else
	{
		$new_version = plugin_app_update_get_version();
		file_put_contents($version_cache_file, $new_version, LOCK_EX);
	}
	$cur_version = APP_VERSION;
	
	$json = array(
		'newest' => $new_version,
		'current' => $cur_version,
	);
	
	if (version_compare($new_version, $cur_version, '>'))
	{
		$json['update'] = TRUE;
	}
	else
	{
		$json['update'] = FALSE;
	}
	
	print_json($json);
	exit;

}


/* End of file app_update.inc.php */
/* Location: /haik-contents/plugin/app_update.inc.php */