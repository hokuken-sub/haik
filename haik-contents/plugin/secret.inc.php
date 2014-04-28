<?php
/**
 *   QHM Secret Plugin ver 0.9
 *   -------------------------------------------
 *   plugin/secret.inc.php
 *   
 *   Copyright (c) 2010 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2007-09-11
 *   modified :
 *   
 *   簡易パスワード認証ページを作るプラグイン
 *   
 *   Usage :
 *     &secret(パスワード(英数のみ));
 *   
 */
function plugin_secret_auth(){
    global $vars, $script, $page_meta;
    $qm = get_qm();
    $qt = get_qt();

    $page = isset($vars['page']) ? $vars['page'] : '';
    $r_page = rawurlencode($page);
    $title = get_page_title($page);
    

    if ( ! isset($page_meta['password']) OR $page_meta['password'] === '')
    {
	    return FALSE;
    }
    
    if (edit_auth($page, FALSE, FALSE))
    {
    	if ($vars['cmd'] === 'read')
			set_notify_msg(__('このページはパスワード認証が設定されています'), 'info');
		return FALSE;
    }

	//session check 
	if (isset($_SESSION['readable_'.$r_page] ) && $_SESSION['readable_'.$r_page] == $r_page)
	{
		return FALSE;
	}



    $auth_url = $script . "?cmd=secret&page=" . urlencode($page);

    if (isset($vars['password']))
    {
		$password = isset($vars['password']) ? $vars['password'] : '';

        //passwd check
        if($password == $page_meta['password']){
        	$_SESSION['readable_'.$r_page] = $r_page;
            return FALSE;
        }
    }
    else if (isset($vars['key']))
    {
	    $pass_hash = $vars['key'];
	    $master_hash = md5($page_meta['password']);
	    
	    if ($pass_hash === $master_hash)
	    {
        	$_SESSION['readable_'.$r_page] = $r_page;
            return FALSE;
	    }
    }
    
    //ログインフォームの表示
    
    $qt->setv('eyecatch', '');
    $qt->setv('summary', '');

    $body = '
<div class="row">
	<div class="col-sm-6 col-sm-offset-3">
		<p>このページの閲覧にはパスワードが必要です。</p>
	</div>
</div>

<div class="row">
	<div class="panel col-sm-6 col-sm-offset-3">
		<div class="panel-heading">'. h($title) .'</div>
		<form method="post" action="'.h($action_url).'" class="form-inline">
			<input type="password" class="form-control" name="password" placeholder="'. __('パスワード') . '" style="width:auto">
			<input type="submit" value="'. __('ログイン') . '" class="btn btn-primary">
		</div>
	</div>
</div>
</form>
';
    
    $plugin_script = '
<script>
$(function(){
	$(":password").focus();
	$("#orgm_eyecatch").hide();
});
</script>
';
    $qt->appendv('plugin_script', $plugin_script);
    
    return $body;

 
}

function plugin_secret_get_url()
{
    global $vars, $script, $page_meta;
    
    if ( ! isset($page_meta['password']) OR $page_meta['password'] === '')
    {
        return FALSE;
    }

    $key = md5($page_meta['password']);
    $url = get_page_url($vars['page']) . '&key=' . rawurlencode($key);
    return $url;
}

/* End of file secret.inc.php */
/* Location: /plugin/secret.inc.php */