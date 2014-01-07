<?php
//-------------------------------------------------
// QHM Initialization program for skin (output)
// This file is required lib/html.php
//
// QHMの編集モードで使う変数などを初期化、設定
// 最後に、ヘッダーの出力までを担当する
//

//---- Prohibit direct access
if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');


//---- set ini values for template engine
$qt->setv('site_title',   $site_title);
$qt->setv('head_tag',     $head_tag);

$qt->appendv('head_prefix', '');
$qt->appendv('user_head', $user_head);
$qt->appendv('plugin_head', '');
$qt->appendv('body_first', '');
$qt->appendv('body_last', '');
$qt->appendv('eyecatch', '');
$qt->appendv('admin_script', '');
$qt->appendv('plugin_script', '');
$qt->appendv('user_script', '');

$qt->setv('_page', $_page);
$qt->setv('_script', $script);
$common_script = '
<script type="text/javascript" src="'. JS_DIR .'origami.js"></script>
<script type="text/javascript" src="'. JS_DIR .'lodash.min.js"></script>
';
$qt->setv('common_script', $common_script);

$_go_url = $script . '?go=' . get_tiny_code($_page);
$qt->setv('go_url', $_go_url);

$qt->setv_once('rss_link', $_LINK['rss']);



//---- define global values for some plugin.

$page_encoding = $qt->getv('page_encoding');
define('TEMPLATE_ENCODE', $page_encoding ? $page_encoding : 'UTF-8');

// Editable mode preparation
$is_editor = check_editable($_page, FALSE, FALSE);
$qt->enable_cache = $is_editor ? FALSE : $qt->enable_cache;

//pluginでデザインが指定されている場合
if ($include_skin_file_path!='')
{
	$style_name = $include_skin_file_path;
}
$style_config = style_config_read();

// !デザインのプレビュー中は、変数を書き換える
if (isset($_SESSION['preview_skin']) && $vars['cmd'] === 'read')
{
	// extract: $style_name, $style_color, $style_texture
	extract($_SESSION['preview_skin']);
	$style_config = style_config_read($style_name);
}

// テンプレートの取得
$template_name = (isset($page_meta['template_name']) && $page_meta['template_name']) ? $page_meta['template_name'] : $style_config['default_template'];
$template_name = $qt->getv('template_name') ? $qt->getv('template_name') : $template_name;

if ( ! isset($style_config['templates'][$template_name])
  OR ! file_exists(SKIN_DIR . $style_name . '/' . $style_config['templates'][$template_name]['filename']))
{
	$template_name = $style_config['default_template'];
}

$qt->setv('admin_nav', '');


