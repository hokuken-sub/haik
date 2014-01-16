<?php
/**
 *   Haik Icon Plugin
 *   -------------------------------------------
 *   plugin/icon.inc.php
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

function plugin_icon_inline()
{
	$args = func_get_args();
	
	$class = '';
	$icon_base = 'orgm-icon';
	$icon_prefix = $icon_base . '-';
	$icon_name = '';
	
	foreach ($args as $arg)
	{
		if ($arg === 'glyphicon')
		{
			$icon_base = 'glyphicon';
			$icon_prefix = $icon_base . '-';
		}
		else if ($arg !== '')
		{
			$icon_name = $arg;
		}
	}
	
	$icon_name = $icon_prefix.$icon_name;
	
	$format = '<i class="%s %s"></i>';
	return sprintf($format, h($icon_base), h($icon_name));
}