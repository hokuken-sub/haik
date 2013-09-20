<?php
/**
 *   Facebook Recommendations Plugin
 *   -------------------------------------------
 *   ./plugin/fb_recommends.inc.php
 *   
 *   Copyright (c) 2011 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-09-02
 *   modified :
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
		'data-site' => '',
		'data-action' => FALSE,
		'data-width' => '190',
		'data-height' => '300',
		'data-header' => 'true',
		'data-font' => 'arial',
		'data-colorscheme' => FALSE,
		'data-ref' => FALSE,
		'data-linktarget' => FALSE,
	);
	
	$attrs = plugin_fb_root_parse_args($args, $def_attrs);
	//default site set
	if ($attrs['site'] == '')
	{
		$parsed = parse_url($script);
		$host = $parsed['host'];
		$attrs['site'] = $host;
	}

	plugin_fb_root_set_jsapi(TRUE);
	$tag = plugin_fb_root_create_tag('fb:recommendations', $attrs);

	$body = $tag;

	return $tag;
}