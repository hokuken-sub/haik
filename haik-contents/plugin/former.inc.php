<?php
/**
 *   Form setting Plugin
 *   -------------------------------------------
 *   former.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/02/14
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */

include_once(LIB_DIR . 'html_helper.php');

function plugin_former_init()
{
	if ( ! exist_plugin('form')) die(__('form プラグインが見つかりません。'));
}

function plugin_former_action()
{
	global $script, $vars, $style_name, $admin_style_name;
	global $is_plugin_page;


	if ( ! ss_admin_check())
	{
		set_flash_msg(__('管理者のみアクセスできます。'), 'error');
		redirect($script);
		exit;
	}
	$qt = get_qt();

	$is_plugin_page = true;

	$style_name = $admin_style_name;
	$qt->setv('template_name', 'filer');

	$css = '<link rel="stylesheet" href="'. PLUGIN_DIR .'former/former.css" />';
	$qt->appendv('plugin_head', $css);


	$plugin_script = '
<script type="text/javascript" src="'.PLUGIN_DIR.'former/former.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>
';
	$qt->appendv_once('plugin_former', 'plugin_script', $plugin_script);

	$mode = isset($vars['mode']) ? $vars['mode'] : '';
	$func_name = 'plugin_former_' . $mode . '_';
	
	if ($mode !== '' && function_exists($func_name))
	{
		return $func_name($id);
	}

	$qt = get_qt();
	$helper = new HTML_Helper();
	
	$page = isset($vars['refer']) ? $vars['refer'] : '';
	$r_page = rawurlencode($page);
	

	// ! View
	$json = array();
	$ec_script = $script . '?cmd=former&refer='. $r_page .'&mode=';
	$new_url = $ec_script . 'edit';

	$iframe_mode = isset($vars['iframe']) ? $vars['iframe'] : FALSE;
	
	if ($iframe_mode && exist_plugin('set_template'))
	{
		plugin_set_template_switch('iframe');
	}
	
	$json['deletable'] = ! $iframe_mode;
	$json['log_viewable'] = ! $iframe_mode;
	$json['iframe'] = $iframe_mode;
	
	$forms = plugin_former_get_forms();
	
	$tmpl_file = PLUGIN_DIR . 'former/index.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();	


	$msg = __('フォームの編集');

	//actions
	$json = array_merge($json, array(
		'editUrl' => $ec_script . 'edit',
		'deleteUrl' => $ec_script . 'delete',
		'logUrl' => $script . '?cmd=form_log_viewer&former&id=',
		'forms' => $forms,
	));
	$qt->setjsv(array('former'=> $json));

	return array('msg'=>$msg, 'body'=>$body);
}

/**
 * body_last にファイル選択用の iframe を追加する。
 *
 */
function plugin_former_set_iframe()
{
	global $script, $vars;
	static $called = FALSE;
	
	if ($called) return FALSE;
	$called = TRUE;
	
	$qt = get_qt();
	
	$url = $script . '?cmd=former&iframe=1';
	
	$html = '
<div class="modal fade" id="orgm_former_selector" role="dialog" tabindex="-1" aria-labelledby="haik former window" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" data-dismiss="modal" class="close" aria-hidden="true">&times;</button>
				<h4>フォーム選択</h4>
			</div>
			<div class="modal-body">
				<iframe data-url="'. h($url) .'"></iframe>
			</div>
			<div class="modal-footer">
				<a href="#" data-cancel class="btn">キャンセル</a>
			</div>
		</div>
	</div>
</div>';
	
	$qt->appendv('body_last', $html);
	
}


function plugin_former_get_forms()
{
	//すべてのブログ記事ファイルを配列に格納
	$file_prefix = 'form_';
	$files = glob(CONFIG_DIR . $file_prefix.'*');

	
	$forms = array();
	foreach ($files as $file)
	{
		$id = substr(basename($file, '.php'), strlen($file_prefix));
		$forms[$id] = form_read($id);
	}

	return $forms;
}


function plugin_former_edit_()
{
	global $script, $vars, $username, $site_title;
	
	$qt = get_qt();
	$id = isset($vars['id']) ? $vars['id'] : '';
	
	//新規作成
	$form = form_read($id);
	if ( ! $form)
	{
		$form = plugin_former_get_form_default();
		
		if ($id)
		{
			$form['id'] = $id;
			set_notify_msg('フォームを作成してください。<br>「更新」するまで保存されません。', 'info');
		}
		
	}
	
	//sort parts
	$order = array();
	foreach ($form['parts'] as $name => $item)
	{
		$order[$name] = $item['order'];
	}
	array_multisort($order, SORT_ASC, $form['parts']);
	
	$page = isset($vars['refer']) ? $vars['refer'] : '';
	$r_page = rawurlencode($page);
	
	$list_url = $script . '?cmd=former&refer=' . $r_page;
	
	$msg = __('フォームの編集');
	
	//include javascript
	$addscript = '
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>
';
	$qt->appendv('plugin_script', $addscript);
	
	$tmpl_file = PLUGIN_DIR . 'former/edit.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();	

	//actions
	$ec_script = $script . '?cmd=former&refer='. $r_page .'&mode=';
	$json = array(
		'getPartsUrl' => $ec_script . 'preview',
		'cancelUrl'   => $ec_script . 'cancel',
		'echoPostUrl' => $ec_script . 'echo_post',
		'updateUrl'   => $ec_script . 'update&id='.$id,
		'form' => $form,
		'partsOptions' => plugin_former_get_parts_options()
	);
	$qt->setjsv(array('former'=> $json));



	return array('msg'=>$msg, 'body'=>$body);

}

