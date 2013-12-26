<?php
/**
 *   app_config_eyecatch
 *   -------------------------------------------
 *   app_config_design.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/12/18
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
function plugin_app_config_eyecatch_action()
{
	global $script, $vars;

	$title = __('アイキャッチの移行');
	$description = __('アイキャッチの移行を行います。');

	$qt = get_qt();

	// ! app_config_init を呼ぶか呼ばないかは後でチェック！
	if ( ! ss_admin_check())
	{
		set_flash_msg('管理者のみアクセスできます。', 'error');
		redirect($script);
		exit;
	}

	if (isset($vars['phase']) && $vars['phase'] === 'move')
	{
		// ページ毎の移行処理
		plugin_app_config_eyecatch_move_();
	}
	
	$pages = array();
	$files = glob(META_DIR . '*.php');
	foreach ($files as $file)
	{
		$page = decode(basename($file, '.php'));
		$meta = meta_read($page);
		if (isset($meta['eyecatch']))
		{
			$pages[] = $page;
		}
	}
	
	$tmpl_file = PLUGIN_DIR . 'app_config/eyecatch.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	
	return array('msg' => $title, 'body' => $body);
}

function plugin_app_config_eyecatch_move_()
{
	global $script, $vars;
	
	// metaをチェック
	$files = glob(META_DIR . '*.php');

	foreach ($files as $file)
	{
		$page = decode(basename($file, '.php'));
		$conf = meta_read($page);
		$meta = $conf['eyecatch'];

		if ($meta != NULL && isset($meta['images']) && count($meta['images']) > 0)
		{
			$eyecatch_str = '';

			// ! eyecatchプラグインの仕様に変更する
			if (count($meta['images']) > 1)
			{
				// eyecatchが複数 → スライド
				$slides = array();
				foreach ($meta['images'] as $data)
				{
					$title = $data['title'];
					$content = $data['content'];
					$content = str_replace("\n", '&br;', $content);
					$content = str_replace("\r", '', $content);

					$image = '';
					if (isset($data['image']) && $data['image'] != '' && $data['image'] != 'none')
					{
						$image = basename($data['image']);
					}
					else if ($meta['background'] != 'false' && isset($meta['background']['image']) && $meta['background']['image'] != '' && $meta['background']['image'] != 'none')
					{
						$image = basename($meta['background']['image']);
					}
					$slides[] = "{$image},{$title},{$content}";
				}
				
				$options = array();
				$options[] = 'fit';
				
				$height = '320';
				// height
				if (isset($meta['height']) && $meta['height'] != '')
				{
					$options[] = $height = $meta['height'];
				}

				$options = array_merge($options, plugin_app_config_eyecatch_get_bgoption($meta));

				$slides = join("\n", $slides);
				$options = join(',', $options);

				$slides = "#slide({$height}){{\n".$slides."\n}}\n";
				$eyecatch_str = "#eyecatch({$options}){{{\n{$slides}\n}}}\n";
			}
			else
			{
				// eyecatchが1枚
				// ! タイトル、サブタイトル色問題
				$data = array_shift($meta['images']);

				$title = trim($data['title']);
				$title_str = '';
				if ($title != '')
				{
					$title_op = array();
					if (isset($data['title_size']) && $data['title_size'] != '') $title_op[] = $data['title_size'];
					if (isset($data['title_color']) && $data['title_color'] != '' ) $title_op[] = $data['title_color'];
					if (count($title_op) > 0)
					{
						$title = "&deco(".join(',',$title_op)."){".$title."};";
					}
					
					$title_str = "&h1{{$title}};\n";
				}
				
				$content = trim($data['content']);
				$content_str = '';
				if ($content != '')
				{
					$content_op = array();
					if (isset($data['content_size']) && $data['content_size'] != '')
					{
						if (is_numeric($data['content_size']))
						{
							$data['content_size'] = $data['content_size'] . 'px';
						}
						$content_op[] = 'font-size:'.$data['content_size'];
					}
					if (isset($data['content_color']) && $data['content_color'] != '') $content_op[] = 'color:'.$data['content_color'];
					if (count($content_op) > 0)
					{
						$content = "STYLE:".join(';', $content_op)."\n{$content}";
					}
					$content_str = "#cols(++++){{{\n{$content}\n}}}\n";
				}

				$options = array();

				// image
				if (isset($data['image']) && $data['image'] != '' && $data['image'] != 'none')
				{
					$options[] = basename($data['image']);
				}
				else if ($meta['background'] != 'false' && isset($meta['background']['image']) && $meta['background']['image'] != '' && $meta['background']['image'] != 'none')
				{
					$options[] = basename($meta['background']['image']);
				
				}
				
				// height
				if (isset($meta['height']) && $meta['height'] != '')
				{
					$options[] = $meta['height'];
				}
				
				// background
				$options = array_merge($options, plugin_app_config_eyecatch_get_bgoption($meta));
				$options = join(',', $options);
				$eyecatch_str = "#eyecatch({$options}){{{{\n{$title_str}\n{$content_str}\n}}}}\n";
			}

			// ページに書込み
			$str = get_source($page, TRUE, TRUE);
			$str = $eyecatch_str.$str;
			$datafile = get_filename($page);
			$fp = fopen($datafile, 'a') or die('書込みができませんでした（'.h(basename($datafile)).':'.encode($page));
			set_file_buffer($fp, 0);
			flock($fp, LOCK_EX);
			ftruncate($fp, 0);
			rewind($fp);
			fputs($fp, $str);
			flock($fp, LOCK_UN);
			fclose($fp);

			// metaからeyecatchを削除して保存
			unset($conf['eyecatch']);
			meta_write($page, $conf, NULL, FALSE);
		}
	}

	set_flash_msg('アイキャッチを移行しました。');
	$redirect = isset($vars['refer']) ? $vars['refer'] : $script;
	redirect($redirect);
	exit;
}

function plugin_app_config_eyecatch_get_bgoption($meta)
{
	$options = array();

	// background color
	if ($meta['background'] && $meta['background'] != 'false')
	{
		if (isset($meta['background']['color']))
		{
			$options[] = 'color='.$meta['background']['color'];
		}
		
		// repeat or cover
		if (isset($meta['background']['repeat']) && $meta['background']['repeat'] == 'repeat')
		{
			$options[] = 'repeat';
		}
		else
		{
			$options[] = 'cover';
		}
	}
	else
	{
		$options[] = 'cover';
	}

	return $options;
}

/* End of file app_config_design.inc.php */
/* Location: /haik-contents/plugin/app_config_design.inc.php */