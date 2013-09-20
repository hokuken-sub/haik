<?php
/**
 *   User Unlock Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/login_unlock.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/08/30
 *   modified :
 *   
 *   
 *   
 *   Usage :
 *   
 */


function plugin_login_unlock_action()
{
	global $script, $vars;
	
	$unlock = -1;
	if ( ! plugin_login_unlock_check()) {
		// error
		$unlock = 0;
//		var_dump('<pre>', ss_get_trycnt_data());exit;
	}
	else
	{
		$user = $vars['username'];
		
//		var_dump('unlock');exit;
		
		// ロックの解除
		$data = ss_get_trycnt_data();
		if (isset($data[$user]))
		{
			$data[$user]['locked'] = FALSE;
			$data[$user]['count'] = 0;
			$data[$user]['token'] = '';
		
			ss_set_trycnt_data($data);
			
			$unlock = 1;
		}
	}


	redirect($script.'?cmd=login&unlocked=' . $unlock);

}

function plugin_login_unlock_set_token()
{
	global $vars, $script;
	
	$user = $vars['username'];
	
	require_once(LIB_DIR."Mcrypt.php");
	$token = ORMcrypt::get_key(48);

	$data = ss_get_trycnt_data();
	$data[$user]['token'] = $token;

	ss_set_trycnt_data($data);
	
	$code = sha1(md5($data[$user]['token'] . $data[$user]['time']));
	
	// メール送信
	$reset_url = $script.'?cmd=login_unlock&username='.rawurlencode($user).'&code='.$code;

	$subject = __('ログインをロックしました');
	$message = __("間違ったユーザー名、パスワードが 5回連続で入力されました。\n安全のために、ログイン画面をロックしました。\n\nロックの解除には、下記のURLをクリックしてください。\n\n*|URL|*\n");
	$merge_tags = array(
		'url' => $reset_url,
	);
	
	orgm_mail_notify($subject, $message, $merge_tags);
	
}

function plugin_login_unlock_check()
{
	global $vars;

	$data = ss_get_trycnt_data();
	
	$user = $vars['username'];
	$code = $vars['code'];
	
	$correct_code = sha1(md5($data[$user]['token'] . $data[$user]['time']));
	
	if (isset($data[$user]))
	{
		if ($correct_code === $code
		 OR ! $data[$user]['locked'])
		{
			return TRUE;
		}
	}	
	return FALSE;	

}
