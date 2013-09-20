<?php
/**
 *   Heading 1 Plugin
 *   -------------------------------------------
 *   /app/haik-contents/plugin/h1.inc.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/04/19
 *   modified : 13/06/25
 *
 *   Description
 *   
 *   
 *   Usage :
 *     #h1(subject)       output h1 tag
 *     #h1(subject,block) wrapped by .page-header block
 *   
 */
function plugin_h1_convert()
{
	static $id = 0;
	$id++;
	
	$args = func_get_args();

	if (count($args) > 1)
	{
		$subtitle = array_pop($args);
		$title = convert_html(join(',', $args), TRUE);
		$class_name = 'page-header';
	}
	else
	{
		$subtitle = '';
		$title = array_pop($args);
		$class_name = '';
	}
	
	$text = trim(convert_html($title, TRUE));
	$subtitle = convert_html($subtitle);
	
	$html = <<<EOD
		<div class="{$class_name}">
			<h1 id="orgm_h1_{$id}">{$title}</h1>
			{$subtitle}
		</div>
EOD;
	

	return $html;
}

/* End of file h1.inc.php */
/* Location: /app/haik-contents/plugin/h1.inc.php */