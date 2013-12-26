<?php
/**
 *   Image Slide
 *   -------------------------------------------
 *   slide.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/04/19
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
function plugin_slide_convert()
{
	global $vars,$script;
	static $slide_num = 0;
	$qt = get_qt();

	$args = func_get_args();
	$body = strip_autolink(array_pop($args)); // Already htmlspecialchars(text)

	$indicator = $slide_button = TRUE;
	$item_height = '450';
	$item_class = ' fit';
	$fit = TRUE;
	
	//options
	foreach ($args as $arg)
	{
		switch ($arg)
		{
			case 'nobutton':
				$indicator = $slide_button = FALSE;
				break;
			case 'noindicator':
				$indicator = FALSE;
				break;
			case 'noslidebutton':
				$slide_button = FALSE;
				break;
			case 'nofit';
				$item_class = '';
				$fit = FALSE;
				break;
			case 'auto':
				$item_height = '';
				break;
			default:
				if (preg_match('/^(\d+)$/', trim($arg), $mts))
				{
					$item_height = $mts[1];
				}
		}
	}

	$body = str_replace("\r", "\n", $body);
	$lines = explode("\n", $body);
	
	$slide_num++;
	
	$items = array();
	$cnt = 0;

	$min_width = FALSE;
	foreach ($lines as $line)
	{
		$line = trim($line);
		if ($line == '')
		{
			continue;
		}

		
		list($filename, $title, $caption) = explode(',', $line, 3);
		$filepath = get_file_path($filename);

		$image = '';
		if (file_exists($filepath))
		{
			list($_width, $_height) = getimagesize($filepath);
			$min_width = ($min_width !== FALSE) ? min($_width, $min_width) : $_width;
			$image = '<img src="'.$filepath.'" alt="">';
		}
		$h = $title ? '<h3 class="no-toc">'.h($title).'</h3>' : '';
		$p = $caption ? convert_html($caption) : '';
		
		$block = ($h OR $p);

		$items[] = '
		<div class="item'.($cnt ? '' : ' active'). $item_class. '"'. ($item_height ? ' style="max-height:'.h($item_height).'px;"' : '').'>
			'.$image.'
			<div class="'. ($block ? 'carousel-caption' : '') .'">
			'.$h.'
			'.$p.'
			</div>
		</div>
';
			$cnt++;
	}
	
	$plural = ($cnt > 1);
	
	if ($cnt > 0)
	{
		$id = 'slide_' . $slide_num;
		$html = '
<div id="'.$id.'" class="carousel slide orgm-carousel" style="'. ($fit === FALSE && $min_width !== FALSE ? ('max-width:'.$min_width.'px;') : '').'">
';
		if ($plural && $indicator)
		{
			$html .= '
	<ol class="carousel-indicators">
';
			for ($i = 0; $i < $cnt; $i++)
			{
				$html .= '
		<li data-target="#'.$id.'" data-slide-to="'.$i.'" class="'.($i ? '' : 'active' ).'"></li>
';
			}
			$html .= '
</ol>';
		}
		$html .='
	<div class="carousel-inner">
		'.join("\n", $items).'
	</div>
';
		if ($plural && $slide_button)
		{
			$html .= '
	<a href="#'.$id.'" class="carousel-control left" data-slide="prev"><span class="icon-prev"></span></a>
	<a href="#'.$id.'" class="carousel-control right" data-slide="next"><span class="icon-next"></span></a>
';			
		}
		$html .= '
</div>
';
	}
	
	return $html;
}
?>