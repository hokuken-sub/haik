<?php
/**
 *   QHM Show Plugin
 *   -------------------------------------------
 *   show.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2010-09-14
 *   modified :
 *   
 *   Image showing plugin
 *   
 *   Usage :
 *   
 */

/////////////////////////////////////////////////
// Default settings

// Horizontal alignment
define('PLUGIN_SHOW_DEFAULT_ALIGN', 'left'); // 'left', 'center', 'right'

// Text wrapping
define('PLUGIN_SHOW_WRAP_TABLE', FALSE); // TRUE, FALSE

// URL指定時に画像サイズを取得するか
define('PLUGIN_SHOW_URL_GET_IMAGE_SIZE', FALSE); // FALSE, TRUE

// UPLOAD_DIR のデータ(画像ファイルのみ)に直接アクセスさせる
define('PLUGIN_SHOW_DIRECT_ACCESS', FALSE); // FALSE or TRUE
// - これは従来のインラインイメージ処理を互換のために残すもので
//   あり、高速化のためのオプションではありません
// - UPLOAD_DIR をWebサーバー上に露出させており、かつ直接アクセス
//   できる(アクセス制限がない)状態である必要があります
// - Apache などでは UPLOAD_DIR/.htaccess を削除する必要があります
// - ブラウザによってはインラインイメージの表示や、「インライン
//   イメージだけを表示」させた時などに不具合が出る場合があります

/////////////////////////////////////////////////

// Image suffixes allowed
define('PLUGIN_SHOW_IMAGE', '/\.(gif|png|jpe?g)$/i');

// Usage (a part of)
define('PLUGIN_SHOW_USAGE', "(attached-file-path[,parameters, ... ][,title])");

// Max file size for upload on script of PukiWikiX_FILESIZE
define('PLUGIN_SHOW_MAX_FILESIZE', (1024 * 1024 * 5)); // default: 5MB

/**
* 画像を添付するためのもの
*/
function plugin_show_action()
{
	global $script,$vars,$username;
	global $html_transitional;
	
	//check auth
	$editable = edit_auth($vars['refer'], FALSE, FALSE);
	
	$ret = array();
	if ( ! $editable)
	{
		$ret['error'] = __('管理者モードでのみファイルを添付できます。');
	}
	if ( ! is_ajax())
	{
		$ret['error'] = __('不正なリクエストです。');
	}

	if (isset($_FILES['files'])){
		switch ($_FILES['files']['error'][0])
		{
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$ret['error'] = __('アップロードされたファイルが大きすぎます。');
				return $ret;
				break;
			default:
				$ret['error'] = __('アップロードに失敗しました。');
				return $ret;
		}

		if (exist_plugin('filer'))
		{
			$filer = plugin_filer_get_instance();
			$file_id_list = $filer->saveUploadFiles();
			if ($file_id_list)
			{
				$file_id = array_pop($file_id_list);
				$file = $filer->findFile($file_id);
				$vars['filename'] = $file['filename'];
			}
		}
	}

	$ret = plugin_show_swap_dummy();
	print_json($ret);
	exit;
}

function plugin_show_inline()
{

	global $script,$vars,$digest,$username;

	$args = func_get_args();

    $editable = edit_auth($vars['page'], FALSE, FALSE);
    
    //添付用のリンクを表示
    if ( ! is_url($args[0]) && ! file_exists(get_file_path($args[0])))
    {
    	if (pathinfo($args[0], PATHINFO_EXTENSION) === 'hdummy')
    	{
	    	return plugin_show_put_dummy($args[0], $args);
    	}
    }

	$params = plugin_show_body($args);

	//error check
	if (isset($params['_error']) && $params['_error'] != '') {
	    
	    if (isset($params['_error']) && $params['_error'] != '') {
			// Error
			return '&amp;show(): ' . $params['_error'] . ';';
		}
	}
	
	return $params['_body'];

}

