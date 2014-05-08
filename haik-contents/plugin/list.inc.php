<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: list.inc.php,v 1.6 2006/05/13 07:36:41 henoheno Exp $
//
// IndexPages plugin: Show a list of page names

function plugin_list_action()
{
	global $vars, $whatsnew;
	$qm = get_qm();

	// Redirected from filelist plugin?
	$filelist = (isset($vars['cmd']) && $vars['cmd'] == 'filelist');
	
	if (isset($vars['json']) && $vars['json'])
	{
		plugin_list_getjson();
	}

	return array(
		'msg'=> __('ページの一覧'),
		'body'=>plugin_list_getlist($filelist));
}

function plugin_list_getjson()
{

	global $script, $non_list, $whatsnew, $username;
	
	$user = is_login() ? $_SESSION['usr'] : '';

	$qt = get_qt();
	$pages = array_diff($user === $username ? get_existpages() : get_readable_pages($user), array($whatsnew));
	$pages = array_diff($pages, preg_grep('/' . $non_list . '/S', $pages));

	$pages = array_values($pages);
	
	$retarr = array();
	foreach ($pages as $page)
	{
		$retarr[$page] = get_page_title($page);
	}
	
	print_json($retarr);
	exit;

}

// Get a list
function plugin_list_getlist($withfilename = FALSE)
{
	global $script, $non_list, $whatsnew, $style_name, $description, $username;

	$user = is_login() ? $_SESSION['usr'] : '';

	$qt = get_qt();
	$pages = array_diff($user === $username ? get_existpages() : get_readable_pages($user), array($whatsnew));

	if (! $withfilename)
	{
		$pages = array_diff($pages, preg_grep('/' . $non_list . '/S', $pages));
	}

	if (empty($pages))
	{
		return '';
	}

	if (exist_plugin('scrollup'))
	{
		do_plugin_convert('scrollup');
	}
	
	if (ss_admin_check())
	{
		if (exist_plugin('app_config'))
		{
			do_plugin_init('app_config');
			$qt->setv('template_name', 'content');
    }
		
		$recent_pages = array();
		if (file_exists(CACHE_DIR.ORGM_UPDATE_CACHE))
		{
			$lines = file_head(CACHE_DIR.ORGM_UPDATE_CACHE, 10);
			foreach ($lines as $line)
			{
				list($timestamp, $pagename) = explode("\t", $line);

				if ($pagetitle = get_page_title(trim($pagename)))
				{
					$recent_pages[] = array(
						'name'=> trim($pagename),
						'title' => $pagetitle
					);
				}
			}
		}
    
    $menu = '<div class="haik-admin-list-menu">';
		$menu .= '<div class="menu-header">'.__('最近編集したページ').'</div>';
		$menu .= '<div class="list-group">';
		foreach($recent_pages as $p)
		{
			$menu .= '<a class="list-group-item" href="'.h(get_page_url($p['name'])).'">'.h($p['title']).'</a></li>';
		}
		$menu .= '</div>';
		$menu .= '<div><a href="'.get_page_url($whatsnew).'" class="muted pull-right">&gt;&gt; すべて表示　</a></div>';
		$qt->setv('menu', $menu);

		return plugin_list_create_html(plugin_list_array($pages),  $withfilename);
	}
	else
	{
		return page_list($pages, 'read', $withfilename);
	}
}

function plugin_list_array($pages)
{
	global $non_list, $layout_pages;
	$qm = get_qm();
	
	$symbol = ' ';
	$other = 'zz';
	$list = array();
	$cnt = 0;
	//並び替える
	foreach ($pages as $file => $page)
	{
		$pgdata = array();
		$pgdata['urlencoded']  = rawurlencode($page);
		$pgdata['sanitized']   = h($page, ENT_QUOTES);
		$pgdata['passage'] = get_pg_passage($page, FALSE);
		$pgdata['mtime'] = date('Y年m月d日 H時i分s秒', filemtime(get_filename($page)));

		$pgdata['title'] = get_page_title($page);
		$pgdata['filename'] = h($file);

		$pgdata['admin'] = FALSE;
		if (preg_match('/' . $non_list . '/S', $page))
		{
			$pgdata['admin'] = TRUE;
			if (strpos($page, ':') === 0 && strpos($page, ':config') === FALSE)
			{
				$pgdata['admin'] = FALSE;
			}
			if (array_key_exists($page, $layout_pages))
			{
				$pgdata['admin'] = FALSE;
				$pgdata['title'] = $layout_pages[$page];
				
			}
		}
		$pgdata['title'] = ($pgdata['title'] == $pgdata['sanitized']) ? '' : '（'.$pgdata['title']. '）';

		$head = (preg_match('/^([A-Za-z])/', $page, $matches)) ? $matches[1] :
			(preg_match('/^([ -~])/', $page, $matches) ? $symbol : $other);

		$list[$head][$page] = $pgdata;
		$cnt++;
	}
	ksort($list);
	
	$tmparr = isset($list[$symbol])? $list[$symbol]: null;
	unset($list[$symbol]);
	$list[$symbol] = $tmparr;
	
	$retlist = array();
	foreach ($list as $head => $pages)
	{
		if (is_null($pages))
		{
			continue;
		}
	
		ksort($pages);

		if ($head === $symbol) {
			$head = $qm->m['func']['list_symbol'];
		} else if ($head === $other) {
			$head = $qm->m['func']['list_other'];
		}

		$retlist[$head] = $pages;
	}
	
	return $retlist;
	
}

