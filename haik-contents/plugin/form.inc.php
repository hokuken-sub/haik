<?php
/**
 *   フォームプラグイン
 *   -------------------------------------------
 *   form.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/04/24
 *   modified :
 *
 *   config/form_ID.php を読み込み、
 *   通知フォームを出力する。
 *   フォームからの投稿処理も行う。
 *   
 *   Usage :
 *   #form(ID)
 *   
 */

define('PLUGIN_FORM_UNLINK_TIME', 3600);
define('PLUGIN_FORM_TMP_PREFIX', 'form-attach-');
define('PLUGIN_FORM_PROTECT_CSRF', FALSE);
define('PLUGIN_FORM_TOKEN_FIELD', '__token__');

define('PLUGIN_FORM_DEBUG', 0);

function plugin_form_action()
{
	global $script, $vars;
	
	if (isset($vars['phase']) && $vars['phase'] === 'confirm')
	{
		if (plugin_form_check_token())
		{
			clear_onetime_token();
		}
		else
		{
			set_notify_msg(__('もう一度やり直してください。'), 'error');
			return '';
		}
		
		if (($error = plugin_form_validate()) !== TRUE)
		{
			$vars['form_error'] = $error;
			set_notify_msg(__('入力項目にエラーがあります。'), 'error');
			return '';
		}

		$_SESSION['form'] = plugin_form_merge_data();

		return array('msg'=> __('確認'), 'body' => plugin_form_get_confirm_html());
	}
	else if (isset($vars['phase']) && $vars['phase'] === 'send')
	{
		if (plugin_form_check_token())
		{
			clear_onetime_token();
		}
		else
		{
			set_notify_msg(__('もう一度やり直してください。'), 'error');
			return '';
		}
		
		return array('msg'=> __('送信完了'), 'body'=> plugin_form_send());
	}
	
}

function plugin_form_check_token()
{
	global $vars;
	
	if ( ! PLUGIN_FORM_PROTECT_CSRF) return TRUE;
	
	$recieved_token = $vars[PLUGIN_FORM_TOKEN_FIELD];
	$stored_token   = get_onetime_token();

	if ($recieved_token !== $stored_token)
	{
		return FALSE;
	}
	
	return TRUE;
}

function plugin_form_convert()
{
	global $script, $vars;
	$args = func_get_args();
	
	if (count($args) == 0)
	{
		if (ss_admin_check())
		{
			set_flash_msg(_('フォームが指定されていません') ,'error');
		}
		return '';
	}

	$id = trim($args[0]);

	$html = plugin_form_get_html($id);
	
	if (check_editable($vars['page'], FALSE, FALSE))
	{
		$form = form_read($id);
		$loglink = '';
		$r_page = rawurlencode($vars['page']);
		
		//ログが有効であれば、ログ閲覧ページへのリンクを出す
		if ($form['log'])
		{
			$loglink = '<br><a href="'. h($script . '?cmd=form_log_viewer&id='.rawurlencode($id).'&refer='.$r_page) .'">'. __('>> このフォームのログを見る。') .'</a>';
		}
		
		$html = $html . '
<div class="alert alert-info">
	<a href="#" class="close" data-dismiss="alert">&times;</a>
	
	<a href="'. h($script. '?cmd=former&mode=edit&id='. rawurlencode($id)). '&refer='. $r_page .'" class="">'. __('>> このフォームの設定を変更する。'). '</a>
	'. $loglink .'
	
</div>
';
	}
	
	return $html;
}


function plugin_form_get_html($id)
{
	$form_basename = 'form_'.$id;
    $form_cachename = CACHE_DIR . $form_basename . '.html';
    $form_filepath = CONFIG_DIR . $form_basename . '.php';

	$html = '';
    if (file_exists($form_cachename) && filemtime($form_cachename) > filemtime($form_filepath))
    {
    	$html = file_get_contents($tcachename);
    }
    else
    {
		$form = form_read($id);
		if ($form)
		{
			$html = plugin_form_create_html($form);
		}
    }

    return $html;
}

