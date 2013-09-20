<?php
/**
 *   Log in
 *   -------------------------------------------
 *   ./login.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/04/18
 *   modified :
 */

function plugin_login_convert()
{

	global $script;
	header('Location: '.$script.'?cmd=login');
	exit;
}


function plugin_login_action()
{
	global $script, $vars, $auth_method_type, $auth_users, $edit_auth_pages, $defaultpage;
	$qm = get_qm();
	
	$page = isset($vars['page']) ? $vars['page'] : '';

	$msg = __('ユーザー認証');


	// Checked by:
	$target_str = '';
	if ($auth_method_type == 'pagename') {
		$target_str = $page; // Page name
	} else if ($auth_method_type == 'contents') {
		$target_str = join('', get_source($page)); // Its contents
	}

	$user_list = array();
	foreach($edit_auth_pages as $key=>$val)
		if (preg_match($key, $target_str))
			$user_list = array_merge($user_list, explode(',', $val));

	if (empty($user_list)) return array('msg'=>$msg, 'body'=>"<p>ユーザー設定が間違っています。</p>"); //TRUE; // No limit
	
	
	//--------------------------------------------
	//Customize from here
	//Session Auth instead of Basic Auth
	//Thanks & Refer SiteDev + AT by AKKO

	 if (is_login())
	 {
	 	set_flash_msg('既に認証済みです。', 'info');
	 	redirect($script);
	 	exit;
	}
	
    $fg = FALSE;

	$fg = ss_chkusr('ユーザー認証', $auth_users);
	if($fg){
		$_SESSION['usr'] = $_POST['username'];
		
		$to = $script;
		if (isset($vars['refer']) && is_page($vars['refer']) && $vars['refer'] !== $defaultpage)
		{
			$to = get_page_url($vars['refer']);
		}
		
		set_flash_msg('ログインしました。');
		
		redirect($to);
		exit;
	}

	auth_catbody($msg, '<div><h2>認証を拒否しました</h2><p>ユーザー名、パスワードが間違っています。</p></div>');
	exit;
}

/* End of file login.inc.php */
/* Location: /haik-contents/plugin/login.inc.php */