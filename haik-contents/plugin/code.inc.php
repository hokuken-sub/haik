<?php
/**
 *   Code Prettify Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/code.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/04/04
 *   modified : 13/09/03
 *   
 *   Google Code Prettify を使い、コードのシンタックスハイライトを行う。
 *   単純に<pre>や<code>で囲むこともできる。
 *
 *   See:
 *     google-code-prettify
 *       https://code.google.com/p/google-code-prettify/
 *   
 *   Usage :
 *     &code{<code>}; // inline code
 *
 *     #code(plain{,scroll}){{...}} // pre block
 *
 *     #code({STYLE_NAME{,linenum{,LANGUAGE}}}){{...}} // pre.prettify block
 *       STYLE_NAME: default value is "sons-of-obsidian". eg) sunburst, default, desert, doxy
 *       linenum: show line number. default value is FALSE
 *       LANGUAGE: default value is AUTO. eg) css, sql, etc.
 *   
 */

function plugin_code_inline()
{

	$args = func_get_args();
	
	$text = array_pop($args);
	
	return '<code>' . $text . '</code>';

}
function plugin_code_convert()
{

	$qt = get_qt();
	
	$args = func_get_args();

	$code = array_pop($args);

	$skin = 'sons-of-obsidian';
	$lang = NULL;
	$display_lines = $plain = $scroll = FALSE;

	foreach ($args as $arg)
	{
		$arg = trim($arg);
		
		switch ($arg)
		{
			case 'sunburst':
			case 'default':
			case 'desert':
			case 'doxy':
				$skin = $arg;
				break;
			case 'sons-of-obsidian':
			case 'sons':
				$skin = 'sons-of-obsidian';
				break;
			case 'line':
			case 'lines':
			case 'linenum':
				$display_lines = TRUE;
				break;
			case 'plain':
			case 'pre':
				$plain = TRUE;
				break;
			case 'scroll':
			case 'scrollable':
				$scroll = TRUE;
				break;
			default:
				$lang = $arg;
		}
	}

	// plain の時は Google-code-prettify を読み込まない
	if ( ! $plain)
	{
		$get_query = array();
		$skin && ($get_query['skin'] = $skin);
		$lang && ($get_query['lang'] = $lang);
		$get_query = http_build_query($get_query);
		$plugin_script = '<script src="//google-code-prettify.googlecode.com/svn/loader/run_prettify.js?'. $get_query .'"></script>
';
		$qt->appendv_once('plugin_code', 'plugin_script', $plugin_script);
		
	}
	
	$format = '<pre class="prettyprint %2$s">%1$s</pre>';
	$plain_format = '<pre class="%3$s">%1$s</pre>';
	
	$body = sprintf($plain ? $plain_format : $format, h($code), $display_lines ? 'linenums' : '', $scroll ? 'pre-scrollable' : '');
	
	return $body;
}

/* End of file code.inc.php */
/* Location: /haik-contents/plugin/code.inc.php */