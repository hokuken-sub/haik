<?php
/**
 *   eyecatch setting
 *   -------------------------------------------
 *   eyecatch.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/02/14
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */

include_once(LIB_DIR . 'html_helper.php');

function plugin_eyecatch_action()
{
	global $script, $vars;

	$page = $vars['page'];

	if ( ! check_editable($page, FALSE, FALSE))
	{
		set_flash_msg(__('管理者のみアクセスできます。'), 'error');
		redirect($script);
		exit;
	}
	
	$qt = get_qt();

	$css = '<link rel="stylesheet" href="'. PLUGIN_DIR .'eyecatch/eyecatch.css" />
';
	$qt->appendv('plugin_head', $css);

	$plugin_script = '
<script src="'.PLUGIN_DIR.'eyecatch/eyecatch.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>
';
	$qt->appendv_once('plugin_eyecatch', 'plugin_script', $plugin_script);

	if (exist_plugin('filer'))
		plugin_filer_set_iframe(':image', 'exclusive', FALSE);

	$mode = isset($vars['mode']) ? $vars['mode'] : '';
	$func_name = 'plugin_eyecatch_' . $mode . '_';
	
	if ($mode !== '' && function_exists($func_name))
	{
		return $func_name();
	}

	$qt = get_qt();
	$helper = new HTML_Helper();
	
	$page = $vars['page'];
	$r_page = rawurlencode($page);
	
	$data = meta_read($page, 'eyecatch');
	$data = plugin_eyecatch_modify_data($data);

	$tmpl_file = PLUGIN_DIR . 'eyecatch/index.html';

	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();	


	$msg = sprintf(__('%sのアイキャッチ編集'), $page);

	foreach ($data['images'] as $key => $row)
	{
		$data['images'][$key]['convert_title'] = convert_html($row['title'], TRUE);
		$data['images'][$key]['convert_content'] = convert_html($row['content']);
	}
	
	//actions
	$ec_script = $script . '?cmd=eyecatch&page='. $r_page .'&mode=';
	$json = array(
		'images' => $data['images'],
		'orgImages' => $data['images'],
		'updateUrl' => $ec_script . 'update',
		'previewUrl' => $ec_script . 'preview',
		'background' => $data['background'],
		'orgBackground' => $data['background'],
		'height' => $data['height'],
		'orgHeight' => $data['height'],
	);
	$qt->setjsv(array('eyecatch'=> $json));

	return array('msg'=>$msg, 'body'=>$body);
}

function plugin_eyecatch_convert()
{
	$args = func_get_args();
	
	$html = '';
	if (exist_plugin('section'))
	{
		array_unshift($args, 'eyecatch');
		$html = call_user_func_array('plugin_section_convert', $args);
	}
	
	$qt = get_qt();
	$qt->setv('eyecatch', $html);
	
	return '';
}

function plugin_eyecatch_modify_data($data)
{
	$data = array_intersect_key($data, array_flip(array('images', 'background', 'height')));
	
	$data['images'] = (isset($data['images']) && is_array($data['images'])) ? $data['images'] : array();
	$data['background'] = isset($data['background']) ? $data['background'] : FALSE;
	$data['height'] = (isset($data['height'])) ? $data['height'] : '';

	$image_prototype = array(
		'title' => '',
		'title_color' => '',
		'title_size' => '',
		'content' => '',
		'content_color' => '',
		'content_size' => '',
		'image' => '',
	);
	foreach ($data['images'] as $i => $image)
	{
		$image = array_merge($image_prototype, $image);
		unset($image['id']);
		if (isset($image['image']) && $image['image'])
		{
			$image['image'] = get_file_path($image['image']);
		}
		$data['images'][$i] = $image;
	}
	
	return $data;

}

function plugin_eyecatch_preview_()
{

	global $script, $vars;
	
	$page = $vars['page'];
	
	$data = plugin_eyecatch_modify_data($vars);
	$org_data = meta_read($page, 'eyecatch');
	
	if (is_null($org_data))
	{
		$org_data = array();
	}
	$data = array_merge($org_data, $data);

	$html = create_eyecatch($data);
	
	$images = array();
	foreach ($data['images'] as $key => $row)
	{
		$images[$key]['convert_title'] = convert_html($row['title'], TRUE);
		$images[$key]['convert_content'] = convert_html($row['content']);
	}

	$json = array(
		'eyecatch' => $html,
		'images'   => $images
	);
	print_json($json);
	exit;
}

