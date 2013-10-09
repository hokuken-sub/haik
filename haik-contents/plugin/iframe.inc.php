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
 *   Description
 *   
 *   Usage :
 *   
 */
define('PLUGIN_IFRAME_ALLOW_CSS', TRUE);

// ----
define('PLUGIN_IFRAME_FIT_IFRAME_JS', '
<script type="text/javascript">
$(function(){
	$(\'iframe.autofit_iframe\').load(function(){
    if (this.contentWindow.document.documentElement)
		$(this).height(this.contentWindow.document.documentElement.scrollHeight+10);
    });
    $(\'iframe.autofit_iframe\').triggerHandler(\'load\');
});
</script>
'
);

function plugin_iframe_convert()
{
	global $pkwk_dtd;
	$qm = get_qm();

	$qt = get_qt();
	
	$args = func_get_args();
	$body = trim(str_replace(array("\n", "\r"), ' ', array_pop($args)));
	$src = trim(array_shift($args));

	
	//オプション：デフォルト値
	$width = '100%';
	$height = 200;
	$align = 'center';
	$resize = TRUE;
	$fit = TRUE;
	$fit_force = FALSE;
	$options = array(
		'frameborder' => '0',
		'class' => '',
	);
	$add_class = 'plugin-haik-iframe';
	
	//HTMLが指定されている場合、そちらからオプションを読み込む
	if (preg_match('/^<iframe ([^>]+)><\/iframe>$/i', $body, $mts))
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
		//文字位置
		else if (in_array($arg, array('left', 'center', 'right')))
		{
			$align = $arg;
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
		$html .= ' ' . $attr . '=' . $value;
	}
	$html .= '></iframe>';
	
	//autofit 
	if ($fit OR $resize)
	{
		$addscript = <<< EOS
<script>
$(function(){
	$("[data-haik-autoresize!=off]");
})
</script>

EOS;
		
	}
	
	return $html;

}


/* End of file iframe.inc.php */
/* Location: /haik-contents/plugin/iframe.inc.php */