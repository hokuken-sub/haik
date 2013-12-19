<?php
/**
 *   Move Element to Bottom Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/bottom.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/12/13
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

function plugin_bottom_convert()
{
	$args = func_get_args();
	$body = array_pop($args);


	$html = convert_html($body);
	
	$html = <<< EOH
<div class="haik-pull-bottom">
	{$html}
</div>
EOH;
	
	return $html;
}