function plugin_eyecatch_update_()
{
	global $script, $vars;
	
	$page = $vars['page'];
	
	$data = plugin_eyecatch_modify_data($vars);
	$org_data = meta_read($page, 'eyecatch');
	
	if (is_null($org_data))
	{
		$org_data = array();
	}
	
	$data = array_merge($org_data, $data);

	//save
	if (meta_write($page, 'eyecatch', $data, FALSE))
	{
		$json = array('success'=>'成功！');
		print_json($json);
		set_flash_msg(__('アイキャッチを更新しました。'));
		exit;
	}
	else
	{
		//error
		$json = array(
			'error' => __('保存できませんでした。もう一度更新してください。'),
		);
		print_json($json);
		exit;
	}

}

function plugin_eyecatch_delete_()
{

	global $script, $vars;
	
	$page = $vars['page'];
	$id = isset($vars['id']) ? (int)$vars['id'] : 0;
	
	$data = meta_read($page, 'eyecatch');
	$images = isset($data['images']) ? $data['images'] : array();
	
	if ( ! $images OR ! is_array($images) OR ! isset($images[$id]))
	{
		//error
		die('不正なリクエストです。');
	}
	
	array_splice($images, $id, 1);

	$data['images'] = $images;

	//save
	if (meta_write($page, 'eyecatch', $data, FALSE))
	{
		$json = array(
			'eyecatch' => create_eyecatch($page),
		);
		print_json($json);
		exit;
	}
	else
	{
		//error
		$json = array(
			'error' => __('削除できませんでした。もう一度更新してください。'),
		);
		print_json($json);
	}
	
	
	
}

function plugin_eyecatch_bg_update_()
{
	global $script, $vars;
	
	$page = $vars['page'];

	$style_ = array('color', 'image', 'repeat', 'position');	
	$bg_style = array_intersect_key($vars, array_flip($style_));
	
	$reset = isset($vars['reset']) ? $vars['reset'] : FALSE;
	
	if ($bg_style['image'] === '')
	{
		$bg_style['image'] = 'none';
	}
	else
	{
		$bg_style['image'] = 'url('. get_file_path($bg_style['image']) .')';
	}
	$bg_style['color'] = $bg_style['color'];
	$bg_style['position'] = ($bg_style['position'] === '') ? '0 0' : $bg_style['position'];
	
	if ($reset)
	{
		$bg_style = FALSE;
	}
	
	$data = meta_read($page, 'eyecatch');
	$data['background'] = $bg_style;
	
	$height = '';
	if (preg_match('/^([0-9.]+(px)?)$/', trim($vars['height']), $matches))
	{
		$height = $matches[1];
	}
	$data['height'] = $height;
	
	if (meta_write($page, 'eyecatch', $data, FALSE))
	{
		$json = $data;
	}
	else
	{
		//faild
		$json = array(
			'error' => __('背景画像設定の更新に失敗しました。')
		);
	}
	print_json($json);
	exit;
}

/**
 * 順番を並び替える。
 * もともとのIDをカンマ区切りで並べたものを受け取る。
 */
function plugin_eyecatch_order_()
{
	global $vars, $script;
	
	$page = $vars['page'];
	$order_list = explode(',', $vars['order_list']);
	
	$data = meta_read($page, 'eyecatch');
	$images = isset($data['images']) ? $data['images'] : array();
	
	if (count($images) !== count($order_list))
	{
		//error
	}
	
	$new_order = array();
	foreach ($order_list as $i => $id)
	{
		if (array_key_exists($id, $images))
		{
			$new_order[] = $images[$id];
		}
	}
	
	$data['images'] = $new_order;

	//save
	if (meta_write($page, 'eyecatch', $data, FALSE))
	{
		$json = array(
			'eyecatch' => create_eyecatch($page),
		);
		print_json($json);
		exit;
	}
	else
	{
		//error
		$json = array(
			'error' => __('保存できませんでした。もう一度更新してください。'),
		);
		print_json($json);
		exit;
	}
		
	
}
 

/* End of file eyecatch.inc.php */
/* Location: /haik-contents/eyecatch/eyecatch.inc.php */