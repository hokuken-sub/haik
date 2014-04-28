<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: read.inc.php,v 1.8 2005/01/15 13:57:07 henoheno Exp $
//
// Read plugin: Show a page and InterWiki
// 
// * 過去のURLからの引っ越しを支援
//   EUC-JPのページ名指定の場合、301ヘッダーを出して、UTF-8エンコーディングしてリダイレクト
//   もし、ページがなければ、編集となるだろう・・・
//
function plugin_read_action()
{
	global $vars, $script;
	global $post;
	$qm = get_qm();
	$qt = get_qt();
	
	$page = isset($vars['page']) ? $vars['page'] : '';

	//キャッシュを無効化
	if( isset($vars['word']) )
		$qt->enable_cache = false;

	if (is_page($page)) {
		// ページを表示
		check_readable($page, true, true);
		header_lastmod($page);

		// Off XSS Protection (Google Chrome)
		if (isset($_SESSION['disable_xss_protection']))
		{
			unset($_SESSION['disable_xss_protection']);
			header('X-XSS-Protection: 0');
		}
		return array('msg'=>'', 'body'=>'');

	}
	else if (! PKWK_SAFE_MODE && is_interwiki($page)) {
		return do_plugin_action('interwiki'); // InterWikiNameを処理

	} else if (is_pagename($page)) {
		$vars['cmd'] = 'edit';

		// 編集権限があれば、編集モードへ。なければ、メッセージを表示
		$editable = edit_auth($page, FALSE, FALSE);
		if($editable){
			return do_plugin_action('edit'); // 存在しないので、編集フォームを表示
		}
		else{
			//404 NOT FOUND
			header(' ',true, 404);
		
			exist_plugin('search');
			$res = "<hr />\n"."<p>{$qm->m['plg_read']['searched']}</p>".plugin_search2_do_search($page);
			return array(
				'msg'=> $qm->m['fmt_err_notfoundpage_title'],
				'body'=> $qm->replace('fmt_err_notfoundpage', $script)
			);
		}
		
	} else {
		//EUCエンコーディングかチェック
		if(mb_detect_encoding($post['page'], 'UTF-8,EUC-JP')=='EUC-JP')
		{
			$u_page = mb_convert_encoding($post['page'], 'UTF-8', 'EUC-JP');
			$enc_page = rawurlencode($u_page);
			
			//redirect
			header("HTTP/1.1 301 Moved Permanently");
			header('Location: '.get_page_url($u_page));
			exit;
		}
	
		// 無効なページ名
		return array(
			'msg'=>$qm->m['fmt_title_invalidiwn'],
			'body' => $qm->replace('fmt_err_invalidiwn', h($page), 'WikiName')
		);
	}
}
?>
