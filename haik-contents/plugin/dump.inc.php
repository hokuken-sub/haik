<?php
// $Id: dump.inc.php,v 1.37 2006/01/12 01:01:35 teanan Exp $
//
// Remote dump / restore plugin
// Originated as tarfile.inc.php by teanan / Interfair Laboratory 2004.

//zip.lib.php を読み込む
require_once(LIB_DIR . 'Ftp.php');
require_once(LIB_DIR . 'qhm_fs.php');

require_once(LIB_DIR. 'zip.lib.php');
require_once(LIB_DIR . 'Unzip.php');

//zip.lib.php を拡張する
class zipfile2 extends zipfile{

	function addDir($dir, $filter = false, $namedecode = false) {
		//最後のスラッシュを削除
		$dir = rtrim($dir, '/');
//		$dir = ($dir{strlen($dir)-1}=='/') ? substr($dir, 0, strlen($dir)-1) : $dir;
		
		if (!file_exists($dir) || !is_dir($dir)) {
			return;
		}
		
		$count = 0;
		$dhandle = opendir($dir);
		if ($dhandle) {
			while (false !== ($fname = readdir($dhandle))) {
		
				if (is_dir( $dir.'/'.$fname )) {
					if (substr($fname, 0, 1) != '.')
						$count += $this->addDir("$dir/$fname", $filter);
				} else {
					if((!$filter || preg_match("/$filter/", $fname)) && $fname != '.' && $fname != '..')
					{
						$filename = $dir. '/'. $fname;
						$handle = fopen($dir.'/'.$fname, "rb");
						$targetFile = fread($handle, filesize($filename));
						fclose($handle);
						if ($namedecode) {
							$filename = plugin_dump_decodename($filename);
						}
						$this->addFile($targetFile, './'.$filename);
						$count++;
					}
				}
			}
			closedir($dhandle);
		}
		return $count;
	
	}
	
	function download($filename) {
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename={$filename}");
		echo $this->file();
	}
}


/////////////////////////////////////////////////
// User defines

// Allow using resture function
define('PLUGIN_DUMP_ALLOW_RESTORE', TRUE); // FALSE, TRUE

// ページ名をディレクトリ構造に変換する際の文字コード (for mbstring)
define('PLUGIN_DUMP_FILENAME_ENCORDING', 'SJIS');

// 最大アップロードサイズ
define('PLUGIN_DUMP_MAX_FILESIZE', 16384); // Kbyte

/////////////////////////////////////////////////
// Internal defines

// Action
define('PLUGIN_DUMP_DUMP',    'dump');    // Dump & download
define('PLUGIN_DUMP_RESTORE', 'restore'); // Upload & restore
define('PLUGIN_DUMP_FULL',    'full');    // Dump & download

global $_STORAGE;

// CONFIG_DIR (haik-contents/config/*.php)
$_STORAGE['CONFIG_DIR']['add_filter']     = '^.+\.(php|txt|sqlite)';
$_STORAGE['CONFIG_DIR']['extract_filter'] = '.+';

// DATA_DIR (haik-contents/wiki/*.txt)
$_STORAGE['DATA_DIR']['add_filter']     = '^[0-9A-F]+\.txt';
$_STORAGE['DATA_DIR']['extract_filter'] = '^((?:[0-9A-F])+)(\.txt){0,1}';

// META_DIR (haik-contents/meta/*.php)
$_STORAGE['META_DIR']['add_filter']     = '^[0-9A-F]+\.php';
$_STORAGE['META_DIR']['extract_filter'] = '^((?:[0-9A-F])+)(\.php){0,1}';

// UPLOAD_DIR (haik-contents/upload/*)
$_STORAGE['UPLOAD_DIR']['add_filter']     = '.+';
$_STORAGE['UPLOAD_DIR']['extract_filter'] = '.+';

// BACKUP_DIR (haik-contents/backup/*.gz)
$_STORAGE['BACKUP_DIR']['add_filter']     = '^[0-9A-F]+\.gz';
$_STORAGE['BACKUP_DIR']['extract_filter'] =  '^((?:[0-9A-F])+)(\.gz){0,1}';

// SKIN_DIR (haik-contents/theme/*)
$_STORAGE['BACKUP_DIR']['add_filter']     = '.+';
$_STORAGE['BACKUP_DIR']['extract_filter'] =  '.+';


