<?php
//-------------------------------------------------
//
// ページメタ情報の展開
//-------------------------------------------------
$page_meta = $app['page.meta'];
$qt->prependv('user_head', $page_meta->get('user_head', ''));
$qt->setv('description', $page_meta->get('description', $page_meta->get('auto_description', '')));
$qt->setv('keywords', $page_meta->get('keywords', ''));

//-------------------------------------------------
//
// ロゴ部分の生成
//-------------------------------------------------
if($qt->getv('logo_image')) {   //プラグイン等によって置き換えられている場合
	$logo_img_path = $qt->getv('logo_image');
	$logo_title = $qt->getv('logo_title');
}
else {
	$logo_img_path = ($logo_image != '') ? get_file_path($logo_image) : '';
}

$logo_cnts = $logo_title;
$logo_class = "";
if ($logo_img_path !== '')
{
	$logo_cnts = '<img src="'.h($logo_img_path).'" alt="'.h($logo_title).'">';
	$logo_class = ' logo-image';
}

$qt->setv('logo', '<a href="'.h($_LINK['top']).'" class="navbar-brand'.$logo_class.'">'.$logo_cnts.'</a>'."\n");

// カスタム背景
if (isset($style_config['textures']['custom']) 
	&& isset($style_custom_bg['filename'])
	&& $style_texture == 'custom')
{
	$repeat = isset($style_custom_bg['repeat']) ?
		 ('background-repeat: '.$style_custom_bg['repeat'].';') : 
		 ('background-size: cover;background-attachment: fixed;');

	$style = '
<style id="orgm_custom_bg">
body {
	background-image: url('.get_file_path($style_custom_bg['filename']).');
	'.$repeat.'
}
</style>

';
	$qt->appendv('plugin_head', $style);
}


// ソーシャルボタン
if (exist_plugin('share_buttons')) {
	do_plugin_convert('share_buttons');
}


//-------------------------------------------------
//
// デザインの設定
//-------------------------------------------------



// CSSの生成

// !ORIGAMI CSS Include
$qt->appendv('style_css', '<link rel="stylesheet" href="'.CSS_DIR.'origami.css">' . "\n");


// !TODO: 印刷用のCSSをどうするか

$style_css = "\t" . '<link rel="stylesheet" href="'. h(SKIN_DIR . $style_name . '/' . $style_config['style_file']) . '">' . "\n";

// include color css
$less_load = $qt->getv('less_load');
if (isset($style_config['colors'][$style_color]))
{
	$color_cssfile = SKIN_DIR . $style_name.'/'.$style_config['colors'][$style_color];
	$lessfile = SKIN_DIR . $style_name . '/less/' . basename($color_cssfile, '.css') . '.less';
	if ($use_less && file_exists($lessfile))
	{
		$style_css .= "\t".'<link href="'.h($lessfile). '" rel="stylesheet/less">'."\n";
		$less_load = TRUE;
	
	}
	else if (file_exists($color_cssfile))
	{
		$style_css .= "\t".'<link rel="stylesheet" href="'.h($color_cssfile).'">'. "\n";
	}
	unset($lessfile);
}

// include texture css
if (isset($style_config['textures'][$style_texture]))
{
	$texture_cssfile = SKIN_DIR . $style_name . '/' . $style_config['textures'][$style_texture];
	$lessfile = SKIN_DIR . $style_name . '/less/' . basename($texture_cssfile, '.css') . '.less';
	if ($use_less && file_exists($lessfile))
	{
		$style_css .= "\t" . '<link href="'.h($lessfile). '" rel="stylesheet/less">' . "\n";
	
		$less_load = TRUE;
	
	}
	else if (file_exists($texture_cssfile))
	{
		$style_css .= "\t".'<link rel="stylesheet" href="'.h($texture_cssfile).'">'. "\n";
	}
	unset($lessfile);
}

// include custom css
$custom_cssfile = CSS_DIR . 'custom_style.css';
$custom_lessfile = CSS_DIR . 'custom_style.less';
if ($style_name !== $admin_style_name &&
   (file_exists($custom_cssfile) OR file_exists($custom_lessfile)))
{
	if ($use_less && file_exists($lessfile))
	{
		$style_css .= "\t" . '<link href="'.h($custom_lessfile).'" rel="stylesheet/less">' . "\n";
		$less_load = TRUE;
	}
	else
	{
		$style_css .= "\t" . '<link href="'.h($custom_cssfile).'" rel="stylesheet">' . "\n";
	}
}

