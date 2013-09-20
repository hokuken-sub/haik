<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: edit.inc.php,v 1.40 2006/03/21 14:26:25 henoheno Exp $
// Copyright (C) 2001-2006 PukiWiki Developers Team
// License: GPL v2 or (at your option) any later version
//
// Edit plugin (cmd=edit)

// Remove #freeze written by hand
define('PLUGIN_EDIT_FREEZE_REGEX', '/^(?:#freeze(?!\w)\s*)+/im');

define('PLUGIN_EDIT_AUTO_DESCRIPTION_LENGTH', 120);

function plugin_edit_action()
{
	global $vars, $layout_pages;
	global $qblog_defaultpage, $style_name, $admin_style_name, $template_name;
	
	$qm = get_qm();
	$qt = get_qt();

	if (PKWK_READONLY) die_message($qm->m['fmt_err_pkwk_readonly']);

	$page = isset($vars['page']) ? $vars['page'] : '';

	$prefix = '';
	check_editable($page, true, true);

	if (isset($vars['preview'])) 
	{
		return plugin_edit_preview();
	} 
	else if (isset($vars['write']))
	{
		return plugin_edit_write();
	}
	else if (isset($vars['cancel']))
	{
		return plugin_edit_cancel();
	}
	
	$style_name = $admin_style_name;
	$qt->setv('template_name', 'editor');
	
	$postdata = @join('', get_source($page));
	if (isset($vars['msg'])) {
		$postdata = $vars['msg'];
	}
	
	if ($postdata == '') $postdata = auto_template($page);

	$vars['notimestamp'] = "true";
	return array('msg'=>$qm->m['fmt_title_edit'], 'body'=> $prefix . edit_form($page, $postdata, FALSE));
}

// Preview
function plugin_edit_preview()
{
	global $vars, $layout_pages;
	global $qblog_defaultpage;
	$qm = get_qm();
	$qt = get_qt();

	$page = isset($vars['page']) ? $vars['page'] : '';
	$refer = isset($vars['refer']) ? $vars['refer'] : $page;
	
	$layout_name = '';
	if (array_key_exists($page, $layout_pages))
	{
		$layout_name = $layout_pages[$page];
	}

	$vars['msg'] = preg_replace(PLUGIN_EDIT_FREEZE_REGEX, '', $vars['msg']);
	$postdata = $vars['msg'];

	
	if ($layout_name !== '')
	{
		$body = convert_html(join('', get_source($refer)));
	}
	else
	{
		$body = '<div id="preview_body">';
		if ($postdata == '')
			$body .= '<strong>' . $qm->m['fmt_msg_preview_delete'] . '</strong>';
		$body .= '<br />' . "\n";
	
		if ($postdata) {
			if ($page !== $qblog_defaultpage && is_qblog())
			{
				$postdata = "#qblog_head\n" . $postdata;
			}
			$postdata = make_str_rules($postdata);
			$postdata = explode("\n", $postdata);
			$postdata = drop_submit(convert_html($postdata));
			$body .= $postdata;
		}
		$body .= '</div>'. "\n";
	}

	return array('msg'=>$qm->m['fmt_title_preview'], 'body'=>$body);
}

