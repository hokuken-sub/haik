<?php
/**
 *   Heading 1 Plugin
 *   -------------------------------------------
 *   /app/haik-contents/plugin/h1.inc.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/04/19
 *   modified : 13/06/25
 *
 *   Description
 *   
 *   
 *   Usage :
 *     #h1(subject)       output h1 tag
 *     #h1(subject,block) wrapped by .page-header block
 *   
 */

function plugin_h1_inline()
{
	$args = func_get_args();
	
	$title = array_pop($args);

	return plugin_h1_body($title);
}

function plugin_h1_convert()
{
	$args = func_get_args();
	
	// まず body を取得
	$body = array_pop($args);

	if (count($args) > 0)
	{
		//引数と複数行部分が同時にある場合、
		//それぞれタイトルとサブタイトルとする
		
		$title = convert_html(join(',', $args), TRUE);
		$subtitle = convert_html($body);
	}
	else
	{
		//複数行のみであれば、1行目をタイトル、
		//2行目以降をサブタイトルとする
		
		$lines = explode("\r", $body);
		$title = convert_html(array_shift($lines), TRUE);
		$subtitle = convert_html(join("\n", $lines));
	}
	
	return plugin_h1_body($title, $subtitle);
}

function plugin_h1_body($title, $subtitle = '')
{
	static $id = 0;
	$id++;

	if ($subtitle === '')
	{
		$html = '<h1 id="haik_h1_'.$id.'">'. $title.'</h1>' . "\n";
	}
	else
	{
		$html = <<< EOD
<div class="page-header">
	<h1 id="haik_h1_{$id}">{$title}</h1>
	{$subtitle}
</div>
EOD;
	}


	return $html;

}

/* End of file h1.inc.php */
/* Location: /app/haik-contents/plugin/h1.inc.php */