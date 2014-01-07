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


function plugin_app_config_eyecatch_set_body()
{
	global $script, $vars, $eyecatch_converted, $whatsnew;

	if (isset($eyecatch_converted) && $eyecatch_converted)
	{
		return;
	}
	
	$_page = isset($vars['page']) ? $vars['page'] : '';

	//一般アクセス時には実行しない
	if ((is_page($_page) && ! check_editable($_page, FALSE, FALSE)) OR ! is_login())
	{
		return;
	}

	// 管理画面の時は、実行しない
	if ($vars['cmd'] !== 'read')
	{
		return;
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
	
	// 1ページもない場合は、移行しない
	if (count($pages) === 0)
	{
		//移行完了フラグを設定
		plugin_app_config_eyecatch_set_converted();
		return;
	}
	
	$title = __('アイキャッチの移行');
	$description = __('アイキャッチの移行を行います。');
	
	$qt = get_qt();
	$r_page = urlencode($vars['page']);
	
	$tmpl_file = PLUGIN_DIR . 'app_config/eyecatch.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	$qt->appendv('body_last', $body);
	
	$plugin_script = '
<script>
$(function(){

	$("#app_config_eyecatch_proceed").on("submit", function(e){
		e.preventDefault();
		
		$("input:submit", this).prop("disabled", true);
		
		var data = $(this).serialize();
		
		$.ajax(ORGM.baseUrl, {
			type: "POST",
			data: data,
			dataType: "json"
		}).then(
			function(res){
			
				if (res.error) {
					ORGM.notify(res.errorMessage, "danger");
					return;
				}
				
				$(".alert", $modal).fadeOut();
				$(".proceed-notice", $modal).html(res.message);
			
				$(".eyecatch-page-list a.btn", $modal).each(function(){
					var $self = $(this);
					var timeout = Math.floor(Math.random() * 1000);
					setTimeout(function(){
						$self.removeClass("btn-info").addClass("btn-danger");
					}, timeout);
					
					$self.on("click", function(){
						if ($self.is(".opened")) return;
						$self.removeClass("btn-danger").addClass("btn-success opened")
							.prepend($("<span></span>", {class: "glyphicon glyphicon-ok"}))
					});
				});
				
			},
			function(){
				ORGM.notify("問題が発生しました。ページを再読み込みし、もう一度お試しください。", "danger")
			}
		).always(function(){
			
		});
		
		
	});
	var $modal = $("#orgm_eyecatch_converter").modal();
	
});
</script>	
';
	
	$qt->appendv('plugin_script', $plugin_script);
}

function plugin_app_config_eyecatch_move_()
{
	global $script, $vars;
	
	$conf = orgm_ini_read();
	
	// metaをチェック
	$files = glob(META_DIR . '*.php');

	// kawazとsemiの場合、textureの指定をチェック
	$texture_path = '';
	$texture_repeat = 'repeat';
	$haikini = orgm_ini_read();
	if (($haikini['style_name'] == 'kawaz' OR $haikini['style_name'] == 'semi') && $haikini['style_texture'] != '')
	{
		$style_path = SKIN_DIR.$haikini['style_name'];
		$textures = array(
			'hemp-light'   => $style_path.'/img/hemp-cloth-05.jpg',
			'hemp-dark'    => $style_path.'/img/hemp-cloth-01.jpg',
			'square'       => $style_path.'/img/square_bg.png',
			'wood'         => $style_path.'/img/wood_pattern_rotate.png',
			'rainbow'      => $style_path.'/img/rainbow_bg.jpg',
		);
		$texture_path = $textures[$haikini['style_texture']];
		
		if ( ! file_exists(UPLOAD_DIR.basename($texture_path)))
		{
			copy($texture_path, UPLOAD_DIR.basename($texture_path));
		}
		
		if ($haikini['style_texture'] == 'rainbow')
		{
			$texture_repeat = 'cover';
		}
	}

	foreach ($files as $file)
	{
		$page = decode(basename($file, '.php'));
		$conf = meta_read($page);
		$meta = $conf['eyecatch'];

		if ($meta != NULL && isset($meta['images']) && count($meta['images']) > 0)
		{
			$eyecatch_str = '';

			// 背景画像の取得
			$background_image = '';
			if ($meta['background'] != 'false' && isset($meta['background']['image']) && $meta['background']['image'] != '' && $meta['background']['image'] != 'none')
			{
				$background_image = basename($meta['background']['image']);
			}
			
			


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
					else if ($background_image != '')
					{
						$image = $background_image;
					}
					else if ($texture_path != '')
					{
						$image = basename($texture_path);
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

				$slides = "#slide({$height},noindicator){{\n".$slides."\n}}\n";
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
				else if ($background_image != '')
				{
					$options[] = $background_image;
				}
				else if ($texture_path != '')
				{
					$options[] = basename($texture_path);
					$options[] = $texture_repeat;
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
	
	//移行完了フラグを設定
	plugin_app_config_eyecatch_set_converted();
	
	$json = array();
	
	$json['message'] = __('アイキャッチを移行しました。<br>下記の「<strong>アイキャッチを設置しているページ</strong>」の表示を確認して、問題があれば編集してください。<br><br><a href="#" class="btn btn-info" target="_blank">新しいアイキャッチについての解説はこちら <i class="glyphicon glyphicon-chevron-right"></i></a>');
	
	print_json($json);
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

function plugin_app_config_eyecatch_set_converted()
{
		// インストーラーを続けるため。。。
		$conf = array('eyecatch_converted' => 1);
		orgm_ini_write($conf);
}


/* End of file app_config_design.inc.php */
/* Location: /haik-contents/plugin/app_config_design.inc.php */