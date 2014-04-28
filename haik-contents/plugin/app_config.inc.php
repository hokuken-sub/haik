<?php
/**
 *   Application Config Plugin
 *   -------------------------------------------
 *   plugin/app_config.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/01/29
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

include_once(LIB_DIR . 'html_helper.php');

function plugin_app_config_init()
{

	global $style_name, $script, $admin_style_name, $_LINK;
	global $vars;
	global $is_plugin_page;
	
	$qt = get_qt();
	$is_plugin_page = true;
	
	if ( ! isset($vars['noauth']) OR $vars['noauth'] === FALSE)
	{
		if ( ! ss_admin_check())
		{
			set_flash_msg('管理者のみアクセスできます。', 'error');
			redirect($script);
			exit;
		}
	}

	$style_name = $admin_style_name;
	$qt->setv('template_name', 'top');
	
	plugin_app_config_set_menu();
	plugin_app_config_set_navi();
	plugin_app_config_set_footer();	
	
	$qt->setv('logo', 'Haik');

	$include_script = '
<script type="text/javascript" src="'. PLUGIN_DIR .'app_config/common.js"></script>
';
	$qt->appendv_once('plugin_app_config_init', 'plugin_script', $include_script);
}


function plugin_app_config_action()
{
	return do_plugin_action("app_config_general");
}

function plugin_app_config_get_menu_items()
{

	return array(
		'general' => array(
			'name' => __('サイト情報'),
			'icon' => 'earth'
		),
		'auth' => array(
			'name' => __('管理者'),
			'icon' => 'user',
		),
		'marketing' => array(
			'name' => __('マーケティング'),
			'icon' => 'stats'
		),
/*
		'blog' => array(
			'name' => __('ブログ'),
			'icon' => 'bubbles'
		),
*/
		'advance' => array(
			'name' => __('高度な設定'),
			'icon' => 'cog',
		),
	);

}

function plugin_app_config_set_menu()
{
	global $script, $vars;
	$qt = get_qt();
	
	$items = plugin_app_config_get_menu_items();
	
	$cur_mode = substr($vars['cmd'], 11);
	
	$menu = '<div id="haik_config_slider" class="fade">
<div class="haik-admin-slider haik-config-menu">
';
	foreach ($items as $name => $item)
	{
		$sub_menu = $toggle = '';
		$icon_name = 'icon-chevron-right';
		$to = $script . '?cmd=app_config'. ($name ? '_'.$name : '');

		$active = '';
		if ($name == $cur_mode)
		{
			$active = ' active';
		}
		else if ($cur_mode == '' && $name == 'general')
		{
			$active = ' active';
		}
		
		$icon = 'orgm-icon-'.$item['icon'];
		$menu .= '<div class="list-group" data-menu="'.h($name).'"><a class="list-group-item'.$active.'" href="'.h($to).'"><i class="orgm-icon '.$icon.'"></i> '. h($item['name']) .'</a></div>';
	}
	
	$menu .= '<div class="list-group"></div>';
	$menu .= '<div class="list-group"><a class="list-group-item" href="'.h(APP_OFFICIAL_SITE. 'index.php?Help').'">ヘルプ</a></div>';
	$menu .= '<div class="list-group"><a class="list-group-item" href="'.h($script. '?cmd=app_update&config').'">アップデート</a></div>';
	$menu .= '</div>';

	$qt->setv('menu', $menu);
	
}

function plugin_app_config_set_navi()
{
// ! 設定用ナビを変更する場合は、ここに書く
}

function plugin_app_config_set_footer()
{
	global $script, $vars;
	$qt = get_qt();
	
	$footer = '
	<div class="container-fluid">
		<a href="'.h($script).'">'.__('トップ').'</a>&nbsp;&nbsp;&nbsp;
		<a href="'.h($script.'?cmd=filelist').'">'.__('ページ一覧').'</a>&nbsp;&nbsp;
		<a href="'.h($script.'?cmd=filer').'">'.__('ファイル管理').'</a>&nbsp;&nbsp;&nbsp;
		--&nbsp;&nbsp;&nbsp;
		<a href="'.h($script.'?cmd=logout').'"><i class="orgm-icon-home"></i> '.('ログアウト').'</a>
	</div>

';
	
	$qt->setv('site_footer', $footer);
}


/* End of file app_config.inc.php */
/* Location: plugin/app_config.inc.php */