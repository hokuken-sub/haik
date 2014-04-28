<?php
/**
 *   Switch SSL Plugin
 *   -------------------------------------------
 *   /app/haik-contents/plugin/ssl.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 
 *   modified : 2013/06/25
 *   
 *   非SSLアクセスをSSLページへ転送する。
 *   
 *   Usage :
 *   
 */

function plugin_ssl_convert()
{

	global $script, $script_ssl, $vars, $reg_exp_host;

	//------------ [重要かつ複雑なロジック] ----------------------------------
	// #sslと記述されたページのみ、ssl通信の対象としたいため以下のような処理をする
	// （ナビ、メニュー、ナビ2などは、通常のURLにリンクさせたい）
	//
	//   0. lib/init.php で、$script_ssl が未設定なら生成される
	//   2. 入れ替えた後は、$script_ssl によって、コンテンツ部分の様々なURLが作られる
	//   3. lib/html.php 内で、元に戻す
	//   4. naviや、menuや、pukiwiki.skin.phpで呼び出すところでは、元の$scriptが使われる
	//
	//   なるべく、ドメインを含めないURL指定を心掛けるとよいかも
	//
	
	// lib/html.php でSSL用の処理(HTMLコードの書き換えを実行)をするためのフラグ
	$qt = get_qt();
	$qt->setv('plugin_ssl_flag', TRUE);

	$ssl_url = $script_ssl.'/'.rawurlencode($vars['page']);
	$h_ssl_url = h($ssl_url);
		
	// 移動を促すメッセージ
	$args = func_get_args();
	
	// 外部ウインドウで開くリストから、通常ページへのURLを除外
	$p_url = parse_url( is_https() ? $script_ssl : $script );
	$reg_exp_host .= ($reg_exp_host=='' ? '' : '|').$p_url['host'];

	// SSLアクセスの場合、何もしない。
	if ($script === $script_ssl) return '';

	if (check_editable($vars['page'], false, false))
	{
		$msg = isset($args[0]) ? h($args[0]) : __('SSL暗号化されたページへ移動してください');

		return <<< EOD
<div id="plugin_ssl_msg" class="alert alert-info">
	<a href="" class="close" data-dismiss="alert">&times;</a>
	<a href="{$h_ssl_url}">{$msg} <i class="orgm-icon orgm-icon-arrow-right"></i></a>
</div>
EOD;
	}
	else
	{
		$js = <<< EOD
<script>
if (document.location.protocol != 'https:') {
	location.href = '{$ssl_url}';
}
</script>
EOD;

		$qt->appendv_once('plugin_ssl', 'plugin_head', $js);
	}
	
	return '';

}

/* End of file ssl.inc.php */
/* Location: /app/haik-contents/plugin/ssl.inc.php */