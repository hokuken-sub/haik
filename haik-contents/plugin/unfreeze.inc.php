<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: unfreeze.inc.php,v 1.10 2004/12/18 01:24:21 henoheno Exp $
//
// Unfreeze(Unlock) plugin

// Show edit form when unfreezed
define('PLUGIN_UNFREEZE_EDIT', TRUE);

function plugin_unfreeze_action()
{
	global $script, $vars, $function_freeze;

	$page = isset($vars['page']) ? $vars['page'] : '';
	if (! $function_freeze || ! is_page($page))
		return array('msg' => '', 'body' => '');

	$page_title = get_page_title($page);

	$pass = isset($vars['pass']) ? $vars['pass'] : NULL;
	$msg = $body = '';
	if (! is_freeze($page)) {
		// Unfreezed already
		$msg  = __('$1 は凍結されていません');
		$body = str_replace('$1', h(strip_bracket($page)),
			__('$1 は凍結されていません'));

	} else if ($pass !== NULL && pkwk_login($pass)) {
		// Unfreeze
		$postdata = get_source($page);
		array_shift($postdata);
		$postdata = join('', $postdata);
		file_write(DATA_DIR, $page, $postdata, TRUE);

		// Update 
		is_freeze($page, TRUE);
		if (PLUGIN_UNFREEZE_EDIT) {
			set_flash_msg(sprintf(__('%s の凍結を解除しました'), $page));
			redirect($page);
			exit;
		} else {
			$vars['cmd'] = 'read';
			$msg  = __('$1 の凍結を解除しました');
			$body = '';
		}

	} else {
		// Show unfreeze form
		$msg    = sprintf(__('%s の凍結解除'), $page_title);
		$s_page = h($page);
		$body   = '<h2>'. $msg. '</h2>';
		$body  .= ($pass === NULL) ? '' : '<div class="alert alert-danger">パスワードが間違っています。</div>'. "\n";
		$body  .= <<<EOD
<p>凍結解除用のパスワードを入力してください。</p>
<form action="$script" method="post">
	<input type="hidden"   name="cmd"  value="unfreeze">
	<input type="hidden"   name="page" value="$s_page">
	<div class="row">
		<div class="col-sm-3">
			<div class="input-group">
				<input type="password" name="pass" size="12" class="form-control" tabindex="1" placeholder="管理者パスワード">
				<span class="input-group-btn">
					<input type="submit"   name="ok"   value="凍結解除" class="btn btn-primary">
				</span>
			</div>
		</div>
	</div>
</form>
EOD;
	}

	return array('msg'=>$msg, 'body'=>$body);
}

/* End of file unfreeze.inc.php */
/* Location: /app/haik-contents/plugin/unfreeze.inc.php */