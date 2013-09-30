<?php
/**
 *   Facebook Plugins' Init File
 *   -------------------------------------------
 *   ./plugin/fb_root.inc.php
 *   
 *   Copyright (c) 2011 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2011-08-10
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

function plugin_fb_root_init()
{
	global $add_xmlns;
	static $inited = FALSE;
	
	if ($inited)
	{
		return;
	}
	$inited = TRUE;
	
	$qt = get_qt();
	$qt->setv('jquery_include', true);
	
}

/**
 * Facebook アプリIDを返す。
 * $fb_app_id がなければ、FALSEを返す。
 */
function plugin_fb_root_get_fb_app_id()
{
	global $fb_app_id;
	if (isset($fb_app_id) && strlen(trim($fb_app_id)) > 0)
	{
		return $fb_app_id;
	}
	return FALSE;
}

/**
 * Facebook 上で表示するために、
 * CSS, javascript をbeforescript へセットする
 */
function plugin_fb_root_set_page()
{
	global $vars;
	$qt = get_qt();
	
	plugin_fb_root_set_jsapi(FALSE);
	plugin_fb_root_set_page_css();
	plugin_fb_root_set_page_js(TRUE);
}
function plugin_fb_root_set_page_css()
{
	global $vars;
	$qt = get_qt();
	
	$beforescript = '
<style type="text/css">
body {
	background: none;
	background-color: #fff;
	width: 520px;
	margin: 0;
	padding: 0;
	overflow-x: hidden;
}
#wrapper{
	width: 520px;
	margin-bottom: 30px;
	padding: 0;
	overflow: hidden;
	border: 0;
}
#headcopy,#header,#navigator,#navigator2,#footer,#license,#wrap_sidebar,#wrap_sidebar2,#toolbar_upper_max,#toolbar_upper_min{
	display: none;
}
#wrap_content {
	width: 100%;
	border: none;
	margin: 0;
	padding: 0;
}
#main {
	width: auto;
	border: none;
	margin: 0;
	padding: 0;
}
#content {
	margin: 0;
	padding: 0;
	border: none;
}
#content h2.title {
	display: none;
}
#body h2, #body h3, #body h4 {
	margin-left: 0;
	margin-right: 0;
}
#body p {
	padding-left: 0;
	padding-right: 0;
}
</style>
';
	$qt->appendv('beforescript', $beforescript);
}
function plugin_fb_root_set_page_js($on_facebook = FALSE)
{
	global $vars;
	$qt = get_qt();
	
	$appid = plugin_fb_root_get_fb_app_id();
	if ($appid === FALSE)
	{
		$appid = '0123456789';
	}
	
	$beforescript = '
<script type="text/javascript">
<!--
$(function(){
	$("body").prepend(\'<div id="fb-root"></div>\');

	if (typeof FB_set_jsapi != "undefined") {
		FB_set_jsapi(document, "script", "facebook-jssdk", function(){
			FB.init({
			appId  : \''. h($appid). '\',
			status : true,
			cookie : true,
			xfbml  : true,
			logging : true
			});
			if (FB.Canvas.isTabIframe()) {
				FB.Canvas.setAutoResize();
				//link mod
				$("#body a:not([href^=#])").attr("target", "_blank")
					.filter("[href*=\'facebook.com\']:not([href*=\'developers.facebook.com\'])").attr("target", "_parent");
				$("form").append(\'<input type="hidden" name="signed_request" value="'.h($vars['signed_request']).'" /> \');
			}
		});
	}
});
--></script>
';
	$qt->appendv_once('plugin_fb_root_page_js', 'plugin_script', $beforescript);
}

function plugin_fb_root_set_jsapi($xfbml = FALSE, $locale = 'ja_JP')
{
	$qt = get_qt();

	$beforescript = plugin_fb_root_get_jsapi($xfbml, $locale);
	$qt->prependv_once('plugin_fb_root_jsapi', 'plugin_script', $beforescript);
}

function plugin_fb_root_get_jsapi($xfbml = FALSE, $locale = 'ja_JP')
{
	$qt = get_qt();
	
	$params = array();
	
	$appid = plugin_fb_root_get_fb_app_id();
	if ($appid !== FALSE)
	{
		$params['appId'] = $appid;
		//app ID が設定されている場合、div#fb-root をセットする
		plugin_fb_root_set_page_js();
	}
	else
	{
		$appendscript = '
FB_set_jsapi(document, "script", "facebook-jssdk", function(){
	$("body").prepend(\'<div id="fb-root"></div>\');
});
';
	}

	if ($xfbml !== FALSE)
	{
		$params['xfbml'] = '1';
	}
	
	$query = http_build_query($params);

	$beforescript = '
<script type="text/javascript">
function FB_set_jsapi(d, s, id, callback) {
	var js, fjs = d.getElementsByTagName(s)[0];
	if (d.getElementById(id)) return;
	
	js = d.createElement(s);
	js.id = id;
	
	$(js).bind("load", function(){
		callback.apply();
	})
	.attr("src", "//connect.facebook.net/'. h($locale). '/all.js#'. $query. '");
	fjs.parentNode.insertBefore(js, fjs);
}
'. $appendscript .'
</script>
';
	
	return $beforescript;
}


function plugin_fb_root_get_preview($xfbml = FALSE, $locale = 'ja_JP')
{

	$params = array();
	
	$appid = plugin_fb_root_get_fb_app_id();
	if ($appid !== FALSE)
	{
		$params['appId'] = $appid;
		//app ID が設定されている場合、div#fb-root をセットする
		plugin_fb_root_set_page_js();
	}

	if ($xfbml !== FALSE)
	{
		$params['xfbml'] = '1';
	}
	
	$query = http_build_query($params);

	$html = '<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/'.h($locale).'/all.js#'.$query.'";
  fjs.parentNode.insertBefore(js, fjs);
}(document, "script", "facebook-jssdk"));</script>';


	return $html;	

}

function plugin_fb_root_parse_request()
{
	global $vars;
	if (isset($vars['signed_request']))
	{
		$encoded_sig = null;
		$payload = null;
		list($encoded_sig, $payload) = explode('.', $vars['signed_request'], 2);
		$sig = base64_decode(strtr($encoded_sig, '-_', '+/'));
		$data = json_decode(base64_decode(strtr($payload, '-_', '+/')));
		return $data;
	}
	return FALSE;
}


function plugin_fb_root_get_apps_url()
{
	return 	'https://developers.facebook.com/apps/';
}

function plugin_fb_root_get_fonts()
{
	$fonts = array('', 'arial', 'lucida grande', 'segoe ui', 'tahoma', 'trebuchet ms', 'verdana');

	return $fonts;
}

function plugin_fb_root_get_colorschemes()
{
	$colorschemes = array('light', 'dark');

	return $colorschemes;
}

/**
 * FB系プラグインに来る引数を解釈する
 *
 * @param array $args array of attributes scaffold
 *
 * Scaffold: [{attr_name: DEFAULT_VALUE}, {attr_name: [DEFAULT_VALUE, DATA_SET]}, ...]
 */
function plugin_fb_root_parse_args($args, $tmpl = array())
{
	$ret = $tmpl;
	
	$init_href = FALSE;
	
	foreach ($args as $i => $arg)
	{
		$arg = trim($arg);
		
		// href, site
		if ( ! $init_url && is_url($arg))
		{
			if (isset($ret['data-href']))
			{
				$ret['href'] = $arg;
			}
			else if (isset($ret['site']))
			{
				$parsed_url = parse_url($arg);
				$ret['site'] = $parsed_url['host'];
			}
			$init_url = TRUE;
		}
		// no send
		else if ($arg == 'nosend' && isset($ret['data-send']))
		{
			$ret['data-send'] = 'false';
		}
		// no faces
		else if ($arg == 'noface' && isset($ret['data-show-faces']))
		{
			$ret['data-show-faces'] = 'false';
		}
		// no header
		else if ($arg == 'noheader' && isset($ret['data-header']))
		{
			$ret['data-header'] = 'false';
		}
		// no stream
		else if ($arg == 'nostream' && isset($ret['data-stream']))
		{
			$ret['data-stream'] = 'false';
		}
		// no border
		else if ($arg == 'noborder' && isset($ret['data-show-border']))
		{
			$ret['data-show-border'] = 'false';
		}
		// force wall
		else if ($arg == 'force-wall' && isset($ret['data-force-wall']))
		{
			$ret['data-force-wall'] = 'true';
		}
		// layouts
		else if (strpos($arg, 'layout=') === 0 && isset($ret['data-layout']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (is_array($ret[$key]))
			{
				$default = $ret[$key][0];
				$opts = $ret[$key][1];
			}
			else
			{
				$opts = $ret[$key];
			}
			if (in_array($val, $opts))
			{
				$ret[$key] = $val;
			}
		}
		// fonts
		else if (strpos($arg, 'font=') === 0 && isset($ret['data-font']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (in_array($val, plugin_fb_root_get_fonts()))
			{
				$ret[$key] = $val;
			}
		}
		// color schemes
		else if (strpos($arg, 'colorscheme=') === 0 && isset($ret['data-colorscheme']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (in_array($val, plugin_fb_root_get_colorschemes()))
			{
				$ret[$key] = $val;
			}
		}
		// link target
		else if (strpos($arg, 'linktarget=') === 0 && isset($ret['data-linktarget']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (preg_match('/^_[a-zA-Z]+$/', trim($val)))
			{
				$ret[$key] = $val;
			}
		}
		// actions
		else if (strpos($arg, 'action=') === 0 && isset($ret['data-action']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (is_array($ret[$key]))
			{
				$opts = $ret[$key][1];
			}
			else
			{
				$opts = $ret[$key];
			}
			if (in_array($val, $opts))
			{
				$ret[$key] = $val;
			}
		}
		// ref
		else if (strpos($arg, 'ref=') === 0 && isset($ret['data-ref']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (preg_match('/^[a-zA-Z0-9+\/=.:_-]+$/', $val))
			{
				$ret[$key] = $val;
			}
		}
		// width
		else if (strpos($arg, 'width=') === 0 && isset($ret['data-width']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (preg_match('/^\d+$/', trim($val)))
			{
				$ret[$key] = $val;
			}
		}
		// height
		else if (strpos($arg, 'height=') === 0 && isset($ret['data-height']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (preg_match('/^\d+$/', trim($val)))
			{
				$ret[$key] = $val;
			}
		}
		// num_posts(num)
		else if (strpos($arg, 'num=') === 0 && isset($ret['data-num-posts']))
		{
			list($key, $val) = explode('=', $arg, 2);
			if (preg_match('/^\d+$/', trim($val)))
			{
				$ret['data-num-posts'] = $val;
			}
		}
		
	}


	foreach ($ret as $key => $val)
	{
		if (is_array($val))
		{
			if ($val[0] !== FALSE)
			{
				$ret[$key] = $val[0];
			}
			else
			{
				unset($ret[$key]);
			}
		}
		else if ($val === FALSE)
		{
			unset($ret[$key]);
		}
	}
	
	return $ret;
}


function plugin_fb_root_create_tag($tag_name, $attrs = array())
{
	$fmt = "<{$tag_name}%s></{$tag_name}>";
	$tag = '';
	if (is_array($attrs))
	{
		$attr_strs = array();
		foreach ($attrs as $attr => $val)
		{
			$attr_strs[] = $attr. '="'. h($val). '"';
		}
		if (count($attr_strs))
		{
			$tag = sprintf($fmt, ' '. join(' ', $attr_strs));
		}
	}
	else
	{
		$tag = sprintf($fmt, ' '. trim($attrs));
	}
	return $tag;
}

/* End of file fb_root.inc.php */
/* Location: ./plugin/fb_root.inc.php */