/**
 * 渡されたページデータを元に高機能インデックスページを生成する
 *
 * @param assoc $pages_data ページデータ。{頭文字: {ページ名:ページデータ, ...}, ...} の連想配列
 * @param boolean $withfilename ファイル名を表示するかどうか
 * @param array $cmds 
 */
function plugin_list_create_html($pages_data,  $withfilename = FALSE)
{
	global $script, $list_index;
	$html = $index = '';
	$qm = get_qm();
	$qt = get_qt();
	$qt->setv('jquery_include', true);

	$head_cnt = 0;
	$indexies = array();
	foreach ($pages_data as $head => $pages)
	{
		if (is_null($pages) || count($pages) === 0)
		{
			continue;
		}
		
		if ($list_index)
		{
			$head_cnt++;
			$indexies[] = '<li><a href="#head_'. $head_cnt. '" id="plugin_list_index_'. $head_cnt. '"><strong>'.
				$head. '</strong></a></li>';
			$html .= '
	<tr class="info plugin_list_navi">
	<td colspan="2" data-non-admin-num="">
		<a href="#plugin_list_index_' . $head_cnt. '" id="head_' . $head_cnt . '"><strong>'. $head. '</strong></a>
	</td>';
		
		}
		
		foreach ($pages as $page => $data)
		{
			$class_admin = $data['admin'] ?  'plugin_list_admin' : '';

			$html .= '
	<tr class="plugin_list_pagerow">
	<td class="'.$class_admin.'">
		<div class="plugin_list_pagename"><a href="'. h(get_page_url($page)). '">' . $data['sanitized'] . $data['title'] . '</a></div>
		<div class="plugin_list_commands">';
		
			$cmds = array();
			foreach (plugin_list_get_commands($page) as $cmd => $cmddata)
			{
				$fmt = $cmddata['format'];
				$label = $cmddata['label'];
				
				$cmds[] = '
			<a href="'. h(sprintf($fmt, $script, $data['urlencoded'])) .'" class="plugin_list_page_'. h($cmd) .'">'. h($label) .'</a>';
			}
			$html .= join(' | ', $cmds);

			$html .= '
		</div>
		' . $data['passage'];
			if ($withfilename)
			{
				$html .= '
	    <div class="plugin_list_filename">ファイル名： '. h($data['filename']). '</div>   ';
			}
			$html .= '
	</td>
	<td class="plugin_list_mtime">
		'. h($data['mtime']) .'
	</td>
	</tr>';
		}
	}

	
	$body = '
<div class="plugin_list">
	<div class="page-header">ページの一覧</div>
	<div id="plugin_list_index">
		<ul class="pagination pagination-mini">
	'. join('', $indexies). '
		</ul>
	</div>
	<table class="table table-condensed">
	<thead>
		<tr>
			<th>
			  <form class="form-inline">
			    <div class="">
			      <label class="col-sm-5 control-label" style="margin-top:10px;">ページ名（タイトル）</label>
            <div class="input-group col-sm-7">
              <span class="input-group-addon input-sm">検索</span>
              <input type="search" size="20" id="plugin_list_searchbox" placeholder="例：FrontPage" class="form-control input-sm" />
            </div>
          </div>
        </form>
      </th>
			<th class="text-center">最終更新日</th>
		</tr>
	</thead>
	<tbody>
	'. $html. '
	</tbody>
	</table>
</div>
<hr />
<a href="#" class="switcher" style="color:inherit;" data-admin-visible="hide"><i class="icon icon-plus-sign"></i> 管理者用ページ</a>
';

	$beforescript = '
<script type="text/javascript" src="'.JS_DIR.'jquery.searchable.js"></script>
<script type="text/javascript">
$(function(){
	$("#plugin_list_index a:nth-child(16n)").after("<br />");

	$("tr.plugin_list_pagerow").mouseenter(function(e){
		e.stopPropagation();
		$("div.plugin_list_commands", this).animate(
			{opacity:1},
			{duration: "fast"});
	});
	$("tr.plugin_list_pagerow").mouseleave(function(e){
		e.stopPropagation();
		$("div.plugin_list_commands", this).animate(
			{opacity:0},
			{duration: "fast"});
	});
	
	$("tr.plugin_list_navi").each(function(){
		var len = $(this).nextUntil(".plugin_list_navi").find("td:first-child:not(.plugin_list_admin)").length;
		$("td", this).attr("data-non-admin-num", len);
	});
	
	switch_admin(false);

	
	$("#plugin_list_searchbox")
	.searchable("div.plugin_list table > tbody > tr", {
		selector: "td:first-child:not([colspan]):not(.plugin_list_admin)"
	})
	.focus().select();

	
	$("input[type=search]").bind("click.searchable", function(){
		if ($(".switcher").attr("data-admin-visible") == "hide") {
			switch_admin(false);
		}
	}).bind("keyup.searchable", function(){
		if ($(".switcher").attr("data-admin-visible") == "hide") {
			switch_admin(false);
		}
	});

	$(".switcher").click(function(){
		if ($(this).attr("data-admin-visible") == "hide") {
			$(this).children("i").removeClass("icon-plus-sign").addClass("icon-minus-sign");
			$(this).attr("data-admin-visible", "show");
			switch_admin(true);

			$("#plugin_list_searchbox").unbind("searchable")
				.searchable("div.plugin_list table > tbody > tr", {
					selector: "td:first-child:not([colspan])"
				})
				.focus().select();
		}
		else {
			$(this).children("i").removeClass("icon-minus-sign").addClass("icon-plus-sign");
			$(this).attr("data-admin-visible", "hide");
			switch_admin(false);

			$("#plugin_list_searchbox").unbind("searchable")
				.searchable("div.plugin_list table > tbody > tr", {
					selector: "td:first-child:not([colspan]):not(.plugin_list_admin)"
				})
				.focus().select();
		}

		return false;
	});
	
	function switch_admin(on)
	{
		var $tr = $("td.plugin_list_admin").closest("tr");
		if (on) {
			$tr.show();
		}
		else {
			$tr.hide();
		}

		$("td[data-non-admin-num=0]").each(function(){
			var href = $("a", this).attr("id");
			var $li = $("a[href=#"+href+"]").closest("li");
			if (on){
				$li.show();
			}
			else {
				$li.hide();
			}
		}).closest("tr").hide();
	}
});
</script>';
	
	$qt->appendv_once('plugin_list', 'plugin_script', $beforescript);
	
	return $body;
}

