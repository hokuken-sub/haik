<?php
/**
 *   Embed Video Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/video.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/04/23
 *   modified : 13/09/05
 *   
 *   設置した動画や動画共有サイト上の動画を貼り付けられる。
 *   
 *   Usage :
 *   #video(filename[,width,height,poster])
 *   
 */

define('PLUGIN_VIDEO_CODE_FLASH', '<script type="text/javascript"> function startvideo_flash(filepath,el,width,height){ el.innerHTML =\'<object width="\'+ width +\'" height="\'+ height +\'" codebase="http://active.macromedia.com/flash7/cabs/ swflash.cab#version=9,0,28,0" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"><param value="\'+ filepath +\'" name="src"/><param value="best" name="quality"/><param value="always" name="allowScriptAccess"/><param value="true" name="allowFullScreen"/><param value="showall" name="scale"/><param value="autostart=true" name="flashVars"/><param name="wmode" value="transparent"><embed width="\'+ width +\'" height="\'+ height +\'" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" flashvars="autostart=true" scale="showall" allowfullscreen="true" allowscriptaccess="always" quality="best" src="\'+ filepath +\'" name="csSWF"/></object>\'; el.onclick=\'return false;\';}</script>');

define('PLUGIN_VIDEO_CODE_MOV', '<script type="text/javascript"> function startvideo_mov(filepath,el,width,height){ el.innerHTML =\'<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"    codebase="http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0" width="\'+ width +\'" height="\'+ height +\'" align="middle">  <param name="src" value="\'+ filepath +\'" />  <param name="autoplay" value="true" />  <embed src="\'+ filepath +\'" autoplay="true" width="\'+ width +\'"      height="\'+ height +\'" align="middle" pluginspage="http://www.apple.com/quicktime/download/">  </embed></object>\'; el.onclick=\'return false;\';}</script>');

define('PLUGIN_VIDEO_CODE_RAM', '<script type="text/javascript"> function startvideo_ram(filepath,el,width,height){ el.innerHTML =\'<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" height="\'+ height +\'" width="\'+ width +\'"><param name="controls" value="ImageWindow"><paramname="console"  value="Clip"><embed type="audio/x-pn-realaudio-plugin" console="Clip" controls="ImageWindows"  height="\'+ height +\'"  width="\'+ width +\'"></embed></object><!--ここからコントロールパネル一式--------><objectid=video1  classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" height="40" width="\'+ width +\'"><param name="controls"  value="All"><param name="console" value="Clip"><param name="autostart" value="true"><param  name="src" value="\'+ filepath +\'"><embedsrc="\'+ filepath +\'" type="audio/x-pn-realaudio-plugin" console="Clip" controls="All" height="40"  width="\'+ width +\'" autostart="true"></embed></object>\';  el.onclick=\'return false;\';}</script>');

define('PLUGIN_VIDEO_CODE_WMV', '<script type="text/javascript"> function startvideo_wmv(filepath,el,width,height){ el.innerHTML =\'<object><param name="Filename" value="\'+ filepath +\'"><param name="AnimationAtStart" value="true"><param name="AutoStart" value="true"><param name="AutoRewind" value="true"><param name="Balance" value="0"><param name="PlayCount" value="true"><param name="ShowControls" value="true"><param name="ShowPositionControls" Value="true"><param name="ShowStatusBar" Value="true"><param name="ShowTracker" Value="true"><param name="ShowAudioControls" value="true"><param name="ShowDisplay" value="false"><param name="ShowGotoBar" value="false"><param name="Volume" value="0"><embed width="\'+ width +\'" height="\'+ height +\'" src="\'+ filepath +\'" showTracker="1" showPositionControls="0" controller="true" autoplay="true" playeveryframe="false"  pluginspage="plugin.html"></object>\'; el.onclick=\'return false;\';}</script>');

define('PLUGIN_VIDEO_CODE_AVI', '<script type="text/javascript"> function startvideo_avi(filepath,el,width,height){ el.innerHTML =\'<object classid="CLSID:6BF52A52-394A-11D3-B153-00C04F79FAA6"><param name="URL" value="\'+ filepath +\'"><param name="Showcontrols" value="true"><param name="AutoStart" value="true"><param name="ShowStatusBar" value="true"><param name="ShowDisplay" value="false"><embed type="video/x-msvideo" src="\'+ filepath +\'" pluginspage="http://www.microsoft.com/isapi/redir.dll?prd=windows&sbp=mediaplayer&ar=Media&sba=Plugin&" name="MediaPlayer" width="\'+ width +\'" height="\'+ height +\'" showcontrols=true autostart=false showstatusbar=true showdisplay=false filename="\'+ filepath +\'" align="middle"></embed></object>\'; el.onclick=\'return false;\';}</script>');


