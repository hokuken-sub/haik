<?php
/**
 *   セッションの保存先設定
 *   -------------------------------------------
 *   app_config_session.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/06/21
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
 
function plugin_app_config_session_init()
{
	global $vars;
	
	$vars['noauth'] = TRUE;
	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}

	$qt = get_qt();
	$qt->setv('template_name', 'narrow');

}


function plugin_app_config_session_action()
{
	global $vars, $config, $script;
	global $session_save_path, $username, $passwd;

	$title = __('特殊なサーバーの設定');
	$description = __('セッションの保存先の設定');
	
	if (isset($vars['phase']) && $vars['phase'] === 'save')
	{
		$fields = array('session_save_path');
		$res = array();

		if (isset($vars['username']))
		{
			if ($vars['username'] == '')
			{
				print_json(array('error' => __('メールアドレスが未入力です') , 'item' => 'username'));
				exit;
			}
			if ( ! is_email($vars['username']))
			{
				print_json(array('error' => __('メールアドレスが正しくありません') , 'item' => 'username'));
				exit;
			}
			if ($username != $vars['username'])
			{
				print_json(array('error' => __('現在のメールアドレスと、一致しません') , 'item' => 'username'));
				exit;
			}
		}

		if (isset($vars['passwd']))
		{
			if ( ! check_passwd($vars['passwd'], $passwd))
			{
				print_json(array('error' => __('現在のパスワードと、一致しません') , 'item' => 'passwd'));
				exit;
			}
		}

		if (isset($vars['session_save_path']))
		{
			$res = array('value'=> ($vars['session_save_path'] == '') ? __('未設定') : __('設定あり'));
		}

		$data = array_intersect_key($vars, array_flip($fields));
		$res['options'] = $data;
		orgm_ini_write($data);
		
		$res['redirectUrl'] = $script . '?cmd=login';

		//ログアウトして、ログイン画面へ飛ばす
		ss_auth_logout();
		secure_session_start();
		set_flash_msg(__('設定を更新しました。<br>ログインしてください。'));
		
		print_json($res);
		exit;
	}
	
	$qt = get_qt();
	if ( ! ss_admin_check())
	{
		$qt->setjsv(array('options'=>array('session_save_path'=> $session_save_path)));
	}
	
	
	$qt->appendv('plugin_script', '<script type="text/javascript" src="'.PLUGIN_DIR.'app_config/redirect.js"></script>');
	

	$tmpl_file = PLUGIN_DIR . 'app_config/session.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => $title, 'body' => $body);

}
?>