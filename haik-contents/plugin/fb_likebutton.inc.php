<?php
/**
 *   Facebook Like|Recommend Button Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/fb_likebutton.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-09-2
 *   modified : 2013-11-21
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
	
	$layouts = array('standard', 'button_count', 'box_count', 'button');
	$actions = array('like', 'recommend');

	// scaffold
	$def_attrs = array(
		'href'        => '',
		'share'       => 'true',
		'layout'      => array('standard', $layouts),
		'show-faces'  => 'true',
		'width'       => '550',
		'font'        => 'arial',
		'colorscheme' => FALSE,
		'action'      => array(FALSE, $actions),
		'ref'         => FALSE
	);
	
	$attrs = plugin_fb_root_parse_args($args, $def_attrs);
	
	//default URL set
	if ($attrs['data-href'] == '')
	{
		$attrs['data-href'] = get_page_url($page);
	}
	
	$attrs['class'] = 'fb-like';
	plugin_fb_root_set_jsapi(TRUE);
	$tag = plugin_fb_root_create_tag('div', $attrs);
	
	return $tag;

}
/* End of file fb_likebutton.inc.php */
/* Location: /haik-contents/plugin/fb_likebutton.inc.php */