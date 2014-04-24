<?php

// ---------------------------------------------
// redirect.inc.php   v. 0.9
//
// 任意のページにリダイレクトする
// URLを設定しない場合は、トップへ
// 管理モードで以外で表示したくないページに使うと便利
// writed by hokuken.com 2007 8/24
// ----------------------------------------------- 

function plugin_redirect_page()
{

	global $vars, $script, $page_meta;
	$qm = get_qm();
	$qt = get_qt();
	
	$page = $vars['page'];

    if (edit_auth($page, FALSE, FALSE))
    {
    	if ($vars['cmd'] === 'read')
			set_notify_msg(__('このページは転送設定がされています。'), 'info');
		return FALSE;
    }
	
	//キャッシュしない
	$qt->enable_cache = false;
    
    $to = $page_meta['redirect'];
    $status = $page_meta['redirect_status'];
    
    if (is_page($to))
    {
	    $url = get_page_url($to);
    }
    else
    {
	    $url = $to;
    }
    
    $status_code = array(
    	'301' => 'HTTP/1.1 301 Moved Permanently'
    );
    
    if (isset($status_codes[$status]))
    {
    	$headers[] = $status_codes[$status];
    }
    
    //自分自身にリダイレクトして、ループする場合は警告する
    if ($url === get_page_url($page))
    {
        return $qm->m['plg_redirect']['err_self_ref'];
    }
    
	$headers[] = 'Location: '. $url;
	foreach ($headers as $header)
	{
		header($header);
	}
    exit;
}


?>