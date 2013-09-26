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

function plugin_app_config_general_init()
{
	
	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}
	
}


function plugin_app_config_general_action()
{
	global $script, $vars, $site_title, $site_close_all, $logo_image, $logo_title;
	global $site_title_delim;

	$title = __('サイト情報');
	$description = __('サイト情報の設定を行います。');

	$qt = get_qt();
	
	$include_css = '
<link rel="stylesheet" href="'.JS_DIR.'datepicker/css/datepicker.css">';
	$include_script = '
<script type="text/javascript" src="'.JS_DIR.'datepicker/js/bootstrap-datepicker.js"></script>
<script type="text/javascript" src="'.JS_DIR.'datepicker/js/bootstrap-datepicker.ja.js"></script>
';
	$qt->prependv_once('plugin_app_config_general_init_script', 'plugin_script', $include_script);
	$qt->prependv_once('plugin_app_config_general_init_css', 'plugin_head', $include_css);

	if (exist_plugin('filer'))
	{
		plugin_filer_set_iframe(':image', 'exclusive', FALSE);
	}

	if (isset($vars['phase']) && $vars['phase'] === 'save')
	{
		$fields = array('site_title', 'style_name', 'site_close_all', 'logo_title', 'logo_image');
		$res = array();
		
		if (isset($vars['site_title']))
		{
			if ($vars['site_title'] == '')
			{
				print_json(array('error' => __('サイトタイトルが未入力です') , 'item' => 'site_title'));
				exit;
			}
			else
			{
				$res = array('value'=> $vars['site_title']);
			}
		}
		
		if (isset($vars['logo_title']))
		{
			$res = array('value'=> ($vars['logo_title'] == '') ?  '' : $vars['logo_title']);
		}
		
		if (isset($vars['logo_image']))
		{
			$res = array('value'=> ($vars['logo_image'] == '') ?  __('指定なし：サイトタイトルを利用') : $vars['logo_image']);
		}
		
		if (isset($vars['site_close_all']))
		{
			if ($vars['site_close_all'])
			{
				if ($vars['site_open_date'] != '')
				{
					$opentime = strtotime($vars['site_open_date']);
					if (! checkdate(date('n', $opentime), date('j', $opentime), date('Y', $opentime)))
					{
						print_json(array('error' => __('公開予定日には正しく日付を入力してください') , 'item' => 'site_open_date'));
						exit;
					}
					$res = array('value'=> __('閉鎖中（'.$vars['site_open_date'].' 公開予定）'));
					$vars['site_close_all'] = $vars['site_open_date'];
				}
				else
				{
					$res = array('value'=> __('閉鎖中'));
				}
			}
			else
			{
				$res = array('value'=> __('公開中'));
			}
		}
		
		$data = array_intersect_key($vars, array_flip($fields));
		orgm_ini_write($data);
		
		unset($_SESSION['preview_skin']);

		$res['message'] = '設定を更新しました';
		$res['options'] = $data;
		
		print_json($res);
		exit;
	}

	$site_close = $site_close_all ? '閉鎖中' : '公開中';
	$site_open_date = '';
	if ($site_close_all)
	{
		if ($site_close_all != '1')
		{
			$site_open_date = $site_close_all;
			$site_close = $site_close . '（'.$site_open_date.'公開予定）';
		}
	}
	
	$page_title_sample = __('ページ名') . $site_title_delim . '${site_title}';
	
	$tmpl_file = PLUGIN_DIR . 'app_config/general.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => $title, 'body' => $body);

}

/* End of file orgm_setting.inc.php */
/* Location: plugin/orgm_setting.inc.php */