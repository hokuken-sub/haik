<?php
/**
 *   Form Log Viewer Plugin
 *   -------------------------------------------
 *   /plugin/form_log_viewer.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/05/10
 *   modified :
 *   
 */

function plugin_form_log_viewer_action()
{
	global $vars;
	
	$mode = isset($vars['mode']) ? $vars['mode'] : 'list';
	
	$func_name = 'plugin_form_log_viewer_' . $mode . '_';
	if (function_exists($func_name))
	{
		return $func_name();
	}
	
	return array('msg'=>'', 'body'=>'');

}

function plugin_form_log_viewer_list_()
{

	global $vars, $script, $defaultpage, $style_name, $admin_style_name;

	if ( ! ss_admin_check())
	{
		set_flash_msg(__('管理者のみアクセスできます。'), 'error');
		redirect($script);
		exit;
	}
	
	$qt = get_qt();
	$style_name = $admin_style_name;
	$qt->setv('template_name', 'filer');

	$id = isset($vars['id']) ? $vars['id'] : '';
	$page = isset($vars['refer']) ? $vars['refer'] : $defaultpage;
	$lognum = isset($vars['lognum']) ? $vars['lognum'] : 0;
	
	$form = form_read($id);

	$files = glob(CACHE_DIR.'form_'.$id.'*.log');
	natsort($files);
	$files = array_reverse($files);

	if (count($files)  == 0)
	{
		set_flash_msg(__('ログがありません。'), 'error');
		if (isset($vars['former']))
		{
			redirect($script.'?cmd=former');
		}
		redirect($page);
	}
	else if (array_key_exists($lognum, $files))
	{
		$logfile = $files[$lognum];
	}
	else
	{
		$logfile = CACHE_DIR . 'form_' . $id . '.log';
		$lognum = 0;
	}
	
	$log_lines = file($logfile);
	$logs = array();
	$key_arr = array('time', 'data');
	foreach ($log_lines as $i => $log)
	{
		if ( ! trim($log))
		{
			continue;
		}
		$log_arr = explode("\t", $log, 2);
		$log_data = array_combine($key_arr, $log_arr);
		$log_data['data'] = unserialize(base64_decode(trim($log_data['data'])));
		$logs[] = $log_data;
	}
	
	//thead
	$heads = array();
	foreach ($form['parts'] as $key => $item)
	{
		$heads[$key] = $item['label'];
	}
	$heads['form_url'] = __('URL');
	
	//データが現在の設定に沿っているか、確認
	//設定にない項目を持っている場合、overflow に入れる
	foreach ($logs as $i => $log)
	{
		$diff = array_diff_key($log['data'], $heads);
		
		$diffstr = '';
		foreach($diff as $key => $d)
		{
			$diffstr .= $key . ' : ' . $d . ', ';
		}
		if ($diffstr != '') $diffstr = substr($diffstr, 0, -2);
		$logs[$i]['overflow'] = $diffstr;
	}

	$tmpl_file = PLUGIN_DIR . 'form_log_viewer/list.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();	
	
	
	$msg = __('フォームのログ確認');
	$body = $body;
	
	return array(
		'msg' => $msg,
		'body' => $body
	);

}

function plugin_form_log_viewer_delete_()
{

}

function plugin_form_log_viewer_download_()
{
	global $script, $vars, $user_agent;
	
	$id = isset($vars['id']) ? $vars['id'] : '';
	$lognum = isset($vars['lognum']) ? $vars['lognum'] : 0;
	

	$form = form_read($id);


	$files = glob(CACHE_DIR.'form_'.$id.'*.log');
	natsort($files);
	$files = array_reverse($files);

	if (count($files)  == 0)
	{
		set_flash_msg(__('ログがありません。'), 'error');
		redirect($page);
	}
	else if (array_key_exists($lognum, $files))
	{
		$logfile = $files[$lognum];
	}
	else
	{
		$logfile = CACHE_DIR . 'form_' . $id . '.log';
		$lognum = 0;
	}
	
	$log_lines = file($logfile);
	$logs = array();
	$key_arr = array('time', 'data');
	foreach ($log_lines as $i => $log)
	{
		if ( ! trim($log))
		{
			continue;
		}
		$log_arr = explode("\t", $log, 2);
		$log_data = array_combine($key_arr, $log_arr);
		$log_data['data'] = unserialize(base64_decode(trim($log_data['data'])));
		$logs[] = $log_data;
	}
	
	//thead
	$heads = array();
	$heads['postdate'] = __('投稿日');
	foreach ($form['parts'] as $key => $item)
	{
		$heads[$key] = $item['label'];
	}
	$heads['form_url'] = __('URL');
	
	// ダウンロード
	$filename = 'form_'.$id.(($lognum == 0) ? '' : '.'.$lognum).'.csv';
	header("Cache-Control: public");
	header("Pragma: public");
	header("Accept-Ranges: none");
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename={$filename}");
	header("Content-Type: application/octet-stream; name={$filename}"); 

	$fp = fopen("php://output", "w");

	ob_start();
	fputcsv($fp, array_keys($heads));

	foreach ($logs as $i => $log)
	{	

		$tmp = array();
		$log['data']['postdate'] = $log['time'];
		foreach ($heads as $key => $val)
		{
			$tmp[$key] = isset($log['data'][$key]) ? $log['data'][$key] : '';
		}
		
		fputcsv($fp, $tmp);
	}
	$contents = ob_get_clean();

	fclose($fp);

	if (preg_match('/Windows (NT [\d.]+|98)/', $user_agent['agent']))
	{
		// windows
		echo mb_convert_encoding( $contents, 'SJIS-win', 'UTF-8');
	}
	else
	{
		// mac
		echo mb_convert_encoding( $contents, 'Shift_JIS', 'UTF-8');
	}
	
	
	exit;
}

/* End of file form_log_viewer.inc.php */
/* Location: /plugin/form_log_viewer.inc.php */