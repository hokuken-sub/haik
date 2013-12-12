<?php
//-------------------------------------------------
// QHM Auth system
// 
// this system is able to work on CGI PHP & Module PHP
// Thanks & Reffer from SiteDev+AT by AKKO
// 

// Output message
// args : $message
// retrun : none
//<a href="{$_SERVER['PHP_SELF']}">re try</a>

define('SS_LOGIN_MAX_TRY_COUNT', 5);
define('SS_LOGIN_LOCK_EXPIRE', 1800);
define('SS_LOGIN_LOCK_FILE', CACHE_DIR . 'user_lock.dat');

// Output Login Form
function ss_auth_loginform($title, $errormsg='', $errortype = 'danger')
{
    global $vars, $script, $script_ssl, $session_save_path, $intro;

    $title .= isset($_SESSION['usr']) ? (' : ' . $_SESSION['usr']) : '';
    
    $qt = get_qt();

    $messages = array();
    if ($errormsg != '')
    {
	    $messages[] = array('type'=>$errortype, 'msg'=>$errormsg);
    }
    
	// Output Form
	$tmp = $vars['page'];
	$vars['page'] = "Page Edit Authorization" ;

	$intro = FALSE;
	if (exist_plugin('intro'))
	{
		$intro = plugin_intro_set();
	}

	$s_username = isset($vars['username']) ? h($vars['username']) : '';


	global $admin_style_name;

	$addjs = '
<script type="text/javascript">
$(function(){
	var usr = document.getElementById("username");
	usr.focus();
	usr.select();
});
</script>
';

$contents = <<< EOD
<form method="post" class="row">
	<div class="field form-group">
		<label for="username" class="sr-only">E-Mail</label>
		<input type="text" class="form-control" name="username" id="username" value="{$s_username}" style="" placeholder="メールアドレス" tabindex="1">
	</div>
	<div class="field form-group">
		<label for="password" class="sr-only">Password</label>
		<input type="password" class="form-control" name="password" id="password" style="" placeholder="パスワード" tabindex="2">
	</div>
	<input type="hidden" name="keep" value="0">
	<input type="submit" class="btn btn-default pull-right" name="send" value="ログイン" tabindex="3">
</form>

{$addjs}
EOD;

	//セッションの書き込み権限のチェック
	$sspath = session_save_path();
	$sspath = $sspath == '' ? '/tmp' : $sspath;
	$ss_write = is_writable( $sspath );
	
	$error_ss = '';

	if ($session_save_path != '') {
		$messages[] = array(
			'type'=>'info',
			'msg'=> sprintf(__('セッションの保存先を設定しています。<a href="%s">&raquo; 変更はこちら</a>'), $script.'?cmd=app_config_session')
		);
	}

	if($ss_write != true){
		if(! isset($vars['chksession']) ){
		
			//セッションチェックのために、sessionをセットして移動させる。
			$t = time();
			$_SESSION['chksession'] = $t;
			
			$cur_url = $_SERVER['REQUEST_URI'];
			$url = $cur_url . ( strpos($cur_url,'?') ? '&' : '?FrontPage&' ) . 'chksession='.$t;

			header('Content-Type: text/html;charset=utf-8');
			echo '<html><head><meta http-equiv="Refresh" content="0;url='.$url.'"></head><body><p><a href="'.$url.'">please click here</a></p></body></html>';
			exit;
		}
		else
		{
			if (FALSE && $vars['chksession'] == $_SESSION['chksession'] )
			{
				//session OK!
			}
			else
			{
				$messages[] = array(
					'type'=>'danger',
					'msg'=> sprintf(__('セッション保存先の書き込み権限を確認できません。<a href="%s">&raquo; 設定はこちら</a>'),
									$script.'?cmd=app_config_session')
				);
			} 
		}
	}

	auth_catbody('ユーザー認証', $contents, $messages);
	$vars['page'] = $tmp;
}

function ss_getURL( $pURL ) {
   $_data = null;
   if( $_http = fopen( $pURL, "r" ) ) {
      while( !feof( $_http ) ) {
         $_data .= fgets( $_http, 1024 );
      }
      fclose( $_http );
   }
   return( $_data );
}

function ss_auth_start(){
    if(! isset( $_SESSION['ct']) ){
		secure_session_start();
    	if(! isset($_SESSION['ct']) ) $_SESSION['ct']=0;
	}
    if(! isset($_SESSION['login']) ) $_SESSION['login']='';
    if(! isset($_SESSION['usr']) ) $_SESSION['usr']='';
}

