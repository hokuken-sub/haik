<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: backup.inc.php,v 1.27 2005/12/10 12:48:02 henoheno Exp $
// Copyright (C)
//   2002-2005 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// Backup plugin

// Prohibit rendering old wiki texts (suppresses load, transfer rate, and security risk)
define('PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING', PKWK_SAFE_MODE || PKWK_OPTIMISE);

function plugin_backup_action()
{
	global $vars, $do_backup, $hr, $script;
	global $layout_pages, $style_name;
	$qm = get_qm();

	$editable = edit_auth($page, FALSE, FALSE);
    if(!$editable){
        header("Location: $script");
        exit();        
    }
    
	if (! $do_backup) return;

	$action = isset($vars['action']) ? $vars['action'] : '';
	$page = isset($vars['page']) ? $vars['page']  : '';
	$s_age  = (isset($vars['age']) && is_numeric($vars['age'])) ? $vars['age'] : 0;

	//ページが設定されていない場合、バックアップページ一覧を表示する
	if ($page == '') 
	{
		return array('msg'=>$qm->m['plg_backup']['title_backuplist'], 'body'=>plugin_backup_get_list_all());
	}

	check_readable($page, true, true);
	$s_page = h($page);
	$r_page = rawurlencode($page);

	if ($action == 'get_source')
	{
		plugin_backup_get_source($page, $s_age);
	} 
	if ($action == 'get_diff')
	{
		plugin_backup_get_diff($page, $s_age);
	} 
	if ($action == 'get_view')
	{
		plugin_backup_get_view($page, $s_age);
	}
	if ($action == 'delete')
	{
		plugin_backup_delete($page);
	}
	if ($action == 'restore')
	{
		plugin_backup_restore($page, $s_age);
	}

	$s_action = $r_action = '';
	if ($action != '') {
		$s_action = h($action);
		$r_action = rawurlencode($action);
	}

	// バックアップ一覧を表示
	if ($s_age <= 0)
	{
		$title = $is_layout ? (h($layout_pages[$page]).'のバックアップ一覧') : $qm->m['plg_backup']['title_pagebackuplist'];
		return array( 'msg'=> $title, 'body'=>plugin_backup_get_list($page));
	}

	return array('msg'=>str_replace('$2', $s_age, $title), 'body'=>$body);
}

// Delete backup
function plugin_backup_delete($page)
{

	$retval = array();
	if (_backup_file_exists($page))
	{
		_backup_delete($page);
		$retval['sts'] = 'success';
		print_json($retval);	
	}
	else
	{
		echo '';
	}
	exit;
}

// Restore backup
function plugin_backup_restore($page, $age)
{
	$retarr = array();
	$backups = _backup_file_exists($page) ? get_backup($page) : array();
	if (count($backups) > 0)
	{
		$old = join('', $backups[$age]['data']);
		$cur = join('', get_source($page));

		page_write($page, $old);

		$retarr['sts'] = 'success';
	    print_json($retarr);
	}
	else
	{
		echo '';
	}
	exit;
}

function plugin_backup_diff($str)
{
	$qm = get_qm();
	$info = <<<EOD
<div class="alert alert-info">
	<button type="button" class="close" data-dismiss="alert">×</button>
	{$qm->m['fmt_msg_addline']}<br />{$qm->m['fmt_msg_delline']}
</div>
EOD;

	return $info . '<pre>' . diff_style_to_css(h($str)) . '</pre>' . "\n";
}