define('PLUGIN_VIDEO_CODE_FLV', '<script type="text/javascript"> function startvideo_flv(filepath,el,width,height){ var fname = filepath.split(/[\\\/]/).pop(); var path = filepath.replace(fname,\'\'); el.innerHTML =\'<object classid="CLSID:D27CDB6E-AE6d-11CF-96B8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="\' + width +\'" height="\' + height + \'" id="player" align="middle">   <param name="movie" value="\' + path + \'player.swf?file=\' + fname + \'&size=false&aplay=true&autorew=false&title=" /><param name="menu" value="false" /><param name="quality" value="high" /><param name="bgcolor" value="#FFFFFF" /><noscript><a href=http://www.dvdvideosoft.com/products/dvd/Free-DVD-Decrypter.htm>dvd decrypter</a></noscript>  <embed src="\' + path + \'player.swf?file=\' + fname + \'&size=false&aplay=true&autorew=false&title=" menu="false" quality="high" bgcolor="#FFFFFF" width="\' + width +\'" height="\' + height + \'" name="player" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /></object>\'; el.onclick=\'return false;\';}</script>');

define('PLUGIN_VIDEO_YOUTUBE_EMBEDED_OPTION', '?theme=light&showinfo=0&autohide=1');

$plugin_dir = PLUGIN_DIR;
$tmpjs = <<<EOD
<script type="text/javascript" src="{$plugin_dir}video/js/swfobject.js"></script> 
<script type="text/javascript"> 
function startvideo_flvplayerlite(url,e,w,h,cnt) {

	var flashvars = {
		vidWidth: w,
		vidHeight: h,
		vidPath: url,
		thumbPath: "",
		autoPlay: "true",
		autoLoop: "false",
		watermark: "hide",
		seekbar: "show",
		showControls: "true",
		vidAspectRatio: "fit",
		plShowAtStart: "true",
		plHideDelay: "2000" 
	};
	var params = {
		menu: "true",
		allowfullscreen: "true",
		allowscriptaccess: "always",
		wmode: "transparent"
	};
	var attributes = {
		id: "playerlite",
		name: "playerlite"
	};

	e.innerHTML = '<span id="playerlite'+cnt+'">ビデオの再生には、FlashPlayerが必要です。<br /><a href="http://get.adobe.com/jp/flashplayer/otherversions/" target="_blank" title="外部ウィンドウで開きます">こちら</a>より、インストールしてください。<br /><br />iPad、iPhoneの方は、こちらをクリックするとビデオが再生されます。<br /><a href="'+url+'">ビデオを再生する</a>';
	e.onclick = 'return false';
	
	flashvars.vidPath = url;
	flashvars.vidWidth = w;
	flashvars.vidHeight = h;
 
	attributes.id = "playerlite"+cnt;
	attributes.name = "playerlite"+cnt;
 
	swfobject.embedSWF("{$plugin_dir}video/swf/playerLite.swf", "playerlite"+cnt, flashvars.vidWidth, flashvars.vidHeight, "9.0.0","{$plugin_dir}video/swf/expressInstall.swf", flashvars, params, attributes);
}
</script> 
EOD;


define('PLUGIN_VIDEO_CODE_FLVPLAYERLITE', $tmpjs);

unset($tmpjs);

define('PLUGIN_VIDEO_EMBED_WIDTH', 500);
define('PLUGIN_VIDEO_EMBED_HEIGHT', 281);