function ss_auth_logout(){
  session_destroy();
}

function ss_chkusr($title , $users )
{
	global $script;
	global $login_log;
	global $intro_flags;
	
	$msg = '';
	$msg_type = 'danger';

	// 認証の場合
	if (isset($_POST['send']))
	{

		$user = isset($_POST['username']) ? $_POST['username'] : '';
		$pass = isset($_POST['password']) ? $_POST['password'] : '';

		$exist_user = array_key_exists($user, $users);
		$locked = FALSE;
		
		//ユーザーがロックされていないかチェック
		$trydata = ss_get_trycnt_data();
		if ($exist_user && isset($trydata[$user]) && $trydata[$user]['locked'])
		{
			//期限をチェックし、
			// 期限切れならロックを解除
			// 日付が変わっても解除
			$now = time();
			if ($trydata[$user]['time'] + SS_LOGIN_LOCK_EXPIRE < $now
			 OR date('Y-m-d', $trydata[$user]['time']) !== date('Y-m-d', $now))
			{
				$trydata[$user]['count'] = 0;
				$trydata[$user]['locked'] = FALSE;
				$trydata[$user]['token'] = '';
				ss_set_trycnt_data($trydata);
			}
			else
			{
				$locked = TRUE;
			}
		}
//		var_dump($trydata);

		//TODO: 時刻、IPアドレス、User のログを取る
		
		// 通常ログインを試行
		if ( ! $locked)
		{
			// User, Passwordをチェック
			$auth = ( $exist_user 
						&& check_passwd($pass, $users[$user]));
			
			
			//認証OK、NGに応じた処理
			if ( $auth ){
				$_SESSION['usr'] = $user;
				
				if (ss_admin_check()) {
					$d = dir(CACHE_DIR);
					while (false !== ($entry = $d->read())) {
						if($entry!='.' && $entry!='..') {
							$entry = CACHE_DIR.$entry;
							if(file_exists($entry)) {
								// cacheqhmディレクトリにある3日前の一時ファイルを削除
								if (mktime(date("H"),date("i"),date("s"),date("n"),date("j")-3,date("Y")) > time(fileatime($entry)) ) {
									unlink($entry);
								}
							}
						}
					}
					$d->close();
				}
				
				//試行回数をリセット
				if (isset($trydata[$user]))
				{
					$trydata[$user]['count'] = 0;
					$trydata[$user]['locked'] = FALSE;
					$trydata[$user]['token'] = '';
					ss_set_trycnt_data($trydata);
				}
				
				// ツアーのログインフラグをオフに
				if ($intro_flags['login'])
				{
					$intro_flags['login'] = 0;
					orgm_ini_write('intro_flags', $intro_flags);
				}
				
				return TRUE;
			}
			else
			{
				//Userのログイン試行回数を加える
				if ($exist_user)
				{
					$data = ss_get_trycnt_data();
					
					$user_trydata = isset($data[$user]) ? $data[$user] : array(
						'locked' => FALSE,
						'count'  => 0,
						'time'   => 0
					);
					
					$user_trydata['count']++;
					$user_trydata['time'] = time();
					$user_trydata['locked'] = (SS_LOGIN_MAX_TRY_COUNT <= $user_trydata['count']);
					
					$data[$user] = $user_trydata;
					
					ss_set_trycnt_data($data);
					
					$locked = $user_trydata['locked'];
					
					if ($locked && exist_plugin('login_unlock'))
					{
						// token を発行して、管理者へメール送信
						plugin_login_unlock_set_token();
					}
					
				}
	
				$msg = __('ユーザー名あるいは、パスワードが間違っています。');
			}
			
		}
		
		
		// ロック中はログインの試行不可
		if ($locked)
		{
			$unlock_time = $trydata[$user]['time'] + SS_LOGIN_LOCK_EXPIRE;
			$msg = '
<p>
	間違ったユーザー名、パスワードが '. SS_LOGIN_MAX_TRY_COUNT .' 回以上連続で入力されました。<br>
	安全のために、ログイン画面をロックしました。<br>
</p>
<p>
	登録されている管理者メールアドレスにロック解除用URLを送信しました。
</p>

<h4 class="login-lock page-header">パスワードを再発行する</h4>
<p>
	登録されている管理者メールアドレスに、再発行用URLを送信します。<br>
	<a href="'.h($script.'?cmd=reset_pw').'" class="btn btn-default">送信</a>
</p>
';
			$msg_type = 'lock';
		}
	}

	ss_auth_loginform($title, $msg, $msg_type);
	exit;
}

