<?php
/**
 *   Template Switcher
 *   -------------------------------------------
 *   plugin/switch_template.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/01/10
 *   modified :
 *   
 *   読み込む
 *   skin/STYLE_NAME/ 内のテンプレートファイル（*.skin.php）を切り替える
 *   
 *   Usage :
 *   
 */

function plugin_set_template_convert()
{
	$tmpl_name = func_get_arg(0);
	
	plugin_set_template_switch($tmpl_name);
}

function plugin_set_template_switch($tmpl_name)
{
	global $include_skin_file_path;

	$qt = get_qt();
	
	$config = style_config_read($include_skin_file_path);
	if (isset($config['templates'][$tmpl_name]))
	{
		$qt->setv('template_name', $tmpl_name);
		return TRUE;
	}
	return FALSE;
	
}