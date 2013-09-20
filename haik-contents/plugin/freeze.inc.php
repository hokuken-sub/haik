<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: freeze.inc.php,v 1.9 2004/12/16 13:32:19 henoheno Exp $
//
// Freeze(Lock) plugin

// Reserve 'Do nothing'. '^#freeze' is for internal use only.
function plugin_freeze_convert() { return ''; }

function plugin_freeze_action()
{
	global $script, $vars, $function_freeze;
	$qt = get_qt();
	
    //キャッシュしない
    $qt->enable_cache = false;

	$page = isset($vars['page']) ? $vars['page'] : '';
	if (! $function_freeze || ! is_page($page))
		return array('msg' => '', 'body' => '');

	$page_title = get_page_title($page);

	$pass = isset($vars['pass']) ? $vars['pass'] : NULL;
	$msg = $body = '';
	if (is_freeze($page)) {
		// Freezed already
		$msg  = __('$1 はすでに凍結されています');
		$body = str_replace('$1', h(strip_bracket($page)),
			__('$1 はすでに凍結されています'));

	} else if ($pass !== NULL && pkwk_login($pass)) {
		// Freeze
		$postdata = get_source($page);
		array_unshift($postdata, "#freeze\n");
		file_write(DATA_DIR, $page, join('', $postdata), TRUE);

		// Update
		is_freeze($page, TRUE);
		
		set_flash_msg(sprintf(__('%s を凍結しました。'), $page_title));
		redirect($page);

	} else {
		// Show a freeze form
		$msg    = sprintf(__('%s の凍結'), $page_title);
		$s_page = h($page);
		$body   = '<h2>'. $msg. '</h2>';
		$body  .= ($pass === NULL) ? '' : '<div class="alert alert-danger">パスワードが間違っています。</div>'. "\n";
		$body  .= <<<EOD
<p>管理者パスワードを入力してください。</p>
<form action="$script" method="post" class="form-inline">
	<input type="hidden"   name="cmd"  value="freeze">
	<input type="hidden"   name="page" value="$s_page">
	<div class="row">
		<div class="col-sm-3">
			<div class="input-group">
				<input type="password" name="pass" size="12" class="form-control" tabindex="1" placeholder="管理者パスワード">
				<span class="input-group-btn">
					<input type="submit" name="ok" value="凍結" class="btn btn-danger">
				</span>
			</div>
		</div>
	</div>
</form>
EOD;
	}

	return array('msg'=>$msg, 'body'=>$body);
}

/* End of file freeze.inc.php */
/* Location: /app/haik-contents/plugin/freeze.inc.php */