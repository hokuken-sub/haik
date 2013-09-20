<?php
/**
 *   Guide Tour Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/intro.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/08/22
 *   modified :
 *   
 *   Show guide-tour of this APP
 *   
 *   Usage :
 *     orgm_admin_init 内で plugin_intro_set を呼び出す。
 *   
 */


/*
function plugin_intro_action()
{

}
*/

function plugin_intro_set()
{
	global $intro_flags, $vars, $script, $site_nav, $menubar;
	$qt = get_qt();
	
	//admin, eyecatch, nav, menu
	if (is_login())
	{
		if ($vars['cmd'] === 'read' && $intro_flags['admin'])
		{
			$json = plugin_intro_step_admin();
		}
		else if ($vars['cmd'] === 'eyecatch' && $intro_flags['eyecatch'])
		{
			$json = plugin_intro_step_eyecatch();
		}
		else if ($vars['cmd'] === 'edit' && $vars['page'] === $site_nav && $intro_flags['nav'])
		{
			$json = plugin_intro_step_nav();
		}
		else if ($vars['cmd'] === 'edit' && $vars['page'] === $menubar && $intro_flags['menu'])
		{
			$json = plugin_intro_step_menu();
		}
		else if ($vars['cmd'] === 'intro' && $vars['done']
			&& in_array($vars['done'], array('admin', 'nav', 'eyecatch', 'menu')))
		{
			$step_keys = explode(',', $vars['done']);
			$steps = array_combine($step_keys, array_fill(0, count($step_keys), 0));
			
			$intro_flags = array_merge($intro_flags, $steps);
			orgm_ini_write('intro_flags', $intro_flags);
			exit;
		}
		else
		{
			return FALSE;
		}
	}
	//start, login
	else
	{
		if ($vars['cmd'] === 'read' && $intro_flags['start'])
		{
			$json = plugin_intro_step_start();
		}
		else if ($vars['cmd'] === 'login' && $intro_flags['login'])
		{
			$json = plugin_intro_step_login();
		}
		else if ($vars['cmd'] === 'intro' && $vars['done']
			&& in_array($vars['done'], array('start', 'login', 'admin')))
		{
			$step_keys = explode(',', $vars['done']);
			$steps = array_combine($step_keys, array_fill(0, count($step_keys), 0));
			
			$intro_flags = array_merge($intro_flags, $steps);
			orgm_ini_write('intro_flags', $intro_flags);
			exit;
		}
		else
		{
			return FALSE;
		}
	}
	
	$addscript = '
<script src="'. PLUGIN_DIR .'intro/intro.js"></script>
<script src="'. PLUGIN_DIR .'intro/common.js"></script>
';
	$addcss = '
	<link rel="stylesheet" href="'. PLUGIN_DIR .'intro/introjs.css"></script>
	<link rel="stylesheet" href="'. PLUGIN_DIR .'intro/common.css"></script>
';

	$qt->appendv_once('plugin_intro_script', 'plugin_script', $addscript);
	$qt->appendv_once('plugin_intro_css', 'plugin_head', $addcss);
	
	$json['doneUrl'] = $script . '?cmd=intro';
	$qt->setjsv(array('intro' => $json));

	return TRUE;
}


function plugin_intro_step_start()
{
	$step_name = 'start';
	
	ob_start();
	include(PLUGIN_DIR . 'intro/start_step_1.html');
	$step_1_html = ob_get_clean();
	
	$steps = array(
		array(
			'html' => $step_1_html,
			'selector' => '#haik_intro_start_modal',
		),
		array(
			'selector' => '#orgm_login',
			'intro' => sprintf(__('%s をクリック'), APP_NAME),
			'position' => 'top',
		),
	);

	$json = array(
		'current' => $step_name,
		'steps' => $steps
	);

	return $json;
}

function plugin_intro_step_login()
{
	
	$step_name = 'login';
	
	$steps = array(
		array(
		),
		array(
		),
		array(
			'selector' => '#login_form',
			'intro' => __('設定したメールアドレス、<br>パスワードを入力。'),
			'position' => 'top',
		),
	);

	$json = array(
		'current' => $step_name,
		'steps' => $steps
	);

	return $json;
		
}
function plugin_intro_step_admin()
{
	
	$step_name = 'admin';
	
	$steps = array(
		array(
			'selector' => '.admin-nav-toolbar.pull-right',
			'intro' => __('<p>まずはここから。</p><div class="row"><span class="col-sm-3 text-right">編集 :</span><span class="col-sm-9">ページ、ナビの編集</span></div><div class="row"><span class="col-sm-3 text-right">サイト :</span><span class="col-sm-9">haik の設定、デザイン変更</span></div><div class="row"><span class="col-sm-3 text-right">haik :</span><span class="col-sm-9">ログアウト、ヘルプなど</span></div>'),
		),
	);

	$json = array(
		'current' => $step_name,
		'steps' => $steps
	);

	return $json;
	
}
function plugin_intro_step_eyecatch()
{
	$step_name = 'eyecatch';
	
	$steps = array(
		array(
			'selector' => '.orgm-eyecatch-controls',
			'intro' => __('<div class="row"><span class="col-sm-3 text-right">編集 :</span><span class="col-sm-9">アイキャッチの追加、編集</span></div><div class="row"><span class="col-sm-3 text-right">設定 :</span><span class="col-sm-9">背景、高さを設定</span></div>'),
		),
		array(
			'selector' => '#toolbar_buttons',
			'intro' => __('編集が完了したら、<br>「更新」をクリックして反映'),
		),
	);

	$json = array(
		'current' => $step_name,
		'steps' => $steps
	);

	return $json;
}
function plugin_intro_step_nav()
{
	$step_name = 'nav';
	
	$steps = array(
		array(
			'selector' => '#orgm_toolbox div:first-child',
			'intro' => __('ここを使うと便利！'),
			'position' => 'bottom',
		),
		array(
			'selector' => '#haik_edit_manual_link',
			'intro' => __('ヘルプも是非！'),
			'position' => 'left',
		),
	);

	$json = array(
		'current' => $step_name,
		'steps' => $steps
	);

	return $json;

}
function plugin_intro_step_menu(){
	
	$step_name = 'menu';
	
	$steps = array(
		array(
			'selector' => '.exnote-agwrapper',
			'intro' => __('特別な書き方で<br>様々なことができます'),
			'position' => 'right',
		),
		array(
			'selector' => '#haik_edit_manual_link',
			'intro' => __('ヘルプも是非！'),
			'position' => 'left',
		),
	);

	$json = array(
		'current' => $step_name,
		'steps' => $steps
	);

	return $json;

}

/*
function plugin_intro_reset_flags($flags = 'all')
{
	global $intro_flags;
	
	if ($flags === 'all')
	{
		$flags = array_keys($intro_flags);
	}
	
	foreach ($intro_flags as $key => $val)
	{
		$intro_flags[$key] = 1;
	}

}
*/

/* End of file intro.inc.php */
/* Location: /haik-contents/plugin/intro.inc.php */