function ss_get_trycnt_data()
{
	$default_data = array();
	
	if (file_exists(SS_LOGIN_LOCK_FILE))
	{
		$file = file_get_contents(SS_LOGIN_LOCK_FILE);
		$data = unserialize($file);
		
		if ($data === FALSE)
		{
			$data = $default_data;
		}
	}
	else
	{
		$data = $default_data;
	}
	
	return $data;
}


function ss_set_trycnt_data($data = array())
{
	$default_data = array();
	
	$file = serialize($data);
	return file_put_contents(SS_LOGIN_LOCK_FILE, $file);
}



function auth_catbody($title, $contents, $messages=array())
{
	global $script, $default_script, $username, $pkwk_dtd, $viewport, $intro, $vars;
	
	$qt = get_qt();

	// Output HTML DTD, <html>, and receive content-type
	if (isset($pkwk_dtd)) {
		$meta_content_type = qhm_output_dtd($pkwk_dtd);
	} else {
		$meta_content_type = qhm_output_dtd();
	}
	
	$orgm = APP_NAME;
	
	$server_port = SERVER_PORT;
	$script_name = SCRIPT_NAME;
	$http_host = $_SERVER['HTTP_HOST'];
	$script_url = qhm_get_script_path();
	$lastscript = $qt->getv('lastscript');
	
	$intro_head = '';
	
	if ($intro)
	{
		$intro_addjs = $qt->getv('plugin_script');
		$intro_addcss = $qt->getv('plugin_head');
		$intro_json = json_encode($qt->getv('ORGM'));
		$ld_addjs = '<script src="'.JS_DIR.'lodash.min.js"></script>';
		
		$intro_head = <<< EOI
	{$ld_addjs}
	<script>
		var ORGM = {$intro_json};
	</script>
{$intro_addcss}
{$intro_addjs}
EOI;
	}

	if ($default_script != '') {
		$messages[] = array(
			'type'=>'info',
			'msg'=>sprintf(__('リンクが正常に動作しないサーバーの設定をしています。　<a href="%s">&raquo; 変更はこちら</a>'),
							$script_url. '?cmd=app_config_script')
		);
	}

	$s_err_msg = sprintf(__('リンクが正常に動作しないサーバーの可能性があります。<br>URLを設定してください。　<a href="%s">&raquo; 設定はこちら</a>'),
							$script_url. '?cmd=app_config_script');
	
	$error_login = 	'';
	
	// ユーザーがロックされている場合
	$trydata = ss_get_trycnt_data();
	$user = isset($_POST['username']) ? $_POST['username'] : FALSE;
	$locked = FALSE;
	
	if ($user && isset($trydata[$user]) && $trydata[$user]['locked'])
	{
		$contents = '';
		$locked = TRUE;
	}

	// 試行回数が1回以上の時はパスワード再発行の案内を出す
	if ( ! $locked && isset($_POST['send']) && $username != '')
	{
		$messages[] = array(
			'type' => 'info',
			'msg'  => '<a href="'.$script.'?cmd=reset_pw'.'">&raquo; パスワードをお忘れの場合</a>'
		);
	}
	
	// ロック解除メッセージを表示
	if (isset($vars['unlocked']) && ! isset($vars['username']))
	{
		if ((int)$vars['unlocked'] === 1)
		{
			$messages[] = array(
				'type' => 'success',
				'msg'  => 'ロックを解除しました。'
			);
		}
		else if ((int)$vars['unlocked'] === 0)
		{
			$messages[] = array(
				'type' => 'warning',
				'msg'  => 'ロックの解除ができません。<br>しばらく経ってからもう一度お試しください。'
			);
		}
		
	}

	$msg_html = '';
	foreach ($messages as $msg)
	{
		$msg_html .= '<div class="alert alert-'.$msg['type'].'"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$msg['msg'].'</div>';
	}
	
	$js_dir = JS_DIR;
	$css_dir = CSS_DIR;
	$img_dir = IMAGE_DIR;
	
	$viewport_tag = sprintf('<meta name="viewport" content="%s">', $viewport);
	
	if (exist_plugin('notify'))
	{
		$notices_html = plugin_notify_get_body();
		$notices_html = str_replace('col-sm-offset-3', '', $notices_html);
	}
	
	
	echo <<<EOD
<head>
{$meta_content_type}
<meta name="robots" content="NOINDEX, NOFOLLOW">
<title>{$title}</title>
{$viewport_tag}
<script src="{$js_dir}jquery.js"></script>
<script src="{$js_dir}bootstrap.js"></script>
<link rel="stylesheet" href="{$css_dir}bootstrap.css">
<link rel="stylesheet" href="{$css_dir}origami.css">
{$intro_head}
<script>
$(function(){
 	if (location.hostname != '{$http_host}' 
 			|| location.port != '{$server_port}' 
 			|| location.pathname != '{$script_name}' ) {

 		var href = '';
 		href = location.origin + location.pathname;
 		
 		if( ! href.match(/\.php$/)) {
 			href += 'index.php';
 		}
 		
 		if (href != '{$script}') {
 			$("#orgm_login").after('<div id="scripterror" class="alert alert-danger container"><button type="button" class="close" data-dismiss="alert">&times;</button>{$s_err_msg}</div>');
		}
 	}
 	return false;
});
</script>
<style>
html, body {
	position: relative;
	width: 100%;
	height: 100%;
}
body {
	background-image: url({$img_dir}login_background.png);
}
input[type=text], input[type=password] {
	background-color: rgba(255,255,255,0.4);
}
#orgm_login{
	padding:0;
	margin:0 auto 15px;
}

