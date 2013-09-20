<?php
/**
 *   nav
 *   -------------------------------------------
 *   nav.inc.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/01/10
 *   modified :
 *
 *   Usage : #nav(page) switch nav page
 *   
 */

function plugin_nav_convert()
{

	$page = func_get_arg(0);
	
	plugin_nav_page($page);
	
	return '';
}

function plugin_nav_page($page, $reset = FALSE)
{
	global $site_nav;
	static $navpage = NULL;
	
	if ($navpage === NULL OR $reset OR ! is_page($page))
	{
		return $site_nav;
	}
	else
	{
		$navpage = $page;
		return $navpage;
	}
	
}

function plugin_nav_create($style = '')
{
	global $site_nav, $vars, $script;
	
	$navpage = plugin_nav_page();
	
	$qt = get_qt();
	
	$navtype_class = '';

	if ($style !== '')
	{
		switch(trim($style))
		{
			case "fixed":
				$navtype_class = " navbar-fixed-top";
				break;
			case "none":
				break;
			case "full":
				$navtype_class = " navbar-static-top";
				break;
		}

		if ($navtype_class != '')
		{
			$qt->appendv('navbar_class', $navtype_class);
		}
	}

	$preview = ($vars['preview'] && $vars['page'] === $site_nav);

	$body = $preview ? $vars['msg'] : get_source($navpage, TRUE, TRUE);
    $body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    $lines = explode("\n", $body);
    $body = convert_html($lines, TRUE);

    $regx = array(
    	'/<ul class="list1"/',
    	'/<ul class="list2"/',
    );
    $replace = array(
    	'<ul class="list1 nav navbar-nav "',
    	'<ul class="list2 dropdown-menu notyet-drop"',
    );
    $body = preg_replace($regx, $replace, $body);
    
    if ($preview)
    {
		$body = '<div class="preview_highlight" data-target="#orgm_nav ul">'. $body .'</div>';
    }
    else
    {
    	$url = preg_quote(get_page_url($vars['page']), '|');
    	
		$ptn = '|<li>(.+href="('.$url.')".+)?</li>|';
		$body = preg_replace($ptn, '<li class="active">$1</li>', $body);
    }
    
    // !insert mark
	if ( ! $qt->getv('SiteNavigatorInsertMark'))
	{
		$body = "\n<!-- SITENAVIGATOR CONTENTS START -->\n<div id=\"orgm_nav\">\n" . $body . "\n</div>\n<!-- SITENAVIGATOR CONTENTS END -->\n";
		$qt->setv('SiteNavigatorInsertMark', TRUE);
	}

	return $body;
}

/* End of file nav.inc.php */
/* Location: /haik-contents/plugin/nav.inc.php */