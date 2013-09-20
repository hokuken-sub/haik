<?php
/**
 *   Error Report
 *   -------------------------------------------
 *   /app/haik-contents/plugin/error_report.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/06/10
 *   modified :
 *   
 *   既知のエラーをユーザーへ知らせる。
 *   または、エラーがないかチェックする
 *   
 *   Usage :
 *   
 */

function plugin_error_report_action()
{

}

function plugin_error_report_set()
{
	global $script, $vars, $app_err, $app_start;

	if ( isset($app_start) && $app_start) return;
	if ( ! is_array($app_err) OR ! $app_err OR ! ss_admin_check()) return;
	
	$qt = get_qt();
	
	$html = '<div class="container">';
	
	// ! ディレクトリエラー
	if (isset($app_err['dirs']) && $app_err['dirs'])
	{
		$message = sprintf(__('フォルダに書き込み権限がありません：%s'), join(', ', $app_err['dirs']));
		$message .= '<br>' . __('このままでは編集ができません。権限の設定を修正してください。');
		set_notify_msg($message, 'error');
	}

	// ! ファイルエラー
	if (isset($app_err['files']) && $app_err['files'])
	{
		$message = sprintf(__('ファイルに書き込み権限がありません：%s'), join(', ', $app_err['files']));
		set_notify_msg($message, 'error');
	}

	// ! php エラー
	if (isset($app_err['php']))
	{
		//encoding error		
		if (isset($app_err['php']['mbstring.encoding_translation']) && $app_err['php']['mbstring.encoding_translation'])
		{
			$message = __('[PHP]mbstring.encoding_translation を Off にしてください。<br>入力した文字が文字化けしてしまう恐れがあります。');
			set_notify_msg($message, 'error');
		}
		
	}
	
	
}

/* End of file error_report.inc.php */
/* Location: /app/haik-contens/plugin/error_report.inc.php */