// Less compiler and watcher
if ($less_load)
{
	$less_loader = "\t" . '<script>
	var less = {env: "development"};
	</script>
	<script type="text/javascript" src="'.JS_DIR.'less-1.4.1.min.js"></script>
	<script type="text/javascript">
	less.watch();
	</script>' . "\n";
	
	$qt->appendv('plugin_head', $less_loader);
}

unset($color_cssfile, $texture_cssfile, $user_cssfile, $less_load);

$qt->appendv('style_css', $style_css);

$qt->setv('style_name', $style_name);
$qt->setv('style_path', SKIN_DIR.$style_name.'/');

//favicon
if (file_exists('favicon.ico'))
{
	$qt->appendv('style_css', "\n\t".'<link rel="shortcut icon" href="favicon.ico">');
}
else if (file_exists(IMAGE_DIR . 'favicon.ico'))
{
	$qt->appendv('style_css', "\n\t".'<link rel="shortcut icon" href="'.IMAGE_DIR.'favicon.ico">');
}

//viewport
if ($qt->getv('custom_viewport'))
{
	$viewport = $qt->getv('custom_viewport');
}
//add base tag
$viewport_tag = sprintf('<meta name="viewport" content="%s">'."\n" , h($viewport));
$viewport_tag .= "\t" . '<base href="'. $script .'">' . "\n";
$qt->setv('viewport', $viewport_tag);


// 自分自身へのリンクを削除する
// ※ おかしな設定の共用SSLにも対応する
$ss = is_https() ? $script_ssl : $script;

$pgname = rawurlencode($title);
$search = array();
$replace = array();
$pairs = array();
preg_match_all('/<\s*a[^>]*>(.*?)<\s*\/a\s*>/',$body,$matches);
for ($i=0; $i< count($matches[0]); $i++) {
  if(preg_match('/'. str_replace('/','\/',$ss) .'\?'.$pgname.'"/',$matches[0][$i])){
  	$search = $matches[0][$i];
	$replace = $matches[1][$i];
	$pairs[$search] = $replace;
  }
}
$qt->setv('body', ($pairs==null) ? $body : strtr($body,$pairs));

//-------------------------------------------------
//
// !ナビ、ナビ２、メニュー部分の生成
//
//-------------------------------------------------


/////////////////////////////////////////////////
// ページが存在しなければ、空のファイルを作成する

if ( ! $app_start)
{
	foreach(array($site_nav, $menubar, $menubar2, $site_footer) as $the_page){
		if (in_array($the_page, $style_config['templates'][$template_name]['layouts'])
		 && ! is_page($the_page))
		 	touch(get_filename($the_page));
	}
}


$scripturi = preg_quote(get_page_url($vars['page']), '|');
if (in_array($site_nav, $style_config['templates'][$template_name]['layouts']))
{
	$vars['page_alt'] = $site_nav; //swfuの制御のため
	if (exist_plugin('nav'))
	{
		global $nav_style;

		$site_navigator = plugin_nav_create($nav_style);
		$qt->setv('site_navigator', $site_navigator);
	}
	unset($vars['page_alt']);
}
	
if (in_array($menubar, $style_config['templates'][$template_name]['layouts']))
{
	if (exist_plugin_convert('menu')) {
		 
		global $qblog_menubar;
		$vars['page_alt'] = $menubar;
		
		if (is_qblog())
		{
			do_plugin_convert('menu', $qblog_menubar);
		}
		
		$ptns = array(
			'|<ul class="(list1)"|',
			'|<ul class="(list2)"|',
			'|<li>(.+href="('.$scripturi.')".+)?</li>|',
		);

		$rpls = array(
			'<ul class="$1 nav"',
			'<ul class="$1 nav"',
			'<li class="active">$1</li>',
		);
		$_menubody = do_plugin_convert('menu');
		$_menubody = preg_replace($ptns, $rpls, $_menubody);
		
		unset($vars['page_alt']);
	
		if (!$qt->getv('MenuBarInsertMark')) {
			$_menubody = "\n<!-- MENUBAR CONTENTS START -->\n<div id=\"orgm_menu\">\n" . $_menubody . "\n</div>\n<!-- MENUBAR CONTENTS END -->\n";
			$qt->setv('MenuBarInsertMark', true);
		}
		
		$addclass = $addattr = '';
		//プレビューならクラスを付ける
		if ($vars['preview'] && $vars['page'] == $menubar)
		{
			$addclass = ' preview_highlight';
			$addattr = ' data-target="#orgm_menu"';
		}
	
		$menubar_tagstr = <<<EOD
	<!-- ■BEGIN id:menubar -->
	<div id="menubar" class="bar{$addclass}"{$addattr}>
	{$_menubody}
	</div>
	<!-- □END id:menubar -->
EOD;

		$qt->setv('menu', $menubar_tagstr);
	}
}

