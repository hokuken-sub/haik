<?php
/**
 *   ORIGAMI Notifier
 *   -------------------------------------------
 *   plugin/notify.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/01/29
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

define('PLUGIN_NOTIFY_AUTO_FADE', TRUE);

function plugin_notify_init()
{
	$qt = get_qt();
	if ( ! $qt->getv('notices') || ! is_array($qt->getv('notices')))
		$qt->setv('notices', array());
}

/**
 * @params string $type notice type: success | info | error | warning (empty)
 * @params string $format message format: wiki | html
 * @params string $message message body; * last index of arguments *
 */
function plugin_notify_convert()
{
	$qt = get_qt();
	$args = func_get_args();
	
	if (count($args) > 0)
	{
		$message = array_pop($args);
		
		$type = isset($args[0]) ? $args[0] : 'success';
		$format = (isset($args[1]) && trim($args[1]) == 'html') ? 'html' : 'wiki';
		
		if ($format === 'wiki')
		{
			$message = convert_html($message, TRUE);
		}
		
		plugin_notify_set_notice($message, $type);
	}
	else
	{
		$body = plugin_notify_get_body();
		$qt->appendv('body_last', $body);
	}
	
	return '';

}

function plugin_notify_set_notice($message, $type = 'success', $set_nav = false, $fade = true, $priority = 10)
{
	$qt = get_qt();
	$notices = $qt->getv('notices');
	
	$notices[] = array(
		'message'  => $message,
		'type'     => $type,
		'priority' => $priority,
		'set_nav'  => $set_nav,
		'fade'     => $fade,
	);

	$qt->setv('notices', $notices);

}


function plugin_notify_get_body()
{
	$qt = get_qt();
	
	$notices = $qt->getv('notices') ? $qt->getv('notices') : array();
	
	uasort($notices, 'plugin_notify_compare');

	$html = '<div class="orgm-notification container-fluid">';
	$nav_html = '';

	foreach ($notices as $notice)
	{
		switch ($notice['type']) {
			case 'error':
				$notice['type'] = 'danger';
		}
		$type = $notice['type'] ? ' alert-'.$notice['type'] : '';
		$message = $notice['message'];
		$auto_click = '';
		if (PLUGIN_NOTIFY_AUTO_FADE)
		{
			$auto_click = ' data-auto-click="2000"';
		}
	  if ($notice['set_nav'])
	  {
	      if ( ! $notice['fade'])
	      {
	          $auto_click = '';
	      }
	      $type = $notice['type'] ? ('text-' . $notice['type']) : '';
  	    $nav_html .= '<div class="haik-nav-notice navbar-text fade"' . $auto_click . '><span class="' . $type . '">' . $message . '</span></div>';
	  }
	  else
	  {
    		$html .= '<div class="row"><div class="col-sm-6 col-sm-offset-3 orgm-notice alert'.h($type).' alert-box fade">';
    		$html .= '<button type="button" data-dismiss="alert" class="close"'.$auto_click.'>&times;</button>';
    		$html .= $message.'</div></div>';
    }
	}
	$html .= $nav_html . '</div>';
	
	return $html;
}


function plugin_notify_compare($a, $b)
{
	if ($a['priority'] == $b['priority'])
		return 0;
	
	return ($a['priority'] > $b['priority']) ? -1 : 1;
}

/* End of file notify.inc.php */
/* Location: plugin/notify.inc.php */
