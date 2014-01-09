<?php
/**
 *   Section Plugin
 *   -------------------------------------------
 *   ./section.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/12/09
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

function plugin_section_convert()
{
	static $cnt = 0;
	
	$qt = get_qt();
	
	$args = func_get_args();
	$body = array_pop($args);
	
	$body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
	
	$h_align  = 'center';//left|center|right
	$v_align  = 'middle';//top|middle|bottom
	$height   = '300px';
	$type     = 'cover';//cover|repeat
	$style    = 'default';//primary|info|success|warning|danger
	$fullpage =  FALSE;//full height section; fit window size
	
	$relative  = TRUE;//position: relative
	$container = TRUE;//FALSE to enable 'fit' option

	$color = FALSE;//inherit
	
	$background_image = FALSE;
	$background_fix   = FALSE;
	$background_color = FALSE;//transparent
	$additional_class = $container_class = '';
	
	$attrs = array(
		'id' => 'haik_section_' . ++$cnt,
		'class' => 'haik-section',
		'style' => '',
	);
	
	foreach ($args as $arg)
	{
		if (preg_match('/\A(left|center|right)\z/i', $arg, $mts))
		{
			$h_align = strtolower($mts[1]);
		}
		else if (preg_match('/\A(top|middle|bottom)\z/i', $arg, $mts))
		{
			$v_align = strtolower($mts[1]);
		}
		else if (preg_match('/\A(cover|repeat)\z/i', $arg, $mts))
		{
			$type = strtolower($mts[1]);
		}
		else if (preg_match('/\.(jpe?g|gif|png)\z/i', $arg))
		{
			$background_image = trim($arg);
		}
		else if (preg_match('/\A([\d.]+)(.*)\z/', $arg, $mts))
		{
			$height = $mts[1] . ($mts[2] ? $mts[2] : 'px');
		}
		else if ($arg === 'page')
		{
			$fullpage = TRUE;
		}
		else if (preg_match('/\A(primary|info|success|warning|danger)\z/i', $arg, $mts))
		{
			$style = $mts[1];
		}
		else if (preg_match('/\Aclass=(.+)\z/', $arg, $mts))
		{
			$additional_class .= $mts[1];
		}
		else if (preg_match('/\Acolor=(.+)\z/', $arg, $mts))
		{
			$color = $mts[1];
		}
		else if (preg_match('/\Abgcolor=(.+)\z/', $arg, $mts))
		{
			$background_color = $mts[1];
		}
		else if ($arg === 'fit')
		{
			$container = FALSE;
		}
		else if ($arg === 'fix')
		{
			$background_fix = TRUE;
		}
		else if ($arg === 'static')
		{
			$relative = FALSE;
		}
		// eyecatch プラグインからの呼び出し時に自動的に付けられるオプション
		else if ($arg === 'eyecatch')
		{
			$additional_class .= ' haik-eyecatch';
		}
	}
	
	// !set attributes
	
	//set base class
	$attrs['class'] .= ' haik-section-' . $style;
	
	if ($relative)
	{
		$attrs['style'] .= 'position:relative;';
	}
	
	if ($color)
	{
		$attrs['style'] .= 'color: ' . h($color) . ';';
	}
	if ($background_color)
	{
		$attrs['style'] .= 'background-color: ' . h($background_color) . ';';
	}
	if ($background_image)
	{
		if (is_url($background_image))
		{
			$filename = $background_image;
		}
		else
		{
			$filename = get_file_path($background_image);
		}
		$attrs['style'] .= 'background-image: url(' . h($filename) . ');';
		
		if ($background_fix)
		{
			$attrs['style'] .= 'background-attachment: fixed;';
		}
		$attrs['data-background-image'] = $background_image;
		$attrs['data-background-type'] = $type;
	}
	
	if ($fullpage)
	{
		$attrs['style'] .= 'height: 600px;';
		$attrs['data-height'] = 'page';
	}
	else
	{
		$attrs['style'] .= 'height: ' . $height. ';';
		$attrs['data-height'] = $height;
	}
	
	$attrs['data-horizontal-align'] = $h_align;
	$attrs['data-vertical-align'] = $v_align;
	
	$attrs['class'] .= ' ' . $additional_class;
	
	$attr_string = '';
	foreach ($attrs as $name => $value)
	{
		$attr_string .= ' ' . $name . '="' . h($value) . '"';
	}
	
	if ($container)
	{
		$container_class = 'container';
	}
	
	// ! make html
	
	$body = convert_html($body);

	$html = <<< EOH
<div {$attr_string}>
	<div class="{$container_class}">
		{$body}
	</div>
</div>
EOH;

	return $html;
}

/* End of file section.inc.php */
/* Location: /haik-contents/plugin/section.inc.php */