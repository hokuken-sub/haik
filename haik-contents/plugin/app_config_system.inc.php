<?php
/**
 *   Application Config Plugin
 *   -------------------------------------------
 *   plugin/app_config.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/01/29
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

function plugin_app_config_system_init()
{
	
	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}
	
}


function plugin_app_config_system_action()
{
	global $vars, $config;
	global $enable_cache, $autolink, $check_login, $ogp_tag, $google_api_key, $change_timestamp;
	global $smtp_auth, $smtp_server, $pop_server, $mail_userid, $mail_passwd;
	global $mail_encode;
	global $fb_auth, $fb_app_id, $fb_app_secret, $fb_group_id, $disqus_shortname;

	$title = __('システム設定');
	$description = __('システムの設定を行います。');

	$qt = get_qt();

	$include_css = '
<link rel="stylesheet" href="'.JS_DIR.'datepicker/css/datepicker.css" />
';

	$qt->prependv_once('plugin_app_config_system_action', 'plugin_head', $include_css);

	$include_script = '
<script type="text/javascript" src="'.JS_DIR.'datepicker/js/bootstrap-datepicker.js"></script>
<script type="text/javascript" src="'.JS_DIR.'datepicker/js/bootstrap-datepicker.ja.js"></script>
';
	$qt->prependv_once('plugin_app_config_system_action', 'plugin_script', $include_script);


	if (isset($vars['phase']) && $vars['phase'] === 'save')
	{
		$fields = array('enable_cache', 'autolink', 'check_login', 'ogp_tag', 'google_api_key', 'change_timestamp', 'smtp_auth', 'smtp_server', 'pop_server', 'mail_userid', 'mail_passwd', 'mail_encode', 'disqus_shortname');
		$res = array();

		if (isset($vars['enable_cache']))
		{
			$res = array('value'=> $vars['enable_cache'] ? __('有効') : __('無効'));
		}

		if (isset($vars['autolink']))
		{
			$res = array('value'=> $vars['autolink'] ? __('有効') : __('無効'));
		}

		if (isset($vars['check_login']))
		{
			$res = array('value'=> $vars['check_login'] ? __('有効') : __('無効'));
		}

		if (isset($vars['google_api_key']))
		{
			$res = array('value'=> $vars['google_api_key'] ? $vars['google_api_key'] : __('設定なし'));
		}

		if (isset($vars['change_timestamp']))
		{
			$res = array('value'=> $vars['change_timestamp'] ? __('常に更新') : __('手動で更新'));
		}

		if (isset($vars['smtp_server']))
		{
			if ($vars['smtp_auth'] == 1)
			{
/*
				if ($vars['pop_server'] == '')
				{
					print_json(array('error' => __('POPサーバーが未入力です'), 'item' => 'pop_server'));
					exit;
				}
*/
				if ($vars['mail_userid'] == '')
				{
					print_json(array('error' => __('ユーザーが未入力です'), 'item' => 'mail_userid'));
					exit;
				}
				if ($vars['mail_passwd'] == '')
				{
					print_json(array('error' => __('パスワードが未入力です'), 'item' => 'mail_passwd'));
					exit;
				}
			}
			$res = array('value'=> ($vars['smtp_server'] == '') ? __('標準') : __('SMTP送信'));
		}
		
		if (isset($vars['mail_encode']))
		{
			$res = array('value'=> ($vars['mail_encode'] == 'UTF-8') ? __('日本語以外の言語（UTF-8）') : __('日本語（ISO-2022-JP）'));
		}
		
		if (isset($vars['fb_auth']))
		{
			if ($vars['fb_auth'] == 1)
			{
				if ($vars['fb_app_id'] == '')
				{
					print_json(array('error' => __('FacebookアプリIDが未入力です'), 'item' => 'fb_app_id'));
					exit;
				}
				if ($vars['fb_app_secret'] == '')
				{
					print_json(array('error' => __('Facebook secret が未入力です'), 'item' => 'fb_app_secret'));
					exit;
				}
				if ($vars['fb_group_id'] == '')
				{
					print_json(array('error' => __('FacebookグループIDが未入力です'), 'item' => 'fb_group_id'));
					exit;
				}
			}
			$res = array('value'=> $vars['fb_auth'] ? __('有効') : __('無効'));
		}

		if (isset($vars['disqus_shortname']))
		{
			$res = array('value'=> $vars['disqus_shortname'] ? $vars['disqus_shortname'] : '設定なし');
		}

		$data = array_intersect_key($vars, array_flip($fields));
		$res['options'] = $data;
		orgm_ini_write($data);

		$res['message'] = '設定を更新しました';
		
		print_json($res);
		exit;
	}
	else if (isset($vars['phase']) && $vars['phase'] === 'exec')
	{
		if ($vars['mode'] === 'maintenance')
		{
			$msg = '';
			
			// !キャッシュファイルの削除
			if ($dir = opendir(CACHE_DIR)) {
				while (($file = readdir($dir)) !== false)
				{
					// .qtc .tmp .tmpr
					if (preg_match('/\.(tmp|tmpr|qtc)$/', $file))
					{
						unlink(CACHE_DIR.$file);
					}
					// sess_xxxx
					else if (preg_match('/^sess_/', $file))
					{
						// 日付を見る 1週間前なら消す
						if (filemtime(CACHE_DIR.$file) < strtotime('1 week ago'))
						{
							unlink(CACHE_DIR.$file);
						}
					}
					// xxxx_search.txt
					else if (preg_match('/_search.txt$/', $file))
					{
						unlink(CACHE_DIR.$file);
					}
					// app_update_xxxx
					else if (preg_match('/^app_update-/', $file))
					{
						unlink(CACHE_DIR.$file);
					}
				}
				closedir($dir);
				
				$msg = __('キャッシュを削除しました') . "\n";
			}
			
			// ! ファイル権限の変更
			require_once(LIB_DIR . 'Ftp.php');
			require_once(LIB_DIR . 'qhm_fs.php');
			$fs = new QHM_FS();
			
			if (file_exists(CONFIG_DIR . 'perms.ini.php'))
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
				
				$msg .= __('ファイル権限をチェックしました。');
			}
			
			$res = array('success'=>$msg);
			
		}

		print_json($res);
		exit;
	}

	$tmpl_file = PLUGIN_DIR . 'app_config/system.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => $title, 'body' => $body);

}


/* End of file orgm_setting.inc.php */
/* Location: plugin/orgm_setting.inc.php */