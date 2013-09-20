<?php
/**
 *   Facebook Like|Recommend Button Plugin
 *   -------------------------------------------
 *   /app/haik-contents/plugin/fb_likebutton.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-09-2
 *   modified : 2013-06-11
 *   
 *   Put Facebook Like|Recommend Button
 *   
 *   Usage : #fb_likebutton(options)
 *   
 */

function plugin_fb_likebutton_init()
{
	if ( ! exist_plugin("fb_root"))
	{
		die('Fatal error: fb_root plugin not found');
	}
	do_plugin_init("fb_root");
}


function plugin_fb_likebutton_inline()
{
	$args = func_get_args();
	return plugin_fb_likebutton_body($args);
}

function plugin_fb_likebutton_convert()
{
	$args = func_get_args();
	return plugin_fb_likebutton_body($args);
}

function plugin_fb_likebutton_body($args)
{
	global $script, $vars;
	
	$page = $vars['page'];
	$r_page = rawurlencode($page);
	$qm = get_qm();
	$qt = get_qt();
	
	$layouts = array('standard', 'button_count', 'box_count');
	$actions = array('like', 'recommend');

	// scaffold
	$def_attrs = array(
		'data-href' => '',
		'data-send' => 'true',
		'data-layout' => array('standard', $layouts),
		'data-show-faces' => 'true',
		'data-width' => '550',
		'data-font' => 'arial',
		'data-colorscheme' => FALSE,
		'data-action' => array(FALSE, $actions),
		'data-ref' => FALSE
	);
	
	$attrs = plugin_fb_root_parse_args($args, $def_attrs);
	
	//default URL set
	if ($attrs['href'] == '')
	{
		$attrs['href'] = $script. '?'. $r_page;
	}
	
	plugin_fb_root_set_jsapi(TRUE);
	$tag = plugin_fb_root_create_tag('fb:like', $attrs);
	
	$body = $tag;
	
	return $body;

}