if ($is_editor OR ss_admin_check())
{
	//オプションをセットする
	$qt->setjsv('baseUrl', $script);
	$qt->setjsv('options', orgm_ini_read());
	$qt->setjsv('cmd', $vars['cmd']);
	$qt->setjsv('page', $vars['page']);
	$qt->setjsv('pageDigest', md5(get_source($_page, TRUE, TRUE)));
	
	
	// アイキャッチの移行
	if (exist_plugin('app_config_eyecatch'))
		plugin_app_config_eyecatch_set_body();
	

	if (exist_plugin('filer'))
		plugin_filer_set_iframe();
	
	//編集画面のみ必要
	if (in_array($vars['cmd'], array('edit', 'secedit')))
	{
		if (exist_plugin('former'))
			plugin_former_set_iframe();
		if (exist_plugin('gmap'))
			plugin_gmap_set_js();
	}
	
	$unload_confirm = isset($unload_confirm) ? $unload_confirm : 1;

	$qt->setjsv('unloadConfirm', $unload_confirm? TRUE: FALSE);
	$qt->setjsv('pluginCategories', get_plugin_list());
	
	$toolbuttons = get_qhm_toolbuttons();
	if ($vars['page'] !== $site_nav)
	{
		array_shift($toolbuttons);
	}
	$qt->setjsv('toolbuttons', $toolbuttons);
	
	//スキンリスト
	$skins = array();
	$dh = opendir(SKIN_DIR);
	while ($dir = readdir($dh))
	{
		if ($dir != '.' && $dir != '..' && is_dir(SKIN_DIR . $dir))
		{
			$skins[] = $dir;
		}
	}
	$qt->setjsv('designs', $skins);

	$js_dir = JS_DIR;
	$css_dir = CSS_DIR;
		
    $admin_script = <<< EOS
<script src="{$js_dir}jquery.exnote.js"></script>
<script src="{$js_dir}jquery.colorpalette.js"></script>
<script src="{$js_dir}jquery.qhm_plugin_helper.js"></script>
<script src="{$js_dir}qhm_plugins.js"></script>
<script src="{$js_dir}admin.js"></script>
<script src="{$js_dir}jquery.ui.widget.js"></script>
<script src="{$js_dir}upload/js/jquery.iframe-transport.js"></script>
<script src="{$js_dir}upload/js/jquery.fileupload.js"></script>
<script src="{$js_dir}upload/js/jquery.fileupload-fp.js"></script>
<script src="{$js_dir}upload/js/jquery.fileupload-ui.js"></script>
<script src="{$js_dir}bootstrap-slider.js"></script>

EOS;
	$admin_css = <<< EOS
	<link rel="stylesheet" href="{$css_dir}slider.css">
	<link rel="stylesheet" href="{$css_dir}admin.css">
EOS;
	$qt->appendv('admin_script', $admin_script);
	$qt->appendv('head_tag', $admin_css);

	// !admin_nav
	$tools = get_admin_tools($_page);

	// -----------------------------------
	// ! admin_nav のセット
	// ステータスによって、表示、非表示を切り替える
	// -----------------------------------
	

	// !デザインのプレビュー中は、関係するボタンを表示
	$prevdiv = '';
	if (isset($_SESSION['preview_skin']) && $vars['cmd'] === 'read')
	{
		unset($tools['editlink'], $tools['sitelink'], $tools['systemlink']);
	}
	else
	{
		unset($tools['applyskinlink'], $tools['changeskinlink'], $tools['previewcancellink']);
	}
	
	
	if ( ! $is_page OR PKWK_READONLY)
	{
		unset($tools['editlink'], $tools['sitelink'], $tools['systemlink']);

		if (isset($vars['refer']) && is_page($vars['refer']))
		{
			$tools['finishlink']['link'] = get_page_url($vars['refer']);
		}
	}
	else
	{
		unset($tools['finishlink']);
	}

/*
	if( ! ss_admin_check())
	{
		if (isset($tools['pagelink'])) unset($tools['pagelink']);
		if (isset($tools['sitelink'])) unset($tools['sitelink']);
		if (isset($tools['toollink'])) unset($tools['toollink']);
		if (isset($tools['configlink'])) unset($tools['configlink']);
		if (isset($tools['helplink'])) unset($tools['helplink']);
	}
	else {
		if (isset($tools['passwordlink'])) unset($tools['passwordlink']);
	}
*/
	
	if ( ! $is_update)
	{
//		unset($tools['userlink']['sub']['updatelink'], $tools['userlink']['sub']['divider']);
	}
	
/*
	if (is_qblog())
	{
		if (isset($tools['pagelink']['sub']['renamelink'])) unset($tools['pagelink']['sub']['renamelink']);
	}
	if ( ! is_page($qblog_defaultpage))
	{
		if (isset($tools['qbloglink'])) unset($tools['qbloglink']);
	}
*/
	
	// レイアウトページがなければレイアウトページの編集リンクを出さない
	foreach ($layout_pages as $k => $val)
	{
		if ( ! in_array($k, $style_config['templates'][$template_name]['layouts']))
		{
			unset($tools['editlink']['sub'][$k.'Link']);
		}
	}
	if (isset($style_config['templates'][$template_name]['elements']))
	{
		$subitems = array();
		foreach ($style_config['templates'][$template_name]['elements'] as $element)
		{
			$subitems[strtolower($element) . 'link'] = array(
				'name' => sprintf(__('%sの編集'), $element),
				'link' => $script . '?cmd=edit&page=' . rawurlencode($element),
			);
		}
		
		$tools['editlink']['sub'] = array_merge(array_slice($tools['editlink']['sub'], 0, 4), $subitems, array_slice($tools['editlink']['sub'], 4));
	}
	if ($_page === $defaultpage) {
		unset($tools['editlink']['sub']['deletelink']);
	}

	// ! 編集・プレビューにはボタンを表示
	if ($vars['cmd'] == 'edit' OR $vars['cmd'] == 'secedit')
	{
		unset($tools['editlink'], $tools['sitelink']);
		
		//モバイルでは表示しない
		unset($tools['systemlink']);
		
		$tools_buttons = '
			<div class="btn-toolbar">
				<div class="btn-group">
					<input type="button" value="更新" tabindex="5" data-name="publish" class="btn btn-primary navbar-btn" data-edit-type="write">';
		if ( ! $change_timestamp)
		{
			$tools_buttons .= '
					<button class="btn btn-primary navbar-btn dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
					<ul class="dropdown-menu">
						<li><a href="#" data-edit-type="write">タイムスタンプを変えずに更新</a></li>
					</ul>';
		}
		$return_page = (isset($vars['refer']) && is_page($vars['refer'])) ? $vars['refer'] : $vars['page'];
		$tools_buttons .= '
				</div>
				<div class="btn-group">
					<input type="button" value="プレビュー" tabindex="4" class="btn btn-info navbar-btn" data-edit-type="preview">
				</div>
				<div class="btn-group">
					<a href="'.h(get_page_url($return_page)).'" class="btn btn-default navbar-btn" tabindex="6" data-edit-type="cancel">キャンセル</a>
				</div>
			</div>
';

		if (isset($vars['preview']) && $vars['preview'])
		{
			$refer = isset($vars['refer']) ? $vars['refer'] : $_page;
			$digest = md5(get_source($_page, TRUE, TRUE));
			$template_name = isset($vars['template_name']) && $vars['template_name'] ? $vars['template_name'] : $style_config['default_template'];
			
			$refer_field = isset($vars['refer']) ? '<input type="hidden" name="refer" value="'.h($refer).'">' : '';
			$id_field = isset($vars['id']) ? '<input type="hidden" name="id" value="'.h($vars['id']).'">' : '';
			$org_field = isset($vars['original']) ? '<input type="hidden" name="original" value="'.h($vars['original']).'">' : '';
			$template_name_field = '<input type="hidden" name="template_name" value="'.h($template_name).'">';

			
			$tools_buttons = '
			<form id="edit_form_preview" action="'. h($script) .'" method="post" style="margin-bottom:0;display:block;float:left;">
				<div class="btn-toolbar">
					<input type="hidden" name="cmd" value="'.h($vars['cmd']).'">
					<input type="hidden" name="page" value="'.h($_page).'">
					'.$refer_field.'
					'.$id_field.'
					'.$org_field.'
					'.$template_name_field.'
					<input type="hidden" name="msg"  value="'.h($vars['msg']).'">
					<input type="hidden" name="digest" value="'.h($digest).'">
					<input type="hidden" name="notimestamp" value="">
					<div class="btn-group">
						<button type="submit" name="" id="re_edit_button" class="btn btn-info navbar-btn">'. __('再編集').'</button>
					</div>
					<div class="btn-group">
						<input type="submit" name="write" class="btn btn-primary navbar-btn" value="'.__('更新').'">
';
		if ( ! $change_timestamp)
		{
			$tools_buttons .= '
						<button type="button" class="btn btn-primary navbar-btn dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
						<ul class="dropdown-menu">
							<li><a href="#" id="notimestamp_preview">タイムスタンプを変えずに更新</a></li>
						</ul>
';
		}
		$tools_buttons .= '
					</div>
					<div class="btn-group">
						<button type="submit" name="cancel" class="btn btn-default navbar-btn">'. __('キャンセル').'</button>
					</div>
				</div>
			</form>
';
		}
	}


	// -----------------------------------
	// ! Admin Nav の作成
	// -----------------------------------	
	$app_name = '<a href="" class="navbar-brand">'.APP_NAME.'</a>';
	$tools_str = get_admin_tools_html($tools);

	$admin_nav = '
<div id="admin_nav" class="navbar navbar-inverse navbar-fixed-top">
	<div class="container">
			'.$tools_str.'
		<div id="toolbar_buttons" class="pull-right">
		'.(isset($tools_buttons) ? $tools_buttons : '').'
		</div>
	</div>
</div>
';

	$qt->appendv('admin_nav', $admin_nav);

}


