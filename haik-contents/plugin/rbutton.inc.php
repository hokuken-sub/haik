<?php
/**
 *   Rounded Button Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/rbutton.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/10/15
 *   modified :
 *   
 *   丸いボタンを設置するプラグイン。
 *   
 *   Usage :
 *     &rbutton(URL|PAGE){TEXT};
 *     &rbutton(URL|PAGE,BGCOLOR#HEX,COLOR#HEX,SIZE,FONTSIZE){TEXT};
 */


define('PLUGIN_RBUTTON_BTN_SIZE', 80);
define('PLUGIN_RBUTTON_FONT_SIZE', 24);
define('PLUGIN_RBUTTON_BTN_COLOR', '#FFB11B');
define('PLUGIN_RBUTTON_FONT_COLOR', '#333');

function plugin_rbutton_inline()
{

	/* パラメーターを取得 */	
	$args = func_get_args();	
	$text = strip_autolink(array_pop($args)); // Already htmlspecialchars(text)

	if(! isset($args[0])) //引数が足りない
		return '&rbutton(URL|PAGE){TEXT};';

	//分解
	$btnsize = PLUGIN_RBUTTON_BTN_SIZE;
	$fsize = PLUGIN_RBUTTON_FONT_SIZE;
	$url = '#';
	$btncolor = PLUGIN_RBUTTON_BTN_COLOR;
	$fcolor = PLUGIN_RBUTTON_FONT_COLOR;

	$color_cnt = 0;
	$font_cnt = 0;

	
	foreach($args as $v){
		if( is_page($v) ){
			$url = get_page_url($v);
		}
		else if( is_url($v) ){
			$url = $v;
		}
		else if (preg_match('/^(\d|\.)/', $v)) {
			if($font_cnt==0){
				$btnsize = $v;
				$font_cnt++;
			}
			else{
				$fsize = $v;
			}
		}
		else{
			if($color_cnt==0){
				$btncolor = $v;				
				$color_cnt++;
			}
			else{
				$fcolor = $v;			
			}
		}
	}

	
	$borderR = $btnsize / 2;

	$style  = "border-radius: {$borderR}px;";
	$style .= "width:{$btnsize}px;height:{$btnsize}px;";
	$style .= "display:inline-block;";
	$style .= "color:{$fcolor};";
	$style .= "font-size:{$fsize}px;";
	$style .= "background-color:{$btncolor};";
	$style .= "text-decoration:none;text-align:center;";
	$style .= "line-height:{$btnsize}px;";
	
	// !TODO: 他の明度を変える方法を考える
	$add_att = "onmouseover=\"this.style.opacity='0.6'\" onmouseout=\"this.style.opacity='1'\"";

	return "<a href=\"{$url}\" style=\"{$style}\" {$add_att}>{$text}</a>";
}

/* End of file rbutton.inc.php */
/* Location: /haik-contents/plugin/rbutton.inc.php */