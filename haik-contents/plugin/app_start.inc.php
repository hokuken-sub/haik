<?php 
/**
 *   app startup
 *   -------------------------------------------
 *   app_start.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/06/18
 *   modified :
 *
 *   haik.ini.php がない場合、初期設定を行う。
 *   
 */

require_once(LIB_DIR . 'Ftp.php');
require_once(LIB_DIR . 'qhm_fs.php');

function plugin_app_start_action()
{
	global $script, $vars, $app_start, $config, $style_name, $admin_style_name;

	$title = sprintf(__('ようこそ %s へ'), APP_NAME);
	$description = sprintf(__('%sの初期設定をします'), APP_NAME);

	if ( ! $app_start)
	{
		redirect($script);
		exit;
	}

	$qt = get_qt();

	//password checker を読み込む
	$passwdcheck_options = array(
		'rankWeakLabel'          => '単純すぎます',
		'rankBetterLabel'        => '簡単です',
		'rankGoodLabel'          => '安心です',
		'rankStrongLabel'        => '強力です',
		'rankGodLabel'           => '強力です！',
		'tooShortErrorLabel'     => '短すぎます',
		'tooLongErrorLabel'      => '長すぎます',
		'hasForbiddenCharsLabel' => '使用できない文字が含まれています',
	);
	$include_script = '
<script type="text/javascript" src="'.JS_DIR.'jquery.passwdcheck.js"></script>
<script type="text/javascript">
$(function(){
			$("input[name=passwd]").passwdcheck($.extend({}, ORGM.passwdcheck.options, {placeholderClass:"col-sm-5"}));
});
</script>
';
	$qt->prependv_once('plugin_app_start_script', 'plugin_script', $include_script);
	$qt->setjsv('passwdcheck', array(
		'options' => $passwdcheck_options
	));

	$style_name = $admin_style_name;
	$qt->setv('template_name', 'dialogue');

	if ( ! isset($_SESSION['app_start']))
	{
		$_SESSION['app_start'] = array();
	}

	//実行ユーザーで書き込みが可能かチェック
	$_SESSION['app_start']['is_writable'] = local_is_writable();

	//2 の場合はユーザー名、パスワードの初期化のみ行う
	if ($app_start == 2)
	{
		return plugin_app_start_set_auth();
	}
	
	$mode = isset($vars['mode']) ? $vars['mode'] : '';

	$func_name = 'plugin_app_start_' . $mode . '_';
	if (function_exists($func_name))
	{
		return $func_name();
	}

	$tmpl_file = PLUGIN_DIR . 'app_start/index.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);
}

/**
 * 認証設定フォーム表示
 */
function plugin_app_start_set_auth_()
{
	global $vars, $script;

	if ( ! $_SESSION['app_start']['is_writable'])
	{
		if ( ! isset($_SESSION['ftp_config']))
		{
			return plugin_app_start_ftp_connect_();
		}
	}
	
	$title = sprintf(__('あと一歩です。'), APP_NAME);
	$example_passwd = create_password();

	$vars = array_merge(array(
		'username' => '',
	), $vars);

	$errmsg = isset($vars['app_start_set_auth_err']) ? $vars['app_start_set_auth_err'] : '';

	$tmpl_file = PLUGIN_DIR . 'app_start/set_auth.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);
}

/**
 * 認証設定フォーム表示（$app_start == 2）
 */
function plugin_app_start_set_auth()
{
	global $app_ini_path, $script, $vars;
	
	if ( ! is_writable($app_ini_path))
	{
		if ( ! isset($_SESSION['ftp_config']))
		{
			return plugin_app_start_ftp_connect_();
		}
	}
	
	
	if ($vars['mode'] === 'init')
	{
		$errmsg = '';
		
		if ($vars['username'] == '')
		{
			$errmsg = __('メールアドレスが未入力です。');
		}
		else if ( ! is_email($vars['username']))
		{
			$errmsg = __('メールアドレスが正しくありません。');
		}
		else if (strlen($vars['passwd']) < 8)
		{
			$errmsg = __('パスワードは8文字以上をご指定ください。');
		}
		else if (strlen($vars['passwd']) > 32)
		{
			$errmsg = __('パスワードは32文字以内をご指定ください。');
		}
		else if ( ! preg_match('/^[a-zA-Z0-9`~!@#$%^&*\(\)_\+=\{\}\[\]\|:;"\'<>,.?\/ -]+$/', $vars['passwd']))
		{
			$errmsg = __('パスワードにご利用できない文字が入っています。');
		}
		
		if ($errmsg === '')
		{
			plugin_app_start_save_auth($vars['username'], $vars['passwd']);
			set_flash_msg(__('管理者設定を更新しました'));
			redirect($script);
			
		}

	}
	
	$title = sprintf(__('あと一歩です。'), APP_NAME);
	
	$tmpl_file = PLUGIN_DIR . 'app_start/set_auth.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);

}

