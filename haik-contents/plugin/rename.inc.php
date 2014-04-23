<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: rename.inc.php,v 1.27 2005/02/27 07:57:26 henoheno Exp $
//
// Rename plugin: Rename page-name and related data
//
// Usage: http://path/to/pukiwikiphp?plugin=rename[&refer=page_name]

define('PLUGIN_RENAME_LOGPAGE', ':RenameLog');

function plugin_rename_action()
{
	global $whatsnew, $style_name, $admin_style_name;
	$qt = get_qt();
	
	if (PKWK_READONLY) die_message('PKWK_READONLY');

	$method = plugin_rename_getvar('method');
	if ($method == 'regex') {
		$src = plugin_rename_getvar('src');
		if ($src == '') return plugin_rename_phase1();

		$src_pattern = '/' . preg_quote($src, '/') . '/';
		$arr0 = preg_grep($src_pattern, get_existpages());
		if (! is_array($arr0) || empty($arr0))
			return plugin_rename_phase1('nomatch');

		$dst = plugin_rename_getvar('dst');
		$arr1 = preg_replace($src_pattern, $dst, $arr0);
		foreach ($arr1 as $page)
			if (! is_pagename($page))
				return plugin_rename_phase1('notvalid');

		return plugin_rename_regex($arr0, $arr1);

	} else {
		// $method == 'page'
		$page  = plugin_rename_getvar('page');
		$refer = plugin_rename_getvar('refer');
		
		$style_name = $admin_style_name;
		$qt->setv('template_name', 'narrow');


		if ($refer == '') {
			return plugin_rename_phase1();

		} else if (! is_page($refer)) {
			return plugin_rename_phase1('notpage', $refer);

		} else if ($refer == $whatsnew) {
			return plugin_rename_phase1('norename', $refer);

		} else if ($page == '' || $page == $refer) {
			return plugin_rename_phase2();

		} else if (! is_pagename($page)) {
			return plugin_rename_phase2('notvalid');

		} else {
			return plugin_rename_refer();
		}
	}
}

// 変数を取得する
function plugin_rename_getvar($key)
{
	global $vars;
	return isset($vars[$key]) ? $vars[$key] : '';
}

// エラーメッセージを作る
function plugin_rename_err($err, $page = '')
{
	if ($err == '') return '';
	
	$errmsgs = array(
		'nomatch'       => __('マッチするページがありません。'),
		'notvalid'      => __('リネーム後のページ名が正しくありません。'),
		'adminpass'     => __('管理者パスワードが正しくありません。'),
		'notpage'       => __('%sはページ名ではありません。'),
		'norename'      => __('%sをリネームすることはできません。'),
		'already'       => __('ページがすでに存在します。: %s'),
		'already_below' => __('以下のファイルがすでに存在します。'),
	);

	$body = '';
	if (is_array($page)) {
		$tmp = '';
		foreach ($page as $_page) $tmp .= '<br />' . $_page;
		$page = $tmp;
	}
	if ($page != '') $body = sprintf($errmsgs[$err], h($page));
	
	$msg = sprintf('<p>エラー: %s</p>', $body);
	return $msg;
}

//第一段階:ページ名または正規表現の入力
function plugin_rename_phase1($err = '', $page = '')
{
	global $script;

	$msg    = plugin_rename_err($err, $page);
	$refer  = plugin_rename_getvar('refer');
	$method = plugin_rename_getvar('method');

	$radio_regex = $radio_page = '';
	if ($method == 'regex') {
		$radio_regex = ' checked="checked"';
	} else {
		$radio_page  = ' checked="checked"';
	}
	$select_refer = plugin_rename_getselecttag($refer);

	$s_src = h(plugin_rename_getvar('src'));
	$s_dst = h(plugin_rename_getvar('dst'));

	$ret = array();
	$ret['msg']  = __('ページ名の変更');
	$ret['body'] = <<<EOD
<div class="page-header">
	{$ret['msg']}
</div>
<div class="panel panel-default">
  <div class="panel-body">
  	<form action="{$script}" method="post">
  		<input type="hidden" name="plugin" value="rename" />
  	
  		<div class="form-group">
  			<div class="radio">
  				<label>
  					<input type="radio" name="method" id="_p_rename_page" value="page"$radio_page /> 変更元ページを指定:
  				</label>
  			</div>
  		</div>
  		<div class="form-group">
  			<div class="row">
  				<div class="col-sm-offset-1 col-sm-6">
  					$select_refer
  				</div>
  			</div>
  		</div>
  	
  		<div class="form-group">
  			<div class="radio">
  				<label>
  					<input type="radio"  name="method" id="_p_rename_regex" value="regex"$radio_regex /> 正規表現で置換:
  				</label>
  			</div>
  		</div>
  		<div class="form-group">
  			<div class="row">
  				<label for="_p_rename_from" class="col-sm-offset-1 col-sm-1 control-label">From:</label>
  				<div class="col-sm-10">
  					<input type="text" name="src" id="_p_rename_from" size="80" value="$s_src" class="form-control">
  				</div>
  			</div>
  		</div>
  	
  		<div class="form-group">
  			<div class="row form-horizontal">
  				<label for="_p_rename_to" class="col-sm-offset-1 col-sm-1 control-label">To:</label>
  				<div class="col-sm-10">
  					<input type="text" name="dst" id="_p_rename_to" size="80" value="$s_dst" class="form-control">
  				</div>
  			</div>
  		</div>
  		
  		<div class="form-group">
  			<input type="submit" value="実行" class="btn btn-primary">
  		</div>
  	</form>
  </div>
</div>
EOD;
	return $ret;
}