/////////////////////////////////////////////////
// ! プラグイン本体

function plugin_dump_init()
{

	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}

	$qt = get_qt();

	$head = '
<script>
$(function(){
	$(".app-config-menu [data-menu=advance]").addClass("active").siblings().removeClass("active");
});
</script>';
	$qt->appendv('plugin_script', $head);

}

function plugin_dump_action()
{
	global $style_name, $admin_style_name, $script, $vars, $password;
	$qt = get_qt();

	if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

	$pass = isset($_POST['pass']) ? $_POST['pass'] : NULL;
	$act  = isset($vars['act'])   ? $vars['act']   : NULL;

	$body = '';

	if ($pass !== NULL) {
		if (! check_passwd($pass, $password)) {
			$message = __('パスワードが間違っています。');
			$body = "<p class=\"alert alert-error\">{$message}</strong></p>\n";
		} else {
			switch($act){
			case PLUGIN_DUMP_DUMP:
				$body = plugin_dump_download();
				break;
			case PLUGIN_DUMP_RESTORE:
				$retcode = plugin_dump_upload();
				$msg = $retcode['code']? __('アップロードが完了しました'): __('アップロードに失敗しました');
				$body .= $retcode['msg'];
				return array('msg' => $msg, 'body' => $back_url.$body);
				break;
			case PLUGIN_DUMP_FULL:
				$body = plugin_dump_download_full();
				break;
			}
		}
	}

	// 入力フォームを表示
	$body .= plugin_dump_disp_form();

	$msg = '';
	if (PLUGIN_DUMP_ALLOW_RESTORE) {
		$msg = __('バックアップと復元');
	} else {
		$msg = __('バックアップ');
	}

	return array('msg' => $msg, 'body' => $back_url.$body);
}


function plugin_dump_decodename($name) {

	$dirname  = dirname(trim($name)) . '/';
	$filename = basename(trim($name));
	if (preg_match("/^((?:[0-9A-F]{2})+)_((?:[0-9A-F]{2})+)/", $filename, $matches)) {
		// attachファイル名
		$filename = decode($matches[1]) . '/' . decode($matches[2]);
	} else {
		$pattern = '^((?:[0-9A-F]{2})+)((\.txt|\.gz)*)$';
		if (preg_match("/$pattern/", $filename, $matches)) {
			$filename = decode($matches[1]) . $matches[2];

			// 危ないコードは置換しておく
			$filename = str_replace(':',  '_', $filename);
			$filename = str_replace('\\', '_', $filename);
		}
	}
	$filename = $dirname . $filename;
	// ファイル名の文字コードを変換
	if (function_exists('mb_convert_encoding'))
		$filename = mb_convert_encoding($filename, PLUGIN_DUMP_FILENAME_ENCORDING);


	return $filename;
}


/////////////////////////////////////////////////
// ファイルのダウンロード
function plugin_dump_download()
{
	global $vars, $_STORAGE, $logo_image;

	// ページ名に変換する
	$namedecode = isset($vars['namedecode']) ? TRUE : FALSE;

	// バックアップディレクトリ
	$bk_wiki   = isset($vars['wiki']);
	$bk_upload = isset($vars['upload']);
	$bk_backup = isset($vars['backup']);
	$bk_config = isset($vars['config']);
	$bk_meta   = isset($vars['meta']);
	$bk_skin   = isset($vars['theme']);

	$filecount = 0;
	$zip = new zipfile2();
	$zipfile = 'haikbk_'.date("Ymd"). '.zip';

	//dirs
	if ($bk_wiki)     $filecount += $zip->addDir(DATA_DIR,        $_STORAGE['DATA_DIR']['add_filter'],     $namedecode);
	if ($bk_upload)   $filecount += $zip->addDir(UPLOAD_DIR,      $_STORAGE['UPLOAD_DIR']['add_filter'],   $namedecode);
	if ($bk_backup)   $filecount += $zip->addDir(BACKUP_DIR,      $_STORAGE['BACKUP_DIR']['add_filter'],   $namedecode);
	if ($bk_config)   $filecount += $zip->addDir(CONFIG_DIR,      $_STORAGE['CONFIG_DIR']['add_filter'],   $namedecode);
	if ($bk_meta)     $filecount += $zip->addDir(META_DIR,        $_STORAGE['META_DIR']['add_filter'],     $namedecode);
	if ($bk_skin)     $filecount += $zip->addDir(SKIN_DIR,        $_STORAGE['SKIN_DIR']['add_filter'],     $namedecode);

	if ($filecount === 0) {
		return '<p class="alert alert-error"><strong>'. __('ファイルがみつかりませんでした。').'</strong></p>';
	} else {
		// ダウンロード
		$zip->download($zipfile);
		exit;
	}
}

