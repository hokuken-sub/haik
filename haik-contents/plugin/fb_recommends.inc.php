<?php
/**
 *   Facebook Recommendations Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/fb_recommends.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-09-02
 *   modified : 2013-11-21
 *   
 *   Put Facebook Recommendations
 *   
 *   Usage : #fb_recommends
 *   
 */

function plugin_fb_recommends_init()
{
	if ( ! exist_plugin("fb_root"))
	{
		die('Fatal error: fb_root plugin not found');
	}
	do_plugin_init("fb_root");
}


function plugin_fb_recommends_convert()
{
	global $script, $vars;

	$args = func_get_args();

	// scaffold
	$def_attrs = array(
		'action'      => 'likes, recommends',
		'app-id'      => FALSE,
		'colorscheme' => FALSE,
		'header'      => 'true',
		'height'      => FALSE,//300px
		'linktarget'  => FALSE,
		'max-age'     => FALSE,
		'ref'         => FALSE,
		'site'        => '',
		'width'       => FALSE,//300px
	);
	
	$attrs = plugin_fb_root_parse_args($args, $def_attrs);
	//default site set
	if ($attrs['data-site'] == '')
	{
		$parsed = parse_url($script);
		$host = $parsed['host'];
		$attrs['data-site'] = $host;
	}

	$attrs['class'] = 'fb-recommendations';
	plugin_fb_root_set_jsapi(TRUE);
	$tag = plugin_fb_root_create_tag('div', $attrs);

	return $tag;
}