function plugin_form_create_html($form = array(), $parts_only = FALSE)
{
	global $script, $vars;

	$page = $vars['page'];
	$r_page = rawurlencode($page);

	// data があれば表示
	$data = (isset($vars['data']) && is_array($vars['data'])) ? $vars['data']: array();
	foreach ($data as $name => $value)
	{
		if ( ! isset($form['parts'][$name])) continue;
		
		$form['parts'][$name]['value'] = $value;
	}

	$preview = (isset($vars['preview']) && is_array($vars['preview']));
	// preview があればマージ
	$data = $preview ? $vars['preview']: array();
	foreach ($data as $name => $value)
	{
		if ( ! isset($form['parts'][$name])) continue;
		
		$form['parts'][$name] = $value;
	}
	
	
	$items = array();
	$attach = FALSE;
	
	$require_mark = sprintf('<span class="orgm-form-required">%s</span>', __('*'));
	
	$error = isset($vars['form_error']) ? $vars['form_error'] : array();
	
	//sort parts
	$order = array();
	foreach ($form['parts'] as $name => $item)
	{
		$order[$name] = $item['order'];
	}
	array_multisort($order, SORT_ASC, $form['parts']);
	
	//form style
	list($form_label_class, $form_control_class, $form_only_control_class) = plugin_form_get_style_classes($form['class']);
	
	foreach ($form['parts'] as $name => $item)
	{
		$tmpl_file = PLUGIN_DIR. 'form/'. $item['type'] . '.html';
		if ($preview && $item['type'] === 'hidden')
		{
			$tmpl_file = PLUGIN_DIR. 'form/'. $item['type'] . '_preview.html';
		}
		if ( ! file_exists($tmpl_file))
		{
			continue;
		}

		ob_start();
		include($tmpl_file);
		$items[$name] = ob_get_clean();
		
		if ($item['type'] == 'file')
		{
			$attach = TRUE;
		}
	}
	
	if ($parts_only)
	{
		return $items;
	}
	
	//CSRF対策
	$protect_csrf_field = '';
	if (PLUGIN_FORM_PROTECT_CSRF)
	{
		set_onetime_token();
		$nonce = get_onetime_token();
		
		$protect_csrf_field = '<input type="hidden" name="'. h(PLUGIN_FORM_TOKEN_FIELD) .'" value="'. h($nonce) .'">';
	}
	
	$html  = '<div class="orgm-form">
	<form method="post" action="'.h($script).'?'.$r_page.'" class="'.h($form['class']).'"'.($attach ? ' enctype="multipart/form-data"' : '') .'>
		'.join("\n", $items).'
		<div class="form-group form-actions">
			<div class="'. h($form_only_control_class) .'">
				<input type="submit" value="'.__('確認').'" class="btn btn-primary">
			</div>
		</div>
		'. $protect_csrf_field .'
		<input type="hidden" name="cmd" value="form">
		<input type="hidden" name="phase" value="confirm">
		<input type="hidden" name="id" value="'.$form['id'].'">
		<input type="hidden" name="page" value="'.h($page).'">
	</form>
</div>
';
	
	return $html;
}

function plugin_form_get_style_classes($style = '')
{
	
	$form_control_class = $form_label_class = $form_only_control_class = 'col-sm-12';

	if ($style === 'form-horizontal')
	{
		$form_control_class = 'col-sm-9';
		$form_label_class = 'col-sm-3';
		$form_only_control_class = 'col-sm-9 col-sm-offset-3';
	}
	
	return array($form_label_class, $form_control_class, $form_only_control_class);
}

