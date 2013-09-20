<?php

function plugin_facebook_auth_action()
{
	global $script, $vars, $defaultpage;
	global $fb_app_id, $fb_app_secret, $fb_group_id;

	$callback = $script . '?cmd=facebook_auth';
	
	$code = $vars['code'];
	$token_url = 'https://graph.facebook.com/oauth/access_token?client_id='.
    $fb_app_id . '&redirect_uri=' . urlencode($callback) . '&client_secret='.
    $fb_app_secret . '&code=' . $code;
    
    $access_token = file_get_contents($token_url);
	
	// 指定したグループに所属しているか確認
	$belongsTo = FALSE;
	
	$data_json = file_get_contents('https://graph.facebook.com/me/groups?' . $access_token);
	$data = json_decode($data_json);
	$groups = $data->data;
	
	foreach ($groups as $group)
	{
		if ($group['id'] === $fb_group_id)
		{
			$belongsTo = TRUE;
		}
	}
	
	$user = FALSE;
	if ($belongsTo)
	{
		$user_json = file_get_contents('https://graph.facebook.com/me?' . $access_token);
		$user = json_decode($user_json);
	}
	
	$fb_users_file = CACHE_DIR . 'fb_users.cache';
	if (file_exists($fb_users_file))
		$fb_users = unserialize(file_get_contents($fb_users_file));
	
	if ($belongsTo)
	{
		if ( ! isset($fb_users[$user->id]))
		{
			$fb_users[$user->id] = array(
				'id' => $user->id,
				'name' => $user->name,
			);
			
			file_put_contents($fb_users_file, serialize($fb_users));
		}
		
		//log in
		$_SESSION['fb_user'] = $fb_users[$user->id];
		set_flash_msg('Facebook ログインしました。');
		redirect($script . '?' . $defaultpage);
	}
	else
	{
		echo '閲覧できません。';
		exit;
	}
	
}

function plugin_facebook_auth_login()
{
	global $script;
	global $fb_app_id, $fb_app_secret;

	$callback = $script . '?cmd=facebook_auth';
	 
	$authURL = 'http://www.facebook.com/dialog/oauth?client_id=' .
	    $fb_app_id . '&scope=user_groups&redirect_uri=' . urlencode($callback);
	    
	 
	header("Location: " . $authURL);	
}

/* End of file facebook_auth.inc.php */
/* Location: plugin/facebook_auth.inc.php */