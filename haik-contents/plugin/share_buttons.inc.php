<?php
/**
 *   ソーシャルボタンの表示
 *   -------------------------------------------
 *   share_buttons.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/04/03
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */

define('PLUGIN_SHARE_BUTTONS_FACEBOOK', '<a href="http://www.facebook.com/share.php?u=%3$s" class="facebook" onclick="window.open(this.href, \'FBwindow\', \'width=650, height=450, menubar=no, toolbar=no, scrollbars=yes\'); return false;" title="%5$s"><i class="orgm-icon orgm-icon-facebook-2"></i><span class="sr-only">%5$s</span></a>');

define('PLUGIN_SHARE_BUTTONS_TWITTER', '<a href="http://twitter.com/share?url=%3$s&text=%4$s" class="twitter" onclick="window.open(this.href, \'tweetwindow\', \'width=550, height=450,personalbar=0,toolbar=0,scrollbars=1,resizable=1\'); return false;" title="%5$s"><i class="orgm-icon orgm-icon-twitter-2"></i><span class="sr-only">%5$s</span></a>');

define('PLUGIN_SHARE_BUTTONS_GOOGLE_PLUS', '<a href="https://plus.google.com/share?url=%3$s" class="google-plus" onclick="window.open(this.href, \'Gwindow\', \'width=650, height=450, menubar=no, toolbar=no, scrollbars=yes\'); return false;" title="%5$s"><i class="orgm-icon orgm-icon-google-plus-2"></i><span class="sr-only">%5$s</span></a>');
 
function plugin_share_buttons_convert()
{
	global $script, $vars;
	global $defaultpage, $site_title, $site_title_delim;
	
	$qt = get_qt();
	
	
	$buttons_options = array(
		'facebook' => array(
			'title' => __('Facebook でシェア'),
		),
		'twitter' => array(
			'title' => __('Twitter でシェア'),
		),
		'google_plus' => array(
			'title' => __('Google+ でシェア'),
		),
	);
	
	$buttons = array();
	$align = 'left';
	
	$args = func_get_args();
	foreach ($args as $arg)
	{
		$arg = strtolower($arg);
		switch ($arg)
		{
			case 'fb':
			case 'facebook':
				$buttons['facebook'] = $buttons_options['facebook'];
				break;
			case 'tw':
			case 'twitter':
				$buttons['twitter'] = $buttons_options['twitter'];
				break;
			case 'gp':
			case 'gplus':
			case 'google-plus':
			case 'google_plus':
			case 'googleplus':
			case 'plus':
			case 'g+':
				$buttons['google_plus'] = $buttons_options['google_plus'];
				break;
			case 'left':
			case 'right':
			case 'center':
				$align = $arg;
				break;
		}
	}
	//無指定の場合、全部
	if (count($buttons) === 0)
	{
		$buttons = $buttons_options;
	}
	
	$url = get_page_url($vars['page']);
	$enc_url = rawurlencode($url);
	$page_title = get_page_title($vars['page']);
	$full_title = ($vars['page'] === $defaultpage) ? $page_title : ($page_title . $site_title_delim . $site_title);
	$enc_full_title = rawurlencode($full_title);
	
	$share_buttons = array_keys($buttons);

	$html = '<div class="share_buttons '.$align.'"><ul class="nav nav-pills">';
	foreach ($share_buttons as $btn)
	{
		$defname = 'PLUGIN_SHARE_BUTTONS_' . strtoupper($btn);
		$title = $buttons[$btn]['title'];
		if (defined($defname))
		{
			$html .= '<li>' . sprintf(constant($defname), h($url), h($full_title), h($enc_url), h($enc_full_title), h($title)) . '</li>';
		}
	}	
	$html .= '</ul></div>';
	
	//$qt->setv('share_buttons', $html);
	
	return $html;
}

/* End of file share_buttons.inc.php */
/* Location: /plugin/share_buttons.inc.php */