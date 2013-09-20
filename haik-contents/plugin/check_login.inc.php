<?php
/**
 *   Check Login Plugin
 *   -------------------------------------------
 *   /app/plugin/check_login.inc.php
 *   
 *   Copyright (c) 2010 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2010-12-15
 *   modified : 2013-06-07
 *   
 *   when [edit] button was pushed, check session status 
 *   
 *   Usage :
 *   
 */

define('PLUGIN_CHECK_LOGIN_LOGOUT', 0);
define('PLUGIN_CHECK_LOGIN_OK', 1);
define('PLUGIN_CHECK_LOGIN_ERROR', 2);

function plugin_check_login_action() {
	global $vars, $script, $auth_users;
	
	$qt = get_qt();
	
	//Ajax
	if (is_ajax())
	{
	
		$mode = isset($vars['mode'])? $vars['mode']: 'check';

		$res = array(
			'status'  => PLUGIN_CHECK_LOGIN_LOGOUT,
			'message' => '',
			'data'    => NULL,
		);
		
		//チェック
		if ($mode === 'check')
		{
			// login OK
			if (isset($_SESSION['usr']) && array_key_exists($_SESSION['usr'], $auth_users))
			{
				$res['status'] = PLUGIN_CHECK_LOGIN_OK;
				$res['message'] = 'login';
			}
			// logout
			else
			{
				$res['status'] = PLUGIN_CHECK_LOGIN_LOGOUT;
				$res['message'] = 'logout';
			}
			
		}
		//ログイン
		else if($mode === 'auth')
		{
		
			$username = isset($vars['username'])? $vars['username']: '';
			$password = isset($vars['password'])? $vars['password']: '';
			
			//OK
			if (isset($auth_users[$username]) && check_passwd($password, $auth_users[$username]))
			{
			
				$_SESSION['usr'] = $username;
				
				$res['status'] = PLUGIN_CHECK_LOGIN_OK;
				$res['message'] = __('ログインしました。');
			
			}
			//NG
			else
			{
				$res['status'] = PLUGIN_CHECK_LOGIN_ERROR;
				$res['message'] = __('メールアドレス、あるいはパスワードが間違っています。');
				sleep(2); //block brute force attack
			}
		
		}
		//ログアウト
		else if ($mode === 'destroy')
		{
			ss_auth_logout();
			$res['status'] = PLUGIN_CHECK_LOGIN_LOGOUT;
			$res['message'] = 'logout';
		}
		else
		{
			$res['status'] = PLUGIN_CHECK_LOGIN_ERROR;
			$res['message'] = 'request error';
			$res['data'] = $vars;
		}
	
		print_json($res);
		exit;
	
	}
	//Browser Access: redirect login
	else
	{
		$to = $script. '?cmd=login';
		redirect($to);
		exit;
	}
	
}

function plugin_check_login_set()
{
	global $vars, $script, $check_login;

	//一般アクセス時には実行しない
	if ( ! $check_login
	 OR (is_page($vars['page']) && ! check_editable($vars['page'], FALSE, FALSE))
	 OR ! is_login()
	)
	{
		return;
	}



	$qt = get_qt();

	if ($qt->is_appended('plugin_check_login')) return;

	$json = array(
		'check_login' => array(
			'LOGOUT' => PLUGIN_CHECK_LOGIN_LOGOUT,
			'OK' => PLUGIN_CHECK_LOGIN_OK,
			'ERROR' => PLUGIN_CHECK_LOGIN_ERROR
		)
	);

	$qt->setjsv($json);

	// !HTML
	$addblock = '
<div id="orgm_login_form" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<a href="#" class="close" data-dismiss="modal">&times;</a>
				<h3>ログイン</h3>
			</div>
			<div class="modal-body">
				<form action="'. h($script) .'" class="form-horizontal">
					<div class="form-group">
						<div class="col-sm-3">
							<label for="checkLoginUsername" class="control-label">メールアドレス</label>
						</div>
						<div class="col-sm-9 controls">
							<input type="text" name="username" id="checkLoginUsername" placeholder="メールアドレス" class="form-control">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<label for="checkLoginPassword" class="control-label">パスワード</label>
						</div>
						<div class="col-sm-9 controls">
							<input type="password" name="password" id="checkLoginPassword" placeholder="パスワード" class="form-control">
						</div>
					</div>
					<input type="hidden" name="cmd" value="check_login">
					<input type="hidden" name="mode" value="auth">
				</form>
			</div>
			<div class="modal-footer">
				<button class="btn btn-primary" data-login>ログイン</button>
				<button class="btn btn-default" data-dismiss="modal">キャンセル</button>
			</div>
		</div>
	</div>
</div>
';

	$qt->appendv_once('plugin_check_login', 'body_last', $addblock);
	
	return;
}



/* End of file check_login.inc.php */
/* Location: /app/plugin/check_login.inc.php */