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
	if (count($args) > 0)
	{
		$class = "orgm-icon orgm-icon-".trim($args[0]);
	}
	
	$format = '<i class="%s"></i>';
	return sprintf($format, h($class));
}