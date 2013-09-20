<?php
/**
 *   Accordion
 *   -------------------------------------------
 *   accordion.inc.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/01/17
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *
 *    #accordion(1){{
 *    - head1
 *    - head2
 *    - [[head3>URL]]
 *
 *    ====
 *    content1
 *    ====
 *    content2
 * 
 *    }}
 * 
 */
function plugin_accordion_convert()
{
	static $s_accordion_cnt = 1;
	
	$qt = get_qt();
	
	$args   = func_get_args();
	$body   = array_pop($args);
    $body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    
    if (count($args) == 1)
    {
	    if (is_numeric(trim($args[0])))
	    {
	    	$open_index = trim($args[0]);
	    }
    }

	$blocks = explode('====', $body);
	$header = array_shift($blocks);
	$headnum = 1;
	$headers = array();
	foreach (explode("\n", $header) as $line)
	{
		if (preg_match("/^\-\s?(.*)$/",$line,$matches))
		{
			$tmp = convert_html($matches[1], TRUE);

			$link = "#acc{$s_accordion_cnt}_collapse{$headnum}";
			$head = $tmp;
			if (preg_match("/^<a href=\"([^\"]*)\".*?>(.*?)<\/a>/",$tmp, $matches2))
			{
				$link = $matches2[1];
				$head = $matches2[2];
			}
			$headers[] = array(
				'link' => h($link),
				'head' => $head
			);
			$headnum++;
		}
	}

	$data_parent = 'accordion'.$s_accordion_cnt;
	$accordion_body = '';
	for ($i=0; $i < count($headers); $i++)
	{
		$collapse_id = 'acc'.$s_accordion_cnt.'_collapse'.($i+1);
		$block_body = '';
		$icon_class = 'icon-plus';
		if (isset($blocks[$i]) && trim($blocks[$i]) != '')
		{
			$add_class = '';
			
			// あらかじめ開いておくインデックスの指定
			if ($open_index == ($i+1))
			{
				$add_class = ' in';
				$icon_class = 'icon-minus';
			}
			
			$block_body = convert_html($blocks[$i]);
			$block_body = <<< EOD
		<div id="{$collapse_id}" class="accordion-body collapse{$add_class}">
			<div class="accordion-inner">{$block_body}</div>
		</div>
EOD;
		}
		
		// リンクが設定されている場合は、そのURLヘ移動するため、アコーディオンの設定をオフに
		$data_toggle = 'collapse';
		if ($headers[$i]['link'] != '#'.$collapse_id)
		{
			$data_toggle = '';
			$icon_class = 'icon-chevron-right';			
		}

		$str = <<< EOD
	<div class="accordion-group">
		<div class="accordion-heading">
			<a class="accordion-toggle" data-toggle="{$data_toggle}" data-parent="#{$data_parent}" href="{$headers[$i]['link']}">
	<i class="{$icon_class} orgm-accordion-icon"></i>
	{$headers[$i]['head']}</a>
		</div>
		{$block_body}
	</div>
EOD;
		$accordion_body .= $str;
	}
	
	$html = <<< EOD
<div class="accordion" id="{$data_parent}">
{$accordion_body}
</div>
EOD;

	$s_accordion_cnt++;
	return $html;
}
?>