function plugin_video_convert(){
    global $vars, $script;
    $qm = get_qm();
	
	$qt = get_qt();


    $args = func_get_args();
    $args_num = count($args);
    
    list($url, $width, $height, $img) = array_pad($args, 4, '');
    
    $url = trim($url);
	if (is_url($url) && preg_match('*^https?://(www\.youtube\.com|youtu\.be|vimeo.com)/*', $url))
	{
		return plugin_video_embed($url, $width, $height, TRUE);
	}
	else if ( ! is_url($url) && ! file_exists(get_file_path($url)))
	{
		return 'error(#video): file not found';
	}

	$url = get_file_path($url);
	$img = get_file_path($img);

	//parse url : 拡張子を取り出し
	$path_parts = pathinfo($url);
	$arr = explode('?', $path_parts['extension']);
	$ext = strtolower($arr[0]);

    $height = $height ? (int)$height : PLUGIN_VIDEO_EMBED_WIDTH;
    $width = $width ? (int)$width : PLUGIN_VIDEO_EMBED_HEIGHT;
	
	//videoファイルのチェック	
	$file_exists = is_url($url) ? $fp = fopen($url, 'rb'): file_exists($url);
	if( ! $file_exists){
		return sprintf(__('指定された動画は、存在しません。：%s'), h($url));
	}

	
    //no image file
    $file_exists = is_url($img)? $fp = fopen($img, 'rb'): file_exists($img);
    if ( isset($fp) ) {fclose($fp);}

    $auto_thumbnail = FALSE;
    if( $img == '' || ! $file_exists ){
    	preg_match('/(.*)\.([^.]+)$/', $url, $matches);
    	$bname = $matches[1];
    	
    	if (file_exists($bname.'.png'))
    		$img = $bname.'.png';
    	else if (file_exists($bname.'.jpg'))
    		$img = $bname.'.jpg';
    	else if (file_exists($bname.'.gif'))
    		$img = $bname.'.gif';
		else
		{
	    	$img = '';
	    	$auto_thumbnail = TRUE;
		}
    }


	switch($ext){
	
	case "swf" :
		$qt->appendv_once('plugin_video_swf', 'beforescript', PLUGIN_VIDEO_CODE_FLASH);
		return plugin_video_htmlcode("flash", $url, $width, $height, $img);		
	break;
	
	case "mov" :
		$qt->appendv_once('plugin_video_mov', 'beforescript', PLUGIN_VIDEO_CODE_MOV);
		return plugin_video_htmlcode("mov", $url, $width, $height+16, $img);
		
		break;

	case "ram" :
		$qt->appendv_once('plugin_video_ram', 'beforescript', PLUGIN_VIDEO_CODE_RAM);
		return plugin_video_htmlcode("ram", $url, $width, $height+16, $img);
		break;

	case "wmv" :
		$qt->appendv_once('plugin_video_wmv', 'beforescript', PLUGIN_VIDEO_CODE_WMV);
		return plugin_video_htmlcode("wmv", $url, $width, $height+16, $img);
	break;

	case "avi" :
		$qt->appendv_once('plugin_video_avi', 'beforescript', PLUGIN_VIDEO_CODE_AVI);
		return plugin_video_htmlcode("avi", $url, $width, $height+16, $img);
		break;

	case "flv" :
		
		$player_path = dirname($url).'/player.swf';
		if( !file_exists( $player_path ) )
		{
			return $qm->m['plg_video']['err_player'];
		}
		
		$qt->appendv_once('plugin_video_flv', 'beforescript', PLUGIN_VIDEO_CODE_FLV);
		return plugin_video_htmlcode("flv", $url, $width, $height, $img);
		break;
	
	case "mp4" :
		
		$player_path = PLUGIN_DIR.'video/swf/playerLite.swf';
		if( !file_exists($player_path) ){
			return $qm->m['plg_video']['err_player'];		
		}
		
		$qt->appendv_once('plugin_video_flvplayerlite', 'plugin_script', PLUGIN_VIDEO_CODE_FLVPLAYERLITE);
		
		static $flvplayerlite_id_cnt;
		$flvplayerlite_id_cnt++;
		
		//flvplayerが、FQDNを要求するので、http(s),ftpから始まっていなければ$scriptでFQDNにする
		if(! preg_match('/^(https?|ftp):\/\//', $url) ){
			$url = dirname($script.'dummy').'/'.$url;
		}

		//iPhone, iPad, Androidに対応（videoタグを出すだけだけど）
		if(strpos(UA_NAME,'iPhone') !== FALSE || strpos(UA_NAME,'iPod') !== FALSE || strpos(UA_NAME,'Mobile Safari') !== FALSE){
			return '<video width="'.$width.'" height="'.$height.'" src="'.h($url).'" poster="'.($auto_thumbnail ? '' : h($img)).'" controls="controls"></video>';
		}

		//flvplayerliteのみ、idが必要なのでムリヤリ挿入
		$result = plugin_video_htmlcode("flvplayerlite", $url, $width, $height.','.$flvplayerlite_id_cnt, $img);
		return $result;
		break;
	case 'ogv':
	case 'webm':
		return '<video width="'.$width.'" height="'.$height.'" src="'.h($url).'" poster="'.($auto_thumbnail ? '' : h($img)).'" controls="controls"></video>';
		break;
	default:
		return $qm->replace('plg_video.err_unsupported', $url);
	}
}