function plugin_former_delete_()
{
	global $script, $vars;
	
	$qt = get_qt();
	$id = isset($vars['id']) ? $vars['id'] : '';
	
	$form = form_read($id);
	if ( ! $form)
	{
		print_json(array('error'=>1, 'message'=>'フォームを削除できませんでした'));
		exit;		
	}

	// config/form_xxxx の削除
	$form_file = CONFIG_DIR .'form_'.$id.'.php';
	if (file_exists($form_file))
	{
		unlink($form_file);
	}

	// 投稿ログの削除
	$files = glob(CACHE_DIR.'form_'.$id.'*.log');
	foreach($files as $file)
	{
		unlink($file);
	}
	
	print_json(array('error'=>0, 'message'=>'フォームを削除しました'));
	exit;
}



function plugin_former_preview_()
{
	global $script, $vars;
	
	$data = $vars['preview'];
	$class = $vars['class'];
	
	$form = array(
		'id' => 'preview',
		'parts' => $data,
		'class' => $class,
	);

	if ( ! exist_plugin('form')) die('cannot find the plugin: form');
	
	$items = plugin_form_create_html($form, TRUE);
	$res = array();
	foreach ($items as $name => $item)
	{
		$res[] = array(
			'html' => $item,
			'name' => $name,
			'type' => $data[$name]['type'],
		);
	}

	print_json($res);
	exit;

}

function plugin_former_cancel_()
{
	global $vars, $script;
	
	$refer = (isset($vars['refer']) && strlen($vars['refer']) > 0) ? $vars['refer'] : FALSE;
	
	if (is_page($refer))
	{
		redirect($refer);
	}
	else
	{
		//form list
		redirect($script . '?cmd=former');
	}

}

function plugin_former_echo_post_()
{
	$data = $_POST;
	print_json($data);
	exit;
}

function plugin_former_update_()
{
	global $vars, $script;
	
	$refer = (isset($vars['refer']) && strlen($vars['refer']) > 0) ? $vars['refer'] : FALSE;

	$form = isset($vars['form']) ? $vars['form'] : array();
	$form_default = plugin_former_get_form_default();
	unset($form_default['parts']);
	
	$form = array_merge_deep($form_default, $form);

	// ! validation
	//id
	if ($form['id'] == '' OR ! preg_match('/^[0-9a-z_-]+$/', $form['id']))
	{
		//エラー
		print_json(array('error'=> __('フォームIDを正しく入力してください')));
		exit;
	}

	// parts
	$parts_options = plugin_former_get_parts_options();
	foreach ($form['parts'] as $name => $item)
	{
		if ($item['type'] !== 'email' && $name  === 'EMAIL')
		{
			//エラー
			print_json(array('error' => __('メールアドレス以外のフォームで、IDにEMAILを指定できません')));
			exit;
		}
		
		$part_options = $parts_options[$item['type']];
		$item = array_intersect_key($item, array_flip($part_options['ui']));
		$item = array_merge_deep($part_options['default'], $item);
		$form['parts'][$name] = $item;
	}
	
	//post[url]
	$form['post']['url'] = trim($form['post']['url']);
	if ($form['post']['url'] !== '' && ! is_url($form['post']['url']))
	{
		//エラー
		print_json(array('error'=> __('連動登録設定のURLが正しくありません')));
		exit;
	}
	
	//notify[to]
	//reply[from_email]
	if (($form['mail']['notify']['to'] !== '' && ! is_email($form['mail']['notify']['to'])) OR
		($form['mail']['reply']['from_email'] !== '' && ! is_email($form['mail']['reply']['from_email'])))
	{
		print_json(array('error'=>__('通知先・送信元メールアドレスに正しくメールアドレスを入力しているかご確認ください')));
		exit;
	}
	
	//post[data]
	if (count($form['post']['data']) > 0)
	{
		$post_data = array();
		foreach ($form['post']['data'] as $i => $data)
		{
			if (trim($data['key']) !== '')
			{
				$post_data[] = $data;
			}
		}
		$form['post']['data'] = $post_data;
	}
	
	
	

	// フォームを書き込む
	form_write($form['id'], $form);


	// idが変更された場合、
	if ($form['id'] !== $vars['id'])
	{
		//古いファイルを消す
		unlink(CONFIG_DIR .'form_'.$vars['id'].'.php');
	}


	$data = array(
		'success' => 1,
		'refer' => $refer,
		'id' => $form['id'],
	);
	
	set_flash_msg(__('フォームの設定を保存しました。'));

	print_json($data);
	exit;	
}