//set page title (title tag of HTML)
if($is_read){
	$page_title = isset($page_meta['title']) ? h($page_meta['title']) : $title;
	$qt->setv_once('page_title', $page_title. $site_title_delim . $site_title);
}
else{ //編集時は、必ずシステム情報でタイトルを作る
	$qt->setv('page_title', $title. $site_title_delim . $site_title);
}

if ($title == $defaultpage){ //トップ用
	$qt->setv('page_title', isset($page_meta['title']) && $page_meta['title'] ? $page_meta['title'] : $site_title);
}


//set canonical url
if ($vars['cmd'] === 'read' && is_page($vars['page']))
{
	$canonical_url = get_page_url($vars['page']);
	$canonical_tag = <<< EOD

	<link rel="canonical" href="{$canonical_url}">
EOD;
	$qt->appendv('head_tag', $canonical_tag);
}

if ($noindex === -1)
{
	$noindex = FALSE;
}
else if (check_non_list($vars['page']))
{
	$noindex = TRUE;
}

//search engine spider control
$qt->setv('noindex', '');
if ($noindex || $nofollow || ! $is_read)  { 
	$noindexstr = '
	<meta name="robots" content="NOINDEX,NOFOLLOW">
	<meta name="googlebot" content="noindex,nofollow">
';
	$qt->appendv('head_tag', $noindexstr);
}

