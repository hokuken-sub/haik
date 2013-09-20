<?php
/**
 *   Change Encoding Plugin
 *   -------------------------------------------
 *   plugin/encoding.inc.php
 *   
 *   Copyright (c) 2010 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 
 *   modified :
 *   
 *   This plugin makes the page encoding
 *   
 *   Usage :
 *   
 */
function plugin_encoding_convert()
{
	$qt = get_qt();
	
	$args = func_get_args();

	if (count($args) == 0)
	{
		return;
	}

	$encoding = $args[0];
	$qt->setv("page_encoding", $encoding);
	
	return;
}
?>