function plugin_form_validate()
{
	global $script, $vars;
	
	$form = form_read($vars['id']);
	$data = $vars['data'];
	
	$error = array();
	foreach ($form['parts'] as $name => $item)
	{
		$value = isset($data[$name]) ? $data[$name] : '';
		
		if ($item['type'] === 'file')
		{
			//file upload error
			if (isset($_FILES['data']['error'][$name]) && $_FILES['data']['error'][$name] > 0)
			{
				switch ($_FILES['data']['error'][$name])
				{
					case UPLOAD_ERR_OK:
					case UPLOAD_ERR_NO_FILE:
						break;
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$error[$name] = __('アップロードされたファイルが大きすぎます。');
						break;
					default:
						$error[$name] = __('アップロードに失敗しました。');
				}
			}
			
			if (isset($error[$name])) continue;
			
			$value = isset($_FILES['data']['name'][$name]) ? $_FILES['data']['name'][$name] : '';
			
		}
		//check list item
		else if (in_array($item['type'], array('checkbox', 'radio', 'select')) && $value !== '')
		{
			if (is_array($value))
			{
				foreach ($value as $v)
				{
					if ( ! in_array($v, $item['options']))
					{
						$error[$name] = __('リスト内の項目を選択してください。');
					}
				}
			}
			else if ( ! in_array($value, $item['options']))
			{
				$error[$name] = __('リスト内の項目を選択してください。');
			}
		}
		
		if ($item['required'] == TRUE)
		{
			if ($value === '')
			{
				$error[$name] = __('必須項目です。');
			}
		}
		
		foreach (explode(',', $item['validation']) as $rule)
		{

			if ($rule === 'int' && $value != '')
			{
				if ( ! preg_match('/^\d+$/', $value))
				{
					$error[$name] = __('整数を入力してください');
				}
			}
			if ($rule === 'number'  && $value != '')
			{
				if ( ! is_numeric($value))
				{
					$error[$name] = __('数字を入力してください');
				}
			}
			if ($rule === 'alnum'  && $value != '')
			{
				if ( ! ctype_alnum($value))
				{
					$error[$name] = __('半角英数を入力してください');
				}
			}
			if ($rule === 'email'  && $value != '')
			{
				if ( ! is_email($value))
				{
					$error[$name] = __('メールアドレスを正しく入力してください');
				}
			}
			if ($rule === 'bool' &&  ! $value)
			{
				$error[$name] = __('チェックが必要です');
			}
		}
	}
	
	if (count($error) > 0)
	{
		return $error;
	}
	
	
	return TRUE;
}


function plugin_form_merge_data()
{
	global $vars;
	
	$form = form_read($vars['id']);
	$data = $vars['data'];
	$files = isset($_FILES['data']) ? $_FILES['data'] : array();

	$ret = array();
	
	foreach ($form['parts'] as $name => $item)
	{
		
		if (isset($data[$name]))
		{
			$ret[$name] = $data[$name];
		}
		else if (isset($files['name'][$name]) && $files['error'][$name] !== UPLOAD_ERR_NO_FILE)
		{
			$file_path = tempnam(CACHE_DIR, PLUGIN_FORM_TMP_PREFIX);
			move_uploaded_file($files['tmp_name'][$name], $file_path);
			$ret[$name] = array(
				'name' => $files['name'][$name],
				'path' => $file_path,
				'type' => $files['type'][$name],
			);
		}
	}
	
	return $ret;

}


/**
 * 添付された一時ファイルを消す
 */
function plugin_form_delete_tmp_files()
{


}

function plugin_form_get_confirm_html()
{
	global $script, $vars;

	$page = $vars['page'];
	$r_page = rawurlencode($page);
	
	$form = form_read($vars['id']);
	$form_id = 'orgm_form_confirm';
	$data = $_SESSION['form'];

	$tr = array();
	foreach ($form['parts'] as $name => $item)
	{
		if ($item['type'] == 'hidden')
		{
			continue;
		}
		if (is_array($data[$name]))
		{
			$value = join(", ", $data[$name]);
		}
		else
		{
			$value = isset($data[$name]) ? $data[$name] : '';
		}

		if ($item['type'] == 'agree')
		{
			$value = $item['label'];
		}
		
		if ($item['type'] === 'file')
		{
			$item_data = isset($data[$name]) ? $data[$name] : array('name' => '');
			$value = $item_data['name'];
			$value_ = $value;
			if (preg_match('/^image\//i', $item_data['type']))
			{
				$img_base64 = base64_encode(file_get_contents($item_data['path']));
				$value_ = '<img src="data:'. h($item_data['type'] . ';base64,' . $img_base64) .'" alt="'. h($item_data['name']) .'" class="img-polaroid" style="max-height:120px">';
			}
		}
		else
		{
			$value_ = nl2br(h($value));
		}

		$tr[] = '
		<tr>
			<th>' . h($item['label']) . '</th>
			<td>' . $value_. '</td>
		</tr>
';

	}
	
	$cancel_values = '';
	foreach ($data as $name => $value)
	{
		if (is_array($value))
		{
			foreach ($value as $v)
			{
				$cancel_values .= sprintf('<input type="hidden" name="data[%s][]" value="%s">'."\n", h($name), h($v));
			}
		}
		else
		{
			$cancel_values .= sprintf('<input type="hidden" name="data[%s]" value="%s">'."\n", h($name), h($value));
		}
	}

	//CSRF対策
	$protect_csrf_field = '';
	if (PLUGIN_FORM_PROTECT_CSRF)
	{
		set_onetime_token();
		$nonce = get_onetime_token();
		
		$protect_csrf_field = '<input type="hidden" name="'. h(PLUGIN_FORM_TOKEN_FIELD) .'" value="'. h($nonce) .'">';
	}


	$html = '
<div class="orgm-form-confirm">

<p>'. __('以下の内容を確認の上、よろしければ「送信」をクリックしてください。'). '</p>
<br>

<table class="table">
	<tbody>
	'.join("", $tr).'
	</tbody>
</table>
<form method="post" action="'.h($script).'?'. $r_page .'" class="'.h($form['class']).'">
	<div class="form-actions">
		<input type="submit" class="btn btn-primary" value="' . h($form['button']) . '">
		<button type="button" class="btn btn-default" onclick="$(\'#orgm_form_confirm_cancel\').submit()">' . __('キャンセル') . '</button>
	</div>
	'. $protect_csrf_field .'
	<input type="hidden" name="cmd" value="form" />
	<input type="hidden" name="phase" value="send" />
	<input type="hidden" name="id" value="'.$form['id'].'">
	<input type="hidden" name="page" value="'.h($page).'" />
</form>
<form id="'. h($form_id) .'_cancel" method="post" action="'.h($script).'?'. $r_page .'" class="hide">
'.$cancel_values.'
</form>
</div>
';

	return $html;
}