if (in_array($menubar2, $style_config['templates'][$template_name]['layouts']))
{
	if (exist_plugin_convert('menu2')) {
		$vars['page_alt'] = $menubar2;

		$ptn = '"'. get_page_url($vars['page']).'"';
		$ptn = '|<(h[2-4][^>]+)>(.+href="('.$scripturi.')".+)?</(h[2-4])>|';
		$_menubody = preg_replace($ptn, '<$1 class="focus">$2</$4>', do_plugin_convert('menu2'));

		unset($vars['page_alt']);

		if (!$qt->getv('MenuBar2InsertMark')) {
			$_menubody = "\n<!-- MENUBAR2 CONTENTS START -->\n<div id=\"orgm_menu2\">\n" . $_menubody . "\n</div>\n<!-- MENUBAR2 CONTENTS END -->\n";
			$qt->setv('MenuBar2InsertMark', true);
		}

		//プレビューならクラスを付ける
		$addclass = $addattr = '';
		if ($vars['preview'] && $vars['page'] == $menubar2)
		{
			$addclass = ' preview_highlight';
			$addattr = ' data-target="#orgm_menu"';
		}

		$menubar_tagstr = <<<EOD
<!-- ■BEGIN id:menubar -->
<div id="menubar2" class="bar{$addclass}"{$addattr}>
{$_menubody}
</div>
<!-- □END id:menubar -->
EOD;
		$qt->setv('menubar2_tag', $menubar_tagstr);
	}
}

if (exist_plugin('footer'))
{
    $footerpage = plugin_footer_page();

    if (in_array($footerpage, $style_config['templates'][$template_name]['layouts']))
    {
      $vars['page_alt'] = $footerpage;
      $footer = plugin_footer_create();
      $qt->setv('site_footer', $footer);
      unset($vars['page_alt']);
    }
}

//------------------------------------------------
// for Screen Reader
//------------------------------------------------
$qt->setv('sr_link', '<a href="#contents" class="sr-only">'.__('本文へ移動').'</a>');

//------------------------------------------------
// video autoload
//------------------------------------------------
if (exist_plugin('video'))
{
	plugin_video_set_autoload();
}

//-------------------------------------------------
// ogp タグを挿入
//-------------------------------------------------
if (exist_plugin('ogp') && ! is_login()) {
	plugin_ogp_set_template();
}


//-------------------------------------------------
// トラッキングコードを挿入
//-------------------------------------------------
if (is_login())
{
	$tracking_script = '';
}
else if ($ga_tracking_id !== '')
{
	$ga_tracking_script = <<< EOS

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '{$ga_tracking_id}']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

EOS;

	$tracking_script = $tracking_script . $ga_tracking_script;
}

$qt->setv('tracking_script', $tracking_script);

//-------------------------------------------------
// ページの公開、閉鎖
//-------------------------------------------------
$page_meta = $app['page.meta'];
if ($page_meta->get('close', 'public') !== 'public')
{
    $close = $page_meta->get('close');
	if ($close === 'closed')
	{
		if (exist_plugin("close"))
		{
			$body = plugin_close_page();
			if ($body !== FALSE)
			{
				$qt->setv("body", $body);
			}
		}
	}
	else if ($close === 'password')
	{
		if (exist_plugin("secret"))
		{
			$body = plugin_secret_auth();
			if ($body !== FALSE)
			{
				$qt->setv('body', $body);
			}
		}
	}
	else if ($close === 'redirect')
	{
		if (exist_plugin("redirect"))
		{
			plugin_redirect_page();
		}
	
	}
}

//-------------------------------------------------
// 通知
//-------------------------------------------------
if (exist_plugin('notify')) {
	do_plugin_convert('notify');
}


/* End of file qhm_init_main.php */
/* Location: ./lib/qhm_init_main.php */