#login_form .field span.add-on {
	color:#666;
}

.centered{
	position: absolute;
	top: 50%;
	left: 50%;
	width: 500px;
	height: 300px;
	margin: -150px 0px 0px -250px;

}

.message {
	margin-left: -15px;
}

.alert-lock {
	padding-left: 0;
	padding-right: 0;
}

.alert-lock p {
	line-height: 1.5em;
	margin-bottom: 1.5em;
}

.alert-lock h4.login-lock {
	margin-top: 30px;
	margin-bottom: 10px;
}

#orgm_login_footer {
	background-color: #fff;
	border-top: 1px solid #e3e3e3;
	min-height: 30px;
}

#orgm_login_footer .navbar-inner {
	width: 500px;
	margin: 0 auto;
}

#orgm_login_footer .haik-brand{
	font-size: 1.8rem;
	padding: 0;
	line-height: 30px;
	padding-left: 0;
	color: #333333;
}

#orgm_login_footer .navbar-text{
	font-size: 1.3rem;
	padding:0;
	margin:0;
	line-height: 30px;
	color: #333333;
}

#orgm_login_footer .navbar-text a {
	color: #333333;
}

#orgm_login_footer .navbar-text a:hover {
	color: #666;
}


@media (max-width:767px) {
	.centered {
		position: relative;
		top: 0;
		left: 0;
		margin: 30px 0 0 0;
		width: 100%;
	}
	.message {
		margin-bottom: 30px;
	}
}
</style>
</head>
<body>
<div class="container">
	<div class="centered">
			{$notices_html}
		<h1>Login</h1>
		<div id="orgm_login" class="row">
			<div class="message col-sm-7">
				Spread your ideas
			</div>
			<div id="login_form" class="col-sm-5">
				{$contents}
			</div>
		</div>
		{$msg_html}
	</div>
</div>
<div id="orgm_login_footer" class="navbar navbar-default navbar-fixed-bottom">
	<div class="navbar-inner">
		<div class="navbar-brand haik-brand">{$orgm}</div>
		<p class="navbar-text pull-right"><a href="{$script}"><i class="orgm-icon orgm-icon-home"></i> トップ</a></p>
		</ul>
	</div>
</div>
</body>
</html>
EOD;

}

