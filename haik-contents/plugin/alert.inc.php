<?php
/**
 *   alert
 *   -------------------------------------------
 *   alert.inc.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/01/11
 *   modified : 13/01/15 close option
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
function plugin_alert_convert()
{
	$args = func_get_args();
	$body = trim(array_pop($args));
	
	list($type, $close) = $args;
	switch(trim($args[0]))
	{
		case 'theme':
		case 'danger':
		case 'success':
		case 'info':
			$type = ' alert-' . trim($type);
			break;
		default:
			$type = ' alert-warning';
	}
	
	$close = $close ? '<button type="button" class="close" data-dismiss="alert">&times;</button>' : '';
	
    $body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    $lines = explode("\n", $body);
    $body = convert_html($lines);
	
	$html = <<<EOD
<div class="orgm-alert alert {$type}">
	{$close}
	{$body}
</div>
EOD;

	return $html;
}

/* End of file alert.inc.php */
/* Location: /app/haik-contents/plugin/alert.inc.php */