/////////////////////////////////////////////////
// ファイルのダウンロード
function plugin_dump_download_full()
{

	error_reporting(E_ERROR | E_PARSE);
	
	global $vars;

	if( isset($vars['_p_dump_memlimit']) ){
		
		if( is_numeric($vars['_p_dump_memlimit_value']) )
		{
				ini_set("memory_limit", $vars['_p_dump_memlimit_value']."M");
		}
		else{
			return 'エラー：不正なメモリの値が設定されました';
		}
	}


	// バックアップディレクトリ
	$bk_dirs = array(
		DATA_HOME
	);
	
	// バックアップファイル (.txt, .phpすべて)
	$bk_files = array();
	$hd = opendir('./');
	while($f = readdir($hd)){
		if( preg_match('/^(:?.*\.(php)|\.htaccess)$/', $f) )
			$bk_files[] = $f;
	}
	
	$bk_fname = 'haikbk_full_'.date("Ymd").'.zip';

	//zipファイルの作成　(メモリーオーバーをする危険あり)
	$zip = new zipfile2();
	foreach($bk_dirs as $dir){
		$zip->addDir($dir);
		//zip_add_dir($dir, $zipFile, 'qhmbk_');
	}
	
	foreach($bk_files as $file){
		if( file_exists($file) )
			$zip->addFile(file_get_contents($file), $file);
	}
	
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$bk_fname}");
	echo $zip->file();
	
	exit;
}



/////////////////////////////////////////////////
// ファイルのアップロード
function plugin_dump_upload()
{
	global $vars, $_STORAGE, $script;

	if (! PLUGIN_DUMP_ALLOW_RESTORE)
		return array('code' => FALSE , 'msg' => __('Restoring function is not allowed'));

	$filename = $_FILES['upload_file']['name'];
	$matches  = array();

	if (!preg_match('/\.zip$/', $filename, $matches)) {
		die_message(__('Invalid file type'));
	}

	if ($_FILES['upload_file']['size'] >  PLUGIN_DUMP_MAX_FILESIZE * 1024)
		die_message(sprintf(__('Max file size exceeded: %d KB'), PLUGIN_DUMP_MAX_FILESIZE));

	
	// Create a temporary tar file
	$uploadfile = tempnam(realpath(CACHE_DIR), 'zip_uploaded_');
	$extdir = $uploadfile . '_dir/';
	mkdir($extdir);//同名のフォルダを作成
	
	if (!move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadfile)) {
		@unlink($uploadfile);
		die_message('ファイルがみつかりませんでした。');
	}
	

	$unzip = new Unzip();
	$file_locations = $unzip->extract($uploadfile, rtrim($extdir, '/'));
	
	unlink($uploadfile);

	$files = array();
	$errors = array();

	foreach ($file_locations as $filepath)
	{
		$name = basename($filepath);
		if ($name === '.htaccess') continue;
		
		$path = substr(dirname($filepath), strlen($extdir));
		$path = preg_replace('/^\.\//', '', $path) . '/';

		$stokey = 'DATA_HOME';
		foreach (array('DATA_DIR', 'CONFIG_DIR', 'BACKUP_DIR', 'META_DIR', 'SKIN_DIR', 'UPLOAD_DIR') as $const)
		{
			if (strpos($path, constant($const)) === 0)
			{
				$stokey = $const;
				break;
			}
		}

		$filter = isset($_STORAGE[$stokey]['extract_filter'])? $_STORAGE[$stokey]['extract_filter']: '';
		
		if ($filter && preg_match("/$filter/", $name))
		{
			$copyfile = $path . $name;

			$files[] = $copyfile;

			if (copy($filepath, $copyfile)) {
				chmod($copyfile, 0666);
			} else {
				$errors[] = $copyfile;
			}
		}
		
	}

	//extdir 削除
	$fs = new QHM_FS();
	$fs->delete_dir($extdir);

	if (empty($files)) {
		@unlink($uploadfile);
		return array('code' => FALSE, 'msg' => '<p>'.__('解凍できるファイルがありませんでした。'). '</p>');
	}
	
	$msg  = '<h2>'. __('復元したファイル一覧'). '</h2>'."\n";
	$msg .= '<ul class="nav nav-list">';
	foreach ($files as $name)
	{
		$msg .= "<li>$name</li>\n";
	}
	$msg .= '</ul>';

	if ($errors)
	{
		$msg .= '<hr>';
		$msg .= '<h2>'. __('復元に失敗したファイル一覧'). '</h2>'."\n";
		$msg .= '<ul class="nav nav-list">';
		foreach ($errors as $name)
		{
			$msg .= "<li>$name</li>\n";
		}
		$msg .= '</ul>';
	}

	$msg .= '<hr>';
	$msg .= '<p><a href="'.h($script).'" class="btn btn-success">'.__('トップへ戻る').'</a></p>';

	return array('code' => TRUE, 'msg' => $msg);
}


