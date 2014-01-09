<?php
/**
 *   eyecatch setting
 *   -------------------------------------------
 *   eyecatch.inc.php
 *
 *   Copyright (c) 2014 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/02/14
 *   modified : 14/01/09
 *
 *   Description
 *   
 *   Usage :
 *   
 */

function plugin_eyecatch_convert()
{
	// アイキャッチのslideプラグインで判別するため
	// true: タイトルがh1に、false: タイトルがh3に
	global $is_eyecatch; 

	$args = func_get_args();


	$html = '';
	$is_eyecatch = TRUE;
	if (exist_plugin('section'))
	{
		array_unshift($args, 'eyecatch');
		$html = call_user_func_array('plugin_section_convert', $args);
	}
	$is_eyecatch = FALSE;
	
	$qt = get_qt();
	$qt->setv('eyecatch', $html);
	
	return '';
}

/* End of file eyecatch.inc.php */
/* Location: /haik-contents/eyecatch/eyecatch.inc.php */