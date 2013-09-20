<?php
/**
 *   Facebook LikeBox Plugin
 *   -------------------------------------------
 *   ./plugin/fb_likebox.inc.php
 *   
 *   Copyright (c) 2011 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-09-02
 *   modified :
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
	$r_page = rawurlencode($page);
	$qm = get_qm();
	$qt = get_qt();
	$args = func_get_args();

	if ( ! exist_plugin("fb_root"))
	{
		die('Fatal error: fb_root plugin not found');
	}

	// scaffold
	$def_attrs = array(
		'data-href' => '',
		'data-width' => '190',
		'data-height' => FALSE,
		'data-colorscheme' => FALSE,
		'data-show-faces' => 'true',
		'data-show-border' => 'true',
		'data-stream' => 'true',
		'data-header' => 'true',
	);
	
	$attrs = plugin_fb_root_parse_args($args, $def_attrs);
	//no URL error
	if ($attrs['href'] == '')
	{
		$errmsg = 'error - #fb_likebox: no facebook page url';
	}
	
	$attrs['class'] = 'fb-like-box';

	plugin_fb_root_set_jsapi(TRUE);
//	$tag = plugin_fb_root_create_tag('fb:like-box', $attrs);
	$tag = plugin_fb_root_create_tag('div', $attrs);

	$body = $tag;

	return $tag;
}