/////////////////////////////////////////////////
// 入力フォームを表示
function plugin_dump_disp_form()
{
	global $script, $defaultpage;
	
	$frm_header = 'バックアップ・復元';

	$act_down = PLUGIN_DUMP_DUMP;
	$act_up   = PLUGIN_DUMP_RESTORE;
	$maxsize  = PLUGIN_DUMP_MAX_FILESIZE;
	$act_full = PLUGIN_DUMP_FULL;
	
	$configdir = CONFIG_DIR;
	$datadir   = DATA_DIR;
	$backupdir = BACKUP_DIR;
	$uploaddir = UPLOAD_DIR;
	$metadir   = META_DIR;
	$skindir   = SKIN_DIR;
	
	$data = <<< EOD

<span class="small">
</span>

<div class="page-header">{$frm_header}</div>

<div class="alert alert-info">
<p>
	<b>注意事項</b><br>
	<ul style="margin-left:8px">
		<li>ファイル数が多い場合、ダウンロード開始まで時間がかかることがあります</li>
		<li>ファイル数が多い場合、途中でエラーするときがあります（FTPソフトをお使い下さい)</li>
		<li>設置されているサーバーによっては、エラーを表示し、動かない場合があります</li>
	</ul>
</p>
</div>

<h3 class="sub-header">フルバックアップ</h3>

<form action="$script" method="post" class="">
<div class="panel panel-default">

	<div class="panel-body">
	<p>システム構成ファイルをすべてバックアップします。</p>
	<br>
	<input type="hidden" name="cmd"  value="dump">
	<input type="hidden" name="page" value="$defaultpage">
	<input type="hidden" name="act"  value="$act_full">
	
	<div class="form-group clearfix">
		<div class="checkbox col-sm-4">
			<label class="control-label"><input type="checkbox" name="_p_dump_memlimit"> メモリ使用量の制限を変更する</label>
		</div>
		<div class="input-group col-sm-4">
			<input type="text" size="4" name="_p_dump_memlimit_value" value="64" class="form-control input-sm">
			<span class="input-group-addon input-sm">MB</span>
		</div>
	</div>

	<div class="form-group clearfix">
		<div class="col-sm-4 text-right">
			<label class="control-label">管理者パスワード</label>
		</div>
		<div class="col-sm-8">
			<div class="row">
				<div class="col-sm-6">
					<input type="password" name="pass" id="_p_dump_adminpass_dump" class="form-control input-sm">
				</div>
				<div class="col-sm-6 row">
					<input type="submit" name="ok" value="ダウンロード" class="btn btn-primary btn-sm">
				</div>
			</div>
		</div>
	</div>
	<span class="help-block">
		※エラーがでて、動かないときに利用して下さい（一部サーバーでは使えません）<br>
		※フルバックアップしたファイルは、FTPソフトでアップロードしてください
	</span>

	</div>
</div>
</form>