//第二段階:新しい名前の入力
function plugin_rename_phase2($err = '')
{
	global $script, $qblog_menubar;

	$msg   = plugin_rename_err($err);
	$page  = plugin_rename_getvar('page');
	$refer = plugin_rename_getvar('refer');
	if ($page == '') $page = $refer;

	//ブログの場合、名称変更は非推奨
	$warn = '';
	if (is_qblog($refer) OR $refer === $qblog_menubar)
	{
		$warn = '
<div class="alert alert-danger">【警告】ブログ関連ページの名称変更を行うと、ブログの動作が不安定になる恐れがあります。</div>
';
	}

	$msg_related = '';
	$related = plugin_rename_getrelated($refer);
	if (! empty($related))
		$msg_related = '
<div class="form-group">
	<label for="" class="col-sm-3"></label>
	<div class="col-sm-9">
		<div class="checkbox">
			<label>
				<input type="checkbox" name="related" id="_p_rename_related" value="1" checked="checked" /> ' . __('関連ページもリネームする') . '
			</label>
		</div>
	</div>
</div>
';

	$msg_rename = sprintf('%sの名前を変更します。', make_pagelink($refer));
	$s_page  = h($page);
	$s_refer = h($refer);

	$ret = array();
	$ret['msg']  = __('ページ名の変更');
	$ret['body'] = <<<EOD
$warn
$msg
<div class="page-header">
	{$ret['msg']}
</div>
<p>{$msg_rename}</p>
<div class="panel panel-default">
  <div class="panel-body">
  	<form action="$script" method="post" class="form-horizontal">
  		<input type="hidden" name="plugin" value="rename" />
  		<input type="hidden" name="refer"  value="$s_refer" />
  		<div class="form-gourp">
  			<label for="_p_rename_newname" class="col-sm-3 control-label">新しい名前:</label>
  			<div class="col-sm-9">
  				<input type="text" name="page" id="_p_rename_newname" size="80" value="$s_page" class="form-control">
  			</div>
  		</div>
  		$msg_related
  		<div class="form-gourp">
  			<label for="" class="col-sm-3"></label>
  			<div class="col-sm-9">
  				<input type="submit" value="実行" class="btn btn-primary">
  			</div>
  		</div>
  	</form>
  </div>
</div>
EOD;
	if (! empty($related)) {
		$ret['body'] .= '<hr /><h4>' . __('関連ページ') . '</h4><ul class="nav nav-list">';
		sort($related);
		foreach ($related as $name)
			$ret['body'] .= '<li>' . make_pagelink($name) . '</li>';
		$ret['body'] .= '</ul>';
	}
	return $ret;
}

//ページ名と関連するページを列挙し、phase3へ
function plugin_rename_refer()
{
	$page  = plugin_rename_getvar('page');
	$refer = plugin_rename_getvar('refer');

	$pages[encode($refer)] = encode($page);
	if (plugin_rename_getvar('related') != '') {
		$from = strip_bracket($refer);
		$to   = strip_bracket($page);
		foreach (plugin_rename_getrelated($refer) as $_page)
			$pages[encode($_page)] = encode(str_replace($from, $to, $_page));
	}

	return plugin_rename_phase3($pages);
}

