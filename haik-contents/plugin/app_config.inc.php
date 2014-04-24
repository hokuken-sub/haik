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
	
	$menu = '';
	$menu .= '<ul class="nav nav-list app-config-menu">';
	
	foreach ($items as $name => $item)
	{
		$sub_menu = $toggle = '';
		$icon_name = 'icon-chevron-right';
		$to = $script . '?cmd=app_config'. ($name ? '_'.$name : '');
		if (isset($item['sub']))
		{
			$icon_name = 'icon-chevron-down';
			
			$sub_id = 'orgm_submenu_'.h($name);
			$collapse = ' collapse';
			$sub_menu_body = '';
			foreach ($item['sub'] as $sub_name => $sub_item)
			{
				$active = '';
				if ($sub_name == $cur_mode)
				{
					$active = ' class="active"';
					$collapse = '';
				}
				else if ($name == $cur_mode)
				{
					$collapse = '';
				}
				$icon = 'orgm-icon-'.$sub_item['icon'];
				$sub_menu_body .= '<li'.$active.'><a href="#"><span class="box"><i class="orgm-icon '.$icon.'"></i></span><span>'.h($sub_item['name']).'</span></a></li>';
			}
			$sub_menu = '<ul class="nav nav-list app-config-submenu'.h($collapse).'" id="'.h($sub_id).'">';
			$sub_menu .= $sub_menu_body;
			$sub_menu .= '</ul>';
			
		}

		$active = '';
		if ($name == $cur_mode)
		{
			$active = ' class="active"';
		}
		else if ($cur_mode == '' && $name == 'general')
		{
			$active = ' class="active"';
		}
		
		$icon = 'orgm-icon-'.$item['icon'];
		$menu .= '<li'.$active.' data-menu="'.h($name).'"><a href="'.h($to).'"'.$toggle.'><span class="box"><i class="orgm-icon '.$icon.'"></i></span><span> '. h($item['name']) .'</span></a>'.$sub_menu.'</li>';
	}
	
	$menu .= '<li class="section"></li>';
	$menu .= '<li><a href="'.h(APP_OFFICIAL_SITE. 'index.php?Help').'">ヘルプ</a></li>';
	$menu .= '<li><a href="'.h($script. '?cmd=app_update&config').'">アップデート</a></li>';
	$menu .= '</ul>';

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