function plugin_form_send()
{
	global $script, $vars;

	$page = $vars['page'];
	$r_page = rawurlencode($page);

	$form = form_read($vars['id']);
	
	if ( ! isset($_SESSION['form']) OR ! is_array($_SESSION['form']) OR ! $_SESSION['form'])
	{
		set_flash_msg(__('送信を完了できませんでした。もう一度最初からやり直してください。'), 'warning');
		redirect($page);
	}
	
	$data = $_SESSION['form'];
	
	// ! メール準備
	$files = array();
	$all_post_data = array();
	$all_post_data_admin = array();
	$merge_tags = $merge_tags_html = array();
	foreach ($form['parts'] as $name => $item)
	{
		$value = isset($data[$name]) ? $data[$name] : '';
		
		if ($item['type'] == 'file')
		{
			if ($value !== '')
			{
				$files[$name] = $value;
			}
			$value = $value['name'];
		}
		else if ($item['type'] == 'agree')
		{
			$value = $item['label'];
		}

		if (is_array($value))
		{
			$value = join(", ", $value);
		}
		

		$merge_tags[$name] = $value;
		$merge_tags_html[$name] = h($value);


		$all_post_data_admin[] = $item['label'].' : '.$value;
		if ($item['type'] != 'hidden')
		{
			$all_post_data[] = $item['label'].' : '.$value;
		}
		
		
	}
	$merge_tags['form_url'] = $script.'?'.$r_page;

	// !ログ出力
	if ($form['log'])
	{
		$ret = plugin_form_log_write($form, $merge_tags);
		if (! $ret)
		{
			//管理者にエラーメール
		}
	}

	// ! メール送信
	if (array_key_exists('EMAIL', $form['parts']) && $data['EMAIL'] != '')
	{
		$subject = $form['mail']['reply']['subject'];
		$body = $form['mail']['reply']['body'];
		$merge_tags['all_post_data'] = join("\n", $all_post_data);
		orgm_mail_send($subject, $body, $data['EMAIL'], $merge_tags, $form['reply']);
	}

	// ! 管理者へメール送信
	$subject = $form['mail']['notify']['subject'];
	$body = $form['mail']['notify']['body'];
	$merge_tags['all_post_data'] = join("\n", $all_post_data_admin);
	plugin_form_mail_notify($subject, $body, $merge_tags, $files);

	// ! POST 送信
	if (isset($form['post']['url']) && $form['post']['url'] !== '')
	{
		plugin_form_send_post($merge_tags);
	}

	// ! 完了メッセージ
	require_once(LIB_DIR . 'simplemail.php');
	$smail = new SimpleMail();
	$smail->set_merge_tags($merge_tags_html);
	$message = $smail->replace_merge_tags($form['message']);
	$html = convert_html($message);
	
	
	// ! 終了処理
	plugin_form_finish();
	
	return $html;

}