<h3 class="sub-header">データのダウンロード</h3>
<form action="$script" method="post" class="form">
<div class="panel panel-default">
	<div class="panel-body">
	<input type="hidden" name="cmd"  value="dump">
	<input type="hidden" name="page" value="$defaultpage">
	<input type="hidden" name="act"  value="$act_down">
	
	<p>指定したフォルダやファイルをZip形式で圧縮します。</p>
	
	<h4 class="sub-header">バックアップディレクトリ</h4>
	<div class="row">
		<div class="col-sm-6">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="config" checked>
					{$configdir}<span class="muted">&nbsp;&nbsp;&nbsp;&nbsp; --- 設定データ</span>
				</label>
			</div>
			<div class="checkbox">
				<label>
					<input type="checkbox" name="wiki" checked>
					{$datadir}<span class="muted">&nbsp;&nbsp;&nbsp;&nbsp; --- ページデータ</span>
				</label>
			</div>
			<div class="checkbox">
				<label>
					<input type="checkbox" name="meta" checked>
					{$metadir}<span class="muted">&nbsp;&nbsp;&nbsp;&nbsp; --- ページメタデータ</span>
				</label>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="backup">
					{$backupdir}<span class="muted">&nbsp;&nbsp;&nbsp;&nbsp; --- 編集履歴</span>
				</label>
			</div>
			<div class="checkbox">
				<label>
					<input type="checkbox" name="theme">
					{$skindir}<span class="muted">&nbsp;&nbsp;&nbsp;&nbsp; --- デザイン</span>
				</label>
			</div>
			<div class="checkbox">
				<label>
					<input type="checkbox" name="upload" checked>
					{$uploaddir}<span class="muted">&nbsp;&nbsp;&nbsp;&nbsp; --- アップロードファイル</span>
				</label>
			</div>
		</div>
	</div>



	<h4 class="sub-header">オプション</h4>
	<div class="">
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="namedecode"> エンコードされているページ名をディレクトリ階層つきのファイルにデコード
				</label>
			</div>
			<span class="help-block">　※復元に使うことはできなくなります。また、一部の文字は '_' に置換されます。</span>
		</div>
		<div class="form-group clearfix">
			<div class="col-sm-4 text-right">
				<label class="control-label">管理者パスワード</label>
			</div>
			<div class="col-sm-8">
				<div class="row">
					<div class="col-sm-6">
						<input type="password" name="pass" id="_p_dump_adminpass_dump" class="form-control input-sm">
					</div>
					<div class="col-sm-6 row">
						<input type="submit" name="ok" value="ダウンロード" class="btn btn-primary btn-sm">
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>
</div>
</form>
EOD;

	if (PLUGIN_DUMP_ALLOW_RESTORE) {
		$frm_rstr_ntc_maxsize = sprintf(__('アップロード可能なファイルサイズは、%d KB です。'), $maxsize);
		$frm_rstr_header = __('データの復元');
		$data .= <<< EOD
<h3 class="sub-header">{$frm_rstr_header} (*.zip)</h3>
<form enctype="multipart/form-data" action="$script" method="post" class="form">
<div class="panel panel-default">
	<div class="panel-body">
	<input type="hidden" name="cmd"  value="dump">
	<input type="hidden" name="page" value="$defaultpage">
	<input type="hidden" name="act"  value="$act_up">
	<div class="alert alert-info">
		<strong>[重要] 同じ名前のファイルは上書きされますので、十分ご注意ください。<br>
		※フルバックアップしたファイルは、この画面から復元できません</strong>
	</div>

	<p><span class="small">$frm_rstr_ntc_maxsize</span></p>
	
	<div class="">
		<div class="form-group">
			<label class="control-label">ファイル:</label>
			<input type="file" name="upload_file">
		</div>
	</div>
	
	<div class="">
		<div class="form-group">
			<div class="col-sm-4 text-right">
				<label class="control-label">管理者パスワード</label>
			</div>
			<div class="col-sm-8">
				<div class="row">
					<div class="col-sm-6">
						<input type="password" name="pass" class="form-control input-sm">
					</div>
					<div class="col-sm-6 row">
						<input type="submit"   name="ok"   value="復元" class="btn btn-primary input-sm">
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>
</div>
</form>
EOD;
	}

	return $data;
}

/* End of file dump.inc.php */
/* Location: /app/haik-contents/plugin/dump.inc.php */