//license
if ($display_login > 0)
{
	if (is_login())
	{
		$app_sign = '<a href="'. h($script) .'" rel="nofollow" id="orgm_login" class="haik-brand">'.APP_NAME.'</a>';
	}
	else
	{
		$app_sign = '<a href="'. h($_LINK['login']) .'" rel="nofollow" id="orgm_login" class="haik-brand">'.APP_NAME.'</a>';
	}
	$qt->setv('license_tag',
			'<p>powered by '. $app_sign .'</p>');
}


//misc info setting
$summaryflag_start = '';
$summaryflag_end = '';
if( ($notes != '') ||  ($related != '') ){ 
 $summaryflag_start = '<div id="summary" class="container"><!-- ■BEGIN id:summary -->';
 $summaryflag_end = '</div><!-- □ END id:summary -->';
}

$notes_tag = '';
if ($notes != '') {
 $notes_tag = <<<EOD
<!-- ■BEGIN id:note -->
<div id="note">
$notes
</div>
<!-- □END id:note -->
EOD;
}

$related_tag = '';
if ($related != '') {
  $related_tag = <<<EOD
<!-- ■ BEGIN id:related -->
<div id="related">
{$related}
</div>
<!-- □ END id:related -->
EOD;
}

$summarystr = <<<EOD
<!-- summary start -->
{$summaryflag_start}
$notes_tag
$related_tag
$summaryflag_end
<!-- summary end -->
EOD;
$qt->setv('summary', $summarystr);