function plugin_form_mail_notify($subject, $message, $merge_tags = NULL, $files = array())
{
	require_once(LIB_DIR . 'simplemail.php');
	
	global $username;
	global $smtp_server, $smtp_auth, $pop_server, $mail_userid, $mail_passwd, $mail_encode;
	
	$mailaddress = $username;
	
	$mail = new SimpleMail();
	$mail->set_encode($mail_encode);
	
	$mailer_name = sprintf(__('%s 通知'), APP_NAME);
	
	$mail->set_params($mailer_name, $mailaddress);
	$mail->set_to($mailer_name, $mailaddress);
	
	$mail->set_smtp_server($smtp_server);
	$mail->set_smtp_auth($smtp_auth, $mail_userid, $mail_passwd, $pop_server);
	$mail->subject = $subject;
	
	foreach ($files as $file)
	{
		$mail->add_attaches($file['name'], $file['path']);
	}
	
	return $mail->send($message, $merge_tags);
}

function plugin_form_send_post($merge_tags)
{
	global $vars;
	$form = form_read($vars['id']);

	$url = trim($form['post']['url']);
	$encode = trim($form['post']['encode']) ? $form['post']['encode'] : 'UTF-8';
	$fields = $form['post']['data'];
	
	if ($url === '' OR count($fields) === 0)  return FALSE;
	
	$mail = new SimpleMail();
	$mail->set_merge_tags($merge_tags);
	
	$postdata = array();
	
	foreach ($fields as $field)
	{
		$postdata[$field['key']] = $mail->replace_merge_tags($field['value']);
	}
	
	mb_convert_variables($encode, 'UTF-8', $postdata);
	
	http_request($url, 'POST', '', $postdata);
	
	return TRUE;
	
}

function plugin_form_finish()
{
	global $vars;
	$form = form_read($vars['id']);
	
	//添付ファイル削除
	foreach ($form as $name => $item)
	{
		if ($item['type'] === 'file')
		{
			$filepath = $_SESSION['form'][$name]['path'];
			if (file_exists($file_path)) unlink($file_path);
		}
	}
	
	$limit = time() - PLUGIN_FORM_UNLINK_TIME;
	foreach (glob(CACHE_DIR . PLUGIN_FORM_TMP_PREFIX . '*') as $filepath)
	{
		if (filemtime($filepath) < $limit)
		{
			unlink($filepath);
		}
	}
	
	unset($_SESSION['form']);


}

/**
 * ログをCSVで保存する。
 * ログファイル名は CACHE_DIR/form_[FORM_ID].log
 * 書式は timestamp pagename email json:data
 */
function plugin_form_log_write($form, $data)
{
	global $vars, $script;

	$page = $vars['page'];
	$url = $script . '?' . rawurlencode($page);
	
	$id = $form['id'];
	
	
	$logfile = CACHE_DIR . 'form_'. $id . '.log';

	// ファイルサイズチェック
	if (filesize($logfile) >  1048576)
	{
		plugin_form_log_rotation($id);
	}
	
	$logs = file_exists($logfile) ? file($logfile) : array();

	$logdata = array(
		'time' => date('Y-m-d H:i:s'),
		'data' => base64_encode(serialize($data))
	);

	$str = join('', $logs) . join("\t", $logdata) . "\n";
	
	$fp = fopen($logfile, 'a');
	if ( ! $fp)
	{
		return FALSE;	
	}
	
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	ftruncate($fp, 0);
	rewind($fp);
	fputs($fp, $str);
	flock($fp, LOCK_UN);
	fclose($fp);
	
	chmod($logfile, 0666);
	
	return TRUE;
} 

function plugin_form_log_rotation($id)
{
	$orgfile = CACHE_DIR.'form_'.$id.'.log';

	$files = glob(CACHE_DIR.'form_'.$id.'*.log');
	if (count($files) == 0)
	{
		return;
	}
	$newfile = CACHE_DIR.'form_'.$id.'.'.count($files).'.log';
	
	// 現在のファイルの名前変更
	rename($orgfile, $newfile);
	chmod($newfile, 0666);
	
	return;
}

/* End of file form.inc.php */
/* Location: /haik-contents/plugin/form.inc.php */