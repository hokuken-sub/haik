<?php
/**
 *   Facebook LikeBox Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/fb_likebox.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-09-02
 *   modified : 2013-11-21
 *   
 *   Put Facebook LikeBox
 *   
 *   Usage : #fb_likebox
 *   
 */

function plugin_fb_likebox_init()
{
	if ( ! exist_plugin("fb_root"))
	{
		die('Fatal error: fb_root plugin not found');
	}
	do_plugin_init("fb_root");
}

/**
 * リアルタイムプレビュー
 */
function plugin_fb_likebox_action()
{
	global $vars;
	$json = array();

	if (isset($vars['preview']))
	{
		
		$html = plugin_fb_root_get_preview(TRUE);
		
		$html .= '
<div class="fb-like-box"
	data-href="'. h($vars['url']) .'" 
	data-width="'. h($vars['width']) .'" 
	'. ($vars['height'] ? 'data-height="'.h($vars['height']).'"' : '') .'
	data-show-faces="'. h($vars['show_faces']) .'"
	data-colorscheme="'. h($vars['color_scheme']) .'"
	data-stream="'. h($vars['stream']) .'"
	data-header="'. h($vars['header']) .'"
	data-show-border="'. h($vars['show_border']) .'">
</div>';
		
		$json['html'] = $html;
		print_json($json);
		exit;
		
	}
	else
	{
		$json['error'] = __('不正なリクエストです。');
		print_json($json);
		exit;

	}
}


function plugin_fb_likebox_convert()
{
	global $script, $vars;
	$page = $vars['page'];
	$args = func_get_args();

	// scaffold
	$def_attrs = array(
		'href'        => '',
		'width'       => '190',
		'height'      => FALSE,
		'colorscheme' => FALSE,
		'show-faces'  => 'true',
		'show-border' => 'true',
		'stream'      => 'true',
		'header'      => 'true',
	);
	
	$attrs = plugin_fb_root_parse_args($args, $def_attrs);
	//no URL error
	if ($attrs['href'] == '')
	{
		$errmsg = 'error - #fb_likebox: no facebook page url';
	}
	
	$attrs['class'] = 'fb-like-box';

	plugin_fb_root_set_jsapi(TRUE);
	$tag = plugin_fb_root_create_tag('div', $attrs);

	return $tag;
}

/* End of file fb_likebox.inc.php */
/* Location: /haik-contents/plugin/fb_likebox.inc.php */