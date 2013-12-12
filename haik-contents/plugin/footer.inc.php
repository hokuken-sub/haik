<?php
/**
 *   footer
 *   -------------------------------------------
 *   footer.inc.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/01/10
 *   modified :
 *
 *   Usage : #footer(page) switch footer page
 *   
 */

function plugin_footer_convert()
{
	
	$page = func_get_arg(0);
	
	plugin_footer_page($page);
	
	return '';
}

function plugin_footer_page($page = NULL, $reset = FALSE)
{
	global $site_footer;
	static $footerpage = NULL;
	
	if ($footerpage === NULL OR $reset OR ! is_page($page))
	{
		return $site_footer;
	}
	else
	{
		$footerpage = $page;
		return $footerpage;
	}
	
}

function plugin_footer_create()
{
	global $site_footer, $vars, $script;
	$footerpage = plugin_footer_page();
	
	$qt = get_qt();

	$preview = (isset($vars['preview']) && $vars['preview'] && isset($vars['page']) && $vars['page'] === $site_footer);
	$body = $preview ? $vars['msg'] : get_source($footerpage, TRUE, TRUE);
    $body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    $lines = explode("\n", $body);
    $body = convert_html($lines, TRUE);
	
    if ($preview)
    {
		$body = '<div class="preview_highlight" data-target="#orgm_footer">'. $body .'</div>';
    }
    
    // !insert mark
	if ( ! $qt->getv('SiteFooterInsertMark'))
	{
		$body = "\n<!-- SITEFOOTER CONTENTS START -->\n<div id=\"orgm_footer\">\n" . $body . "\n</div>\n<!-- SITEFOOTER CONTENTS END -->\n";
		$qt->setv('SiteFooterInsertMark', TRUE);
	}

	return $body;
}
?>