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
	global $config, $style_color, $style_texture, $style_custom_bg, $admin_style_name;
	global $use_less, $site_title_delim;

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

	//from 「デザイン確定」ボタン
	$apply_design = FALSE;
	if (isset($vars['phase']) && $vars['phase'] === 'apply_preview_design' && isset($_SESSION['preview_skin']))
	{
		$apply_design = TRUE;
		$vars['phase'] = 'save';
		$vars = array_merge_deep($vars, $_SESSION['preview_skin']);
	}

	if (isset($vars['phase']) && $vars['phase'] === 'get_skin_data')
	{
		$res = array();
		$sname = $vars['style_name'];
		$style_config = style_config_read($sname);
		
		foreach (array('color', 'texture') as $type)
		{
			$res[$type]['html'] = '';
			$plural = $type . 's';
			$config_name = 'style_' . $type;
			$style_class_name = 'sample-style-'.$type;
			if (isset($style_config[$plural]))
			{
				$matched_cnt = 0;
				foreach ($style_config[$plural] as $value => $file)
				{
					if ($type == 'color')
					{
						$matched = ($style_color == $value);
					}
					else if ($type == 'texture')
					{
						$matched = ($style_texture == $value);
					}

					$res[$type]['html'] = $res[$type]['html'] . '<li class="sample-cut '.($matched ? 'active' : '').'"><a href="#" class="thumbnail '.$style_class_name.'-'.h($value).'" data-style_'.$type.'="'.h($value).'" title="'.h($value).'">'.(($type === 'texture' && $value === 'custom') ? '<i class="orgm-icon orgm-icon-wrench"></i>' : '').'<span class="sr-only">'.h($value).'</span></a></li>';
					
					$matched_cnt = $matched ? $matched_cnt+1 : $matched_cnt;
				}
				$res[$type]['html'] = '<li class="sample-cut '.($matched_cnt ? '' : 'active').'"><a href="#"  class="thumbnail '.$style_class_name.' " data-style_'.h($type).'="" title="'.h(__('なし')).'"><span class="sr-only">'.__('なし'). '</span></a></li>' . $res[$type]['html'];

			}
		}
		

		//サンプル用スタイルファイルの有無を調べる
		if ($use_less)
		{
			$style_type = 'less';
			$sample_style_file = SKIN_DIR . $sname . '/less/' . basename($style_config['sample_style_file'], '.css') . '.less';
			$rel = 'stylesheet/less';
			if ( ! file_exists($sample_style_file))
			{
				$sample_style_file = FALSE;
			}
		}
		else
		{
			$style_type = 'css';
			$sample_style_file = SKIN_DIR . $sname . '/' . $style_config['sample_style_file'];
			$rel = 'stylesheet';
			if ( ! file_exists($sample_style_file))
			{
				$sample_style_file = FALSE;
			}
		}
		
		if ($sample_style_file !== FALSE)
		{
			$res['sample_style'] = array(
				'type' => 'text/' . $style_type,
				'file' => $sample_style_file
			);
		}
		
		
		print_json($res);
		exit;
	}
	else if (isset($vars['phase']) && $vars['phase'] === 'preview_design')
	{
		$preview_skin_name    = isset($vars['style_name'])   ? $vars['style_name']   : '';
		$preview_color_name   = isset($vars['style_color'])   ? $vars['style_color']   : '';
		$preview_texture_name = isset($vars['style_texture']) ? $vars['style_texture'] : '';
		$preview_custom_bg = isset($vars['style_custom_bg']) ? $vars['style_custom_bg'] : array();
		
		$preview_style_config = style_config_read($preview_skin_name);
		
		$json = array();
		
		if ($preview_style_config)
		{
			$preview_color_name = isset($preview_style_config['colors'][$preview_color_name]) ? $preview_color_name : '';
			$preview_texture_name = isset($preview_style_config['textures'][$preview_texture_name]) ? $preview_texture_name : '';
			
			$_SESSION['preview_skin'] = array(
				'style_name' => $preview_skin_name,
				'style_color' => $preview_color_name,
				'style_texture' => $preview_texture_name,
				'style_custom_bg' => $preview_custom_bg,
			);
			
			$json['redirect'] = $script;
		}
		else
		{
			$json['error'] = __('プレビューできません。');
		}
		
		print_json($json);
		exit;
	}
	else if (isset($vars['phase']) && $vars['phase'] === 'cancel_preview_design')
	{
		unset($_SESSION['preview_skin']);
		
		if (is_ajax())
		{
			$value  = $config['style_name'];
			$value .= (($style_color.$style_texture) != '') ? '（' : '';
			$value .= ($style_color != '') ? (__('色：') . $style_color) : '';
			$value .= (($style_color.$style_texture) != '') ? __('、') : '';	
			$value .= ($style_texture != '') ? (__('背景：') . $style_texture) : '';
			$value .= (($style_color.$style_texture) != '') ? '）' : '';
			print_json(array('value' => $value));
		}
		else
		{
			set_flash_msg(__('デザインのプレビューを解除しました。'));
			$redirect = isset($vars['refer']) ? $vars['refer'] : $script;
			redirect($redirect);
		}
		exit;
	}
	else if (isset($vars['phase']) && $vars['phase'] === 'save')
	{
		$fields = array('site_title', 'style_name', 'site_close_all', 'logo_title', 'logo_image', 'style_color', 'style_texture', 'style_custom_bg');
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
		
		if (isset($vars['style_name']))
		{
			$style_message = $vars['style_name'];
			if (isset($vars['style_color']) && $vars['style_color'] != '' OR
				isset($vars['style_texture']) && $vars['style_texture'])
			{
				$style_message .= '（色：'.$vars['style_color'].'、背景：'.$vars['style_texture'].'）';
			}
			$res = array('value'=> $style_message);

			$style_config = style_config_read($vars['style_name']);
			if ( ! isset($style_config['textures']['custom']))
			{
				unset($fields['style_custom_bg']);
			}
			else {
				if ( ! isset($vars['style_custom_bg']) OR count($vars['style_custom_bg']) == 0)
				{
					unset($fields['style_custom_bg']);
				}
			}
		}
		else
		{
			unset($fields['style_custom_bg']);
		}
		
		$data = array_intersect_key($vars, array_flip($fields));
		orgm_ini_write($data);
		
		unset($_SESSION['preview_skin']);

		$res['message'] = '設定を更新しました';
		$res['options'] = $data;
		
		if ($apply_design)
		{
			set_flash_msg($res['message']);
			$redirect = isset($vars['refer']) ? $vars['refer'] : $script;
			redirect($redirect);
		}

		print_json($res);
		exit;
	}

	// !スキンの取得はどこで？
	// ORGM.skins とかに変更したい
	//デザインテンプレートの指定
	$obj = dir(SKIN_DIR);
	$skin_dirs = array();
	while ($entry = $obj->read())
	{
		if(is_dir(SKIN_DIR.$entry) && ($entry!='.') && ($entry!='..') && (file_exists(SKIN_DIR.$entry.'/config.php')))
		{
			if ($admin_style_name === $entry)
			{
				continue;
			}
			$skin_dirs[$entry] = style_config_read($entry);
		}
	}
	ksort($skin_dirs);


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
	
	$style_name = $config['style_name'];
	//プレビュー中はそれを反映する
	$is_preview = FALSE;
	if (isset($_SESSION['preview_skin']))
	{
		extract($_SESSION['preview_skin']);
		$is_preview = TRUE;
		$qt->setjsv('previewSkin', $_SESSION['preview_skin']);
	}
	
	// プレビュー解除リンク
	$qt->setjsv('cancelPreviewUrl', $script.'?cmd=app_config_general&phase=cancel_preview_design');
	

	//サンプル用スタイルファイルの読み込み
	$style_config = style_config_read($style_name);
	if ($use_less)
	{
		$sample_style_file = SKIN_DIR . $style_name . '/less/' . basename($style_config['sample_style_file'], '.css') . '.less';
		$rel = 'stylesheet/less';
		if (file_exists($sample_style_file))
		{
			$qt->setv('less_load', TRUE);
		}
		else
		{
			$sample_style_file = FALSE;
		}
	}
	else
	{
		$sample_style_file = SKIN_DIR . $style_name . '/' . $style_config['sample_style_file'];
		$rel = 'stylesheet';
		if ( ! file_exists($sample_style_file))
		{
			$sample_style_file = FALSE;
		}
	}
	
	if ($sample_style_file !== FALSE)
	{
		$head = "\t" . '<link rel="'.$rel.'" href="'.h($sample_style_file).'">' . "\n";
		$qt->appendv('plugin_head', $head);
	}

	//カスタム背景
	$enable_custom_bg = isset($style_config['textures']['custom']);
	$custom_bg = isset($style_custom_bg['filename']) ? get_file_path($style_custom_bg['filename']) : FALSE;
	
	$page_title_sample = __('ページ名') . $site_title_delim . '${site_title}';
	
	
	$tmpl_file = PLUGIN_DIR . 'app_config/general.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => $title, 'body' => $body);

}

/* End of file orgm_setting.inc.php */
/* Location: plugin/orgm_setting.inc.php */