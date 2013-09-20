<?php
/**
 *   音声再生
 *   -------------------------------------------
 *   jplayer.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/04/23
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
define('PLUGIN_JPLAYER_THEME_DIR', PLUGIN_DIR.'jplayer/themes/');
define('PLUGIN_JPLAYER_THEME_FILE', 'theme.html');
define('PLUGIN_JPLAYER_THEME_CSS', 'theme.css');
define('PLUGIN_JPLAYER_THEME_DEFAULT', 'minimum');
define('PLUGIN_JPLAYER_THUMBNAIL', 'thumbnail.png');


function plugin_jplayer_convert()
{
	static $s_jplayer_cnt = 0, $theme;

	$qm = get_qm();
	$qt = get_qt();

    $args = func_get_args();
	
	$body = '';
	$body = array_pop($args);

	$options = array();
    foreach ($args as $arg)
    {
        list($key, $val) = explode('=', $arg, 2);
        $options[$key] = $val;
    }
	$options['auto'] = isset($options['auto']) ? 'true' : 'false';
	$options['showlist'] = 'false';
	
	//theme は1ページに付き一つだけ
	if ( ! isset($theme))
	{
		//スタイルを切り替える
		if ( ! isset($options['style']) 
			OR ! file_exists(PLUGIN_JPLAYER_THEME_DIR.$options['style'].'/'.PLUGIN_JPLAYER_THEME_FILE))
		{
			$options['style'] = PLUGIN_JPLAYER_THEME_DEFAULT;
		}
		$theme = $options['style'];
	}
	else
	{
		$options['style'] = $theme;
	}
	$options['style'] = PLUGIN_JPLAYER_THEME_DIR.$options['style'];

	//アートワークがあるかどうか調べる
	if (isset($options['artwork']))
	{
		//なければデフォルト画像を使う
		if ( ! is_url($options['artwork']) && ! file_exists(trim($options['artwork'])))
		{
			$options['artwork'] = $options['style'].'/'.PLUGIN_JPLAYER_THUMBNAIL;
		}
	}
	
	$flist_js = '';
	$track = array();
    if (isset($body)) {
	    $body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
        $lines = explode("\n", $body);
	    foreach ($lines as $l) {
	    	if ($l != '') {
		    	list($name, $mp3, $poster) = array_pad(explode(',', $l), 3, '');
		    	
				//アートワークがなければデフォルト画像を使う
				if ( ! is_url($poster) &&  ! file_exists(trim($poster)))
				{
					$poster = isset($options['artwork']) ? $options['artwork'] : ($options['style'].'/'.PLUGIN_JPLAYER_THUMBNAIL);
				}
				
				$track[] = array(
					'title'  => h($name),
					'mp3'    => get_file_path($mp3),
					'poster' => $poster,
				);
	    	}
	    }
	    
	    $flist_js = json_encode($track);
    }

    // はじめての定義の場合、jQueryを出力
	$pid = "jquery_jplayer_" . $s_jplayer_cnt++;
	$addscript = '
<link rel="stylesheet" type="text/css" href="'.h($options['style'].'/'.PLUGIN_JPLAYER_THEME_CSS).'" media="all" />
<script src="'.PLUGIN_DIR.'jplayer/jquery.jplayer.min.js"></script>
<script src="'.PLUGIN_DIR.'jplayer/jplayer.playlist.min.js"></script>
<script src="'.PLUGIN_DIR.'jplayer/jquery.jplayer.plugin.js"></script>
';

	$qt->appendv_once('plugin_jplayer', 'user_script', $addscript);
	
	$addscript = '
<script>
$("#'. $pid .'").data("playList.jplayer", '.$flist_js.');
</script>
';
	
	$qt->appendv('user_script', $addscript);

	if (strlen($flist_js) > 0)
	{
		$options['showlist'] = 'true';
	}

 	$attributes = 'id="'.h($pid).'" jp-auto="'.h($options['auto']).'" jp-showlist="'.h($options['showlist']).'" jp-artwork="'. h($options['artwork']) .'"';

	ob_start();
	include($options['style'].'/'.PLUGIN_JPLAYER_THEME_FILE);
	$out = ob_get_clean();
	$out = str_replace('#{$attributes}',$attributes, $out);

    return $out;
}

?>