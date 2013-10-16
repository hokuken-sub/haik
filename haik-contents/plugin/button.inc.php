<?php
/**
 *   button
 *   -------------------------------------------
 *   button.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/01/23
 *   modified : 13/06/12
 *
 *   Description
 *   
 *   
 *   Usage : &button(link_to[,type][,size]){label};
 *   
 */
function plugin_button_inline()
{
	global $script, $vars, $site_nav;
	
	$args   = func_get_args();
	$text   = strip_autolink(array_pop($args));
	
	if (count($args) > 0)
	{
		$href = array_shift($args);
		if (is_page($href))
		{
			$href = $script.'?'.rawurlencode($href);
		}
		//存在しないページ
		else if ( ! is_url($href) && is_pagename($href))
		{
			$href = $script . '?cmd=edit&page=' . rawurlencode($href);
		}
	
		$type = ' btn-default';
		$size = '';
		$class = '';
		foreach($args as $arg)
		{
			switch($arg){
				case 'primary':
				case 'info':
				case 'success':
				case 'warning':
				case 'danger':
				case 'link':
				case 'default':
					$type = ' btn-'.$arg;
					break;
				case 'theme':
					$type = ' btn-'.$arg;
					break;
				case 'large':
				case 'lg':
					$size = ' btn-lg';
					break;
				case 'small':
				case 'sm':
					$size = ' btn-sm';
					break;
				case 'mini':
				case 'xs':
					$size = ' btn-xs';
					break;
				case 'block':
					$size = ' btn-'.$arg;
					break;
				default:
					$class .= ' '.$arg;
			}
		}
	}
	
	$wrapper = '%s';
	if (isset($vars['page_alt']) && $vars['page_alt'] === $site_nav)
	{
		$wrapper = '<div class="btn-group">%s</div>';
	}

	$html = sprintf($wrapper, '<a class="btn'.$type.$size.$class.'" href="'.h($href).'">'.$text.'</a>');
	return $html;
}

/* End of file button.inc.php */
/* Location: /app/haik-contents/plugin/button.inc.php */