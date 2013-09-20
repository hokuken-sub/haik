<?php
/**
 *   特殊なサイトのリンク設定
 *   -------------------------------------------
 *   app_config_script.inc.php
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
 
function plugin_app_config_script_init()
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


function plugin_app_config_script_action()
{
	global $vars, $config, $script, $script_ssl, $default_script, $default_script_ssl;
	global $username, $passwd;

	$title = __('特殊なサーバーの設定');
	$description = __('リンクの設定');
	
	if (isset($vars['phase']) && $vars['phase'] === 'save')
	{
		$fields = array('script', 'script_ssl');
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

		$res_message = '';

		if (isset($vars['script']))
		{
			$res_message =  __('リンク設定: ').($vars['script'] == '' ? '未設定' : '設定あり');
		}

		if (isset($vars['script_ssl']))
		{
			$res_message .=  __(',　SSLリンク設定: ').($vars['script_ssl'] == '' ? '未設定' : '設定あり');
		}
		$res = array('value'=> $res_message);

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
		$qt->setjsv(array('options'=>array('script'=> $default_script, 'script_ssl'=>$default_script_ssl)));
	}
	
	$qt->appendv('plugin_script', '<script type="text/javascript" src="'.PLUGIN_DIR.'app_config/redirect.js"></script>');
	

	$tmpl_file = PLUGIN_DIR . 'app_config/script.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => $title, 'body' => $body);


}

?>