function plugin_backup_get_list($page)
{
	global $layout_pages;

	$qm = get_qm();
	$qt = get_qt();

	$script = get_script_uri();
	$r_page = rawurlencode($page);
	$s_page = h($page);

	$plugin_script = '
<script type="text/javascript">
$(function(){

	$("#backup-list tbody tr").click(function(){
		var $tr = $(this);
		var s_age = $tr.attr("data-age");
		var action = "'.h($script).'";
		var data = {
			action : "get_view",
			age : s_age,
			page : "'.$s_page.'",
			plugin : "backup"
		};

		$.post(action, data, function(res){
			if (res)
			{
				$("#backupDocsContent").html(res.data);
				$("#backupModalLabel").html("'.$s_page.'："+res.time);
				var w = $("#backup-list").closest("div").width();
				$("#backupModal div.modal-dialog").css({
					"width" : w + 40
				});
				$("#backupModal").attr("data-age", s_age).modal();
			}
		}, "json");
	});

	$("#btnRestore").click(function(){
		var action = "'.h($script).'";
		var s_age = $("#backupModal").attr("data-age");
		var data = {
			action : "restore",
			age : s_age,
			page : "'.$s_page.'",
			plugin : "backup"
		};

		if ( ! window.confirm("'.$s_page.'ページを\nこのバックアップの状態に戻してよろしいですか？"))
		{
			return false;
		}

		$.post(action, data, function(res){
			if (res)
			{
				if (res.sts == "success")
				{
					$("#restoreCompleteAlert").show();
				}
				$("#backupModal").modal("hide");
			}
		}, "json");
	});
	
	$("#btnDeleteConfirm").click(function(){
		$("#deleteModal").modal();
	});
		
	$("#deleteModal button.btnDelete").click(function(){
		var action = "'.h($script).'";
		var data = {
			action : "delete",
			page : "'.$s_page.'",
			plugin : "backup"
		};

		$.post(action, data, function(res){
			if (res)
			{
				if (res.sts == "success")
				{
					$("#backup-list").remove();
					$("#deleteCompleteAlert").show();
				}
				$("#deleteModal").modal("hide");
			}
		}, "json");
	});
	
	$("#switchView").click(function(){
		var $view = $(this);
		var type = $(this).attr("data-view");
		
		var s_age = $("#backupModal").attr("data-age");
		var mode = (type == "diff") ? "get_view" : "get_diff";
		var action = "'.h($script).'";
		var data = {
			action : mode,
			age : s_age,
			page : "'.$s_page.'",
			plugin : "backup"
		};

		$.post(action, data, function(res){
			if (res)
			{
				$("#backupDocsContent").html(res.data);
				if (type == "diff")
				{
					$view.attr("data-view", "view").find("i").removeClass("icon-white");
				}
				else
				{
					$view.attr("data-view", "diff").find("i").addClass("icon-white");
				}
			}
		}, "json");
		return false;
	});
	
});
</script>
';

	$qt->appendv_once('plugin_backup', 'plugin_script', $plugin_script);

	
	//バックアップ一覧へのリンクは、
	//レイアウト部品の場合、編集リンクを表示する
	$retval = array();
	$editlink = h($script).'?cmd=edit&amp;page='.$r_page;
	$pagelink = h($script).'?'.$r_page;

	$title = array_key_exists($page, $layout_pages) ? $layout_pages[$page] : $s_page;
	
	$retval[0] = '
<p style="font-size:1.2em;"><a class="backup-title" href="'.$pagelink.'">'.$title.'</a>のバックアップ</p>
';
	$retval[1] = '
<!-- Delete Backup Complete -->
<div id="deleteCompleteAlert" class="alert alert-success hide">
	<button type="button" class="close" data-dismiss="alert">×</button>
	<h4 class="alert-heading">バックアップを削除しました</h4>
	<p><a href="'.h($script).'?'.$r_page.'>'.$s_page.'</a>のバックアップを削除しました。</p>
	<p>
    	<a class="btn" href="'.h($script).'?cmd=edit&amp;page='.$r_page.'">'.$s_page.'の編集画面へ</a>
    </p>
</div>
<!-- Restore Backup Complete -->
<div id="restoreCompleteAlert" class="alert alert-success hide">
	<button type="button" class="close" data-dismiss="alert">×</button>
	<h4 class="alert-heading">バックアップを復元しました</h4>
	<p><a href="'.h($script).'?'.$r_page.'">'.$s_page.'</a>をバックアップの状態に復元しました。<br />ページをご確認ください。</p>
	<p>
    	<a class="btn" href="'.h($script).'?cmd=edit&amp;page='.$r_page.'">'.$s_page.'の編集画面へ</a>
    </p>
</div>

<!-- Backup List -->
<table id="backup-list" class="table table table-hover">
	<thead>
		<tr>
			<td style="text-align:right;">
			<button id="btnDeleteConfirm" class="btnDelete btn btn-danger">バックアップの削除</button>
			</td>
		</tr>
	</thead>
	<tbody>
		$1
	</tbody>
</table>
';
	$retval[2] = '
<!-- Backup Modal -->
<div id="backupModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="bakupModalLabel" aria-hidden="true" data-age="'.h($age).'">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h5 id="backupModalLabel"></h5>
			</div>
			<div class="modal-body">
				<div class="bs-docs-example">
					<a href="#" id="switchView" class="switchButton" data-view="view">表示の切替<i class="icon-hand-up"></i></a>
					<div id="backupDocsContent"></div>
				</div>
			</div>
			<div class="modal-footer">
				<div style="float:left;height:30px;line-height:30px;"><a href="'.$script.'?cmd=edit&amp;page='.$r_page.'">このページの編集画面へ</a></div>
				<div style="float:right;">
					<button id="btnRestore" class="btn btn-primary">この内容で公開する</button>
					<button class="btn" data-dismiss="modal" aria-hidden="true">キャンセル</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="backup delete dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h5>バックアップの削除</h5>
			</div>
			<div class="modal-body">
				<p>'.$s_page.'ページのバックアップを削除します。<br />よろしいですか？</p>
			</div>
			<div class="modal-footer">
				<button class="btn" data-dismiss="modal" aria-hidden="true">キャンセル</button>
				<button class="btn btn-danger btnDelete">削除する</button>
			</div>
		</div>
	</div>
</div>
';

	$backups = _backup_file_exists($page) ? get_backup($page) : array();
	if (empty($backups)) {
		return '
<div id="deleteCompleteAlert" class="alert alert-warning">
	<button type="button" class="close" data-dismiss="alert">×</button>
	<h4 class="alert-heading">バックアップがありません</h4>
	<p><a href="'.h($script).'?'.$r_page.'">'.$s_page.'</a>のバックアップはありません。</p>
	<p><a class="btn" href="'.h($script).'?cmd=edit&amp;page='.$r_page.'"">'.$s_page.'の編集画面へ</a></p>
</div>
';
	}

	krsort($backups);
	$href = $script . '?cmd=backup&amp;page=' . $r_page . '&amp;age=';
	$backuplist_html = '';
	foreach ($backups as $age => $data)
	{
		$date = format_date($data['time'], FALSE);
		$backuplist_html .= '
		<tr data-age="'.h($age).'">
			<td><i class="icon icon-search"></i> '.h($date).'</td>
		</tr>
';
	}
	$retval[1] = str_replace('$1', $backuplist_html, $retval[1]);
	return join('', $retval);
}