/**
 * フォーム内容チェック。
 * 良ければ、set_auth 開始後、
 * ログイン。
 */
function plugin_app_start_init_()
{
	global $script, $vars;
	
	$errmsg = __('送信内容にエラーがあります。もう一度、お試しください。');
	
	$config = array();
	if (isset($_SESSION['ftp_config']))
	{
		$_SESSION['ftp_config']['connect'] = TRUE;
		$config['ftp_config'] = $_SESSION['ftp_config'];
	}
	
	$fs = new QHM_FS($config);
	
	if (isset($vars['username']) && isset($vars['passwd']))
	{

		$errmsg = '';
		
		if ($vars['username'] == '')
		{
			$errmsg = __('メールアドレスが未入力です。');
		}
		else if ( ! is_email($vars['username']))
		{
			$errmsg = __('メールアドレスが正しくありません。');
		}
		else if (strlen($vars['passwd']) < 8)
		{
			$errmsg = __('パスワードは6文字以上でご指定ください。');
		}
		else if (strlen($vars['passwd']) > 32)
		{
			$errmsg = __('パスワードは32文字以内をご指定ください。');
		}
		else if ( ! preg_match('/^[a-zA-Z0-9`~!@#$%^&*\(\)_\+=\{\}\[\]\|:;"\'<>,.?\/ -]+$/', $vars['passwd']))
		{
			$errmsg = __('パスワードにご利用できない文字が入っています。');
		}
		
		if ($errmsg === '')
		{
			//init
			plugin_app_start_copy_skel($fs);

			//permission
			plugin_app_start_set_permission($fs);
			
			//save username and passwd
			plugin_app_start_save_auth($vars['username'], $vars['passwd']);

			//Complete
			return plugin_app_start_complete_();
			
		}
	}
	
	$vars['app_start_set_auth_err'] = $errmsg;

	return plugin_app_start_set_auth_();
	

}

function plugin_app_start_set_sitecopy()
{
	global $vars, $defaultpage;
	
	$config = orgm_ini_read();

	$main_copy = isset($vars['maincopy']) ? $vars['maincopy'] : $config['site_title'];
	$sub_copy = isset($vars['subcopy']) ? $vars['subcopy'] : '';

	$ptns = array(
		'/^\/\/ MAIN_COPY$/',
		'/^\/\/ SUB_COPY$/'
	);
	$rpls = array(
		'&h1{' . $main_copy . '};',
		$sub_copy
	);
	
	$lines = get_source($defaultpage);

	$lines = preg_replace($ptns, $rpls, $lines);
	
	$source = join('', $lines);
	
	file_put_contents(get_filename($defaultpage), $source, LOCK_EX);

}	

function plugin_app_start_ftp_connect_()
{
	global $vars, $script;
	
	if (isset($_SESSION['app_start']['is_writable']) && $_SESSION['app_start']['is_writable'])
	{
		redirect($script . '?cmd=app_start&mode=set_auth');
		exit;
	}
	
	
	$ftp_config = array(
		'hostname'=>'localhost',
		'username' => get_current_user(),
		'dir' => APP_HOME,
	);
	
	$title = __('FTP接続');
	
	$ftp_type = is_writable(CACHE_DIR) ? 'default' : 'full';

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
			plugin_app_start_clean_tmpfile();
			$tmp_file = realpath(tempnam(CACHE_DIR, 'app_start-'));
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
				
				redirect($script . '?cmd=app_start&mode=set_auth');
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
			$ftp_type = 'full';
			$title = __('FTP 接続：エラー');
		}
	}


	$tmpl_file = PLUGIN_DIR . 'app_start/ftp_connect.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);

}

function plugin_app_start_set_info_()
{
	global $script;
	
	$conf = array('app_start' => 0);
	orgm_ini_write($conf);
	
	plugin_app_start_set_sitecopy();

	redirect($script);
	exit;
}

function plugin_app_start_complete_()
{
	global $script;

	// iniファイルからユーザー名を取得（表示用）
	$config = orgm_ini_read();
	$username = $config['username'];

	$title = sprintf(__('ようこそ %s へ！'), APP_NAME);
	
	plugin_app_start_send_mail();
	
	$tmpl_file = PLUGIN_DIR . 'app_start/info.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);

}