// Inline: Show edit (or unfreeze text) link
function plugin_edit_inline()
{
	global $script, $vars, $fixed_heading_anchor_edit;
	$qm = get_qm();

	if (PKWK_READONLY) return ''; // Show nothing 

	// Arguments
	$args = func_get_args();

	// {label}. Strip anchor tags only
	$s_label = strip_htmltag(array_pop($args), FALSE);

	$page    = array_shift($args);
	if ($page == NULL) $page = '';
	$_noicon = $_nolabel = FALSE;
	foreach($args as $arg){
		switch(strtolower($arg)){
		case ''       :                   break;
		case 'nolabel': $_nolabel = TRUE; break;
		case 'noicon' : $_noicon  = TRUE; break;
		default       : return $qm->m['plg_edit']['err_usage'];
		}
	}

	// Separate a page-name and a fixed anchor
	list($s_page, $id, $editable) = anchor_explode($page, TRUE);

	// Default: This one
	if ($s_page == '') $s_page = isset($vars['page']) ? $vars['page'] : '';

	// $s_page fixed
	$isfreeze = is_freeze($s_page);
	$ispage   = is_page($s_page);

	// Paragraph edit enabled or not
	$short = h($qm->m['plg_edit']['title_short']);
	if ($fixed_heading_anchor_edit && $editable && $ispage && ! $isfreeze) {
		// Paragraph editing
		$id    = rawurlencode($id);
		$title = h($qm->replace('plg_edit.title', $page));
		$icon = '<img src="' . IMAGE_DIR . 'paraedit.png' .
			'" width="9" height="9" alt="' .
			$short . '" title="' . $title . '" /> ';
		$class = ' class="anchor_super"';
	} else {
		// Normal editing / unfreeze
		$id    = '';
		if ($isfreeze) {
			$title = $qm->replace('plg_edit.title_unfreeze', $s_page);
			$icon  = 'unfreeze.png';
		} else {
			$title = $qm->replace('plg_edit.title', $s_page);
			$icon  = 'edit.png';
		}
		$title = h($title);
		$icon = '<img src="' . IMAGE_DIR . $icon .
			'" width="20" height="20" alt="' .
			$short . '" title="' . $title . '" />';
		$class = '';
	}
	if ($_noicon) $icon = ''; // No more icon
	if ($_nolabel) {
		if (!$_noicon) {
			$s_label = '';     // No label with an icon
		} else {
			$s_label = $short; // Short label without an icon
		}
	} else {
		if ($s_label == '') $s_label = $title; // Rich label with an icon
	}

	// URL
	if ($isfreeze) {
		$url   = $script . '?cmd=unfreeze&amp;page=' . rawurlencode($s_page);
	} else {
		$s_id = ($id == '') ? '' : '&amp;id=' . $id;
		$url  = $script . '?cmd=edit&amp;page=' . rawurlencode($s_page) . $s_id;
	}
	$atag  = '<a' . $class . ' href="' . $url . '" title="' . $title . '">';
	static $atags = '</a>';

	if ($ispage) {
		// Normal edit link
		return $atag . $icon . $s_label . $atags;
	} else {
		// Dangling edit link
		return '<span class="noexists">' . $atag . $icon . $atags .
			$s_label . $atag . '?' . $atags . '</span>';
	}
}

