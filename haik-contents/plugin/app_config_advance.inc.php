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

function plugin_app_config_advance_init()
{
	
	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}

}


function plugin_app_config_advance_action()
{
	global $script, $vars;
	global $user_head;
	
	$qt = get_qt();
	$helper = new HTML_Helper();
	
	$qt->setv('include_facebook', TRUE);
	
	$title = __('高度な設定');
	$description = __('サイト関連する設定を行います。');


	if (isset($vars['phase']) && $vars['phase'] === 'save')
	{
		$fields = array('user_head');
		$res = array();
		
		if (isset($vars['user_head']))
		{
			$res = array('value'=> $vars['user_head'] ? $vars['user_head'] : '');
		}

		$data = array_intersect_key($vars, array_flip($fields));
		$res['options'] = $data;
		orgm_ini_write($data);

		$res['message'] = '設定を更新しました';
		
		print_json($res);
		exit;
	}


	$tmpl_file = PLUGIN_DIR . 'app_config/advance.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	
	return array('msg' => $title, 'body' => $body);

}


/* End of file orgm_setting.inc.php */
/* Location: plugin/orgm_setting.inc.php */