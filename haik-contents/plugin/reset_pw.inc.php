<?php
/**
 * QHM パスワード変更プラグイン
 *
 */
if ( ! defined('ALLOW_PASSWD_PATTERN'))
{
	define('ALLOW_PASSWD_PATTERN', '/^[a-zA-Z0-9`~!@#$%^&*\(\)_\+=\{\}\[\]\|:;"\'<>,.?\/ -]+$/');
}

function plugin_reset_pw_action()
{
	global $script, $username, $vars, $get, $app_ini_path;
	global $admin_style_name, $style_name, $disable_site_auth;
	
	$qt = get_qt();

	$style_name = $admin_style_name;
	$qt->setv('template_name', 'narrow');
	
	if (is_login())
	{
		set_flash_msg(__("ログイン中です。<br>設定より変更してください。"));
		redirect($script);
	}

	if( ! is_writable($app_ini_path) )
	{
		set_flash_msg(__("設定ファイル config/haik.ini.php に書き込めません。<br>権限を設定してください（666）。"));
		redirect($script);
	}
	
	//サイト認証を無効にする
	$disable_site_auth = 1;
	
	// 再発行URLの送信
	if (isset($vars['mode']) && $vars['mode'] == 'send')
	{
		$retarr = plugin_reset_pw_send_remind();
	}
	else if (isset($vars['mode']) && $vars['mode'] == 'set')
	{
		// ユーザー名、パスワードを変更後、認証画面へ移動
		// ftp情報をリセット
		$retarr = plugin_reset_pw_reset_password();
	}
	else if (isset($get['code']) && $get['code'] != '')
	{
		// code送信後、フォームを表示
		$retarr = plugin_reset_pw_form_reset();
	}
	else
	{
		// フォームの表示 登録メールアドレスとボタン
		$retarr = plugin_reset_pw_form_remind();
	}

	return $retarr;
}


/**
* パスワード再発行のフォーム表示
*/
function plugin_reset_pw_form_remind($error_msg = '')
{
	global $script, $vars;

	if ($error_msg != '')
	{
		$error_msg = '<div class="alert alert-danger">'.$error_msg.'</div>';
	}
	
	$body = '
<div class="page-header">'.__('パスワードの再設定').'</div>
<div class="container-fluid">
	<p>'.__("パスワードをリセットするには、管理者メールアドレスを入力してください。<br>送信ボタンをクリックすると、管理者メールアドレスへメールを送信します。").'</p>
	<br>
	'.$error_msg.'
	<form method="post" action="'.h($script).'">
		<input type="hidden" name="mode" value="send" />
		<input type="hidden" name="plugin" value="reset_pw" />
		<div class="form-group">
			<label for="" class="control-label">'.__('メールアドレス').'</label>
			<div class="row">
				<div class="col-sm-6">
					<input type="text" name="reset_pw[email]" value="" class="form-control"  />
				</div>
			</div>
		</div>
		<div class="form-group">
			<input type="submit" class="btn btn-primary" value="'.__('送信').'" />
			<a href="'.$script.'" class="btn btn-default">キャンセル</a>
		</div>
	</form>
</div>
';

	return array('msg'=>__('パスワードの再設定'), 'body'=>$body);
}

function plugin_reset_pw_send_remind()
{
	global $script, $vars, $username;

	// 登録メールアドレスチェック
	if  (trim($vars['reset_pw']['email']) != $username)
	{
		$error = 'メールアドレスが登録されているものと異なります';
		return plugin_reset_pw_form_remind($error);
	}

	// orgm.ini.php の reset_pw_token を変更
	require_once(LIB_DIR."Mcrypt.php");
	$code = ORMcrypt::get_key(48);
	orgm_ini_write(array('reset_pw_token' => $code));

	// メール送信
	$reset_url = $script.'?cmd=reset_pw&code='.$code;
	$subject = __('パスワードの再発行');
	$message = __("パスワードの再発行をします。\n下記のURLをクリックしてください。\n*|URL|*\n");
	$merge_tags = array(
		'url' => $reset_url,
	);
	orgm_mail_notify($subject, $message, $merge_tags);
	
	set_flash_msg(__('パスワードの再発行：メールを送信しました<br>登録メールアドレスにメールを送信しました。'));
	redirect($script);
}

