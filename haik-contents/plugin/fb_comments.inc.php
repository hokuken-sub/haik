<?php
/**
 *   Facebook Comments Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/fb_comments.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-09-02
 *   modified : 2013-11-21
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
		$url = $vars['url'] ? $vars['url'] : get_page_url($vars['page']);
		
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
	$args = func_get_args();

	// scaffold
	$def_attrs = array(
		'href'        => '',
		'width'       => '550',
		'numposts'    => '2',// in arg: num
		'colorscheme' => FALSE
	);
	
	$attrs = plugin_fb_root_parse_args($args, $def_attrs);
	//default URL set
	if ($attrs['data-href'] == '')
	{
		$attrs['data-href'] = get_page_url($page);
	}

	$attrs['class'] = 'fb-comments';

	plugin_fb_root_set_jsapi(TRUE);
	$tag = plugin_fb_root_create_tag('div', $attrs);

	return $tag;
}

/* End of file fb_comments.inc.php */
/* Location: /haik-contents/plugin/fb_comments.inc.php */