function plugin_backup_get_source($page, $age)
{
	$retarr = array();
	$backups = _backup_file_exists($page) ? get_backup($page) : array();

	if (count($backups) > 0)
	{
		$retarr['data'] = join("", $backups[$age]['data']);
		$retarr['time'] = format_date($backups[$age]['time'], FALSE);
	    print_json($retarr);
	}
	else
	{
		echo '';
	}
	exit;
}

function plugin_backup_get_diff($page, $age)
{
	$retarr = array();
	$backups = _backup_file_exists($page) ? get_backup($page) : array();
	if (count($backups) > 0)
	{
		$old = join('', $backups[$age]['data']);
		$cur = join('', get_source($page));
		$retarr['data'] = plugin_backup_diff(do_diff($old, $cur));
		$retarr['time'] = format_date($backups[$age]['time'], FALSE);
	    print_json($retarr);
	}
	else
	{
		echo '';
	}
	exit;
}

function plugin_backup_get_view($page, $age)
{
	global $layout_pages;

	$retarr = array();

	$backups = _backup_file_exists($page) ? get_backup($page) : array();
	if (count($backups) > 0)
	{
		$data = convert_html($backups[$age]['data']);
		if (array_key_exists($page, $layout_pages))
		{
			switch($page)
			{
				case 'MenuBar':
					$data = '<div id="sidebar"><div id="menubar" class="bar">'.$data.'</div>';
					break;
				case 'SiteNavigator':
					$data = '<div id="navigator">'.$data.'</div>';
					break;
				case 'SiteNavigator2':
					$data = '<div id="navigator2">'.$data.'</div>';
					break;
			}
		}
		$retarr['data'] = $data;
		$retarr['time'] = format_date($backups[$age]['time'], FALSE);
	    print_json($retarr);
	}
	else
	{
		echo '';
	}
	exit;
}


// List for all pages
function plugin_backup_get_list_all($withfilename = FALSE)
{
	global $cantedit;

	$pages = array_diff(get_existpages(BACKUP_DIR, BACKUP_EXT), $cantedit);

	if (empty($pages)) {
		return '';
	} else {
		return page_list($pages, 'backup', $withfilename);
	}
}

/* End of file backup.inc.php */
/* Location: /app/haik-contents/plugin/backup.inc.php */