function plugin_show_convert()
{
	if (! func_num_args())
		return '<p>' . __('#show(ファイル名[,オプション, ... ][,画像の説明])'). '</p>' . "\n";

	$params = plugin_show_body(func_get_args());

	if (isset($params['_error']) && $params['_error'] != '') {
		return sprintf("<p>#show: %s</p>\n", $params['_error']);
	}

	if ((PLUGIN_SHOW_WRAP_TABLE && ! $params['nowrap']) || $params['wrap']) {
		// 枠で包む
		// margin:auto
		//	Mozilla 1.x  = x (wrap,aroundが効かない)
		//	Opera 6      = o
		//	Netscape 6   = x (wrap,aroundが効かない)
		//	IE 6         = x (wrap,aroundが効かない)
		// margin:0px
		//	Mozilla 1.x  = x (wrapで寄せが効かない)
		//	Opera 6      = x (wrapで寄せが効かない)
		//	Netscape 6   = x (wrapで寄せが効かない)
		//	IE6          = o
		$margin = ($params['around'] ? '0px' : 'auto');
		$margin_align = ($params['_align'] == 'center') ? '' : ";margin-{$params['_align']}:0px";
		$params['_body'] = <<<EOD
<table class="style_table" style="margin:$margin$margin_align">
 <tr>
  <td class="style_td">{$params['_body']}</td>
 </tr>
</table>
EOD;
	}

	$style_ard = '';
	$class = '';
	if ($params['around']) {
		$param_ard = ($params['_align'] == 'right') ? 'right' : 'left';
		$class .= 'pull-' . $param_ard;
		$style = "float:$param_ard";
		$style_ard = "_" . $param_ard;
		
	} else {
		$class .= 'align-' . $params['_align'];
	}

	// divで包む
	return "<div class=\"orgm-show-block {$class}\">{$params['_body']}</div>\n";
}

