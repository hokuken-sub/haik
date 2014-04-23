<?php
/////////////////////////////////////////////////
// Main

$retvars = array();
$is_cmd = FALSE;
if (isset($vars['cmd'])) {
	$is_cmd  = TRUE;
	$plugin = & $vars['cmd'];
} else if (isset($vars['plugin'])) {
	$plugin = & $vars['plugin'];
} else {
	$plugin = '';
}
if ($plugin != '') {
	if (exist_plugin_action($plugin)) {
		// Found and exec
		$retvars = do_plugin_action($plugin);
		if ($retvars === FALSE) exit; // Done

		if ($is_cmd) {
			$base = isset($vars['page'])  ? $vars['page']  : '';
		} else {
			$base = isset($vars['refer']) ? $vars['refer'] : '';
		}
	} else {
		// Not found
		$msg = 'plugin=' . h($plugin) .
			' is not implemented.';
		$retvars = array('msg'=>$msg,'body'=>$msg);
		$base    = & $defaultpage;
	}
}

$title = h(strip_bracket($base));
$page  = make_search($base);
if (isset($retvars['msg']) && $retvars['msg'] != '') {
	$title = str_replace('$1', $title, $retvars['msg']);
	$page  = str_replace('$1', $page,  $retvars['msg']);
}

$page_meta = meta_read($base);

//全体認証
if ( ! ss_admin_check() && ! $disable_site_auth)
{
	if ($site_close_all)
	{
		//------------------------------------------------------
		// * サイト全体を閉鎖するオプションがOnの場合、
		//　　cmd=loginへのアクセス以外、全部、「閉鎖中」を出す
		//------------------------------------------------------
		$_SESSION['usr'] = null;
		output_site_close_message($site_title, $script.'?cmd=login');
		exit;
	}
	//Facebook Login
	if (isset($fb_auth) && $fb_auth)
	{
		if ( ! isset($_SESSION['fb_user']) && exist_plugin('facebook_auth'))
		{
			plugin_facebook_auth_login();
		}
	}
}

// 共用SSLの多くは、SSL通信なのに、SERVER変数が、http系になっていることがほとんど
// そこで、SSL通信の場合、$scriptは、https://.. にするために、$script_sslを使うようにする
// 独自SSLを導入している場合でも、対応できる ( lib/init.php で、$script_ssl を作っているので )
// 　※ なお、convert_htmlが終わった時点で、元に戻す（このelseブロックの最後を参照）
if( is_https() ){
	$scr_tmp     = $script;
	$script      = $script_ssl;
	$script_ssl  = $scr_tmp;	
}

$qt = get_qt();

if (isset($retvars['body']) && $retvars['body'] != '') {
	$body = $retvars['body'];
} else {
	if ($base == '' || ! is_page($base)) {
	    global $defaultpage;
		$base  = $defaultpage;
		$title = h(strip_bracket($base));
		$page  = make_search($base);
	}

	$vars['cmd']  = 'read';
	$vars['page'] = $base;

	//--------------------------------------------------------------------------
	//
	// * キャッシュを有効にするカスタマイズ by hokuken.com
	//
	// ・編集モードの場合、キャッシュは強制的に無効
	//
	// ・キャッシュは、有効だが、キャッシュファイル無効の場合は、$qt->create_cache = true;
	// 　として、動的にロードされるべきプラグインの制御を有効にする(ex: popularプラグインなど)
	//
	// ・キャッシュが有効で、キャッシュファイルがあるなら、キャッシュを出力
	//
	//--------------------------------------------------------------------------

	//編集状態の場合、強制的にキャッシュを無効
	$qt->enable_cache = edit_auth($base, FALSE, FALSE)===TRUE ? false : $qt->enable_cache;
	
	//携帯の場合、強制的にキャッシュ機能をオフ。もし、転送設定があれば、転送する
	if( preg_match('/keitai.skin.php$/', SKIN_FILE) ){
		$qt->enable_cache = false;
	}
	//スマートフォンの場合、強制的にキャッシュ機能をオフ
	if (is_smart_phone()) {
		$qt->enable_cache = false;
	}
	
	global $script;
	//$scriptが変化している場合、キャッシュの有効期限をリフレッシュ
	chk_script($script);

	if( $qt->enable_cache ){ //キャッシュ有効
		//キャッシュを出力して終了
		if ($qt->cache_is_available()) {
			$qt->disp();
			exit;
		} else {
			//ここではコンテンツ部分のみ生成するため、キャッシュは保存しない
			//出力の部分で保存します
			$qt->create_cache = true;
			$src = get_source($base);
			
			if (is_qblog())
			{
				array_unshift($src, "#qblog_head\n");
				array_push($src, "#qblog_foot\n", "#qblog_comment");
			}
			
			$body = convert_html($src);
			
			if (!$qt->enable_cache) { //プラグインによってキャッシュを無効にされている場合
				//動的プラグインを実行しておく
				$qt->create_cache = false;
				$body = $qt->replace_dynamic_plugin($body);
			}
		}
	}
	else
	{
		$src = get_source($base);
		
		if (is_qblog())
		{
			array_unshift($src, "#qblog_head\n");
			array_push($src, "#qblog_foot\n", "#qblog_comment");
		}
		
		$body = convert_html($src);
	}
	
}

if (!$qt->getv('body_insert_mark')) {
	$body = "\n<!-- BODYCONTENTS START -->\n<div id=\"orgm_body\">" . $body . "\n</div>\n<!-- BODYCONTENTS END -->\n";
	$qt->setv('body_insert_mark', true);
}


// Output
catbody($title, $page, $body);
exit;
