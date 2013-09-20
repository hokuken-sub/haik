<?php
/**
 *   Progress Bar Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/progress_bar.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/08/22
 *   modified :
 *   
 *   進捗をバーで表す。
 *   
 *   Usage :
 *     #progress_bar(60)
 *   
 */

function plugin_progress_bar_convert()
{

	$args = func_get_args();
	
	$value = 100;
	$color = $style = $animation = '';
	
	foreach ($args as $arg)
	{
		$arg = trim($arg);
		
		if (is_numeric($arg))
		{
			$value = (int)$arg;
		}
		else
		{
			switch ($arg)
			{
				case 'success':
				case 'info':
				case 'warning':
				case 'danger':
					$color = ' progress-bar-' . $arg;
					break;
				case 'striped':
				case 'stripe':
					$style = ' progress-striped';
					break;
				case 'active':
				case 'animated':
					$animation = ' active';
					break;
			}
		}
	}
	
	
	$html = <<< EOH
<div class="progress{$style}{$animation}">
	<div class="progress-bar{$color}" role="progressbar" aria-valuenow="{$value}" aria-valuemin="0" aria-valuemax="100" style="width: {$value}%;">
		<span class="sr-only">{$value}% Complete</span>
	</div>
</div>
EOH;

	return $html;
}