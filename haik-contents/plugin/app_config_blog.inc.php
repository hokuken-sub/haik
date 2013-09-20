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

function plugin_app_config_blog_init()
{
	
	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}

}


function plugin_app_config_blog_action()
{
	global $description, $vars;
	global $qblog_social_widget, $qblog_social_html;

	$qt = get_qt();
	$helper = new HTML_Helper();
	
	$title = __('ブログ設定');
	$description = __('ブログの設定を行います。');

	$tmpl_file = PLUGIN_DIR . 'app_config/blog.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	
	return array('msg' => $title, 'body' => $body);

}


/* End of file orgm_setting.inc.php */
/* Location: plugin/orgm_setting.inc.php */