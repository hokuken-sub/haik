<?php

/***************************************************************************
 *                         QHM Plugin
 *                    ------------------------------
 *   Filename:             close.inc.php
 *   Project:              hokuken lab.
 *   Company:              hokuken lab.
 *   Copyright:            (C) 2007 hokuken lab.
 *   Website:              http://www.hokuken.com
 *   Version:              1.0
 *   Build Date:           2008-04-02
 *
 *   If you find bugs/errors/anything else you would like to point to out
 *   to us please feel free to contact us.
 *
 *   What it does:
 *   showing message on close
 *
 ***************************************************************************/

function plugin_close_page()
{
	global $vars, $script;
	$qm = get_qm();
	$qt = get_qt();
	
    $page = isset($vars['page']) ? $vars['page'] : '';
    
    //キャッシュ無効
	$qt->enable_cache = false;
    
	$title = get_page_title($page);

    if (edit_auth($page, FALSE, FALSE))
    {
    	if ($vars['cmd'] === 'read')
			set_notify_msg(__('このページは閉鎖中です'), 'info');
		return FALSE;
    }
    else
    {

	    $qt->setv('eyecatch', '');
	    $qt->setv('summary', '');

	    $msg = __('このページは閉鎖中です。');
    	$body = '
<div class="row">
	<div class="panel col-sm-6 col-sm-offset-3">
		<h4>'. h($title) .'</h4>
		<hr />
		<p>'.h($msg). '</p>
	</div>
</div>
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
    	
    	//output 503 Status
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
//		header('Retry-After: 300');
		
		return $body;
	}
}

/* End of file close.inc.php */
/* Location: /app/haik-contents/plugin/close.inc.php */