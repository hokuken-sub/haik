<?php
/**
 *   Insert HTML to Body First
 *   -------------------------------------------
 *   /plugin/body_first.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/06/05
 *   modified :
 *   
 *   insert html to #{$body_first}
 *   
 *   Usage : #body_first{{ ... }}
 *   
 */

function plugin_body_first_convert()
{
	global $vars;
	
	$args   = func_get_args();
	$body = array_pop($args);
	
	$body = str_replace(array("\r\n", "\r"), array("\r", "\n"), $body);

	$qt = get_qt();
	$qt->appendv('body_first', $body);
	
	return "";
}


/* End of file body_first.inc.php */
/* Location: /plugin/body_first.inc.php */