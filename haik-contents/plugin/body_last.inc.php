<?php
/**
 *   Insert HTML to Body Last
 *   -------------------------------------------
 *   /plugin/body_last.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/06/05
 *   modified :
 *   
 *   insert html to #{$body_last}
 *   
 *   Usage : #body_last{{ ... }}
 *   
 */

function plugin_body_last_convert()
{
	global $vars;
	
	$args   = func_get_args();
	$body = array_pop($args);
	
	$body = str_replace(array("\r\n", "\r"), array("\r", "\n"), $body);

	$qt = get_qt();
	$qt->appendv('body_last', $body);
	
	return "";
}


/* End of file body_last.inc.php */
/* Location: /plugin/body_last.inc.php */