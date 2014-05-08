<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: html.php,v 1.57 2006/04/15 17:33:35 teanan Exp $
// Copyright (C)
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// HTML-publishing related functions

// Show page-content
function catbody($title, $page, $body)
{
    global $app;
    global $script, $script_ssl, $vars, $arg, $defaultpage, $whatsnew, $hr;
    global $related_link, $cantedit, $function_freeze, $app_err;
    global $search_word_color, $foot_explain, $note_hr, $head_tags;
    global $nofollow;
    global $_LINK;
    
    global $pkwk_dtd;     // XHTML 1.1, XHTML1.0, HTML 4.01 Transitional...
    global $site_title, $site_title_delim;   // Title of this site
    global $do_backup;    // Do backup or not
    global $style_name, $logo_title, $logo_image, $logo_header, $admin_style_name;
    global $noindex, $accesstag_moved; //for skin by hokuken
    global $display_login;	// Site administration menu 20 JUN 2007
    global $adcode;			// AD code (exp. Google Adwords, Analytics ... )  25 JLY 2007 by hokuken.com
    global $include_skin_file_path; //orignal skin setting
    global $unload_confirm, $check_login;
    global $other_plugins, $other_plugin_categories;
    global $default_script, $init_scripts;
    global $is_update;
    global $layout_pages;
    global $qblog_defaultpage, $qblog_menubar, $qblog_title;
    global $shiftjis; //Shift-JIS converter
    global $eucjp; //EUC-JP converter
    global $non_list, $whatsnew, $change_timestamp;
    global $template_name, $viewport;
    global $user_head, $ga_tracking_id, $tracking_script;
    global $style_color, $style_texture, $style_custom_bg, $use_less, $app_start; // style color
    global $site_nav, $menubar, $menubar2, $site_footer;
    global $is_plugin_page;


	// body部分以外は、元々の$script を使う（通常のリンク設定）を使う
	// 結果、$body内は、script_sslを使ったリンクになるが、ナビ、メニューなどは、元の$scriptを必ず使う
	$script = $init_scripts['normal'];
	$script_ssl = $init_scripts['ssl'];

	if (! file_exists(SKIN_FILE) || ! is_readable(SKIN_FILE))
		die_message('SKIN_FILE is not found');

	$_page  = isset($vars['page']) ? $vars['page'] : '';
	$r_page = rawurlencode($_page);

	//QHM Template
	$qt = get_qt();
	if (!$qt->set_page) {
		$qt->set_page($_page);
	}
	
	//QHM Messages
	$qm = get_qm();
	
	// Set $_LINK for skin
	$_LINK = array();
	$_LINK['add']      = "$script?cmd=add&page=$r_page";
	$_LINK['backup']   = "$script?cmd=backup&page=$r_page";
	$_LINK['copy']     = "$script?plugin=copy&refer=$r_page";
	$_LINK['diff']     = "$script?cmd=diff&page=$r_page";
	$_LINK['edit']     = "$script?cmd=edit&page=$r_page";
	$_LINK['filelist'] = "$script?cmd=filelist&refer=$r_page";
	$_LINK['freeze']   = "$script?cmd=freeze&page=$r_page";
	$_LINK['help']     = get_page_url('Help');
	$_LINK['list']     = "$script?cmd=list";
	$_LINK['new']      = "$script?plugin=newpage&refer=$r_page";
	$_LINK['rdf']      = "$script?cmd=rss&ver=1.0";
	$_LINK['recent']   = get_page_url($whatsnew);
	$_LINK['refer']    = "$script?plugin=referer&page=$r_page";
	$_LINK['reload']   = get_page_url($_page);
	$_LINK['rename']   = "$script?plugin=rename&refer=$r_page";
	$_LINK['delete']   = "$script?cmd=delete&page=$r_page";
	$_LINK['rss']      = "$script?cmd=rss";
	$_LINK['rss10']    = "$script?cmd=rss&ver=1.0"; // Same as 'rdf'
	$_LINK['rss20']    = "$script?cmd=rss&ver=2.0";
	$_LINK['search']   = "$script?cmd=search";
	$_LINK['top']      = $script. '/';
	
	$_LINK['apply_preview_skin']  = "$script?cmd=app_config_design&phase=apply_preview_design&refer=$r_page";
	$_LINK['cancel_preview_skin'] = "$script?cmd=app_config_design&phase=cancel_preview_design&refer=$_page";
	$_LINK['preview_skin_edit'] = "$script?cmd=app_config_design#style_name";

	$_LINK['unfreeze']  = "$script?cmd=unfreeze&page=$r_page";
	$_LINK['login']     = $script."?cmd=login&refer=$r_page";
	$_LINK['logout']    = "$script?cmd=logout";
	$_LINK['config']    = "$script?cmd=app_config";
	$_LINK['edit_menu'] = "$script?cmd=edit&page=MenuBar&refer=$r_page";
	$_LINK['edit_menu2']= "$script?cmd=edit&page=MenuBar2&refer=$r_page";
	$_LINK['edit_nav'] = "$script?cmd=edit&page=SiteNavigator&refer=$r_page";
	$_LINK['edit_footer']= "$script?cmd=edit&page=SiteFooter&refer=$r_page";
	$_LINK['yetlist']   = "$script?cmd=yetlist";
	$_LINK['filer']     = "$script?cmd=filer&refer=$r_page";
	$_LINK['former']     = "$script?cmd=former&refer=$r_page";

	$_LINK['map']       = $script.'?cmd=map&refer='.$r_page;
	$_LINK['password']  = $script.'?plugin=qhmsetting&phase=user2&mode=form';
	$_LINK['system_update'] = $script.'?plugin=app_update';
	$_LINK['skin_update'] = $script.'?plugin=design_wand';
	$_LINK['help_site'] = APP_MANUAL_SITE;
	
	if (is_login()) $qt->setjsv('links', $_LINK);

	// ページ情報の読込み
    $page_meta = $app['page.meta'];

	// Init flags
	$is_page = (is_pagename($_page) && $_page != $whatsnew);

	$is_read = (arg_check('read') && is_page($_page));
	$has_temp_skin = (isset($_SESSION['temp_skin']) && strlen($_SESSION['temp_skin']) > 0);
	$is_update =  isset($_COOKIE['APP_VERSION']) && $_COOKIE['APP_VERSION'] > APP_VERSION;

	// Last modification date (string) of the page
	$lastmodified = $is_read ?  format_date(get_filetime($_page)) .
		' ' . get_pg_passage($_page, FALSE) : '';

	// List of related pages
	$related  = ($related_link && $is_read) ? make_related($_page) : '';

	// List of footnotes
	ksort($foot_explain, SORT_NUMERIC);
	$notes = ! empty($foot_explain) ? $note_hr . join("\n", $foot_explain) : '';

	// Tags will be inserted into <head></head>
	$head_tag = ! empty($head_tags) ? join("\n", $head_tags) ."\n" : '';

	// Search words
	if ($search_word_color && isset($vars['word'])) {
		$body = '<div class="small">' . __('これらのキーワードがハイライトされています：') . h($vars['word']) .
			'</div>' . $hr . "\n" . $body;

		// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
		// with array_splice(), array_flip()
		$words = preg_split('/\s+/', $vars['word'], -1, PREG_SPLIT_NO_EMPTY);
		$words = array_splice($words, 0, 10); // Max: 10 words
		$words = array_flip($words);

		$keys = array();
		foreach ($words as $word=>$id) $keys[$word] = strlen($word);
		arsort($keys, SORT_NUMERIC);
		$keys = get_search_words(array_keys($keys), TRUE);
		$id = 0;
		foreach ($keys as $key=>$pattern) {
			$s_key    = h($key);
			$pattern  = '/' .
				'<textarea[^>]*>.*?<\/textarea>' .	// Ignore textareas
				'|' . '<[^>]*>' .			// Ignore tags
				'|' . '&[^;]+;' .			// Ignore entities
				'|' . '(' . $pattern . ')' .		// $matches[1]: Regex for a search word
				'/sS';
			$decorate_Nth_word = create_function(
				'$matches',
				'return (isset($matches[1])) ? ' .
					'\'<strong class="word' .
						$id .
					'">\' . $matches[1] . \'</strong>\' : ' .
					'$matches[0];'
			);
			$body  = preg_replace_callback($pattern, $decorate_Nth_word, $body);
			$notes = preg_replace_callback($pattern, $decorate_Nth_word, $notes);
			++$id;
		}
	}

	

	//----------------- 携帯の場合の処理 --------------------------------------
	if( preg_match('/keitai.skin.php$/', SKIN_FILE) ){
		require(LIB_DIR.'haik_admin_init.php');
		require(LIB_DIR.'haik_init.php');
		require(SKIN_FILE);
		return;
	}
	//------------------- IF UA is mobile, end here -----------------------

	///////////////////////////////////////////////////////////////////
	//
	// Main
	//
	
	//common setting
	require(LIB_DIR . 'haik_admin_init.php');

	// encoding
	$output_encode = CONTENT_CHARSET;
	if (TEMPLATE_ENCODE != CONTENT_CHARSET)
	{
		$qt->set_encode(true);
		$output_encode = TEMPLATE_ENCODE;
	}

	//output common header (available change encode)
	$qt->setv('meta_content_type', qhm_output_dtd($pkwk_dtd, CONTENT_CHARSET, $output_encode));

	
	//pluginでデザインが指定されている場合
	if ($include_skin_file_path!='')
	{
		$style_name = $include_skin_file_path;
	}
	$style_config = style_config_read();

	require(LIB_DIR.'haik_init.php');

	
	//独自のテンプレートファイルをチェック
	$skin_file = SKIN_DIR.'origami.skin.php';
	if (isset($style_config['templates'][$template_name]))
	{
		$skin_file = SKIN_DIR."{$style_name}/".$style_config['templates'][$template_name]['filename'];
	}

	$longtaketime = getmicrotime() - MUTIME;
	$taketime = sprintf('%01.03f', $longtaketime);
	$qt->setv('taketime', $taketime);


	//-------------------------------------------------------------------
	// 	プレビュー用のskinファイルを表示
	$tmpfilename = '';
	if ($has_temp_skin)
	{
		$tmpfilename = $skin_file = tempnam(realpath(CACHE_DIR), 'qhmdesign');
		file_put_contents($skin_file, $_SESSION['temp_skin']);
		$qt->setv('default_css', $_SESSION['temp_css']);
		$qt->setv('style_path', $_SESSION['temp_style_path']);
	}
	//-------------------------------------------------------------------

	//skinファイルを読み込んで、表示
	$qt->read($skin_file, $_page);

	// 一時ファイルの削除
	if (file_exists($tmpfilename) && strpos(basename($tmpfilename), 'qhmdesign') === 0)
	{
		unlink($tmpfilename);
	}
}

