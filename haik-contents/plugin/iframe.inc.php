<?php
/**
 *   Iframe Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/iframe.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 
 *   modified : 2013/10/09
 *   
 *   iframe を設置する。
 *   オートリサイズ機能：親要素の横幅に合わせて、iframe をリサイズする機能。デフォルトで有効。
 *   オートフィット機能：iframe window の大きさに合わせて iframe をリサイズする機能。デフォルトで無効。X-FRAME-OPTION: SAMEORIGIN の場合、動作しない場合があるので注意
 *   
 *   Usage :
 *     #iframe(URL[,HEIGHT[,WIDTH]])
 *     #iframe(URL[,WIDTHxHEIGHT])
 *     #iframe(URL[,width=WIDTH[,height=HEIGHT]])
 *     #iframe{{
 *     <iframe ...></iframe>
 *     }}
 *   
 */

function plugin_iframe_convert()
{
	$qt = get_qt();
	
	$args = func_get_args();
	$body = '';
	if (count($args) > 0)
	{
		$body = trim(str_replace(array("\n", "\r"), ' ', $args[count($args)-1]));
		if (preg_match('/^<iframe ([^>]+)><\/iframe>$/i', $body, $mts))
		{
			array_pop($args);
		}
		else
		{
			$body = '';
		}
	}
	$src = isset($args[0]) && is_url($args[0], FALSE, TRUE) ? array_shift($args) : '';

//var_dump(h($body), $src);

	//オプション：デフォルト値
	$width = '100%';
	$height = 200;
	$resize = TRUE;
	$fit = FALSE;
	$fit_force = FALSE;
	$options = array(
		'frameborder' => '0',
		'class' => '',
	);
	$add_class = 'plugin-haik-iframe';
	
	//HTMLが指定されている場合、そちらからオプションを読み込む
	if ($body)
	{
		$attrs = ' ' . $mts[1] . ' ';
		if (preg_match_all('/\s([^= ]+?)(?:="([^"]*)")/', $attrs, $mtsarr))
		{
			foreach ($mtsarr[1] as $i => $attr)
			{
				$value = $mtsarr[2][$i];
				switch ($attr)
				{
					case 'src':
					case 'width':
					case 'height':
						${$attr} = trim($value);
						break;
					case 'data-haik-autoresize':
						$resize = (trim($value) !== "off");
						break;
					case 'data-haik-autofit':
						$fit = (trim($value) !== "off");
						$fit_force = $fit && (trim($value) === "force");
						break;
					default:
						$options[$attr] = $value;
				}
			}
		}
	}
	
	//error
	if (strlen(trim($src)) == 0) {
		$msg = __('URLが指定されていません。');
		$usage = '#iframe(URL[,height[,width]])';
		return <<< EOD
<div class="plugin-iframe-msg alert alert-danger">
	<a href="" class="close" data-dismiss="alert">&times;</a>
	{$msg}<br>
	<code>{$usage}</code>
</div>
EOD;
	}

	//引数を解析

	$height_set = $width_set = FALSE;
	foreach ($args as $arg)
	{
		$arg = trim($arg);
		
		//数字のみの指定の場合、高さ→幅の順で取得する
		if (preg_match('/^\d+$/', $arg, $mts))
		{
			if ($height_set)
			{
				$width = $mts[0];
				$width_set = TRUE;
			}
			else
			{
				$height = $mts[0];
				$height_set = TRUE;
			}
		}
		//width= height= の指定も可能
		else if (preg_match('/^(width|height)=(.+)$/', $arg, $mts))
		{
			${$mts[1]} = $mts[2];
			${$mts[1] . '_set'} = TRUE;
		}
		//000x000 （幅x高さ）の指定も可能
		else if (preg_match('/^(\d+)x(\d+)$/', $arg, $mts))
		{
			$width = $mts[1];
			$height = $mts[2];
			$height_set = $width_set = TRUE;
		}
		//オートリサイズをオフ
		else if ($arg === 'fix')
		{
			$resize = FALSE;
		}
		//コンテンツサイズに合わせてサイズを調節する
		else if ($arg === 'fit')
		{
			$fit = TRUE;
			$fit_force = TRUE;
		}
		//属性=属性値
		else if (preg_match('/^([^= ]+)=(.*)$/i', $arg, $mts))
		{
			$options[$mts[1]] = h($mts[2]);//ここでエスケープ
		}
		
	}

	$options['class'] .= ' ' . $add_class;
	
	//fit を明示的に指定しておらず、
	//width を指定している場合、fit を解除する
	if ($width_set && ! $fit_force)
	{
		$fit = FALSE;
	}
	
	$html = '<iframe src="'. h($src) .'" width="'. h($width) .'" height="'. h($height) .'" '. ($resize ? 'data-haik-autoresize' : '') .' '. ($fit ? 'data-haik-autofit' : '');
	foreach ($options as $attr => $value)
	{
		$html .= ' ' . $attr . '="' . $value. '"';
	}
	$html .= '></iframe>';
	
	//autofit 
	if ($fit OR $resize)
	{
		$addscript = <<< EOS
<script>
$(function(){
	var \$autofit = $("iframe[data-haik-autofit][data-haik-autofit!=off]")
	.on("load.haik.iframe", function(){
		if (this.contentWindow.document.documentElement)
			$(this).height(this.contentWindow.document.documentElement.scrollHeight+10);
	});
	setTimeout(function(){
		\$autofit.triggerHandler("load.haik.iframe");
	}, 25);

	var \$autoresize = $("iframe[data-haik-autoresize][data-haik-autoresize!=off]")
	.each(function(){
		var \$self = \$(this)
		  , orgWidth = \$self.width()
		  , orgHeight = \$self.height()
		\$self.data({
			orgWidth: orgWidth,
			orgHeight: orgHeight
		});
		var ratio = false;
		if (/^\d+$/.test(\$self.attr("width"))) {
			ratio = orgHeight / orgWidth;
		}
		\$self.data("ratio", ratio);
	})
	.on("resize.haik.iframe", function(){
		var \$self = \$(this);
		var width, height, ratio, parentWidth,
			orgWidth, orgHeight, newWidth, newHeight;
		width = \$self.width();
		height = \$self.height();
		orgWidth = \$self.data("orgWidth");
		orgHeight = \$self.data("orgHeight");
		ratio = \$self.data("ratio");
		parentWidth = \$self.parent().width();
		if (parentWidth < width) {
			newWidth = parentWidth;
			newHeight = ratio !== false ? newWidth * ratio : height;
			\$self.width(newWidth).height(newHeight);
		}
		else if (parentWidth > width && width < orgWidth) {
			\$self.width(orgWidth).height(orgHeight);
		}
		
	});
	
	var fireResize = function(){
		\$autoresize.each(function(){
			\$(this).triggerHandler("resize.haik.iframe");
		});
	};
	
	setTimeout(fireResize, 25);
	
	$(window).on("resize.haik.iframe", fireResize);
	
})
</script>

EOS;
		$qt->appendv_once('plugin_iframe', 'plugin_script', $addscript);
		
	}
	
	return $html;

}


/* End of file iframe.inc.php */
/* Location: /haik-contents/plugin/iframe.inc.php */