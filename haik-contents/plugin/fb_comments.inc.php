<?php
/**
 *   Facebook Comments Plugin
 *   -------------------------------------------
 *   ./plugin/fb_comments.inc.php
 *   
 *   Copyright (c) 2011 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-09-02
 *   modified :
 *   
 *   Put Facebook Comments
 *   
 *   Usage : #fb_comments
 *   
 */

function plugin_fb_comments_init()
{
	if ( ! exist_plugin("fb_root"))
	{
		die('Fatal error: fb_root plugin not found');
	}
	do_plugin_init("fb_root");
}

function plugin_fb_comments_action()
{

	global $vars, $script;
	$json = array();

	if (isset($vars['preview']))
	{
		
		$html = plugin_fb_root_get_preview(TRUE);
		$url = $vars['url'] ? $vars['url'] : $script . '?' . rawurlencode($vars['page']);
		
		$html .= '
<div class="fb-comments"
	data-href="'. h($url) .'"
	data-width="'. h($vars['width']) .'"
	data-colorscheme="'.h($vars['color_scheme']).'"
	data-numposts="'. h($vars['numposts']) .'">
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

function plugin_fb_comments_convert()
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
		'data-width' => '550',
		'data-numposts' => '2',// in arg: num
		'data-colorscheme' => FALSE
	);
	
	$attrs = plugin_fb_root_parse_args($args, $def_attrs);
	//default URL set
	if ($attrs['href'] == '')
	{
		$attrs['href'] = $script. '?'. $r_page;
	}

	plugin_fb_root_set_jsapi(TRUE);
	$tag = plugin_fb_root_create_tag('fb:comments', $attrs);

	$body = $tag;

	return $tag;
}

/* End of file fb_comments.inc.php */
/* Location: /haik-contents/plugin/fb_comments.inc.php */