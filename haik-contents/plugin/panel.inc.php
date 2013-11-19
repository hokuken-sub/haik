<?php
/**
 *   Bootstrap Panel Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/panel.inc.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/01/10
 *   modified : 13/09/05
 *
 *   @see: http://getbootstrap.com/components/#panels
 *   
 *   Usage :
 *     #panel(primary){{
 *     * Panel Header
 *     ====
 *     Panel Contents
 *     }}
 *   
 */

function plugin_panel_convert()
{
	$qm = get_qm();
	$qt = get_qt();

	$args   = func_get_args();
	$body   = array_pop($args);
	
	$msg = '';
	$delim = "\r====\r";

	$panel_type = 'default';
	// color
	if (count($args) > 0)
	{
		$color = trim($args[0]);
		
		switch ($color)
		{
			case 'primary':
			case 'success':
			case 'info':
			case 'warning':
			case 'danger':
			case 'theme':
				$panel_type = $color;
				break;
		}

	}

	$data = explode($delim, $body, 3);
	$data_length = count($data);
	$header = $body = $footer = '';
	
	// header, body, footer
	if ($data_length > 1)
	{
		list($header, $body, $footer) = array_pad($data, 3, '');
	}
	// body
	else
	{
		$body = $data[0];
	}
	
	$header_fmt = "\t" . '<div class="panel-heading">%s</div>' . "\n";
	$body_fmt   = "\t" . '<div class="panel-body">%s</div>' . "\n";
	$footer_fmt = "\t" . '<div class="panel-footer">%s</div>' . "\n";

	$html = '<div class="orgm-panel panel panel-'.h($panel_type).'">' . "\n";

	if ($header)
	{
		$header = str_replace("\r", "\n", str_replace("\r\n", "\n", $header));
		$lines = explode("\n", $header);
		$header_html = convert_html($lines, TRUE);
		$header_html = preg_replace('/<h([1-7]) /', '<h\1 class="panel-title" ', $header_html);
	}
	
	$html .= $header ? sprintf($header_fmt, $header_html) : '';
	
	$body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
	$lines = explode("\n", $body);
	$html .= sprintf($body_fmt, convert_html($lines));
	
	
	$footer = str_replace("\r", "\n", str_replace("\r\n", "\n", $footer));
	$lines = explode("\n", $footer);
	$html .= $footer ? sprintf($footer_fmt, convert_html($lines, TRUE)) : '';
	
	$html .= '</div>' . "\n";
	
	return $html;
}


/* End of file panel.inc.php */
/* Location: /haik-contents/plugin/cols.inc.php */