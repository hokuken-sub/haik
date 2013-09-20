<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: links.inc.php,v 1.23 2005/02/27 09:43:12 henoheno Exp $
//
// Update link cache plugin

function plugin_links_action()
{
	global $script, $post, $vars, $foot_explain;
	$qm = get_qm();

	if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing"');

	$msg = $body = '';
	if (empty($vars['action']) || empty($post['adminpass']) || ! pkwk_login($post['adminpass'])) {
		$msg   = 'キャッシュ更新';
		$body  = convert_html('* 処理内容

:キャッシュを更新|
全てのページをスキャンし、あるページがどのページからリンクされているかを調査して、キャッシュに記録します。

* 注意
実行には数分かかる場合もあります。実行ボタンを押したあと、しばらくお待ちください。

* 実行
管理者パスワードを入力して、[実行]ボタンをクリックしてください。');

		$body .= <<<EOD
<form method="POST" action="$script" class="form-inline">
	<input type="hidden" name="plugin" value="links">
	<input type="hidden" name="action" value="update">
	<label class="control-label">管理者パスワード</label>
	<input type="password" name="adminpass" id="_p_links_adminpass" value="" class="form-control input-sm" style="width:auto;">
	<input type="submit" value="実行" class="btn btn-primary btn-sm">
</form>
EOD;

	} else if ($vars['action'] == 'update') {
		links_init();
		$foot_explain = array(); // Exhaust footnotes
		$msg  = 'キャッシュ更新';
		$body = 'キャッシュの更新を完了しました';
	} else {
		$msg  = 'キャッシュ更新';
		$body = '不正なパラメータです。キャッシュの更新ができませんでした。';
	}
	return array('msg'=>$msg, 'body'=>$body);
}

/* End of file links.inc.php */
/* Location: /app/haik-contents/plugin/links.inc.php */