function plugin_show_body($args)
{
	global $script, $vars;
	global $WikiName, $BracketName; // compat
	$qt = get_qt();

	// 戻り値
	$params = array(
		'left'    => FALSE, // 左寄せ
		'center'  => FALSE, // 中央寄せ
		'right'   => FALSE, // 右寄せ
		'aroundl' => FALSE, //回り込み左寄せ
		'aroundc' => FALSE, //回り込み中央寄せ
		'aroundr' => FALSE, //回り込み右寄せ
		'wrap'    => FALSE, // TABLEで囲む
		'nowrap'  => FALSE, // TABLEで囲まない
		'around'  => FALSE, // 回り込み
		'noicon'  => FALSE, // アイコンを表示しない
		'nolink'  => TRUE, // 元ファイルへのリンクを張らない
		'popup'   => FALSE, //lightbox で表示
		'normal'  => FALSE, //画像へのリンクを付ける
		'linkurl' => FALSE, //popup, normal のリンク先
		'label'   => FALSE, //labelを指定すると強制的に表示をlabelにする(popup想定)。画像ファイルを指定するとそれを表示する。
		'noimg'   => FALSE, // 画像を展開しない
		'zoom'    => FALSE, // 縦横比を保持する
		'change'  => FALSE, // マウスオーバーで、画像を切り替える
		'lighter' => FALSE, // マウスオーバーで、画像の明るさを上げる
		'circle'  => FALSE, // .img-circle by bootstrap
		'rounded' => FALSE, // .img-rounded by bootstrap
		'round'   => FALSE, // .img-rounded
		'polaroid'=> FALSE, // .img-polaroid by bootstrap
		'pola'    => FALSE, // .img-polaroid
		'class'   => '', // 指定したClass を付ける
		'ogp'     => FALSE, // og:image として使用する
		'thumbnail' => FALSE, // linkurl やポップアップ機能が有効な際に表示を切り替える
		'_size'  => FALSE, // サイズ指定あり
		'_w'     => 0,       // 幅
		'_h'     => 0,       // 高さ
		'_%'     => 0,     // 拡大率
		'_args'  => array(),
		'_done'  => FALSE,
		'_error' => ''
	);

	// 添付ファイルのあるページ: defaultは現在のページ名
	$page = isset($vars['page']) ? $vars['page'] : '';

	// 添付ファイルのファイル名
	$name = '';

	// 添付ファイルまでのパスおよび(実際の)ファイル名
	$file = '';

	// 第一引数: "画像ファイル名"、あるいは"画像ファイルパス"、あるいは"画像ファイルURL"を指定
	$name = array_shift($args);
	$is_url = is_url($name);

	//画像ファイルかどうか
	if (!preg_match(PLUGIN_SHOW_IMAGE, $name)) {
		$params['_error'] = sprintf(__("画像ファイルではありません。：''%s''"), h($name));
	}

	if( !$is_url ){
		$file = $name;
		if( !is_file($file) ){			
			$file = UPLOAD_DIR.$file;
			if( !is_file($file) ){
				$params['_error'] = sprintf(__("ファイルが見つかりません。：''%s''"), h($name));
				return $params;
			}
		}
	}

	// 残りの引数の処理
	if( !empty($args) ){
		foreach ($args as $arg) {
			plugin_show_check_arg($arg, $params);
		}
	}

	if ( is_page($params['linkurl']) ) {
		$params['linkurl'] = get_page_url($params['linkurl']);
	}

	/*
	 $nameをもとに以下の変数を設定
	 $url,$url2 : URL
	 $title :タイトル
	 $info : 画像ファイルのときgetimagesize()の'size'
	         画像ファイル以外のファイルの情報
	         添付ファイルのとき : ファイルの最終更新日とサイズ
	         URLのとき : URLそのもの
	*/
	$title = $url = $url2 = $info = $style = '';
	$width = $height = 0;
	$matches = array();

	if ($is_url) {	// URL
		if (PKWK_DISABLE_INLINE_IMAGE_FROM_URI) {
			$url = h($name);
			$params['_body'] = '<a href="' . $url . '">' . $url . '</a>';
			return $params;
		}

		$url = $url2 = h($name);
		$title = h(preg_match('/\/(.+?)$/', $name, $matches) ? $matches[1] : $url);

		if (PLUGIN_SHOW_URL_GET_IMAGE_SIZE && (bool)ini_get('allow_url_fopen')) {
			$size = @getimagesize($name);
			if (is_array($size)) {
				$width  = $size[0];
				$height = $size[1];
				$info   = $size[3];
			}
		}

	} else { // 添付ファイル

		$title = h($name);

		$file = (substr($file, 0, 2)== './') ? substr($file, 2) : $file;
		$url = $url2 = $file;

		$width = $height = 0;
		$size = @getimagesize($file);
		if (is_array($size)) {
			$width  = $size[0];
			$height = $size[1];
		}
	}
	
	//first Image をセット
	$qt->set_first_image($is_url? $url: (dirname($script). '/'. $url), $params['ogp']);

	// 拡張パラメータをチェック
	if (! empty($params['_args'])) {
		$_title = array();
		foreach ($params['_args'] as $arg) {
			if (preg_match('/^([0-9]+)x([0-9]+)$/', $arg, $matches)) {
				$params['_size'] = TRUE;
				$params['_w'] = $matches[1];
				$params['_h'] = $matches[2];

			} else if (preg_match('/^([0-9.]+)%$/', $arg, $matches) && $matches[1] > 0) {
				$params['_%'] = $matches[1];

			} else {
				$_title[] = $arg;
			}
		}

		if (! empty($_title)) {
			$title = h(join(',', $_title));
			$title = make_line_rules($title);
		}
	}

	// 画像サイズ調整
	// 指定されたサイズを使用する
	if ($params['_size']) {
		if ($width == 0 && $height == 0) {
			$width  = $params['_w'];
			$height = $params['_h'];
		} else if ($params['zoom']) {
			$_w = $params['_w'] ? $width  / $params['_w'] : 0;
			$_h = $params['_h'] ? $height / $params['_h'] : 0;
			$zoom = max($_w, $_h);
			if ($zoom) {
				$width  = (int)($width  / $zoom);
				$height = (int)($height / $zoom);
			}
		} else {
			$width  = $params['_w'] ? $params['_w'] : $width;
			$height = $params['_h'] ? $params['_h'] : $height;
		}
	}
	if ($params['_%']) {
		$width  = (int)($width  * $params['_%'] / 100);
		$height = (int)($height * $params['_%'] / 100);
	}
	if ($params['_size'] OR $params['_%'])
	{
		if ($width && $height) $info = "width=\"$width\" height=\"$height\" ";
	}
	else
	{
		$style = 'style="max-width:100%;" ';
	}

	// アラインメント判定
	$params['_align'] = PLUGIN_SHOW_DEFAULT_ALIGN;
	foreach (array('right', 'left', 'center', 'aroundr', 'aroundc', 'aroundl') as $align) {
		//aroundx
		if (strpos($align, 'around') === 0 && $params[$align]) {
			$params['around'] = TRUE;
			$align = substr($align, -1, 1);
			switch ($align) {
				case 'r':
					$align = 'right';
					break;
				case 'c':
					$align = 'center';
					break;
				default:
					$align = 'left';
			}
			$params['_align'] = $align;
			break;
		}
		else if ($params[$align])  {
			$params['_align'] = $align;
			break;
		}
	}

	$mouseover = '';
	if ( $params['change'] ){
		$a_path = explode('.', $url);
		$a_path[ (count($a_path)-2) ] .= '_onmouse';
		$mo_url = join('.', $a_path);
		$mouseover = " onmouseover=\"this.src='{$mo_url}'\" onmouseout=\"this.src='{$url}'\" ";
		$mouseover .= 'onload="qhm_preload(\''. $mo_url .'\');"';
		
		//preload
		$addscript = '
<script type="text/javascript">
var qhm_preloaded = {};
function qhm_preload(src) {
	if (typeof document.images != "undefined" && typeof qhm_preloaded[src] == "undefined") {
		var img = new Image();
		img.src = src;
		qhm_preloaded[src] = 1;
	}
}
</script>
';
		$qt->appendv_once('plugin_show_preload', 'plugin_script', $addscript);
	}
	
	$iclass = '';

	$aclass = $arel = '';
	$url2 = $params['linkurl']? $params['linkurl']: $url2;
	
	//異なるURLが2つある場合、nolink をFALSEに
	if ($url2 != $url) {
		$params['nolink'] = FALSE;
	}
	
	//表示設定
	if ($params['popup'])
	{
		$addscript = '
<script type="text/javascript" src="'.JS_DIR.'bootstrap-lightbox.js"></script>
<link href="'.CSS_DIR.'bootstrap-lightbox.css" rel="stylesheet">
<script type="text/javascript">
$(function(){
	$("a.orgm-show-popup").on("click", function(e){
		e.preventDefault();
		var $self = $(this)
		  , data = {
			filepath: $self.attr("href"),
			title: $self.attr("title")
		};
		$("#pluginShowPopupTmpl").tmpl(data).appendTo("body");
		$("#pluginShowPopup").lightbox();
		
	});
	
	$(document).on("hidden.bs.modal", "#pluginShowPopup", function(){
		$(this).remove();
	});
});
</script>
';
		$qt->appendv_once('plugin_show_popup', 'plugin_script', $addscript);
		
		$tmpl = '
<script type="text/x-jquery-tmpl" id="pluginShowPopupTmpl">
<div id="pluginShowPopup" class="modal lightbox fade" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content lightbox-content">
		<img src="${filepath}">
		<div class="lightbox-caption"><p>${title}</p></div>
		</div>
	</div>
</div>

</script>
';
		$qt->appendv_once('plugin_show_popup_tmpl', 'body_last', $tmpl);
		
		//文字列の場合：グループ
		if ($params['popup'] !== TRUE) {
			$gb_type = ($url == $url2)? 'imageset': 'pageset';
			$gb_grp = $params['popup'];
//			$arel = ' rel="_'. $gb_grp.'"';
			$aclass = 'orgm-show-popup';
		} else {
			$aclass = 'orgm-show-popup';
		}
		$params['nolink'] = FALSE;
	}
	else if ($params['normal'])
	{
		$params['nolink'] = FALSE;
	}

	if ($params['thumbnail'] !== FALSE)
	{
		$aclass = trim($aclass . ' thumbnail');
	}
	
	foreach (array('circle', 'rounded', 'round', 'polaroid', 'pola') as $deco)
	{
		
		if ($params[$deco] !== FALSE)
		{
			switch ($deco)
			{
				case 'pola':
				case 'polaroid':
					$params['class'] .= ' img-thumbnail';
					break;
				case 'round':
				case 'rounded':
					$params['class'] .= ' img-rounded';
					break;
				default:
					$params['class'] .= ' img-' . $deco;
			}
			break;
		}
	}

	if ($params['lighter'])
	{
		$aclass .= ' orgm-show-lighter';
	}
	

	if ($iclass !== '')
	{
		$iclass = 'class="' . $iclass . '"';
	}
	//create class of <a>
	if ($aclass !== '')
	{
		$aclass = ' class="'. $aclass .'"';
	}

	//指定されたクラスを追加する
	$imgclass = ' class="' . h($params['class']) . '"';

	if($params['label']!==FALSE && $params['label']!=''){
		//画像を指定した場合、画像を表示する
		if (preg_match('/\.(jpe?g|gif|png)$/', $params['label'])) {
			$url = get_file_path($params['label']);
			$size = plugin_show_get_imagesize($params['label']);
			//高さと幅を指定している場合はそちらを利用する
			if ($params['_w'] === 0 && $params['_h'] === 0 && $size !== FALSE)
			{
				$info = '';
				if (is_array($size)) {
					$width  = $size[0];
					$height = $size[1];
					if ($params['_%'] !== 0)
					{
						$width = floor($width * $params['_%'] / 100);
						$height = floor($height * $params['_%'] / 100);
					}
					$info = "width=\"{$width}\" height=\"{$height}\"";
				}
				$params['_body'] = "<img src=\"$url\" alt=\"$title\" title=\"$title\" $info $mouseover $style{$imgclass}>";
			}
			$params['_body'] = "<img src=\"$url\" alt=\"$title\" title=\"$title\" $info $mouseover $style{$imgclass}>";
		}
		else
		{
			$params['_body'] = h($params['label']);
		}
	}
	else{
		$params['_body'] = "<img src=\"$url\" alt=\"$title\" title=\"$title\" $info $mouseover $iclass $style{$imgclass}>";
	}

	if (! $params['nolink'] && $url2)
		$params['_body'] = "<a href=\"$url2\" title=\"$title\"$aclass$arel>{$params['_body']}</a>";

	return $params;
}