//正規表現でページを置換
function plugin_rename_regex($arr_from, $arr_to)
{
	$exists = array();
	foreach ($arr_to as $page)
		if (is_page($page))
			$exists[] = $page;

	if (! empty($exists)) {
		return plugin_rename_phase1('already', $exists);
	} else {
		$pages = array();
		foreach ($arr_from as $refer)
			$pages[encode($refer)] = encode(array_shift($arr_to));
		return plugin_rename_phase3($pages);
	}
}

function plugin_rename_phase3($pages)
{
	global $script, $passwd;

	$msg = $input = '';
	$files = plugin_rename_get_files($pages);

	$exists = array();
	foreach ($files as $_page=>$arr)
		foreach ($arr as $old=>$new)
			if (file_exists($new))
				$exists[$_page][$old] = $new;

	$pass = plugin_rename_getvar('pass');
	if ($pass != '' && check_passwd($pass, $passwd)) {
		return plugin_rename_proceed($pages, $files, $exists);
	} else if ($pass != '') {
		$msg = plugin_rename_err('adminpass');
	}

	$method = plugin_rename_getvar('method');
	if ($method == 'regex') {
		$s_src = h(plugin_rename_getvar('src'));
		$s_dst = h(plugin_rename_getvar('dst'));
		$msg   .= __('正規表現で置換') . '<br />';
		$input .= '<input type="hidden" name="method" value="regex" />';
		$input .= '<input type="hidden" name="src"    value="' . $s_src . '" />';
		$input .= '<input type="hidden" name="dst"    value="' . $s_dst . '" />';
	} else {
		$s_refer   = h(plugin_rename_getvar('refer'));
		$s_page    = h(plugin_rename_getvar('page'));
		$s_related = h(plugin_rename_getvar('related'));
		$msg   .= '変更元ページを指定' . '<br />';
		$input .= '<input type="hidden" name="method"  value="page" />';
		$input .= '<input type="hidden" name="refer"   value="' . $s_refer   . '" />';
		$input .= '<input type="hidden" name="page"    value="' . $s_page    . '" />';
		$input .= '<input type="hidden" name="related" value="' . $s_related . '" />';
	}

	if (! empty($exists)) {
		$msg .= __('以下のファイルがすでに存在します。') . '<ul class="nav nav-list">';
		foreach ($exists as $page=>$arr) {
			$msg .= '<li>' . make_pagelink(decode($page));
			$msg .= '→';
			$msg .= h(decode($pages[$page]));
			if (! empty($arr)) {
				$msg .= '<ul>' . "\n";
				foreach ($arr as $ofile=>$nfile)
					$msg .= '<li>' . $ofile .
					'→' . $nfile . '</li>' . "\n";
				$msg .= '</ul>';
			}
			$msg .= '</li>' . "\n";
		}
		$msg .= '</ul><hr />' . "\n";

		$input .= '<input type="radio" name="exist" value="0" checked="checked" />' .
			__('そのページを処理しない') . '<br />';
		$input .= '<input type="radio" name="exist" value="1" />' .
			__('そのファイルを上書きする') . '<br />';
	}

	$ret = array();
	$ret['msg'] = __('ページ名の変更');
	$ret['body'] = <<<EOD
<div class="page-header">{$ret['msg']}</div>
<p>$msg</p>
<div class="panel panel-default">
  <div class="panel-body">
  	<form action="$script" method="post">
  		<input type="hidden" name="plugin" value="rename" />
  		$input
  
  		<div class="form-inline">
  			<label for="_p_rename_adminpass" class="control-label">管理者パスワード</label>
  			<input type="password" name="pass" id="_p_rename_adminpass" value="" class="form-control" style="width:auto">
  			<input type="submit" value="実行" class="btn btn-primary">
  		</div>
  	</form>
  </div>
</div>
<p>以下のファイルをリネームします。</p>
EOD;

	ksort($pages);
	$ret['body'] .= '<ul class="list-group">'."\n";
	foreach ($pages as $old=>$new)
	{
		$ret['body'] .= '<li class="list-group-item">'.make_pagelink(decode($old)).' → '.h(decode($new)).'</li>'."\n";
	}
	$ret['body'] .= '</ul>'."\n";
	return $ret;
}

