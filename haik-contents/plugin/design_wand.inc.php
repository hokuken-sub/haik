<?php 
/**
 *   design wand
 *   -------------------------------------------
 *   design_wand.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/10/10
 *   modified :
 *
 *   デザインの更新、削除などを行う。
 *   
 */

require_once(LIB_DIR . 'Ftp.php');
require_once(LIB_DIR . 'qhm_fs.php');

function plugin_design_wand_action()
{
	global $script, $vars, $config, $style_name, $admin_style_name;

	if ( ! ss_admin_check())
	{
		set_flash_msg('管理者のみアクセスできます。', 'error');
		redirect($script);
		exit;
	}

	$qt = get_qt();

	$style_name = $admin_style_name;
	$qt->setv('template_name', 'filer');

	//javascript
	$include_script = '	<script type="text/javascript" src="'. PLUGIN_DIR .'design_wand/design_wand.js"></script>
';
	$qt->prependv_once('plugin_design_wand_script', 'plugin_script', $include_script);

	//css
	$css = '	<link rel="stylesheet" type="text/css" href="'. PLUGIN_DIR .'design_wand/design_wand.css">
';
	$qt->appendv('plugin_head', $css);

	if ( ! isset($_SESSION['design_wand']))
	{
		$_SESSION['design_wand'] = array();
	}

	//実行ユーザーで書き込みが可能かチェック
	if (! isset($_SESSION['design_wand']['is_writable']))
	{
		$_SESSION['design_wand']['is_writable'] = local_is_writable();
	}


	$mode = isset($vars['mode']) ? strtolower($vars['mode']) : 'list';

	$func_name = 'plugin_design_wand_' . $mode . '_';
	if (function_exists($func_name))
	{
		return $func_name();
	}

	return array('msg'=>'', 'body'=>'');
}

/**
 * 更新のあるデザイン一覧を取得
 */
function plugin_design_wand_check_update()
{

	$skin_path = dir(SKIN_DIR);
	$update_dirs = array();
	while ($entry = $skin_path->read())
	{
		if(is_dir(SKIN_DIR.$entry) && ($entry!='.') && ($entry!='..') && (file_exists(SKIN_DIR.$entry.'/config.php')))
		{
			if ($admin_style_name === $entry)
			{
				continue;
			}
			$arr = style_config_read($entry);
			$arr['skel_version'] = style_config_read_skel($entry, 'version');
			
			if ($arr['version'] < $arr['skel_version'])
			{
				$update_dirs[] = $arr;
			}
		}
	}
	ksort($update_dirs);

	return $update_dirs;

}

/**
 * 更新があるかどうか確認する
 */
function plugin_design_wand_check_version_()
{
	$json = array();
	
	$update_skins = plugin_design_wand_check_update();
	$json['updates'] = $update_skins;
	
	print_json($json);
	exit;	

}

/**
 * デザイン一覧の表示
 */
function plugin_design_wand_list_()
{
	global $vars, $script, $admin_style_name;
	$qt = get_qt();

	$update_dirs = plugin_design_wand_check_update();
	$update_count = count($update_dirs);
	
	$is_ftp = $_SESSION['design_wand']['is_writable'] ? 0 : 1;

	$ftp_config = array(
		'hostname'=>'localhost',
		'username' => get_current_user(),
		'dir' => APP_HOME,
	);

	
	$qt->setjsv(array(
		'design_wand' => array(
			'updates'   => $update_dirs,
			'baseUrl'   => $script.'?cmd=design_wand',
			'updateUrl' => $script.'?cmd=design_wand',
		)
	));

	
	$tmpl_file = PLUGIN_DIR . 'design_wand/index.html';

	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => __('デザイン管理'), 'body' => $body);
}

/**
 * デザインのアップデート
 */
function plugin_design_wand_update_()
{
	global $vars;

	$config = array();
	if (isset($_SESSION['ftp_config']))
	{
		$_SESSION['ftp_config']['connect'] = TRUE;
		$config['ftp_config'] = $_SESSION['ftp_config'];
	}

	$fs = new QHM_FS($config);

	//init
	$ret = plugin_design_wand_copy_skel($fs);
	
	//permission
	plugin_design_wand_set_permission($fs);
	
	print_json(array('error'=>0, 'message'=>'デザインを更新しました。', 'updates'=>$ret));
	exit;
}



function plugin_design_wand_ftp_connect_()
{
	global $vars, $script;

	$res = array(
		'error'    => 0,
		'message'  => '',
		'debug'    => 0,
		'vars'     => $vars,
	);
	
	if (isset($_SESSION['design_wand']['is_writable']) && $_SESSION['design_wand']['is_writable'])
	{
		print_json($res);
		exit;
	}

	$ftp_config = array(
		'hostname'=>'localhost',
		'username' => get_current_user(),
		'dir' => APP_HOME,
	);

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
		plugin_design_wand_clean_tmpfile();
		$tmp_file = realpath(tempnam(CACHE_DIR, 'design_wand-'));
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
			$res['message'] = 'FTP接続：成功しました。';
		}
		else
		{
			// invalid dir
			$res['error'] = 1;
			$res['message'] = __('FTP 接続エラー：').$fs->ftp->errmsg;
		}
		$fs->ftp->close();
	}
	else
	{
		// cannot connect
		$res['error'] = 1;
		$res['message'] = __('FTP 接続エラー：').$fs->ftp->errmsg;
	}

	print_json($res);
	exit;
}

function plugin_design_wand_complete_()
{
	global $script;

	$tmpl_file = PLUGIN_DIR . 'design_wand/complete.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	return array('msg'=>$title, 'body'=>$body);
}

function plugin_design_wand_copy_skel($fs)
{
	global $vars;

	$ret = array();

	$designs = array();
	if (isset($vars['update_designs']) && count($vars['update_designs']) > 0)
	{
		$designs = $vars['update_designs'];
	}
	
	if (count($designs) == 0)
	{
		return $ret;
	}
	
	$skel_skin_dir = SKEL_DIR . 'skin/';
	$files = scandir($skel_skin_dir);

	foreach ($files as $file)
	{
		if ($file === '.' OR $file === '..')
			continue;
		
		$filepath = $skel_skin_dir . $file;
		if (is_dir($filepath))
		{
			if (! in_array($file, $designs))
			{
				continue;
			}
			$dstpath = SKIN_DIR . $file;
			plugin_design_wand_copy_r($fs, $filepath, $dstpath, TRUE);
			$ret[] = $file;
		}
	}
	return $ret;
}

/**
 * Set Permission
 * @params QHM_FS $fs
 */
function plugin_design_wand_set_permission($fs)
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

function plugin_design_wand_clean_tmpfile()
{
	$files = glob(CACHE_DIR . 'design_wand-*');
	foreach ($files as $file)
	{
		unlink($file);
	}
}


function plugin_design_wand_copy_r($fs, $src, $dst, $overwrite = TRUE)
{
	if (is_dir($src))
	{
		$fs->mkdir($dst);
		$files = scandir($src);
		foreach ($files as $file)
		{
			if ($file !== '.' && $file !== '..')
			{
				plugin_design_wand_copy_r($fs, "$src/$file", "$dst/$file", $overwrite);
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




/* End of file design_wand.inc.php */
/* Location: /app/haik-contents/plugin/design_wand.inc.php */