function plugin_video_htmlcode($type, $url, $width, $height, $img)
{
	$qm = get_qm();
	
	list($height, $_id) = explode(',', $height, 2);
	
	if ($img === '')
	{
		$img_tag = '<div class="img-video-poster" title="'. h(__('クリックして再生して下さい。')).'" style="width:'. h($width) .'px;height:'. h($height) .'px;"><div></div></div>';
		$img_tag = '<div class="img-video-poster" title="'. h(__('クリックして再生して下さい。')).'"><div></div></div>';
	}
	else
	{
		$img_tag = sprintf('<img src="%1$s" title="'. h(__('クリックして再生して下さい。')).'" width="%2$s" height="%3$s" style="cursor:pointer" alt="'. h(__('クリックして再生して下さい。')).'" />', h($img), h($width), h($height));
	}
	$html = sprintf('<div class="orgm-video-player orgm-video-embed-wrapper"><a onclick="javascript:startvideo_%1$s(\'%2$s\',this, %3$s, %4$s); return false;">%5$s</a></div>', h($type), h($url), h($width), h($height), $img_tag);
	$html = sprintf('<div class="orgm-video-player orgm-video-poster-wrapper"><a class="orgm-video-play-trigger" data-type="%1$s" data-src="%2$s" data-org-width="%3$s" data-org-height="%4$s" data-id="%6$s">%5$s</a></div>', h($type), h($url), h($width), h($height), $img_tag, $_id);
	
	return $html;
}

/**
 * YouTube と Vimeo を貼り付ける
 */
function plugin_video_embed($url, $width, $height, $block = FALSE)
{
	$html = '';
	
	//YouTube
	//短縮URL(youtu.be)も
	if (preg_match('|^https?://www\.youtube\.com/watch\?v=(\w+)|', $url, $mts)
	 OR preg_match('|^https?://youtu\.be/(\w+)|', $url, $mts))
	{
		$width = $width ? (int)$width : PLUGIN_VIDEO_EMBED_WIDTH;
		$height = $height ? (int)$height : PLUGIN_VIDEO_EMBED_HEIGHT;
		$vid = $mts[1];
		$embed_url = '//www.youtube.com/embed/' . $vid . PLUGIN_VIDEO_YOUTUBE_EMBEDED_OPTION;
		$html = '<iframe width="'. h($width) .'" height="'. h($height) .'" src="'. h($embed_url) .'" frameborder="0" allowfullscreen></iframe>';
	}
	//Vimeo
	else if (preg_match('|^https?://vimeo\.com/(\d+)$|', $url, $mts))
	{
		$width = $width ? (int)$width : PLUGIN_VIDEO_EMBED_WIDTH;
		$height = $height ? (int)$height : PLUGIN_VIDEO_EMBED_HEIGHT;
		$vid = $mts[1];
		$embed_url = '//player.vimeo.com/video/'. $vid . '?title=0&portrait=0&byline=0';
		$html = '<iframe src="'. h($embed_url) .'" width="'. h($width) .'" height="'. h($height) .'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
	}

	if ($block)
	{
		$html = '<div class="orgm-video-embed-wrapper">' . $html . '</div>';
	}

	return $html;
}

/**
 * 文中のYoutube, Vimeo 動画へのリンクを自動的に iframe にする。
 */
function plugin_video_set_autoload()
{
	global $video_autoload;
	
	if (isset($video_autoload) && $video_autoload)
	{
		$qt = get_qt();
		$qt->setjsv('videoAutoload', array(
			'width'  => PLUGIN_VIDEO_EMBED_WIDTH,
			'height' => PLUGIN_VIDEO_EMBED_HEIGHT,
			'youtube' => array(
				'embededOption' => PLUGIN_VIDEO_YOUTUBE_EMBEDED_OPTION
			)
		));
	}
	

}


/* End of file video.inc.php */
/* Location: /haik-contens/plugin/video.inc.php */