/**
* パスワード再設定のフォームを表示
*/
function plugin_reset_pw_form_reset($error = '')
{
	global $script, $vars, $reset_pw_token;
	global $username;

	$code = isset($vars['code']) ? trim($vars['code']) : '';;
	
	if ($code == '' OR $code != $reset_pw_token)
	{
		set_flash_msg(__('パスワードの設定ができません。<br />再度、パスワードの再発行を行ってください。'));
		redirect($script);
	}
	
	if ($error != '')
	{
		$error_msg = '<p class="text-error">'.$error.'</p>';
	}

	$qt = get_qt();
	
	//password checker を読み込む
	$passwdcheck_options = array(
		'rankWeakLabel'          => '単純すぎます',
		'rankBetterLabel'        => '簡単です',
		'rankGoodLabel'          => '安心です',
		'rankStrongLabel'        => '強力です',
		'rankGodLabel'           => '強力です！',
		'tooShortErrorLabel'     => '短すぎます',
		'tooLongErrorLabel'      => '長すぎます',
		'hasForbiddenCharsLabel' => '使用できない文字が含まれています',
	);
	
	$include_style = '
<style>
	.passwdcheck-placeholder label.control-label {
		padding-top:0;
	}
	.passwdcheck-placeholder .progress{
		height: 10px;
	}
</style>
';
	$qt->prependv_once('plugin_reset_pw_style', 'plugin_script', $include_style);
	
	$include_script = '
<script type="text/javascript" src="'.JS_DIR.'jquery.passwdcheck.js"></script>
<script type="text/javascript">
$(function(){
			$("input[name=\'reset_pw[password1]\']").passwdcheck($.extend({}, ORGM.passwdcheck.options, {placeholderClass:"col-sm-5"}));
});
</script>
';
	$qt->prependv_once('plugin_reset_pw_script', 'plugin_script', $include_script);
	$qt->setjsv('passwdcheck', array(
		'options' => $passwdcheck_options
	));

	$example_passwd = create_password();

	$body = '
<div class="page-header">'.__('パスワードの再設定').'</div>
<div class="container-fluid">
	<p>'.__('新しいパスワードを入力してください').'</p>
'.$error_msg.'
	<form method="post" action="'.$script.'" class="form-horizontal">
	
		<div class="form-group">
			<label for="" class="col-sm-3 control-label">'.__('メールアドレス').'</label>
			<div class="row">
				<div class="col-sm-6">
					<input type="text" name="reset_pw[username]" value="'.$vars['reset_pw']['username'].'" class="form-control">
				</div>
			</div>
		</div>
	
		<div class="form-group">
			<label for="" class="col-sm-3 control-label">'.__('新しいパスワード').'</label>
			<div class="row">
				<div class="col-sm-3">
					<input type="password" name="reset_pw[password1]" class="form-control">
				</div>
			</div>
			<div class="row">
				<div class="col-sm-9 col-sm-offset-3 help-block">
					<span class="password-example strong-italic">'.h($example_passwd).'</span> のように、大文字、小文字、数字、記号を混ぜ、ランダムなパスワードを入力してください。
				</div>
			</div>
		</div>
	
		<div class="form-group">
			<label for="" class="col-sm-3 control-label">'.__('パスワード再入力').'</label>
			<div class="row">
				<div class="col-sm-4">
					<input type="password" name="reset_pw[password2]" class="form-control">
				</div>
			</div>
		</div>
	
		<div class="form-group">
			<div class="col-sm-9 col-sm-offset-3">
				<input type="submit" class="btn btn-primary" value="'.__('設定する').'" />
			</div>
		</div>
	
		<input type="hidden" name="code" value="'.$code.'" />
		<input type="hidden" name="mode" value="set" />
		<input type="hidden" name="plugin" value="reset_pw" />
	</form>
</div>
';

	return array('msg' => __('パスワードの再設定'), 'body' => $body);
}

function plugin_reset_pw_reset_password()
{
	global $script, $vars, $username;
	$error = '';

	if ($vars['reset_pw']['username'] !=  $username)
	{
		$error .= __('メールアドレスが違います<br>');
	}
	if ($vars['reset_pw']['password1'] != $vars['reset_pw']['password2'])
	{
		$error .= __('新パスワードが一致しません<br>');
	}
	if ( ! preg_match(ALLOW_PASSWD_PATTERN , $vars['reset_pw']['password1']))
	{
		$error .= __('パスワードにご利用できない文字が入っています。<br>');
	}
	if (strlen($vars['reset_pw']['password1']) < 8 )
	{
		$error .= __('パスワードは、8文字以上を設定してください<br>');
	}
	if (strlen($vars['reset_pw']['password1']) >= 32 )
	{
		$error .= __('パスワードは、32文字以内を設定してください<br>');
	}


	if ($error != '')
	{
		return plugin_reset_pw_form_reset($error);
	}

	ss_auth_logout();
	
	// 設定ファイルへの書込み
	$data = array(
		'passwd' => pkwk_hash_compute($vars['reset_pw']['password1']),
		'reset_pw_token' => '',
	);
	orgm_ini_write($data);
	
	// ログインロックも解除する
	$trydata = ss_get_trycnt_data();
	if (isset($trydata[$username]))
	{
		$trydata[$username]['locked'] = FALSE;
		$trydata[$username]['count'] = 0;
		$trydata[$username]['token'] = '';
		
		ss_set_trycnt_data($trydata);
	}

	$login_url = h($script.'?cmd=login');
	$body = '
<h2>'.__('パスワードの再設定完了').'</h2>
<p>'.__('パスワードの再設定を完了しました。<br>再度、ログインが必要です。').'</p>
<p><a href="'.$login_url.'" class="btn btn-primary">ログインする</a></p>
';
	
	return array('msg'=>$msg, 'body'=>$body);
}

/* End of file reset_pw.inc.php */
/* Location: /haik-contents/plugin/reset_pw.inc.php */