// ! Libraries Include
$qt->setv('jquery_script', '<script type="text/javascript" src="'.JS_DIR.'jquery.js"></script>
<script type="text/javascript" src="'.JS_DIR.'jquery.tmpl.min.js"></script>');

$bootstrap = CSS_DIR.'bootstrap.min.css';

if (isset($style_config['bootstrap']) && $style_config['bootstrap'])
{
	$bootstrap = $style_config['bootstrap']['core'] ? (SKIN_DIR . $style_name. '/' . $style_config['bootstrap']['core']) : FALSE;
}
$bootstrap = $bootstrap ? '<link rel="stylesheet" href="'. $bootstrap .'">' : '';

$qt->setv('bootstrap_css', $bootstrap.'
	<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
	  <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
');
$qt->setv('bootstrap_script', '
<script type="text/javascript" src="'.JS_DIR.'bootstrap.js"></script>
<script type="text/javascript" src="'.JS_DIR.'typeahead.js"></script>
<script type="text/javascript" src="'.JS_DIR.'extends.js"></script>
');

//-------------------------------------------------
// ログインをチェックし、ログアウトしてれば再ログインをさせるjavascriptの読み込み
//-------------------------------------------------
if (exist_plugin('check_login')) {
	plugin_check_login_set();
}

// ! page_meta plugin
if ($vars['cmd'] === 'read' && exist_plugin('page_meta'))
{
	plugin_page_meta_set_body();
}

// ! app_config_design plugin
if ($vars['cmd'] === 'read' && exist_plugin('app_config_design'))
{
	plugin_app_config_design_set_body();
}


// ! shortcut cheat sheat

if ((is_page($vars['page']) && check_editable($vars['page'], FALSE, FALSE)) && is_login())
{

	$qt->appendv('body_last', '
<div id="orgm_shortcut_cheatsheat" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="shortcut key list" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
		

			<div class="modal-body">
			
				<h4>全体 <small><strong>G</strong>を押してから次のキーを押します。</small></h4>
				<table class="table">
					<thead>
						<tr>
							<th>キー</th>
							<th>アクション</th>
							<th>キー</th>
							<th>アクション</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>G → C</code></td>
							<td>設定画面</td>
							<td><code>G → L</code></td>
							<td>ファイル一覧</td>
						</tr>
						<tr>
							<td><code>G → E</code></td>
							<td>ページの編集／再編集</td>
							<td><code>G → M</code></td>
							<td>ページの詳細設定</td>
						</tr>
						<tr>
							<td><code>G → F</code></td>
							<td>ファイル管理</td>
							<td><code>G → N</code></td>
							<td>ページの追加</td>
						</tr>
						<tr>
							<td><code>G → G</code></td>
							<td>Google を開く</td>
							<td><code>G → Q</code></td>
							<td>サイト内検索</td>
						</tr>
						<tr>
							<td><code>G → H</code></td>
							<td>トップページへ移動</td>
							<td><code>G → T</code></td>
							<td>ページの最上部へ移動</td>
						</tr>
						<tr>
							<td><code>G → I</code></td>
							<td>アイキャッチ編集</td>
							<td><code>?</code></td>
							<td>ショートカットヘルプを開く</td>
						</tr>
					</tbody>
				</table>
				
				<h4>編集時の入力欄 <small>文章入力中にいつでも使えます。</small></h4>
				<table class="table">
					<thead>
						<tr>
							<th>キー</th>
							<th>アクション</th>
							<th>キー</th>
							<th>アクション</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code><script>document.write((navigator.platform.indexOf("Win") != -1) ? "Ctrl" : "⌘")</script> + P</code></td>
							<td>プレビュー</td>
							<td><code><script>document.write((navigator.platform.indexOf("Win") != -1) ? "Ctrl" : "⌘")</script> + 1〜9</code></td>
							<td>プラグイン履歴を呼び出す</td>
						</tr>
						<tr>
							<td><code><script>document.write((navigator.platform.indexOf("Win") != -1) ? "Ctrl" : "⌘")</script> + S</code></td>
							<td>更新</td>
							<td><code>Esc</code></td>
							<td>フォーカスを外す</td>
						</tr>
					</tbody>
				</table>	
			</div>
		
		</div>
	</div>
</div>
');
}

if (is_qblog())
{
	$qt->setv('page_title', $qblog_title.' - '.$site_title);
}

if ($app_err && exist_plugin('error_report'))
{

	plugin_error_report_set();

}

if (exist_plugin('loading_indicator'))
{
	plugin_loading_indicator_set();
}

if (exist_plugin('intro'))
{
	plugin_intro_set();
}

/* End of file qhm_init.php */
/* Location: ./lib/qhm_init.php */