function secure_session_start()
{
	global $script, $script_ssl, $vars, $session_save_path;

	// ****************************************************
	// 共用SSLのおかしなサーバー変数に最大限対応するためのロジック
	// 
	$vals = parse_url( (is_https() ?  $script_ssl : $script) );
	// ******************************************************

	// make session values
	if(TRUE){
	
		$domain = $vals['host'];
		
		if($domain != 'localhost' && $domain != '127.0.0.1'){
			if(isset($vals['port']))
			{
				$domain .= ':'.$vals['port'];
			}
			$dir = str_replace('\\', '', dirname( $vals['path'] ));
			$ckpath = ($dir=='/') ? '/' : $dir.'/';
						
			if( function_exists('ini_set') ){
				ini_set('session.use_trans_sid',0);
				ini_set('session.name', APP_SESSION_NAME.strlen($ckpath));
				ini_set('session.use_only_cookies', 1);
				ini_set('session.cookie_path', $ckpath);
				ini_set('session.cookie_domain', $domain);
				ini_set('session.cookie_lifetime', 0);
			}
		}
	}

	$ssname = session_name();
	if(
		(UA_PROFILE=='keitai' && isset($vars['mobssid']) && $vars['mobssid']=='yes' )
		|| (UA_PROFILE=='keitai' && isset($vars[$ssname]) )
	){
		ini_set('session.use_only_cookies', 0);
		ini_set('session.use_trans_sid', 1); 	
	}

	if ($session_save_path != '') {
		session_save_path($session_save_path);
	}

	session_start();
}

function get_fingerprint()
{
   // Security SALT
   global $ss_salt, $script;
   
   $fingerprint = ($ss_salt == '') ? 'KAIENK8H3HEBBJU3HJCKIEIA8HFUNDAP763J' : $ss_salt;

   if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
       $fingerprint .= $_SERVER['HTTP_USER_AGENT'];
   }
   if ( ! empty( $_SERVER['HTTP_ACCEPT_CHARSET'] ) ) {
       $fingerprint .= $_SERVER['HTTP_ACCEPT_CHARSET'];
   }
   $fingerprint .= $script;
   return md5( $fingerprint );
}

function ss_admin_check($clear=false){
	global $username;
	
	if( isset($_SESSION['usr']) && ($_SESSION['usr']==$username) ){
		return true;
	}
	else{
		return false;
	}
}


/**
* SSL通信をしているか、可能な限りチェックするためのプログラム
* 多くの共用SSLのサーバー変数がめちゃくちゃだから必要
*
* 解説 : 独自SSLの場合、HTTPS = on、SERVER_PORT = 443 がセットされる
*  困るのはそれ以外のメチャクチャな設定の場合。通常のアクセスと識別できるようなロジックが必要
*/
function is_https(){
	
	//$scriptは、入れ替わっている可能性があるので、元の情報によってチェックする
	global $init_scripts;
	$script = $init_scripts['normal'];
	$script_ssl = $init_scripts['ssl'];
	
	
	// サーバー環境変数の中から、SSLを決定づけられる情報で判定
	$cond = array(
		'HTTPS' => 'on',
		'SERVER_PORT' => '443',
		'HTTP_X_FORWARDED_PROTO' => 'https', //例 : ロリポップ
	);
	
	foreach($cond as $k=>$v){
		if( isset($_SERVER[$k]) &&  $_SERVER[$k]==$v ){
			return true;	
		}
	}
	
	
	
	// サーバー環境変数内に、SSL通信を決定づける情報がない場合、
	// おそらく、プロキシを使ってSSL通信をしていると考えられるため
	// HTTP_X_FORWARDED系変数を使って、SSL判定を行う
	//
	// 
	if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && isset($_SERVER['HTTP_VIA']) ){
		$host = $_SERVER['HTTP_VIA'];
	}
	else if( isset($_SERVER['HTTP_X_FORWARDED_HOST']) ){
		$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
	}
	else if( isset($_SERVER['SERVER_NAME']) ){
		$host = $_SERVER['SERVER_NAME'];
	}
	else if( isset($_SERVER['HTTP_HOST']) ){
		$host = $_SERVER['HTTP_HOST'];
	}
	else{ //すべての変数が取れなければ、$script とする
		return preg_match('/^https:/', $script);
	}


	$ptrn = array(
		'SSL\d+.HETEML.JP', //ヘテムル
		'SS\d+.CORESSL.JP', //Coreserver
		'SS\d+.XREA.COM',   //XREA
	);	
	
	$ptrstr = '/('.implode(')|(', $ptrn).')/';
	
	if( preg_match($ptrstr, strtoupper($host)) ){
		return true;
	}

	return false;
}

function get_session_params(){
	
	
	$params = array(
		'session.use_trans_sid',
		'session.name',
		'session.use_only_cookies',
		'session.cookie_path',
		'session.cookie_domain',
		'session.cookie_lifetime',
		'session.save_path',
	);

	$params = array_flip($params);
	foreach($params as $k=>$v){
		$params[$k] = ini_get($k);
	}

	return $params;	
}


?>