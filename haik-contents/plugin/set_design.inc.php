<?php
/**
 *   ORGM Set Design Plugin
 *   -------------------------------------------
 *   plugin/set_design.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013-11-25
 *   modified :
 *   
 *   指定したデザインを使用します。
 *   
 *   Usage :
 *   
 */

function plugin_set_design_convert()
{
	global $vars, $include_skin_file_path;
	global $style_name, $style_color, $style_texture;
	
	$qm = get_qm();
	$qt = get_qt();
	
	$args = func_get_args();

	$design = $laout = $color = $texture = '';
	foreach ($args as $arg)
	{
		list($optkey, $optval) = explode('=', $arg, 2);
		switch ($optkey) {
			case 'design': 
				$design = $optval;
				break;
			case 'layout': 
				$layout = $optval;
				break;
			case 'color': 
				$color = $optval;
				break;
			case 'texture': 
				$texture = $optval;
				break;
		}
	}

	if ($design != '')
	{
		if( file_exists(SKIN_DIR.$design) )
		{
			$include_skin_file_path = $design;	
		}
	}
	$config = style_config_read( ($include_skin_file_path != '') ? $include_skin_file_path : '');
	
	if ($layout != '')
	{
		if (isset($config['templates'][$layout]))
		{
			$qt->setv('template_name', $layout);
		}
	}

	if ($color != '')
	{
		if (isset($config['colors'][$color]))
		{
			$style_color = $color;
		}
	}

	if ($texture != '')
	{
		if (isset($config['textures'][$texture]))
		{
			$style_texture = $texture;
		}
	}
}
?>