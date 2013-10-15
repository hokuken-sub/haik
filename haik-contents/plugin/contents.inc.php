<?php
/**
 *   Table of Contents
 *   -------------------------------------------
 *   /plugin/contents.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/04/19
 *   modified :
 *   
 */

define('PLUGIN_CONTENTS_DEFAULT_LEVEL', 1);
define('PLUGIN_CONTENTS_DEFAULT_TARGET', '#orgm_body');
define('PLUGIN_CONTENTS_DEFAULT_FLAT_FLAG', 0);

function plugin_contents_convert()
{
	$qt = get_qt();
	$qt->setjsv('TOC', true);
	
	$args = func_get_args();
	
	$level  = PLUGIN_CONTENTS_DEFAULT_LEVEL;
	$target = PLUGIN_CONTENTS_DEFAULT_TARGET;
	$flat   = PLUGIN_CONTENTS_DEFAULT_FLAT_FLAG;
	$title  = __('目次');
	$notitle= FALSE;
	$class  = 'orgm-toc';
	
	foreach ($args as $arg)
	{
		$arg = trim($arg);
		if (preg_match('/^level=(\d+)$/', $arg, $mts))
		{
			$level = $mts[1];
		}
		else if (preg_match('/^target=(.*)$/', $arg, $mts))
		{
			$target = $mts[1];
		}
		else if ($arg === 'flat')
		{
			$flat = 1;
		}
		else if ($arg === 'notitle')
		{
			$notitle = TRUE;
		}
		else if ($arg === 'noborder')
		{
			$class .= ' orgm-toc-noborder';
		}
		//目次タイトル
		else
		{
			$title = $arg;
		}
	}
	
	$title = $notitle ? '' : $title;

	return '<nav class="'. h($class) .'" data-level="'. h($level) .'" data-selector="h1,h2,h3,h4" data-target="'. h($target) .'" data-flat="'. h($flat) .'" data-title="'. h($title) .'"></nav>';

}

/* End of file contents.inc.php */
/* Location: /plugin/contents.inc.php */