<?php
/**
 *   user script
 *   -------------------------------------------
 *   user_script.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/06/20
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
function plugin_user_script_convert()
{
	global $vars;
	$qm = get_qm();
	$qt = get_qt();
	$page = $vars['page'];
	if (! (PKWK_READONLY > 0 or is_freeze($page) or plugin_user_script_is_edit_auth($page))) {
		return $qm->replace('fmt_err_not_editable', '#user_script', $page);
	}

	$args = func_get_args();
	$js   = array_pop($args);
	$js   = str_replace("\r", "\n", str_replace("\r\n", "\r", $js));
	$qt->appendv('user_script', $js);

	return "";
}

function plugin_user_script_is_edit_auth($page, $user = '')
{
	global $edit_auth, $edit_auth_pages, $auth_method_type;
	if (! $edit_auth) {
		return FALSE;
	}
	// Checked by:
	$target_str = '';
	if ($auth_method_type == 'pagename') {
		$target_str = $page; // Page name
	} else if ($auth_method_type == 'contents') {
		$target_str = join('', get_source($page)); // Its contents
	}

	foreach($edit_auth_pages as $regexp => $users) {
		if (preg_match($regexp, $target_str)) {
			if ($user == '' || in_array($user, explode(',', $users))) {
				return TRUE;
			}
		}
	}
	return FALSE;
}
?>