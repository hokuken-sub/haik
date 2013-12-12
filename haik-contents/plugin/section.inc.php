<?php
/**
 *   Section Plugin
 *   -------------------------------------------
 *   ./section.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/12/09
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

function plugin_section_convert()
{
	$args = func_get_args();
	$body = array_pop($args);
	
	$body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
	
	$delim = "\n====\n";
	if (strpos($body, $delim) !== FALSE)
	{
		$slides = explode($delim, $body);
		$body = '';
		
		foreach ($slides as $slide)
		{
			$part = convert_html($slide);
			$body .= '<div class="slide">'.$part.'</div>';
		}
	}
	else
	{
		$body = convert_html($body);
	}

	$format = '<div class="section">%s</div>';

	$html = sprintf($format, $body);

	return $html;
}

/* End of file section.inc.php */
/* Location: /haik-contents/plugin/section.inc.php */