function plugin_former_get_parts_options()
{

	return array(
		'text' => array(
			'ui' => array('id','label','order','help','value','size','required','validation','placeholder','before','after'),
			'default' => array(
				'type' => 'text',
				'label' => '',
				'help'  => '',
				'value' => '',
				'size' => '',
				'placeholder' => '',
				'before' => '',
				'after' => '',
				'validation' => 0,
				'required' => 0,
			),
		),
		'email' => array(
			'ui' => array('id','label','order','help','required','placeholder'),
			'default' => array(
				'type' => 'email',
				'label' => 'メールアドレス',
				'help'  => '',
				'value' => '',
				'size' => 'col-sm-6',
				'placeholder' => '',
				'validation' => 'email',
				'required' => 0,
			),
		),
		'textarea' => array(
			'ui' => array('id','label','order','help','value','size','required','placeholder','rows'),
			'default' => array(
				'type' => 'textarea',
				'label' => '',
				'value' => '',
				'size' => 'col-sm-6',
				'rows' => 5,
				'placeholder' => '',
				'required' => 0,
			)
		),
		'checkbox' => array(
			'ui' => array('id','label','order','help','value','required','options', 'layout'),
			'default' => array(
				'type' => 'checkbox',
				'label' => '',
				'value' => '',
				'required' => 0,
				'options' => array(),
				'layout' => 'horizontal',
			)
		),
		'radio' => array(
			'ui' => array('id','label','order','help','value','required','options', 'layout'),
			'default' => array(
				'type' => 'radio',
				'label' => '',
				'value' => '',
				'required' => 0,
				'options' => array(),
				'layout' => 'horizontal',
			)
		),
		'select' => array(
			'ui' => array('id','label','order','help','value','required','options', 'empty'),
			'default' => array(
				'type' => 'select',
				'label' => '',
				'value' => '',
				'required' => 0,
				'empty' => '',
				'options' => array(),
			)
		),
		'hidden' => array(
			'ui' => array('id','value','order'),
			'default' => array(
				'type' => 'agree',
				'value' => '0',
				'required' => 1,
			),
		),
		'file' => array(
			'ui' => array('id','label','order','help','required'),
			'default' => array(
				'type' => 'file',
				'label' => '',
				'help'  => '',
				'required' => 0,
			),
		),
		'agree' => array(
			'ui' => array('id','label','order','help'),
			'default' => array(
				'type' => 'agree',
				'label' => '同意する',
				'help'  => '',
				'value' => '0',
				'required' => 1,
				'validation' => 'bool',
			),
		),
	);

}

function plugin_former_get_form_default()
{

	return array(
		'id' => '',
		'description' => '問い合わせ用',
		'class' => 'form-horizontal',
		'deletable' => 1,
		'mail' => array(
			'notify' => array(
				'subject' => '【通知】お問い合わせがありました',
				'body'    => 'お問い合わせがありました。

投稿内容：
*|ALL_POST_DATA|*


フォーム：
*|FORM_URL|*
',
				'to'      => '',
			),
			'reply' => array(
				'subject' => 'お問い合わせありがとうございます。',
				'body'    => '*|NAME|* 様
こんにちは。

お問い合わせ、ありがとうございました。
後ほどご連絡いたします。


以上です。

投稿内容：
*|ALL_POST_DATA|*
',
				'from_name' => '',
				'from_email' => '',
			),
		),
		'button' => '送信',
		'message' => '
* お問い合わせの完了

こんにちは。
*|NAME|* 様

お問い合わせ、ありがとうございました。
確認メールをお送りしましたので、
ご確認ください。

以上です。
',
		'log' => 1,
		
		'parts' => array(
			//text
			'NAME' => array(
				'type' => 'text',
				'label' => 'お名前',
				'help'  => '姓・名をご入力ください。',
				'value' => '',
				'size' => '',
				'placeholder' => '',
				'before' => '',
				'after' => '',
				'validation' => 0,
				'required' => 1,
				'order' => 0,
			),
			'EMAIL' => array(
				'type' => 'email',
				'label' => 'メールアドレス',
				'help' => 'メールアドレスをご入力ください。',
				'value' => '',
				'size' => 'col-sm-6',
				'before' => '',
				'after' => '',
				'placeholder' => 'メールアドレス',
				'validation' => 'email',
				'required' => 1,
				'order' => 1,
			),
			//textarea
			'MEMO' => array(
				'type' => 'textarea',
				'label' => '内容',
				'value' => '',
				'size' => 'col-sm-6',
				'placeholder' => '',
				'validation' => 0,
				'required' => 1,
				'rows' => 5,
				'order' => 2,
			),
		),
		
		'post' => array(
			'url' => '',
			'encode' => 'UTF-8',
			'data' => array()// [{key: "key", value: "value"}, ...]
		),
	
	);

}

?>
