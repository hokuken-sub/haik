<?php
// $Id: copy.inc.php,v 1.21 2005/02/27 08:06:48 henoheno Exp $
//
// Load copy plugin

define('MAX_LEN', 60);

function plugin_copy_action()
{
	global $script, $vars;
	$qm = get_qm();

	if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');
	if (! isset($vars['refer']) || ! is_page($vars['refer']))
		return FALSE;

	$lines = get_source($vars['refer']);

	// Remove '#freeze'
	if (! empty($lines) && strtolower(rtrim($lines[0])) == '#freeze')
		array_shift($lines);

	$begin = (isset($vars['begin']) && is_numeric($vars['begin'])) ? $vars['begin'] : 0;
	$end   = (isset($vars['end'])   && is_numeric($vars['end']))   ? $vars['end'] : count($lines) - 1;
	if ($begin > $end) {
		$temp  = $begin;
		$begin = $end;
		$end   = $temp;
	}
	$page    = isset($vars['page']) ? $vars['page'] : '';
	$is_page = is_page($page);

	// edit
	if ($is_pagename = is_pagename($page) && (! $is_page || ! empty($vars['force']))) {
		$postdata       = join('', array_splice($lines, $begin, $end - $begin + 1));
		$vars['cmd'] = 'edit';
		$vars['refer']  = $vars['page'];
		$vars['msg'] = $postdata;
		if (exist_plugin('edit'))
		{
			return do_plugin_action('edit');
		}
		return FALSE;
	}
	$begin_select = $end_select = '';
	for ($i = 0; $i < count($lines); $i++) {
		$line = h(mb_strimwidth($lines[$i], 0, MAX_LEN, '...'));

		$tag = ($i == $begin) ? ' selected="selected"' : '';
		$begin_select .= "<option value=\"$i\"$tag>$line</option>\n";

		$tag = ($i == $end) ? ' selected="selected"' : '';
		$end_select .= "<option value=\"$i\"$tag>$line</option>\n";
	}

	$_page = h($page);
	$msg = $title_msg = $tag = '';
	if ($is_page) {
		$msg = "「{$_page}」は、既に存在します。<br />上書きする場合は、「ページを上書きする」にチェックをしてください。";
		$title_msg = "{$_page}は、既に存在します。";
		$tag = '<label class="checkbox"><input type="checkbox" name="force" value="1" />'.'ページを上書きする</label>';
	}
	else if ($page != '' && ! $is_pagename)
	{
		$msg = str_replace('$1', $_page, $qm->m['plg_copy']['err_invalid']);
		$title_msg = $msg;
	}
	
	if ($msg != "")
	{
		$msg = '<div class="alert alert-error">'.$msg.'</div>';
	}

	$s_refer = h($vars['refer']);
	$s_page  = ($page == '') ? $s_refer."のコピー" : $_page;
	$ret     = <<<EOD
<h2>{$s_refer}の複製</h2>
<div style="margin-top:30px">
	$msg
	<form action="$script" method="post" class="form-inline">
		<input type="hidden" name="plugin" value="copy" />
		<input type="hidden" name="refer"  value="$s_refer" />
		<div class="form-group">
			<label class="control-label" for="_p_copy_refer">ページ名：</label>
		  	<input type="text" name="page" id="_p_copy_refer" value="$s_page" class="form-control" style="width:auto" />
		  	<input class="btn btn-primary" type="submit" name="submit" value="複製" />
		</div>
	</form>
</div>
EOD;

	$retvar['msg']  = ($title_msg == '') ? "{$s_refer}の複製" : $title_msg;
	$retvar['body'] = $ret;

	return $retvar;
}

/* End of file copy.inc.php */
/* Location: /app/haik-contents/plugin/copy.inc.php */