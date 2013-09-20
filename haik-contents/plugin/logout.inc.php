<?php
/**
 *   Log out
 *   -------------------------------------------
 *   ./logout.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/04/18
 *   modified :
 */

function plugin_logout_action()
{
	global $script;

	ss_auth_logout();

	secure_session_start();

	set_flash_msg(__('ログアウトしました。'));
	redirect($script);
}
?>