// オプションを解析する
function plugin_show_check_arg($val, & $params)
{
	global $script;


	if ($val == '') {
		$params['_done'] = TRUE;
		return;
	}

	if (! $params['_done']) {

		foreach (array_keys($params) as $key) {
			if (strpos(strtolower($val), $key) === 0) {
				if (strpos($val, '=')) {
					list($optkey, $optval) = explode('=', $val, 2);
					$params[$key] = $optval;
				} else {
					$params[$key] = TRUE;
				}
				return;
			}
		}
		$params['_done'] = TRUE;
	}

	

	if (is_url($val)) {
		$params['linkurl'] = $val;
	} else {
		$params['_args'][] = $val;
	}

}



function plugin_show_swap_dummy()
{
	global $script, $vars;

	$dummy_id = $vars['id'];
	$filename = $vars['filename'];
	$options = $vars['options'];
	$digest = $vars['digest'];	
	$page = $vars['refer'];

	// ダイジェストのチェック
	$postdata = get_source($page);
	$thedigest = md5(join('', $postdata));
	// 更新の衝突を検出
	if ($thedigest != $digest)
	{
		$ret['error'] = __('ファイルが挿入できませんでした。<br>ページを再読み込みしてやり直してください。<br>');
		return $ret;
	}

	// dummyをoptions含め入れ替える
	$postdata = preg_replace('/&show\('.preg_quote($dummy_id, '/').'(.*?)\);/', '&show('.$filename.'$1);' ,$postdata);
	
	$new_postdata = join('', $postdata);

	$options = explode(',', $options);
	array_unshift($options, $filename);
	$params = plugin_show_body($options);


	$ret['success'] = __('成功しました。');
	$ret['digest'] = md5($new_postdata);
	$ret['html'] = $params['_body'];
	
	
	page_write($page, $new_postdata);
	
	return $ret;
}




