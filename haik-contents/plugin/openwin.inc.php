<?php
/**
 *   Open New Window
 *   -------------------------------------------
 *   openwin.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/04/19
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
define('PLUGIN_OTHERWIN_ALLOW_CSS', TRUE); // TRUE, FALSE

// ----
define('PLUGIN_OPENWIN_USAGE', '&openwin(win){[[link]]};');

function plugin_openwin_inline()
{
	$args = func_get_args();
	$link = array_pop($args);

	if ($link == '')
	{
		return PLUGIN_OPENWIN_USAGE;
	} 

	$win = '_blank';
	if (count($args) > 0)
	{
		$win = $args[0];
	}
	
	$link = preg_replace('/<a /', '<a target="'.h($win).'" ', $link);
	
	return $link;
}
?>