function plugin_list_get_commands($page)
{
	$retarr = array(
		'read' => array(
			'format' => '%s?%s',
			'label' => '表示'
		),
		'edit' => array(
			'format' => '%s?cmd=edit&page=%s',
			'label' => '編集'
		),
		'diff' => array(
			'format' => '%s?cmd=diff&page=%s',
			'label' => '差分'
		),
		'backup' => array(
			'format' => '%s?cmd=backup&page=%s',
			'label' => 'バックアップ'
		),
		'rename' => array(
			'format' => '%s?cmd=rename&refer=%s',
			'label' => '名前変更'
		),
		'delete' => array(
			'format' => '%s?cmd=delete&page=%s',
			'label' => '削除'
		),
		'map' => array(
			'format' => '%s?cmd=map&refer=%s',
			'label' => 'マップ'
		),
		'template' => array(
			'format' => '%s?cmd=copy&refer=%s',
			'label' => '複製'
		),
	);
	
	if (PKWK_READONLY)
	{
		return array('read' => $retarr['read']);
	}

	if ( ! ss_admin_check())
	{
		unset($retarr['diff'], $retarr['backup'], $retarr['rename'], $retarr['map'], $retarr['template']);
		if ( ! check_editable($page, FALSE, FALSE))
		{
			unset($retarr['edit']);
		}
	}
	
	return $retarr;
}

/* End of file list.inc.php */
/* Location: /app/haik-contents/plugin/list.inc.php */