// Write, add, or insert new comment
function plugin_edit_write()
{
	global $vars, $script, $layout_pages, $defaultpage;
	global $notimeupdate, $do_update_diff_table;
	global $qblog_defaultpage, $date_format, $qblog_menubar;
	global $change_timestamp;
	$qm = get_qm();

	$page   = isset($vars['page'])   ? $vars['page']   : '';
	$refer  = isset($vars['refer'])   ? $vars['refer']   : $page;
	$add    = isset($vars['add'])    ? $vars['add']    : '';
	$digest = isset($vars['digest']) ? $vars['digest'] : '';
	$template_name = isset($vars['template_name']) ? $vars['template_name'] : '';

	$vars['msg'] = preg_replace(PLUGIN_EDIT_FREEZE_REGEX, '', $vars['msg']);
	$msg = $vars['msg'];
	

	$retvars = array();

	// Collision Detection
	$oldpagesrc = join('', get_source($page));
	$oldpagemd5 = md5($oldpagesrc);


	if ($digest != $oldpagemd5) {
		$vars['digest'] = $oldpagemd5; // Reset

		$original = isset($vars['original']) ? $vars['original'] : '';
		list($postdata_input, $auto) = do_update_diff($oldpagesrc, $msg, $original);

		$retvars['msg' ] = __('$1 で【更新の衝突】が起きました');
		
		$retvars['body'] = '<h2>「更新の衝突」が起きました。</h2>';
		
		if ($auto)
		{
			$retvars['body'] .= '
<div class="alert alert-warning">
	あなたがこのページを編集している間に、他の人が同じページを更新したか、<br>
	あるいは、ブラウザの「戻る」ボタンで戻って編集をし直した可能性があります。<br>
	確認後、「更新」を押してください。<br>
	<br>
	※ ブラウザの「戻る」ボタンで編集をし直さないようにしてください。
</div>
';
		}
		else
		{
			$retvars['body'] .= '
<div class="alert alert-warning">
	あなたがこのページを編集している間に、他の人が同じページを更新してしまったようです。<br>
	今回追加した行は +で始まっています。<br>
	!で始まる行が変更された可能性があります。<br>
	!や+で始まる行を修正して再度ページの更新を行ってください。
</div>
';
		}
		$retvars['body'] .= $do_update_diff_table;
		$retvars['body'] .= edit_form($page, $postdata_input, $oldpagemd5, FALSE);
		return $retvars;
	}

	// Action?
	if ($add) {
		// Add
		if (isset($vars['add_top']) && $vars['add_top']) {
			$postdata  = $msg . "\n\n" . @join('', get_source($page));
		} else {
			$postdata  = @join('', get_source($page)) . "\n\n" . $msg;
		}
	} else {
		// Edit or Remove
		$postdata = $msg;
	}
	
	//defaultpage, layout_pages の時は title を破棄
	if ($page === $defaultpage OR array_key_exists($page, $layout_pages))
	{
		unset($vars['title']);
	}
	
	//ブログの時は、タイトルを足す
	if ($page !== $qblog_defaultpage && is_qblog())
	{
		global $qblog_default_cat;
		$title = trim($vars['title']);
		$image = trim($vars['image']);
		$cat   = trim($vars['category']);
		$cat   = ($cat === '') ? $qblog_default_cat : $cat;
		
		if ($postdata !== '')
		{
			$postdata = 'TITLE:'. $title . "\n" . $postdata;
		}
	}
	
	//メタ情報を保存する
	$meta = array(
		'auto_description' => create_page_description($page, PLUGIN_EDIT_AUTO_DESCRIPTION_LENGTH, $postdata),
	);
	if (isset($vars['title']))
	{
		$meta['title'] = trim($vars['title']);
	}
	if (isset($vars['template_name']) && trim($vars['template_name']))
	{
		$meta['template_name'] = $template_name;
	}
	meta_write($page, $meta);
	

	// NULL POSTING, OR removing existing page
	if ($postdata == '') {
		page_write($page, $postdata);
		
		set_flash_msg(sprintf(__('%sを削除しました。'), h($page)));
		redirect($script);
		
		exit;
	}

	// $notimeupdate: Checkbox 'Do not change timestamp'
	if ($change_timestamp)
	{
		$notimestamp = FALSE;
	}
	else
	{
		$notimestamp = isset($vars['notimestamp']) && $vars['notimestamp'] != '';
	}
	if ($notimeupdate > 1 && $notimestamp && ! pkwk_login($vars['pass'])) {
		// Enable only administrator & password error
		$retvars['body']  = '<p><strong>' . $qm->m['fmt_msg_invalidpass'] . '</strong></p>' . "\n";
		$retvars['body'] .= edit_form($page, $msg, $digest, FALSE);
		return $retvars;
	}

	page_write($page, $postdata, $notimeupdate != 0 && $notimestamp);
	
	//ブログの場合
	if ($page !== $qblog_defaultpage && is_qblog())
	{
		// 日付の変更があったら、ページ名の変更
		$page_date = get_qblog_date($date_format, $page);
		if ($page_date AND $vars['qblog_date'] != $page_date)
		{
			// ページ名の変更
			if (exist_plugin('rename'))
			{
				// ! renameのために $vasの値を変更
				$vars['page'] = $newpage = qblog_get_newpage($vars['qblog_date']);
				$vars['refer'] = $refer = $page;
				$pages = array();
				$pages[encode($refer)] = encode($newpage);
				$files = plugin_rename_get_files($pages);
				$exists = array();
				foreach ($files as $_page => $arr)
				{
					foreach ($arr as $old => $new)
					{
						if (file_exists($new))
						{
							$exists[$_page][$old] = $new;
						}
					}
				}
				plugin_rename_proceed($pages, $files, $exists, FALSE);
				
				//保留コメントリスト内のページ名を変更
				$datafile = CACHEQBLOG_DIR . 'qblog_pending_comments.dat';
				$pending_comments = unserialize(file_get_contents($datafile));
				foreach ($pending_comments as $i => $comment)
				{
					if ($comment['page'] == $page)
					{
						$pending_comments[$i]['page'] = $newpage;
					}
				}
				file_put_contents($datafile, serialize($pending_comments), LOCK_EX);
				
				//最新コメントリスト内のページ名を変更
				$datafile = CACHEQBLOG_DIR . 'qblog_recent_comments.dat';
				file_put_contents($datafile, str_replace($page, $newpage, file_get_contents($datafile)), LOCK_EX);
				
				//変数を格納し直す
				$page = $newpage;
			}
		}

		//ブログの時は、ポストキャッシュを書き換える
		$option = array('category' => $cat, 'image' => $image);
		qblog_update_post($force, $page, $option);
		
		//Ping送信を行う
		if ( ! $notimestamp)
		{
			send_qblog_ping();
		}
	}

	
	set_flash_msg('ページを更新しました。');
	
	$redirect = $script . '?' . rawurlencode($refer);
	header('Location: ' . $redirect);

	exit;
}

// Cancel (Back to the page / Escape edit page)
function plugin_edit_cancel()
{
	global $vars, $script, $layout_pages, $qblog_menubar, $qblog_defaultpage;
	global $defaultpage;
	
	$page = isset($vars['page']) ? $vars['page'] : $defaultpage;
	$refer = isset($vars['refer']) ? $vars['refer'] : $page;
	$refer = is_page($refer) ? $refer : $defaultpage;

	header('Location: ' . $script . '?' . $refer);


	exit;
}

/* End of file edit.inc.php */
/* Location: /app/haik-contents/plugin/edit.inc.php */