function plugin_app_start_guide_()
{
	$title = sprintf(__('%s のはじめかた'), APP_NAME);
	
	$conf = array('app_start' => 0);
	orgm_ini_write($conf);

	$tmpl_file = PLUGIN_DIR . 'app_start/guide.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);
}

function plugin_app_start_send_mail()
{
	global $script;
	
	$config = orgm_ini_read();
	$username = $config['username'];
	
	$merge_tags = array(
		'app_name' => APP_NAME,
		'url' => $script,
		'username' => $username,
		'app_site' => APP_OFFICIAL_SITE,
	);
	
	$subject = sprintf(__('%s へようこそ！'), APP_NAME);

	$body = __('新しい *|APP_NAME|* サイトの設置に成功しました。
ありがとうございます !

あなたのサイトは、下記のURLです。

*|URL|*



下記の情報を使って、管理者としてログインできます:

メールアドレス：*|USERNAME|*
パスワード: 管理者設定で入力したパスワード

それではサイト作成を楽しんでください。


パスワードをお忘れの場合は、
下記のリンクより、パスワードの再設定をしてください。
*|URL|*?cmd=reset_pw



*|APP_NAME|* by Hokuken.Inc
*|APP_SITE|*

');

	orgm_mail_notify($subject, $body, $merge_tags, $username);

}

function plugin_app_start_copy_skel($fs)
{
	global $defaultpage;
	$files = scandir(SKEL_DIR);

	foreach ($files as $file)
	{
		if ($file === '.' OR $file === '..')
			continue;
		
		$filepath = SKEL_DIR . $file;
		
		if (is_dir($filepath))
		{
			$dstpath = DATA_HOME . $file;
			plugin_app_start_copy_r($fs, $filepath, $dstpath, FALSE);
		}
		else
		{
			$filename = $file;
			if ($filename === 'skel.htaccess')
			{
				//special copy
				// - rename to .htaccess
				// - replace # BEGIN # END
				
				$dst = APP_HOME . '.htaccess';
				
				$haik_htaccess = file_get_contents($filepath);
				$org_htaccess = file_exists($dst) ? file_get_contents($dst) : '';

				if (preg_match('/^# BEGIN haik$/m', $org_htaccess) && preg_match('/^# END haik$/m', $org_htaccess))
				{
					$htaccess = $org_htaccess;
				}
				else
				{
					$htaccess = $org_htaccess . "\n" . $haik_htaccess;
				}
				
				$fs->write($dst, $htaccess);
				$fs->chmod($dst, 0666);
				
			}
			else if ($filename === '.htaccess')
			{
				//no copy
			}
			else
			{
				//normal copy
				$dst = APP_HOME . $filename;
				if ( ! file_exists($dst))
				{
//echo $filepath, ' -&gt; ', $dst, '<br>';
					$fs->changeidir();
					$fs->upload($filepath, $dst);
				}
			}
		}
	}

	// FrontPage は自動的に作られるため、
	// ページ内容が空の場合、skelをコピーする
	
	if (filesize(get_filename($defaultpage)) === 0)
	{
//var_dump($defaultpage, filesize(get_filename($defaultpage)));
		$src = SKEL_DIR . 'wiki/' . encode($defaultpage) . '.txt';
		$dst = get_filename($defaultpage);
		$fs->changeidir();
		$fs->upload($src, $dst);
	}

}

/**
 * Set Permission
 * @params QHM_FS $fs
 */
function plugin_app_start_set_permission($fs)
{

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

}

function plugin_app_start_save_auth($username, $passwd)
{
	$passwd = pkwk_hash_compute($passwd);
	$conf = compact('username', 'passwd');
	orgm_ini_write($conf);
}

function plugin_app_start_clean_tmpfile()
{
	$files = glob(CACHE_DIR . 'app_start-*');
	foreach ($files as $file)
	{
		unlink($file);
	}
}


function plugin_app_start_copy_r($fs, $src, $dst, $overwrite = TRUE)
{

	if (is_dir($src))
	{
		$fs->mkdir($dst);
		$files = scandir($src);
		foreach ($files as $file)
		{
			if ($file !== '.' && $file !== '..')
			{
				plugin_app_start_copy_r($fs, "$src/$file", "$dst/$file", $overwrite);
			}
		}
	}
	else if (file_exists($src))
	{
		if ($overwrite OR ! file_exists($dst))
		{
//echo $src, ' -&gt; ', $dst, '<br>';
			$fs->changeidir();
			$fs->upload($src, $dst);
		}
	}

}




/* End of file app_start.inc.php */
/* Location: /app/haik-contents/plugin/app_start.inc.php */