function plugin_rename_get_files($pages)
{
	$files = array();
	$dirs  = array(BACKUP_DIR, DIFF_DIR, DATA_DIR);
	if (exist_plugin_convert('attach'))  $dirs[] = UPLOAD_DIR;
	if (exist_plugin_convert('counter')) $dirs[] = COUNTER_DIR;
	if (exist_plugin('qblog'))   $dirs[] = CACHEQBLOG_DIR;

	// and more ...

	$matches = array();
	foreach ($dirs as $path) {
		$dir = opendir($path);
		if (! $dir) continue;

		while ($file = readdir($dir)) {
			if ($file == '.' || $file == '..') continue;

			foreach ($pages as $from=>$to) {
				$pattern = '/^' . str_replace('/', '\/', $from) . '([._].+)$/';
				if (! preg_match($pattern, $file, $matches))
					continue;

				$newfile = $to . $matches[1];
				$files[$from][$path . $file] = $path . $newfile;
			}
		}
	}
	return $files;
}

function plugin_rename_proceed($pages, $files, $exists, $redirect=TRUE)
{
	global $now;

	if (plugin_rename_getvar('exist') == '')
		foreach ($exists as $key=>$arr)
			unset($files[$key]);

	set_time_limit(0);
	foreach ($files as $page=>$arr) {
		foreach ($arr as $old=>$new) {
			if (isset($exists[$page][$old]) && $exists[$page][$old]) {
				unlink($new);
			}
			rename($old, $new);

			// linkデータベースを更新する BugTrack/327 arino
			links_update($old);
			links_update($new);
		}
	}

	$postdata = get_source(PLUGIN_RENAME_LOGPAGE);
	$postdata[] = '*' . $now . "\n";
	if (plugin_rename_getvar('method') == 'regex') {
		$postdata[] = '-' . __('正規表現で置換') . "\n";
		$postdata[] = '--From:[[' . plugin_rename_getvar('src') . ']]' . "\n";
		$postdata[] = '--To:[['   . plugin_rename_getvar('dst') . ']]' . "\n";
	} else {
		$postdata[] = '-' . __('変更元ページを指定') . "\n";
		$postdata[] = '--From:[[' . plugin_rename_getvar('refer') . ']]' . "\n";
		$postdata[] = '--To:[['   . plugin_rename_getvar('page')  . ']]' . "\n";
	}

	if (! empty($exists)) {
		$postdata[] = "\n" . __('以下のファイルを上書きしました。') . "\n";
		foreach ($exists as $page=>$arr) {
			$postdata[] = '-' . decode($page) .
				'→' . decode($pages[$page]) . "\n";
			foreach ($arr as $ofile=>$nfile)
				$postdata[] = '--' . $ofile .
					'→' . $nfile . "\n";
		}
		$postdata[] = '----' . "\n";
	}

	foreach ($pages as $old=>$new) {
		$postdata[] = '-' . decode($old) .
			'→' . decode($new) . "\n";

		// tinycodeの追加
		add_tinycode(decode($new));
	}

	// 更新の衝突はチェックしない。

	// ファイルの書き込み
	page_write(PLUGIN_RENAME_LOGPAGE, join('', $postdata));

	//リダイレクト
	$page = plugin_rename_getvar('page');
	if ($page == '') $page = PLUGIN_RENAME_LOGPAGE;

	if ($redirect)
	{
		pkwk_headers_sent();
		header('Location: ' . get_script_uri() . '?' . rawurlencode($page));
		exit;
	}
}

function plugin_rename_getrelated($page)
{
	$related = array();
	$pages = get_existpages();
	$pattern = '/(?:^|\/)' . preg_quote(strip_bracket($page), '/') . '(?:\/|$)/';
	foreach ($pages as $name) {
		if ($name == $page) continue;
		if (preg_match($pattern, $name)) $related[] = $name;
	}
	return $related;
}

function plugin_rename_getselecttag($page)
{
	global $whatsnew;

	$pages = array();
	foreach (get_existpages() as $_page) {
		if ($_page == $whatsnew) continue;

		$selected = ($_page == $page) ? ' selected' : '';
		$s_page = h($_page);
		$pages[$_page] = '<option value="' . $s_page . '"' . $selected . '>' .
			$s_page . '</option>';
	}
	ksort($pages);
	$list = join("\n" . ' ', $pages);

	return <<<EOD
<select name="refer" class="form-control">
 <option value=""></option>
 $list
</select>
EOD;

}

/* End of file rename.inc.php */
/* Location: /app/haik-contents/plugin/rename.inc.php */