<?php
/**
 *   ページのメタ情報を変更する
 *   -------------------------------------------
 *   plugin/page_meta.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/02/13
 *   modified : 13/09/05
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

define('PLUGIN_PAGE_META_USE_GOO_GL', TRUE);

include_once(LIB_DIR . 'html_helper.php');

function plugin_page_meta_action()
{
	global $script, $vars, $style_name;
	
	$page = $vars['page'];

	if ( ! check_editable($page, FALSE, FALSE))
	{
		$json = array('error'=>__('管理者のみアクセスできます。'));
		print_json($json);
		exit;
	}
	
	$style_config = style_config_read($style_name);
	
	$mode = isset($vars['mode']) ? $vars['mode'] : '';
	
	//Ajax update
	if ($mode === 'update')
	{

		$keys = array(
			'title',
			'description',
			'auto_description',
			'keywords',
			'close',
			'password',
			'redirect',
			'redirect_status',
			'template_name',
			'user_head',
		);		
		$data = array_intersect_key($vars, array_flip($keys));
		
		$errjson = array(
			'error' => '',
			'item'  => '',
		);

		foreach ($data as $key => $value)
		{
			switch ($key)
			{
				case 'close':
					if ( ! preg_match('/^(?:public|closed|password|redirect)$/', $value))
					{
						die('Invalid value receieved: 1');
					}
					break;
				case 'password':
					if ($data['close'] === 'password')
					{
						if (($value == '') || (strlen($value) < 6))
						{
							$errjson['error'] = __('パスワードは6文字以上で入力してください。');
							$errjson['item'] = $key;
						}
					}
					break;
				case 'redirect':
					if ($data['close'] === 'redirect')
					{
						if ( ! is_url($value) && ! is_page($value))
						{
							$errjson['error'] = __('転送先にはURLかページ名を入力してください。');
							$errjson['item'] = $key;
						}
					}
					break;
				case 'redirect_status':
					if ($data['close'] === 'redirect')
					{
						if ($value !== '301' && $value !== '302')
						{
							$errjson['error'] = __('転送ステータスには301か302を指定してください。');
							$errjson['item'] = $key;
						}
					}
					break;
				case 'template_name':
					//存在しないレイアウトはデフォルトに変換
					if ( ! isset($style_config['templates'][$value]))
					{
						$data[$key] = $style_config['default_template'];
					}
					break;
				default:
			}
		}
		
		if ($errjson['error'] !== '')
		{
			print_json($errjson);
			exit;
		}
		
		//save
		meta_write($page, $data);

		app_put_lastmodified();

		
		if ($data['close'] !== 'public')
		{
			foreach (array('.tmp', '.tmpr') as $ext)
			{
				$tmpfile = CACHE_DIR . encode($page) . $ext;
				if (file_exists($tmpfile)) unlink($tmpfile);
			}
		}
		
		$json = array(
			'success' => 1,
			'redirect' => $script. '?' . rawurlencode($page),
		);
		
		print_json($json);
		
		exit;
	}


}


function plugin_page_meta_set_body()
{
	global $script, $vars, $defaultpage, $site_title;
	global $style_name;
	
	//一般アクセス時には実行しない
	if ((is_page($vars['page']) && ! check_editable($vars['page'], FALSE, FALSE)) OR ! is_login())
	{
		return;
	}
	
	$qt = get_qt();
	
	$page = $vars['page'];
	$r_page = urlencode($page);

	if ($page === $defaultpage)
	{
		$is_defaultpage = TRUE;
		$default_title = $site_title;
	}
	else
	{
		$is_defaultpage = FALSE;
		$default_title = $page;
	}
	
	$meta = meta_read($page);
	$config = style_config_read($style_name);
	
	$def = array(
		'title' => $default_title,
		'description' => '',
		'auto_description' => '',
		'keywords' => '',
		'close' => 'public',
		'password' => '',
		'redirect' => '',
		'redirect_status' => '301',
		'template_name' => isset($config['default_template']) ? $config['default_template'] : '',
		'user_head' => ''
	);
	
	$meta = array_merge($def, $meta);
	
	$meta['isDefaultPage'] = $is_defaultpage;

	
	
	//templates
	if ( ! isset($config['templates']) OR ! is_array($config['templates']))
	{
		$config['templates'] = array();
	}
	
	$thumbnail_fmt = 'thumbnail.%s.png';
	$skindir = SKIN_DIR . $style_name . '/';
	foreach ($config['templates'] as $name => $options)
	{
		if (isset($options['thumbnail']))
		{
			$config['templates'][$name]['thumbnail'] = $skindir . $options['thumbnail'];
			continue;
		}
		$thumbnail = $skindir . sprintf($thumbnail_fmt, $name);
		$config['templates'][$name]['thumbnail'] = FALSE;
		if (file_exists($thumbnail))
		{
			$config['templates'][$name]['thumbnail'] = $thumbnail;
		}
		else
		{
//			$config['templates'][$name]['thumbnail'] = 'http://dummyimage.com/300x200.png';
		}
	}

	$tmpls = $config['templates'];
	
	$meta['templates'] = $tmpls;
	
    if (isset($meta['password']) && $meta['password'] !== '' && exist_plugin('secret'))
    {
	    $meta['limited_url'] = plugin_secret_get_url();
    }
    
    //短縮URL
    $shortened_url = $meta['shortened_url'] = FALSE;
    if (PLUGIN_PAGE_META_USE_GOO_GL && exist_plugin('goo_gl'))
    {
	    $shortened_url = $meta['shortened_url'] = plugin_goo_gl_get_shortened(get_page_url($vars['page']));
    }
	
	$json = array(
		'pageMeta' => $meta
	);
	$qt->setjsv($json);
		
	$helper = new HTML_Helper();
	
	$tmpl_file = PLUGIN_DIR . 'page_meta/element.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	$qt->appendv('body_last', $body);
}

/* End of file page_meta.inc.php */
/* Location: /haik-contents/plugin/page_meta.inc.php */