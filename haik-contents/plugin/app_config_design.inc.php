<?php
/**
 *   app_config_design
 *   -------------------------------------------
 *   app_config_design.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/07/29
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
function plugin_app_config_design_action()
{
	global $script, $vars, $config;
	global $style_color, $style_texture, $admin_style_name,$style_custom_bg;
	global $use_less;

	$title = __('サイト情報');
	$description = __('サイト情報の設定を行います。');

	$qt = get_qt();

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

					$res[$type]['html'] = $res[$type]['html'] . '<li class="sample-cut '.($matched ? 'active' : '').'"><a href="#" class="thumbnail '.$style_class_name.'-'.h($value).'" data-style_'.$type.'="'.h($value).'" title="'.h($value).'">'.h($value).'</a></li>';
					
					$matched_cnt = $matched ? $matched_cnt+1 : $matched_cnt;
				}
				$res[$type]['html'] = '<li class="sample-cut '.($matched_cnt ? '' : 'active').'"><a href="#"  class="thumbnail '.$style_class_name.'" data-style_'.h($type).'="" title="'.h(__('なし')).'">'.__('なし'). '</a></li>' . $res[$type]['html'];
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

			$r_refer = isset($vars['refer']) ? rawurlencode($vars['refer']) : '';
			
			$json['redirect'] = $script . '?' . $r_refer;
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
		$fields = array('style_name', 'style_color', 'style_texture', 'style_custom_bg');
		$res = array();
		
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
		
		$data = array_intersect_key($vars, array_flip($fields));
		orgm_ini_write($data);
		
		unset($_SESSION['preview_skin']);

		set_flash_msg('設定を更新しました。');
		$redirect = isset($vars['refer']) ? $vars['refer'] : $script;
		redirect($redirect);
		exit;
	}

	// !スキンの取得はどこで？
	// ORGM.skins とかに変更したい
	//デザインテンプレートの指定
	$obj = dir(SKIN_DIR);
	$skin_dirs = array();
	while ($entry = $obj->read())
	{
		if(is_dir(SKIN_DIR.$entry) && ($entry!='.') && ($entry!='..') && (file_exists(SKIN_DIR.$entry.'/theme.yml')))
		{
			if ($admin_style_name === $entry)
			{
				continue;
			}
			$skin_dirs[$entry] = style_config_read($entry);
		}
	}
	ksort($skin_dirs);

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
	if ($use_less)
	{
		$sample_style_file = SKIN_DIR . $style_name . '/less/samples.less';
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
		$sample_style_file = SKIN_DIR . $style_name . '/css/samples.css';
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
	
	$tmpl_file = PLUGIN_DIR . 'app_config/design.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	
	return array('msg' => $title, 'body' => $body);
}

function plugin_app_config_design_set_body()
{
	global $script, $vars, $config;
	global $style_name, $style_color, $style_texture, $admin_style_name, $style_custom_bg;
	global $use_less;


	//一般アクセス時には実行しない
	if ((is_page($vars['page']) && ! check_editable($vars['page'], FALSE, FALSE)) OR ! is_login())
	{
		return;
	}
	
	$qt = get_qt();

	$title = __('デザイン変更');
	$description = __('サイトのデザインを変更します');

	
	$page = $vars['page'];
	$r_page = urlencode($page);

	// !スキンの取得はどこで？
	// ORGM.skins とかに変更したい
	//デザインテンプレートの指定
	$obj = dir(SKIN_DIR);
	$skin_dirs = array();
	while ($entry = $obj->read())
	{
		if(is_dir(SKIN_DIR.$entry) && ($entry!='.') && ($entry!='..') && (file_exists(SKIN_DIR.$entry.'/theme.yml')))
		{
			if ($admin_style_name === $entry)
			{
				continue;
			}
			$skin_dirs[$entry] = style_config_read($entry);
		}
	}
	ksort($skin_dirs);

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

	$include_css = '	<link rel="stylesheet" type="text/css" href="'. PLUGIN_DIR .'app_config/design.css">
';
	$qt->appendv('plugin_head', $include_css);

	$include_script = '
<script type="text/javascript" src="'. PLUGIN_DIR .'app_config/design.js"></script>
';
	$qt->appendv_once('plugin_app_config_design', 'plugin_script', $include_script);

	$json = array(
		'options' => array(
			'style_name'    => $style_name,
			'style_color'   => $style_color,
			'style_texture' => $style_texture,
			'style_custom_bg' => $style_custom_bg,
		)
	);
	$qt->setjsv($json);


	$tmpl_file = PLUGIN_DIR . 'app_config/design.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	$qt->appendv('body_last', $body);
}

/* End of file app_config_design.inc.php */
/* Location: /haik-contents/plugin/app_config_design.inc.php */