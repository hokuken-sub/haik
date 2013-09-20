<?php
/**
 *   トップに戻るリンク
 *   -------------------------------------------
 *   scrollup.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/04/22
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
function plugin_scrollup_convert()
{
	$args = func_get_args();

	$qt = get_qt();
	
	$target = 'body';
	$text = __('トップ');
	if (count($args) > 0)
	{
		$target = $args[0];
		if (isset($args[1]))
		{
			$text = $args[1];
		}
	}

	$body = '
<a href="#" class="scroll-up thumbnail" data-target="'.h($target).'">
	<span><i class="orgm-icon orgm-icon-arrow-up"></i><br>'.h($text).'</span>
</a>
';
	$qt->appendv_once('plugin_scrollup', 'body_last', $body);
	
	return;
}
?>