// Show 'edit' form
function edit_form($page, $postdata, $digest = FALSE)
{
    global $app;
	global $script, $vars, $defaultpage, $rows, $cols, $hr, $function_freeze;
	global $whatsnew;
	global $notimeupdate;
	global $qblog_defaultpage, $style_name, $date_format, $qblog_default_cat;
	global $layout_pages, $config;

	$page_meta = $app['page.meta'];

	$qt = get_qt();
	
	// Newly generate $digest or not
	if ($digest === FALSE) 
	{
		$digest = md5(join('', get_source($page)));
	}

 	// Add plugin
	$addtag = $add_top = '';
	if(isset($vars['add'])) {
		$addtag  = '<input type="hidden" name="add" value="true">';
		$add_top = isset($vars['add_top']) ? ' checked="checked"' : '';
		$add_top = '<input type="checkbox" name="add_top" ' .
			'id="_edit_form_add_top" value="true"' . $add_top . '>' . "\n" .
			'  <label for="_edit_form_add_top">' .
				'<span class="small">' . __('ページの上に追加') . '</span>' .
			'</label>';
	}

	//新規作成の場合、ページ名を大見出しとして挿入する
	$refer = (isset($vars['refer']) && $vars['refer'] != '') ? $vars['refer'] : $page;
	$template_name = isset($vars['template_name']) ? $vars['template_name'] : $page_meta->get('template_name', '');
	$style_config = style_config_read($config['style_name']);

	if ( ! isset($style_config['templates'][$template_name])
	  OR ! file_exists(SKIN_DIR . $config['style_name'] . '/' . $style_config['templates'][$template_name]['filename']))
	{
		$template_name = $style_config['default_template'];
	}
	
	$r_page      = rawurlencode($page);
	$s_page      = h($page);
	$s_refer      = h($refer);
	$s_digest    = h($digest);
	$s_postdata  = h($postdata);
	$s_original  = isset($vars['original']) ? h($vars['original']) : $s_postdata;
	$b_preview   = isset($vars['preview']); // TRUE when preview
	$btn_preview = $b_preview ? __('再プレビュー') : __('プレビュー');
	$s_template_name = h($template_name);

	// Checkbox 'do not change timestamp'
	$add_notimestamp = '';
	if ($notimeupdate != 0) {
		$checked_time = isset($vars['notimestamp']) ? ' checked="checked"' : '';
		// Only for administrator
		if ($notimeupdate == 2) {
			$add_notimestamp = '   ' .
				'<input type="password" name="pass" size="12">' . "\n";
		}
		$add_notimestamp = '<label for="_edit_form_notimestamp"><input type="checkbox" name="notimestamp" ' .
			'id="_edit_form_notimestamp" value="true"' . $checked_time . ' tabindex="9">' . "\n" .
			'   ' . '<span class="small">' .
			__('タイムスタンプを変更しない') . '</span></label>' . "\n" .
			$add_notimestamp .
			'&nbsp;';
	}
	
	// !ブログ用編集フォーム
	if ($page !== $qblog_defaultpage && is_qblog())
	{
		//メタデータを取得
		$data = get_qblog_post_data($page);
		$data['title'] = isset($vars['title']) ? $vars['title'] : $data['title'];
		$data['category'] = isset($vars['category']) ? $vars['category'] : $data['category'];
		$data['image'] = isset($vars['image']) ? $vars['image'] : $data['image'];

		$date = get_qblog_date($date_format, $page);
		if (isset($vars['qblog_date']) && $date !== trim($vars['qblog_date']))
		{
			$dates = array_pad(explode('-', $vars['qblog_date'], 3), 3, 0);
			$valid = checkdate($dates[1], $dates[2], $dates[0]);
			$date = $valid ? trim($vars['qblog_date']) : $date;
		}
		
		$category = (isset($data['category']) && strlen(trim($data['category'])) > 0) ? $data['category'] : '';
		$qblog_categories = array_keys(get_qblog_categories());
		$qblog_cat_json = json_encode($qblog_categories);
		$h_qblog_cat_json = h(json_encode($qblog_categories));
		$qblog_cat_list = '<ul class="qblog_categories dropdown-menu">';
		foreach ($qblog_categories as $cat)
		{
			$qblog_cat_list .= '<li>' . h($cat) . '</li>';
		}
		$qblog_cat_list .= '</ul>';
		
		$h2title = '新規投稿';
		if (is_page($page))
		{
			$h2title = $data['title'].'の編集';
		}
		
		$js_dir = JS_DIR;
		$plugin_dir = PLUGIN_DIR;
		
		$body = <<< EOD
<script src="{$js_dir}datepicker/js/bootstrap-datepicker.js"></script>
<link rel="stylesheet" href="{$js_dir}datepicker/css/datepicker.css">
<link rel="stylesheet" href="{$plugin_dir}qblog/qblog.css">
<style type="text/css">
.form-horizontal .control-label {width: 100px;}
.form-horizontal .controls {margin-left: 120px;}
.form-horizontal .form-actions {padding-left: 120px;}
#body .qblog_categories {margin: 0;padding:4px;}
.qblog_edit_form ul.qblog_categories li {float:left;margin:2px;}
.qblog_edit_form ul.online_help {display:none;}
#content h2.title{text-align: left;font-size: 12px;line-height: 1.5em;margin: 12px;border-bottom: 1px solid #CCC;padding: 2px;color:#333;}
.qblog_categories {position: relative;top: 0;left: 0;border:none;box-shadow:none;}
.qblog_edit_form div.set-thumbnail{display:none;
border:1px solid #ccc;border-radius:3px;padding:10px;color:#666;
}
ul.typeahead.dropdown-menu {text-align:left}
.qblog_edit_form input:-moz-placeholder {color: #999}
.qblog_edit_form input::-webkit-input-placeholder {line-height:30px;}
</style>

<script tyle="text/javascript">
$(function(){
	$('#qblog_datepicker').datepicker({
		language: "japanese"
//		format: "yyyy/mm/dd"
	});
	if ($("input[name=category]").val().length == 0) {
		$('#qblog_cat_trigger').click();
	}
	
	$('h2.title').text('{$h2title}');
	
	$('a.show-thumbnail').click(function(){
		if ($(this).next().is(':visible')) {
			$(this).next().hide();
		}
		else {
			$(this).next().show();
		}
		return false;
	});
	
});
</script>

<div class="qblog_edit_form">
<form action="$script" method="post" class="form-horizontal" id="edit_form_main">
  $addtag
  <input type="hidden" name="cmd"    value="edit">
  <input type="hidden" name="page"   value="{$s_page}">
  <input type="hidden" name="digest" value="{$s_digest}">
  <fieldset>
  	<div class="control-group">
  		<label class="control-label">日付</label>
		<div class="controls"><input type="text" name="qblog_date" id="qblog_datepicker" tabindex="1" class="datepicker" size="16" value="{$date}"  data-date="{$date}"  data-date-format="yyyy-mm-dd"></div>
  	</div>
  	<div class="control-group">
  		<label class="control-label">タイトル</label>
  		<div class="controls"><input type="text" name="title" value="{$data['title']}" tabindex="2"></div>
  	</div>
  	<div class="control-group">
  		<label class="control-label">カテゴリ</label>
  		<div class="controls">
  			<input type="text" name="category" value="{$category}" placeholder="{$qblog_default_cat}" tabindex="3" class="input-xlarge" data-provide="typeahead" data-source="{$h_qblog_cat_json}" autocomplete="off">
			<a id ="qblog_cat_trigger" class="btn dropdown-toggle" data-toggle="dropdown" href="#" style="color:#333">
			    カテゴリ
			    <span class="caret"></span>
			</a>
			{$qblog_cat_list}
		</div>
  	</div>
  	<div class="control-group">
  		<label class="control-label">記事の内容</label>
  		<div class="controls">
  			<textarea name="msg" id="msg" rows="$rows" cols="$cols" tabindex="4">$s_postdata</textarea>
  		</div>
  	</div>
  	<div class="control-group">
  		<div class="controls">
	  		<a class="show-thumbnail" href="#">サムネイルを指定する &gt;&gt;</a>
  			<div class="set-thumbnail">
  				<small>自動で本文の画像が使われます。<br>特別に指定したい場合、画像を画像名またはURLで指定してください。</small>
  				<p style="color:#333;">画像名またはURL：<input type="text" name="image" value="{$data['image']}" tabindex="5"></p>
  				<p><small><span class="swfu"><a href="swfu/index_child.php">&gt;&gt;QHMのファイル管理（SWFU）を使って画像をアップする</a></span></small></p>
  			</div>
  		</div>
  	</div>
  	<div class="form-actions">
  		<input type="submit" name="preview" value="$btn_preview" tabindex="6" class="btn btn-primary">
  		<input type="submit" name="write"   value="ページの更新" tabindex="7" class="btn btn-primary">
  		<input type="submit" name="cancel" value="キャンセル" tabindex="8" class="btn btn-default">
  		$add_top
  		$add_notimestamp
  		<textarea name="original" rows="1" cols="1" style="display:none">$s_original</textarea>
  	</div>
  </fieldset>
</form>
</div>

EOD;

		$body .= <<<EOD
<script src="{$js_dir}datepicker/js/bootstrap-datepicker.js"></script>
<link rel="stylesheet" href="{$js_dir}datepicker/css/datepicker.css">
EOD;

	}
	// !標準編集フォーム
	else
	{
		$class = '';
		if ($page === $defaultpage OR array_key_exists($page, $layout_pages))
		{
			$page_title = '';
			$class = 'hide';
		}
		$page_title = $page_meta->get('title', $page);
		$page_meta_yaml = $page_meta->toYaml();
		$manual_link = manual_link('StartGuide', '', '<a href="%s" id="haik_edit_manual_link" class="btn btn-default btn-sm" target="_blank">?</a>');
		
		$body = '
<div class="row">
	<div class="edit_form col-sm-offset-1 col-sm-10 col-xs-12">
	<form action="'.$script.'" method="post" style="margin-bottom:0px;" id="edit_form_main">
		'.$addtag.'
		<input type="hidden" name="cmd"    value="edit">
		<input type="hidden" name="page"   value="'.$s_page.'">
		<input type="hidden" name="digest" value="'.$s_digest.'">
		<input type="hidden" name="refer" value="'.$s_refer.'">
		<input type="hidden" name="template_name" value="'.$s_template_name.'">
		
		<input type="text" name="title" value="'.h($page_title).'" placeholder="ページタイトル" class="col-sm-12 '.$class.'" tabindex="1">
		<textarea name="page_meta" rows="5" cols="'.$cols.'" placeholder="YAML で設定を記述してください" tabindex="3" class="col-sm-12">'.h($page_meta_yaml).'</textarea>
		
		<div class="btn-toolbar pull-right">
			<div class="btn-group">
				'.$manual_link.'
			</div>
		</div>
		<div id="orgm_toolbox"></div>
		
		<textarea name="msg" id="msg" rows="'.$rows.'" cols="'.$cols.'" placeholder="クリックして文章を入力してください。" tabindex="2" data-exnote="onready" class="col-sm-12">'.$s_postdata.'</textarea>
		<br>
		<div class="edit_buttons" style="float:left;">
		<input type="submit" name="preview" value="プレビュー" tabindex="4" class="btn btn-info">
		<input type="submit" name="write"   value="公開" tabindex="5" class="btn btn-primary">
		<input type="submit" name="cancel" value="破棄" tabindex="6" class="btn btn-default">
		'.$add_top.'
		'.$add_notimestamp.'
		</div>
		<textarea name="original" rows="1" cols="1" style="display:none">'.$s_original.'</textarea>
	</form>
	</div>
</div>
';
	
	}
	


	$qm = get_qm();
	$helpstr = $qm->m['html']['view_help_message'];

	return $body;
}


// Related pages
function make_related($page, $tag = '')
{
	global $script, $vars, $rule_related_str, $related_str;
	global $_ul_left_margin, $_ul_margin, $_list_pad_str;

	$links = links_get_related($page);

	if ($tag) {
		ksort($links);
	} else {
		arsort($links);
	}

	$_links = array();
	foreach ($links as $page=>$lastmod) {
		if (check_non_list($page)) continue;

		$r_page   = rawurlencode($page);

		$s_page = h(get_page_title($page));
		$passage  = get_passage($lastmod);
		$_links[] = $tag ?
			'<a href="' . get_page_url($page) . '" title="' .
			$s_page . ' ' . $passage . '">' . $s_page . '</a>' :
			'<a href="' . get_page_url($page) . '">' .
			$s_page . '</a>' . $passage;
	}
	if (empty($_links)) return ''; // Nothing

	if ($tag == 'p') { // From the line-head
		$margin = $_ul_left_margin + $_ul_margin;
		$style  = sprintf($_list_pad_str, 1, $margin, $margin);
		$retval =  "\n" . '<ul' . $style . '>' . "\n" .
			'<li>' . join($rule_related_str, $_links) . '</li>' . "\n" .
			'</ul>' . "\n";
	} else if ($tag) {
		$retval = join($rule_related_str, $_links);
	} else {
		$retval = join($related_str, $_links);
	}

	return $retval;
}

// User-defined rules (convert without replacing source)
function make_line_rules($str)
{
	global $line_rules;
	static $pattern, $replace;

	if (! isset($pattern)) {
		$pattern = array_map(create_function('$a',
			'return \'/\' . $a . \'/\';'), array_keys($line_rules));
		$replace = array_values($line_rules);
		unset($line_rules);
	}

	return preg_replace($pattern, $replace, $str);
}

// Remove all HTML tags(or just anchor tags), and WikiName-speific decorations
function strip_htmltag($str, $all = TRUE)
{
	static $noexists_pattern;

	if (! isset($noexists_pattern))
		$noexists_pattern = '#<span class="noexists">([^<]*)<a[^>]+>' .
			preg_quote('?', '#') . '</a></span>#';

	// Strip Dagnling-Link decoration (Tags and "?")
	$str = preg_replace($noexists_pattern, '$1', $str);

	if ($all) {
		// All other HTML tags
		return preg_replace('#<[^>]+>#',        '', $str);
	} else {
		// All other anchor-tags only
		return preg_replace('#<a[^>]+>|</a>#i', '', $str);
	}
}

// Remove AutoLink marker with AutLink itself
function strip_autolink($str)
{
	return preg_replace('#<!--autolink--><a [^>]+>|</a><!--/autolink-->#', '', $str);
}

// Make a backlink. searching-link of the page name, by the page name, for the page name
function make_search($page)
{
	global $script;

	$s_page = h($page);
	$r_page = rawurlencode($page);

	return '<a href="' . $script . '?plugin=related&amp;page=' . $r_page .
		'">' . $s_page . '</a> ';
}

// Make heading string (remove heading-related decorations from Wiki text)
function make_heading(& $str, $strip = TRUE)
{
	global $NotePattern;

	// Cut fixed-heading anchors
	$id = '';
	$matches = array();
	if (preg_match('/^(\*{0,3})(.*?)\[#([A-Za-z][\w-]+)\](.*?)$/m', $str, $matches)) {
		$str = $matches[2] . $matches[4];
		$id  = & $matches[3];
	} else {
		$str = preg_replace('/^\*{0,3}/', '', $str);
	}

	// Cut footnotes and tags
	if ($strip === TRUE)
		$str = strip_htmltag(make_link(preg_replace($NotePattern, '', $str)));

	return $id;
}

// Separate a page-name(or URL or null string) and an anchor
// (last one standing) without sharp
function anchor_explode($page, $strict_editable = FALSE)
{
	$pos = strrpos($page, '#');
	if ($pos === FALSE) return array($page, '', FALSE);

	// Ignore the last sharp letter
	if ($pos + 1 == strlen($page)) {
		$pos = strpos(substr($page, $pos + 1), '#');
		if ($pos === FALSE) return array($page, '', FALSE);
	}

	$s_page = substr($page, 0, $pos);
	$anchor = substr($page, $pos + 1);

	if($strict_editable === TRUE &&  preg_match('/^[a-z][a-f0-9]{7}$/', $anchor)) {
		return array ($s_page, $anchor, TRUE); // Seems fixed-anchor
	} else {
		return array ($s_page, $anchor, FALSE);
	}
}

// Check HTTP header()s were sent already, or
// there're blank lines or something out of php blocks
function pkwk_headers_sent()
{
	if (PKWK_OPTIMISE) return;

	$file = $line = '';
	if (version_compare(PHP_VERSION, '4.3.0', '>=')) {
		if (headers_sent($file, $line))
		    die('Headers already sent at ' .
		    	h($file) .
			' line ' . $line . '.');
	} else {
		if (headers_sent())
			die('Headers already sent.');
	}
}

// Output common HTTP headers
function pkwk_common_headers()
{
	if (! PKWK_OPTIMISE) pkwk_headers_sent();

	if(defined('PKWK_ZLIB_LOADABLE_MODULE')) {
		$matches = array();
		if(ini_get('zlib.output_compression') &&
		    preg_match('/\b(gzip|deflate)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) {
		    	// Bug #29350 output_compression compresses everything _without header_ as loadable module
		    	// http://bugs.php.net/bug.php?id=29350
			header('Content-Encoding: ' . $matches[1]);
			header('Vary: Accept-Encoding');
		}
	}
}

// DTD definitions
define('PKWK_DTD_HTML_5',                 21);
define('PKWK_DTD_XHTML_1_1',              17); // Strict only
define('PKWK_DTD_XHTML_1_0',              16); // Strict
define('PKWK_DTD_XHTML_1_0_STRICT',       16);
define('PKWK_DTD_XHTML_1_0_TRANSITIONAL', 15);
define('PKWK_DTD_XHTML_1_0_FRAMESET',     14);
define('PKWK_DTD_HTML_4_01',               3); // Strict
define('PKWK_DTD_HTML_4_01_STRICT',        3);
define('PKWK_DTD_HTML_4_01_TRANSITIONAL',  2);
define('PKWK_DTD_HTML_4_01_FRAMESET',      1);

define('PKWK_DTD_TYPE_XHTML',  1);
define('PKWK_DTD_TYPE_HTML',   0);

// Output HTML DTD, <html> start tag. Return content-type.
function pkwk_output_dtd($pkwk_dtd = PKWK_DTD_XHTML_1_1, $charset = CONTENT_CHARSET)
{
	global $ogp_tag, $add_xmlns;
	static $called;
	if (isset($called)) die('pkwk_output_dtd() already called. Why?');
	$called = TRUE;

	$type = PKWK_DTD_TYPE_XHTML;
	$option = '';
	$html5 = FALSE;
	switch($pkwk_dtd){
	case PKWK_DTD_HTML_5:
		$type = PKWK_DTD_TYPE_HTML;
		$html5 = TRUE;
		break;
	case PKWK_DTD_XHTML_1_1             :
		$version = '1.1' ;
		$dtd     = 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd';
		break;
	case PKWK_DTD_XHTML_1_0_STRICT      :
		$version = '1.0' ;
		$option  = 'Strict';
		$dtd     = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd';
		break;
	case PKWK_DTD_XHTML_1_0_TRANSITIONAL:
		$version = '1.0' ;
		$option  = 'Transitional';
		$dtd     = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd';
		break;

	case PKWK_DTD_HTML_4_01_STRICT      :
		$type    = PKWK_DTD_TYPE_HTML;
		$version = '4.01';
		$dtd     = 'http://www.w3.org/TR/html4/strict.dtd';
		break;
	case PKWK_DTD_HTML_4_01_TRANSITIONAL:
		$type    = PKWK_DTD_TYPE_HTML;
		$version = '4.01';
		$option  = 'Transitional';
		$dtd     = 'http://www.w3.org/TR/html4/loose.dtd';
		break;

	default: die('DTD not specified or invalid DTD');
		break;
	}

	$charset = h($charset);

	// Output XML or not   --- edit by hokuken for some javascripts on IE6 & IE7 ---
	/*if ($type == PKWK_DTD_TYPE_XHTML) echo '<?xml version="1.0" encoding="' . $charset . '" ?>' . "\n";*/

	// Output doctype
	if ($pkwk_dtd == PKWK_DTD_HTML_5)
	{
		echo '<!DOCTYPE html>' . "\n";
	}
	else {
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD ' .
			($type == PKWK_DTD_TYPE_XHTML ? 'XHTML' : 'HTML') . ' ' .
			$version .
			($option != '' ? ' ' . $option : '') .
			'//EN" "' .
			$dtd .
			'">' . "\n";
	}

	// Output <html> start tag
	echo '<html';
	if ($type == PKWK_DTD_TYPE_XHTML)
	{
		echo ' xmlns="http://www.w3.org/1999/xhtml"'; // dir="ltr" /* LeftToRight */
		echo ' xml:lang="' . LANG . '"';
		if ($ogp_tag === 1)
		{
			echo ' xmlns:og="http://ogp.me/ns#"';
		}
		//Internet Explorer に必要なxmlns を吐き出す
		$fb_xmlns = 'xmlns:fb="http://ogp.me/ns/fb#"';
		if (strtoupper(UA_NAME) === 'MSIE' && ( ! isset($add_xmlns) OR stripos($add_xmlns, $fb_xmlns) === FALSE))
		{
			echo ' ', $fb_xmlns;
		}
		if (isset($add_xmlns))
		{
			echo $add_xmlns;
		}
		if ($version == '1.0') echo ' lang="' . LANG . '"'; // Only XHTML 1.0
	}
	else
	{
		echo ' lang="' . LANG . '"'; // HTML
	}
	echo '>' . "\n"; // <html>

	if ($html5)
	{
		return '<meta charset="UTF-8">' . "\n";
	}
	else
	{
		return '<meta http-equiv="content-type" content="text/html; charset=' . $charset . '">' . "\n";
	}
}

//For qhm template engine & qhm cache engine
function qhm_output_dtd($pkwk_dtd, $content_charset = CONTENT_CHARSET, $encode = CONTENT_CHARSET){

	// Output HTTP headers
	pkwk_common_headers();
	header('Cache-control: no-cache');
	header('Pragma: no-cache');
	header('Content-Type: text/html; charset=' . $encode);
	
	// Output HTML DTD, <html>, and receive content-type
	$meta_content_type = pkwk_output_dtd($pkwk_dtd);

	if( $content_charset != $encode)
	{
		$meta_content_type = str_replace($content_charset, $encode, $meta_content_type);
	}
	
	return $meta_content_type;
}


/* End of file html.php */
/* Location: ./lib/html.php */