/**
 * Get Image Size
 *
 * @param string $image_path image's path or URL
 * @return array size of image OR FALSE
 */
function plugin_show_get_imagesize($image_path = '')
{
	$is_url = is_url($image_path);
	
	if ($is_url) {
		if (PKWK_DISABLE_INLINE_IMAGE_FROM_URI) {
			$url = h($image_path);
			$params['_body'] = '<a href="' . $url . '">' . $url . '</a>';
			return $params;
		}

		$url = h($image_path);
		$title = h(preg_match('/\/(.+?)$/', $image_path, $matches) ? $matches[1] : $url);

		if (PLUGIN_SHOW_URL_GET_IMAGE_SIZE && (bool)ini_get('allow_url_fopen')) {
			$size = @getimagesize($url);
			return $size;
		}

	} else {

		$file = $image_path;
		if ( ! is_file($file))
		{
			$file = UPLOAD_DIR . $file;
			if ( ! is_file($file))
			{
				return FALSE;
			}
		}

		$file = (substr($file, 0, 2) == './') ? substr($file, 2) : $file;

		$width = $height = 0;
		$size = @getimagesize($file);
		return $size;
	}
	
}


function plugin_show_put_dummy($dummy_id, $args)
{
	global $vars, $script, $digest;
	static $called = FALSE;
	$qt = get_qt();
	
	$is_preview = ($vars['cmd'] === 'edit' && isset($vars['preview']));
	
	$ret = '';
	if ( ! check_editable($vars['page'], FALSE, FALSE))
	{
		return '';
	}
	if (isset($vars['page_alt']))
	{
		return '';
	}

	array_shift($args);
	$s_args = trim( rtrim(join(",",$args),',') );
	$page = $vars['page'];
	$r_page = rawurlencode($page);
	$r_args = rawurlencode($s_args);
	
	$filer_options = json_encode(array(
		'search_word' => ':image',
		'select_mode' => 'exclusive'
	));

	$label = $is_preview ? __('仮画像') : __('クリックして画像を選択');
    $ret = '
<div class="img-polaroid img-dummy">
	<a href="#" data-dummy="'. h($dummy_id) .'" data-options="'.h($s_args).'" data-filer-options="'. h($filer_options) .'">'.$label.'</a>
	<input type="file" name="files[]" class="dummy-upload hide">
</div>';


    if (exist_plugin('filer'))
    {
	    plugin_filer_set_iframe();
    }
	if ( ! $called)
	{
		$qt->setjsv('show', array(
			'postUrl' => $script . '?cmd=show&refer='. $r_page
		));
	}

	$called = TRUE;
    
    return $ret;

}


/* End of file show.inc.php */
/* Location: /haik-contents/plugin/show.inc.php */