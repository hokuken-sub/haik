<?php
/**
 *   Download Button Plugin
 *   -------------------------------------------
 *   /plugin/download.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/04/19
 *   modified :
 *   
 *   ファイルをダウンロードさせるボタンやリンクを出せる。
 *   
 *   Usage : &download(ファイル名[[,通知フラグ],スタイル]){ラベル};
 *   
 */


function plugin_download_inline()
{
    global $vars, $script, $app_ini_path;
 
    $qm = get_qm();
    $page = $vars['page'];
    $r_page = rawurlencode($page);
    
    $args   = func_get_args();
    
    $label = array_pop($args);
    $args_num = count($args);
    
    if ($args_num < 1 OR 3 < $args_num)
    {
    	return __('error: &amp;download(file[[,notify],type]){label};');
    }
    
    list($filename, $notify, $type) = array_pad($args, 3, '');

	$filepath = is_url($filename) ? $filename : get_file_path($filename);
	
    //param check
    if ( ! is_url($filepath) && ! file_exists($filepath))
    {
    	return __('ファイルがみつかりません');
    }
    
    
    if ($label === '')
    {
        $label = __('ダウンロード');
    }
    else
    {
	    $label = preg_replace('/:([\w-]+):/', '<i class="icon-$1"></i>', $label);
    }

    $notify = ($notify == 'notify') ? 1 : 0;
    
    $class_str = get_bs_style(($type === '') ? 'default' : $type);

    //url decode
	$filename = rawurlencode(rawurlencode($filename));
    
    //ボタン作成
    $md5 = md5(file_get_contents($app_ini_path).filemtime(get_filename($page)));
    $dlurl = $script .'?cmd=download&refer='.$r_page.'&filename='.$filename.'&key='.$md5.'&notify='.$notify;
    
    $btn = '<a href="'. h($dlurl) .'" class="'. h($class_str) .'">'. $label .'</a>';
    
    return $btn;
}



function plugin_download_action(){
	
	global $vars, $script, $app_ini_path;
	
	$page = trim($vars['refer']);
	$filename = rawurldecode($vars['filename']);
	$key = $vars['key'];
	$notify = $vars['notify'];

    $src = get_source($page, TRUE, TRUE);
    if ( ! is_page($page) OR ! preg_match('/&download\('. preg_quote($filename, '/').'(?:,|\))/', $src))
    {
	    die(__('Invalid Request<br />不正なリクエストです。'));
    }
    
    $md5 = md5(file_get_contents($app_ini_path).filemtime(get_filename($page)));
    
    if ($md5 !== $key)
    {
	    return array(
	    	'msg' => __('ファイルのダウンロードができません。'),
	    	'body' => __('<b>ファイルのダウンロードができません。</b><br>もういちど、戻ってから試してください。'));
    }

    $filepath = get_file_path($filename);
	
	if ( ! is_url($filename) && ( ! file_exists($filepath) OR $filepath == ''))
	{
		return array(
			'msg'=> __('ファイルが見つかりません。'),
			'body'=> '<p>'. __('指定されたファイルは存在しません。'). '</p><pre>'. h($filepath) .'</pre>');
	}
    
	// メール通知
	if($notify)
	{
		$subject = __("ダウンロード通知");
		$message = __("ファイルがダウンロードされました。\n\nページ名：*|PAGE|*\nファイル名：*|FILE|*\nダウンロードページ：*|URL|*");
		$merge_tags = array(
			'page' => $page,
			'file' => $filename,
			'url'  => $_SERVER['HTTP_REFERER'],
		);
		orgm_mail_notify($subject, $message, $merge_tags);
	}
    
	//download file
	$fp = fopen($filepath, "rb");
	
	//get filename
	$tmparr = explode('?', basename($filepath));
	$filebasename = $tmparr[0];
	
	header("Cache-Control: public");
	header("Pragma: public");
	header("Accept-Ranges: none");
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=$filebasename");
	header("Content-Type: application/octet-stream; name=$filebasename"); 
	
	fpassthru($fp);
	fclose($fp);


	
	exit;
	
}


/* End of file download.inc.php */
/* Location: /plugin/download.inc.php */