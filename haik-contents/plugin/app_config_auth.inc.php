<?php
/**
 *   Application Config Apps Plugin
 *   -------------------------------------------
 *   plugin/app_config_apps.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/02/06
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

function plugin_app_config_auth_init()
{
	
	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}

}


function plugin_app_config_auth_action()
{
	global $script, $vars;
	global $username, $passwd;
	
	$title = __('管理者');
	$description = __('ログイン時のメールアドレスとパスワードの設定をします');

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
';
	$qt->prependv_once('plugin_app_config_auth_script', 'plugin_script', $include_script);

	$qt->setjsv('passwdcheck', array(
		'options' => $passwdcheck_options
	));
	
	$example_passwd = create_password();
	

	if (isset($vars['phase']) && $vars['phase'] === 'save')
	{
		$fields = array('username', 'passwd');
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
			
			$res = array('value'=>$vars['username']);
		}
		
		if (isset($vars['passwd']) OR isset($vars['new_passwd']) OR isset($vars['re_passwd']))
		{
			if ( ! isset($vars['passwd']) || ! check_passwd($vars['passwd'], $passwd))
			{
				print_json(array('error' => __('現在のパスワードと、一致しません') , 'item' => 'passwd'));
				exit;
			}
	
			if ( ! isset($vars['new_passwd']) || ($vars['new_passwd'] == '') || (strlen($vars['new_passwd']) < 8))
			{
				print_json(array('error' => __('新しいパスワードは8文字以上をご指定ください') , 'item' => 'new_passwd'));
				exit;
			}

			if (strlen($vars['new_passwd']) > 32)
			{
				print_json(array('error' => __('新しいパスワードは32文字以内をご指定ください') , 'item' => 'new_passwd'));
				exit;
			}
			
			if ( ! preg_match('/^[a-zA-Z0-9`~!@#$%^&*\(\)_\+=\{\}\[\]\|:;"\'<>,.?\/ -]+$/', $vars['new_passwd']))
			{
				print_json(array('error' => __('パスワードにご利用できない文字が入っています') , 'item' => 'new_passwd'));
				exit;
			}

			if ( ! isset($vars['re_passwd']) || ($vars['re_passwd'] != $vars['new_passwd']))
			{
				print_json(array('error' => __('パスワードの確認が正しくありません') , 'item' => 're_passwd'));
				exit;
			}
			
			$vars['passwd'] = pkwk_hash_compute($vars['new_passwd']);
			$res = array('value' => '******');
		}
		
		$data = array_intersect_key($vars, array_flip($fields));
		orgm_ini_write($data);

		$res['message'] = '設定を更新しました';
		
		//passwd を変更した場合、
		//強制ログアウトし、ログインを促す
		if (isset($data['passwd']) && $data['passwd'])
		{
			$_SESSION = array();
			ss_auth_logout();
			$res['item'] = 'passwd';
			$res['message'] = 'パスワードを変更しました。<br>もう一度ログインし直してください。';
			$res['redirect_to'] = $script . '?cmd=login';
		}
		//username を変更した場合、
		//セッションも書き換える
		else if (isset($data['username']) && $data['username'])
		{
			$_SESSION['usr'] = $data['username'];
		}
		
		print_json($res);
		exit;
	}
	else if (isset($vars['phase']) && $vars['phase'] === 'mailcheck')
	{
		$to = $vars['username'];

		$subject = __('メール送信テスト');
		$message = __("メールの送信テストです。\n\n送信元：*|SENDER|*");
		$merge_tags = array(
			'sender' => $script,
		);
		$ret = orgm_mail_notify($subject, $message, $merge_tags, $to);
		if ($ret)
		{
			print_json(array('success' => __('メールを送信しました')));
			exit;
		}
		
		print_json(array('error' => __('メールが失敗しました')));
		exit;
	}


//	$vars = array_merge(compact(array('username')), $vars);

	$tmpl_file = PLUGIN_DIR . 'app_config/auth.html';

	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => $title, 'body' => $body);

}


/* End of file orgm_setting.inc.php */
/* Location: plugin/orgm_setting.inc.php */