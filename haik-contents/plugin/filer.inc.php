<?php
/**
 *   File Manager
 *   -------------------------------------------
 *   filer.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/03/05
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */

include_once(LIB_DIR . 'html_helper.php');
include_once(LIB_DIR . 'UploadHandler.php');

define('PLUGIN_FILER_MD5_FILE', CACHE_DIR . 'filer_hash.dat');

function plugin_filer_action()
{
	global $script, $vars, $style_name, $admin_style_name;

	if ( ! ss_admin_check())
	{
		set_flash_msg('管理者のみアクセスできます。', 'error');
		redirect($script);
		exit;
	}

	$qt = get_qt();

	$style_name = $admin_style_name;
	$qt->setv('template_name', 'filer');

	//javascript
	$scripts = '
<script type="text/javascript" src="'. PLUGIN_DIR .'filer/filer.js"></script>
<script type="text/javascript" src="'. JS_DIR .'jquery.filer-grid.js"></script>
<script type="text/javascript" src="'. JS_DIR .'jcrop/js/jquery.Jcrop.js"></script>
';
	$qt->appendv('plugin_script', $scripts);
	
	$css = '<link rel="stylesheet" href="'. PLUGIN_DIR .'filer/filer.css" />
<link rel="stylesheet" href="'.JS_DIR.'jcrop/css/jquery.Jcrop.css" />
';
	$qt->appendv('plugin_head', $css);
	
	//navi
	if (exist_plugin('app_config'))
	{
		plugin_app_config_set_navi();
	}

	
	$mode = isset($vars['mode']) ? strtolower($vars['mode']) : 'list';
	
	$func_name = 'plugin_filer_' . $mode . '_';
	if (function_exists($func_name))
	{
		return $func_name();
	}

	return array('msg' => '', 'body' => '');

}

/**
 * body_last にファイル選択用の iframe を追加する。
 *
 * @param string $search_word search conditions
 * @param string $select_mode multiple OR exclusive selective
 * @param boolean|string $footer display footer OR footer HTML
 */
function plugin_filer_set_iframe($search_word = ':image', $select_mode = '', $footer = TRUE)
{
	global $script, $vars;
	static $called = FALSE;
	
	if ($called) return FALSE;
	$called = TRUE;
	
	$qt = get_qt();
	
	$url = $script . '?cmd=filer&iframe=1';
	
	$iframe_mode = TRUE;
	
	ob_start();
	include(PLUGIN_DIR . 'filer/nav.html');
	$nav = ob_get_clean();
	
	$html = '
<div class="modal fade" id="orgm_filer_selector">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header clearfix">
				<button type="button" data-dismiss="modal" class="close">&times;</button>
				<h4 class="pull-left">ファイル選択</h4>
				'. $nav .'
			</div>
			<div class="modal-body">
				<iframe data-url="'. h($url) .'" data-search_word="'. h($search_word) .'" data-select_mode="'. $select_mode .'" webkitallowfullscreen mozallowfullscreen></iframe>
			</div>
';

	if ($footer === TRUE)
	{
		$html .= '
			<div class="modal-footer">
				<button type="button" class="btn btn-primary">選択</button><button type="button" data-dismiss="modal" class="btn btn-default">キャンセル</button>
			</div>
';
	}
	else if ($footer !== FALSE)
	{
		$html .= $footer;
	}
	
	$html.= '
		</div>
	</div>
</div>';
	
	$qt->appendv('body_last', $html);
	
}

function plugin_filer_post_()
{
	global $script;

	$filer = plugin_filer_get_instance();
	
	$id_list = $filer->saveUploadFiles();
	if ($id_list)
	{
		$conditions = array('id IN' => $id_list);
		$files = $filer->findFiles($conditions, 'id DESC', count($files));
		print_json($files);
	}
	else
	{
		$max_filesize = ini_get('upload_max_filesize');
		$errmsg = sprintf(__('アップロードできませんでした。ファイルの容量が %s を超えていないか確認してください。'), $max_filesize);
		print_json(array(
			'error' => $errmsg
		));
	}
	
	exit;

}

function plugin_filer_update_()
{

	global $vars, $script;

	$filer = plugin_filer_get_instance();

	//filename は必ず保存してあるものを使う
	$file = $filer->findById($vars['id']);
	if ( ! $file)
	{
		//failed
		exit;
	}

	$vars['filename'] = $file['filename'];
	$newfilepath = $filepath = $filer->uploadDir . $vars['filename'];
	unset($file);

	$duplication = ! (isset($vars['overwrite']) && $vars['overwrite']);
	
	if ($duplication)
	{
		if ($filer->duplicateFile($vars['id']))
		{
			$id = $filer->getLastInsertID();
			$file = $filer->findById($id);
			$vars['id'] = $id;
			$vars['filename'] = $file['filename'];
			$newfilepath = $filer->uploadDir . $vars['filename'];
			unset($file);
		}
		else
		{
			//failed
			// !TODO: return error message
			exit;
		}
	}
	
	$error = FALSE;
	$image_edit = ($duplication && ORGM_Filer::filetype($filepath) === 'image');

	foreach ($vars as $field => $val)
	{
		switch ($field)
		{
			case 'id':
				if ( ! preg_match('/^\d+$/', $val)) $error = TRUE;
				break;
			case 'title':
				break;
			case 'width':
			case 'height':
				if ( ! preg_match('/^\d+$/', $val)) $error = TRUE;
				else $image_edit = TRUE;
				$$field = $val;
				unset($vars[$field]);
				break;
			case 'crop':
				if ($val['w'] && $val['h'])
				{
					$image_edit = TRUE;
					$crop = $val;
				}
				unset($vars[$field]);
				break;
			case 'rotate':
				if ( ! is_numeric($val)) $error = TRUE;
				else $image_edit = TRUE;
				$rotate = $val;
				unset($vars[$field]);
				break;
			case 'tag':
				break;
			case 'star':
				$vars[$field] = ($val ? 1 : 0);
				break;
		}
	}
	
	if ( ! $error)
	{
		$result = $filer->editFile($vars);
		
		if ($result)
		{
			if ($filer->imageEditable && $image_edit)
			{
				foreach ($filer->uploadDirs as $dir => $options)
				{
					if ($dir == '')
					{
						$imgoption = array_merge($options, array(
							'width' => $width,
							'height' => $height,
							'crop' => $crop,
							'rotate' => $rotate,
						));
						
						$success = $filer->createImage($filepath, $newfilepath, $imgoption);
					}
					else
					{
						$filepath = $newfilepath;
						$newfilepath = $filer->uploadDir . $dir . '/' . $vars['filename'];
						$imgoption = $options;
						$success = $filer->createImage($filepath, $newfilepath, $imgoption);
					}
				}
			}

			$file = $filer->findFile($vars['id']);
			
			print_json($file);
			exit;
			
		}
		
	}
	
	print_json(array(
		'error' => 'file update failed...'
	));
	
	exit;

}

function plugin_filer_delete_()
{
	global $script, $vars;
	
	$filer = plugin_filer_get_instance();
	
	$id = $vars['id'];
	$file = $filer->findFile($id);
	
	if ($filer->deleteFile($id))
	{
	
		$json = array(
			'message' => sprintf(__('ファイル（%s）を削除しました。'), $file['title'])
		);
	}
	else
	{
		$json = array(
			'error' => __('ファイルの削除ができません。')
		);
	}
	
	print_json($json);
	
	
	exit;
	
}

function plugin_filer_download_()
{
	global $vars, $script;

	$filer = plugin_filer_get_instance();
	
	if (isset($vars['file']))
	{
		$filer->handler->get();
		exit;
	}
/*
	if (isset($vars['file']))
	{
		$filename = $vars['file'];
		$filepath = UPLOAD_DIR . $filename;
		
		if (file_exists($filepath) && ! is_dir($filepath)
			&& $fp = fopen($filepath, 'rb'))
		{
			//Download
			header("Cache-Control: public");
			header("Pragma: public");
			header("Accept-Ranges: none");
			header("Content-Transfer-Encoding: binary");
			header("Content-Disposition: attachment; filename={$filename}");
			header("Content-Type: application/octet-stream; name={$filename}");

			fpassthru($fp);
			fclose($fp);
			exit;
		}
	}
*/
	
	set_flash_msg(__('ダウンロードできませんでした。'), 'error');
	redirect($script . '?cmd=filer');
	exit;
}

function plugin_filer_folder_()
{
	global $vars, $script;
	
	$qt = get_qt();
	
	$filer = plugin_filer_get_instance();
	
	$type = isset($vars['type']) ? $vars['type'] : 'tag';
	$folders = $filer->listBy($type);
	
	$qt->setjsv(array(
		'filer' => array(
			'folders' => $folders
		)
	));

	$tmpl_file = PLUGIN_DIR . 'filer/folder.html';
	
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => 'ファイル管理', 'body' => $body);
}


function plugin_filer_list_()
{
	global $vars, $script;
	$qt = get_qt();
	$helper = new HTML_Helper();

	$filer = plugin_filer_get_instance();

	$word = isset($vars['search_word']) ? $vars['search_word'] : '';
	
	if (preg_match('/^:(tag|day)$/', $word, $mts))
	{
		$vars['type'] = $mts[1];
		return plugin_filer_folder_();
	}
	$conditions = plugin_filer_filter_condition($word);

	$size = 30;
	
	// 画像の読込み
	$order = 'id DESC';
	$urlpath = dirname($script . 'dummy.php');
	$_page = isset($vars['_page']) ? $vars['_page'] : 1;
	$limit = (($_page-1) * $size) . ',' . $size;

	$files = $filer->findFiles($conditions, $order, $limit);

	$moreurl = $script . '?cmd=filer&_page='. ($_page+1). '&search_word=' . rawurlencode($word);
	
	$json = array(
		'files' => $files,
		'more' => $moreurl
	);
	
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
	{
		print_json($json);
		exit;
	}

	$json['postUrl']   = $script . '?cmd=filer&mode=post';
	$json['deleteUrl'] = $script . '?cmd=filer&mode=delete';
	$json['updateUrl'] = $script . '?cmd=filer&mode=update';
	$json['downloadUrl'] = $script . '?cmd=filer&mode=download&download&file=';
	$json['hashCheckUrl'] = $script . '?cmd=filer&mode=hash_check';
	$json['checkUrl'] = $script . '?cmd=filer&mode=check';
	
	// ! View
	$iframe_mode = isset($vars['iframe']) ? $vars['iframe'] : FALSE;
	$select_mode = isset($vars['select_mode']) ? $vars['select_mode'] : '';
	
	if ($iframe_mode && exist_plugin('set_template'))
	{
		plugin_set_template_switch('iframe');
	}
	
	$json['deletable'] = ! $iframe_mode;
	$json['checkable'] = $iframe_mode;
	$json['selectExclusive'] = ($select_mode === 'exclusive');
	$json['iframe'] = $iframe_mode;
	$json['fullscreen'] = FALSE;
	$json['listSize'] = $size;

	$qt->setjsv(array(
		'filer' => $json
	));

	$refer = (isset($vars['refer']) && is_page($vars['refer'])) ? $vars['refer'] : '';
	$refer_param = $refer ? '&refer=' . rawurlencode($vars['refer']) : '';

	$base_url = $script . '?cmd=filer';
	$star_link = $base_url . '&search_word=' . rawurlencode(':star') . $refer_param;
	$all_link = $base_url . $refer_param;
	
	$tmpl_file = PLUGIN_DIR . 'filer/index.html';
	
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();
	
	return array('msg' => __('ファイル管理'), 'body' => $body);
}

function plugin_filer_hash_check_()
{
	global $script, $vars;
	
	$json = array(
		'result' => 'ok'
	);
	
	$filer = plugin_filer_get_instance();
	
	//更新を感知
	try {
		if ( ! $filer->checkHash())
		{
			$json['result'] = 'changed';
		}
	} catch (AccessControlException $e) {
		$json['result'] = 'error';
		$json['error'] = $e->getMessage();
	}
	
	print_json($json);
	exit;
}

function plugin_filer_check_()
{
	global $script, $vars;
	
	$filer = plugin_filer_get_instance();


	$message = $filer->checkFile();
	set_flash_msg($message);
		
	redirect($script . '?cmd=filer');
	exit;

}

function plugin_filer_get_instance()
{
	global $script;
	static $filer = FALSE;
	
	if ($filer !== FALSE) return $filer;
	
	$options = array(
		'dbFile'    => CONFIG_DIR . 'filer.sqlite',
		'scriptUrl' => $script,
		'uploadDir' => UPLOAD_DIR,
		'cacheDir'  => CACHE_DIR,
		'hashFile'  => PLUGIN_FILER_MD5_FILE,
		'notTagged' => __('タグ付けなし'),
		'timeline'  => array(
			'today' => __('今日'),
			'lastweek' => __('1週間以内'),
			'month' => __('Y年m月')
		),
	);
	$filer = new ORGM_Filer($options);
	
	return $filer;
}


function plugin_filer_filter_condition($word)
{
	$org_word = $word;
	
	$word = trim($word);
	$word = str_replace('　', ' ', $word);
	
	if ($word == '')
	{
		return '1';
	}
	
	//タグ検索
	if (strpos($word, ':tag ') === 0 OR $org_word === ':tag ')
	{
		$tag_name = trim(substr($word, 5));

		$conditions = array(
			'tag LIKE' => ','.$tag_name.','
		);
		return $conditions;
	}
	else if (strpos($word, ':day ') === 0)
	{
		$day = substr($word, 5);

		$conditions = array(
			'created LIKE' => $day . '%'
		);
		return $conditions;
	}
	
	$words = explode(' ', $word);
	
	$conditions = array();
	
	$allow_all = TRUE;
	foreach ($words as $word)
	{
		switch($word)
		{
			case ':all' :
				if ($allow_all) return 1;
				break;
			case ':image' :
			case ':video' :
			case ':audio' :
			case ':doc':
				if ( ! isset($conditions['type']))
				{
					$allow_all = FALSE;
					$type = ltrim($word, ':');
					$conditions['type'] =  $type;
				}
				break;
			case ':star':
				$allow_all = FALSE;
				$conditions['star'] = '1';
				break;
			default:
				$allow_all = FALSE;
				$conditions['OR'] = array(
					'filename LIKE' => '%'.$word.'%',
					'title LIKE' => '%'.$word.'%',
					'tag LIKE' => '%'.$word.'%',
				);
		}
	}
	
	return $conditions;
}


class ORGM_Filer {
	
	const ORDER = 'id DESC';
	const LIMIT = '30';
	
	public $dbFile;
	
	public $db;
	
	public $tableName = 'files';
	
	public $schema = array(
		'id'       =>  'INTEGER PRIMARY KEY AUTOINCREMENT',
		'filename' => '',
		'title'    => '',
		'type'     => '',
		'size'     => 'INTEGER',
		'tag'      => '',
		'star'     => 'INTEGER DEFAULT 0',
		'created'  => '',
	);
	
	public $history = array();
	
	public $cacheDir = '';
	public $hashFile = '';
	
	/** UploadHandler */
	public $handler;
	
	public $handlerOptions = array(
		'mkdir_mode' => 0777,
		'orient_image' => TRUE
	);
	
	public $paramName = 'files';
	
	public $uploadDir = '';
	
	public $uploadDirs = array(
        '' => array(
            'max_width' => 1920,
            'max_height' => 1200,
            'jpeg_quality' => 95,
        ),
		'thumbnail' => array(
			'max_width'  => 120,
			'max_height' => 120,
			'inverse'    => TRUE,
		)
//		'x2'//for retina
	);
	
	public $scriptUrl = '';
	public $uploadUrl = '';
	
	public $notTagged = '';
	
	public $imageEditable;
	
	public $timeline = array(
		'today' => 'Today',
		'lastweek' => 'Last week',
		'month' => 'Y-m'
	);
	
	public function ORGM_Filer($options = array(), $connect = TRUE)
	{
		//check GD module
		$this->imageEditable = extension_loaded('gd');

		//thumbnail disabled
		if ( ! $this->imageEditable)
		{
			foreach ($this->uploadDirs as $dir => $diropt)
			{
				if ($dir !== '')
				{
					unset($this->uploadDirs[$dir]);
				}
			}
		}

		$script_url = $options['scriptUrl'];
		$upload_dir = $options['uploadDir'];
		$upload_url = dirname($script_url . 'dummy') . '/' . $upload_dir;
		$this->uploadUrl = $upload_url;

		//Upload Handler
		$handlerOptions = array_key_exists('handler', $options) ? $options['handler'] : array();
		$handlerOptions = array_merge($this->handlerOptions, $handlerOptions);
		
		$handlerOptions['image_versions'] = $this->uploadDirs;
		
		$handlerOptions['script_url'] = $script_url;
		$handlerOptions['upload_dir'] = $upload_dir;
		$handlerOptions['upload_url'] = $upload_url;
		$handlerOptions['param_name'] = isset($options['paramName']) ? $options['paramName'] : $this->paramName;

		//download Handler
		$handlerOptions['download_via_php'] = TRUE;
		$handlerOptions['inline_file_types'] = '';

		$this->handler = new ORGM_UploadHandler($handlerOptions, FALSE);
		
		
		foreach ($options as $key => $value)
		{
			switch ($key)
			{
				case 'scriptUrl':
				case 'uploadDir':
				case 'uploadUrl':
				case 'tableName':
				case 'handler':
				case 'db':
				case 'dbFile':
				case 'cacheDir':
				case 'hashFile':
				case 'notTagged':
				case 'timeline':
					$this->{$key} = $value;
			}
		}
		

		
		if ($connect)
			$this->connect();
	}
	
	public function connect()
	{
		$init = FALSE;
		$src = APP_HOME . $this->dbFile;
		if ( ! file_exists($src)) $init = TRUE;
		
		$conn = new PDO('sqlite:'. $src);
		if ( ! $conn) die('cannot connect database: ' . $src);
		$this->db = $conn;
		
		//テーブルを作成
		if ($init)
		{
			chmod($src, 0666);
			$this->initialize() OR die('cannot create table: ' . $this->tableName);
		}
	}
	
	public function initialize()
	{
		$this->db->query();
		$sql_format = 'CREATE TABLE %s (%s)';
		$schema = array();
		foreach ($this->schema as $field => $option)
		{
			$schema[] = trim($field . ' ' . $option);
		}
		
		$sql = sprintf($sql_format, $this->tableName, join(',', $schema));

		return $this->query($sql);
	}
	
	public function insert($data)
	{
		$fields = $this->schema;
		unset($fields['id']);
		
		$data = array_intersect_key($data, $fields);
		
		$data['created'] = date('Y-m-d H:i:s');
		$fields_ = join(',', array_keys($data));
		
		$data_ = array();
		foreach ($data as $field => $val)
		{
			$data_[$field] = ':' . $field;
		}
		
		$sql = sprintf('INSERT INTO %s(%s) VALUES(%s);', $this->tableName, $fields_, join(',', $data_));
		$sth = $this->db->prepare($sql);

		foreach ($data as $field => $val)
		{
			$data_type = $this->getPDODataType($field, $val);
			$sth->bindValue($data_[$field], $val, $data_type);
		}

		return $sth->execute();
	}
	
	public function update($data = array())
	{
		if ( ! array_key_exists('id', $data)) return FALSE;
		
		$sql_format = 'UPDATE %s SET %s WHERE id = %d';
		
		$id = $data['id'];
		$data = array_intersect_key($data, $this->schema);
		unset($data['id']);
		
		$set_ = array();
		foreach ($data as $field => $value)
		{
			$set_[] = sprintf('%s = %s', $field, $this->quoteValue($field, $value));
		}
		
		$sql = sprintf($sql_format, $this->tableName, join(',', $set_), $id);
		
		return $this->query($sql);
	}
	
	public function delete($id)
	{
		$sql_format = 'DELETE FROM %s WHERE id = %d';
		$sql = sprintf($sql_format, $this->tableName, $id);
		
		return $this->query($sql);
	}
	
	public function deleteAll($conditions)
	{
		$sql_format = 'DELETE FROM %s WHERE %s';
		$condition = $this->createQuery($conditions);
		$sql = sprintf($sql_format, $this->tableName, $sql_format);

		return $this->query($sql);
	}
	
	public function find($conditions = array(), $order = 'id ASC', $limit = '0, 30')
	{
		$fields_ = join(',', array_keys($this->schema));
		$sql_format = 'SELECT %s FROM %s WHERE %s ORDER BY %s LIMIT %s';
		
		$bindArray = array();
		$where = $this->bindQuery($conditions, ' AND ', $bindArray);

		$sql = sprintf($sql_format, $fields_, $this->tableName, $where, $order, $limit);
		$sth = $this->db->prepare($sql);

		foreach ($bindArray as $i => $row)
		{
			$sth->bindValue($i+1, $row['value'], $row['type']);
		}

		$res = $sth->execute();
		if ($res)
		{
			return $sth->fetchAll(PDO::FETCH_ASSOC);
		}

		return $res;
	}
	
	public function findById($id)
	{
		$conditions = array('id' => $id);
		$order = 'id ASC';
		$limit = '0,1';
		$files = $this->find($conditions, $order, $limit);
		return array_pop($files);
	}
	
	public function count($conditions = array())
	{
		$sql_format = 'SELECT count(*) AS count FROM %s WHERE %s';
		$bindArray = array();
		$condition = $this->bindQuery($conditions, ' AND ', $bindArray);
		$sql = sprintf($sql_format, $this->tableName, $condition);

		$sth = $this->db->prepare($sql);

		foreach ($bindArray as $i => $row)
		{
			$sth->bindValue($i+1, $row['value'], $row['type']);
		}

		$res = $sth->execute();
		if ($res)
		{
			$res = $sth->fetch(PDO::FETCH_ASSOC);
			return $res['count'];
		}
		
		return 0;
	}
	
	public function createQuery($conditions = array(), $concat = ' AND ')
	{
		if ($conditions == 1 OR $conditions === '' OR count($conditions) === 0)
			return '1';
		
		foreach ($conditions as $field => $q)
		{
			if ($field === 'OR' && is_array($q))
			{
				$where[] = sprintf('(%s)', $this->createQuery($q, ' OR '));
				continue;
			}
			
			if (preg_match('/^(\w+)\s+(.+)$/', $field, $mts))
			{
				$field = $mts[1];
				$operand = trim($mts[2]);
			}
			else if (is_array($q))
			{
				$operand = 'IN';
			}
			else
			{
				$operand = '=';
			}
			$operand = sprintf(' %s ', trim($operand));
			
			$q = '?';
			if (is_array($q))
			{
				$q_ = array();
				foreach ($q as $qi)
				{
					$q_[] = $this->quoteValue($field, $qi);
				}
				$q = sprintf('(%s)', join(',', $q_));
			}
			else
			{
				$q = $this->quoteValue($field, $q);
			}
			
			$where[] = $field . $operand . $q;
		}
		
		return join($concat, $where);
	}
	
	/**
	 * prepared statement を作り、対応した配列を返す。
	 *
	 * @return [string $prepared_statement, array $bindArray]
	 */
	public function bindQuery($conditions = array(), $concat = ' AND ', &$bindArray)
	{
		if ($conditions == 1 OR $conditions === '' OR count($conditions) === 0)
			return '1';
		
		foreach ($conditions as $field => $q)
		{
			if ($field === 'OR' && is_array($q))
			{
				$where[] = sprintf('(%s)', $this->bindQuery($q, ' OR ', $bindArray));
				continue;
			}

			if (preg_match('/^(\w+)\s+(.+)$/', $field, $mts))
			{
				$field = $mts[1];
				$operand = trim($mts[2]);
			}
			else if (is_array($q))
			{
				$operand = 'IN';
			}
			else
			{
				$operand = '=';
			}
			$operand = sprintf(' %s ', trim($operand));
			
			$q_ = array();
			if (is_array($q))
			{
				foreach ($q as $qi)
				{
					$bindArray[] = array('value'=>$qi, 'type'=>$this->getPDODataType($field, $qi));
				}
				$q = sprintf('(%s)', join(',', array_fill(0, count($q), '?')));
			}
			else
			{
				$bindArray[] = array('value'=>$q, 'type'=>$this->getPDODataType($field, $q));
				$q = '?';
			}
			
			$where[] = $field . $operand . $q;
		}
		
		return join($concat, $where);
	}
	
	private function quoteValue($field, $value)
	{
		if ( ! array_key_exists($field, $this->schema)) return $value;

		if (strpos($this->schema[$field], 'INTEGER') !== FALSE)
		{
			$value = (int)$value;
		}
		else
		{
			$value = $this->db->quote($value);
		}
		return $value;
	}
	
	public function listBy($group = 'tag', $limit = 4)
	{
		$cachefile = $this->cacheDir . 'filer_listby_' . $group . '.dat';
		if (FALSE && file_exists($cachefile) &&
			filemtime($cachefile) > filemtime($this->dbFile))
		{
			return unserialize(file_get_contents($cachefile));
		}
		
		$count = $this->count('1');
		$files = $this->find('1', 'id ASC', $count);
		
		$folders = array();
		$id = 1;
		$notag = $this->notTagged;
		$notag_folder = array(
			'id' => $id++,
			'title' => $notag,
			'url' => $script . '?cmd=filer&search_word='. rawurlencode(":tag "),
			'files' => array()
		);
		
		foreach ($files as $file)
		{
			switch ($group)
			{
	
				case 'event':
				case 'day':
					$folder_name = substr($file['created'], 0, 10);
					if (isset($folders[$folder_name]))
					{
						if (count($folders[$folder_name]['files']) < 4)
						{
							$folders[$folder_name]['files'][] = $file;
						}
					}
					else
					{
						$folders[$folder_name] = array(
							'id' => $id++,
							'title' => $folder_name,
							'url' => $script . '?cmd=filer&search_word='. rawurlencode(":day {$folder_name}"),
							'files' => array(
								$file
							)
						);
					}
					break;
							
				case 'tag':
				default:
					if ($file['tag'] === '') break;

					$tag = trim($file['tag'], ',');
					$tags = explode(',', $tag);
					
					if ($tag)
					{
						foreach ($tags as $tag)
						{
							if (isset($folders[$tag]))
							{
								if (count($folders[$tag]['files']) < $limit)
									$folders[$tag]['files'][] = $file;
							}
							else
							{
								$folders[$tag] = array(
									'id' => $id++,
									'title' => $tag,
									'url' => $script . '?cmd=filer&search_word='. rawurlencode(":tag {$tag}"),
									'files' => array(
										$file
									)
								);
							}
						}
					}
					else
					{
						if (count($notag_folder['files']) < $limit)
							$notag_folder['files'][] = $file;
					}
					
				
			}
		}
	
		ksort($folders);
		$folders = array_values($folders);
		array_unshift($folders, $notag_folder);
		
		//thumbnail をセット
		$thumbdir = $this->imageEditable ? 'thumbnail/' : '';
		foreach ($folders as $i => $folder)
		{
			foreach ($folder['files'] as $j => $file)
			{
				$folders[$i]['files'][$j] = array_merge(array(
					'thumbnail' => $this->uploadDir . $thumbdir . $file['filename'],
				), $file);
			}
		}

		//キャッシュを保存
		file_put_contents($cachefile, serialize($folders));
	
		return $folders;

	}
	
	public function vacuum()
	{
		$sql = 'VACUUM';
		
		return $this->db->query($sql);
	}
	
	public function getLastInsertID()
	{
		$sql = 'SELECT LAST_INSERT_ROWID() AS last_id';
		$res = $this->db->query($sql);
		$res = $res->fetch(PDO::FETCH_ASSOC);
		return $res['last_id'];
	}
	
	public function query($sql)
	{
		$this->history[] = $sql;
		return $this->db->query($sql);
	}

	// ! File Related Functions
	
	public function saveUploadFiles()
	{
		if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && count($_FILES[$this->paramName]))
		{
			//save to local
			$result = $this->handler->post(FALSE);
			
			if (isset($result['error']))
			{
				return FALSE;
			}
			if (array_key_exists($this->paramName, $result))
			{
				$files = $result[$this->paramName];
				
				$id_list = array();
				foreach ($files as $file)
				{
					$filepath = $this->uploadDir . $file->name;
					$data = array(
						'filename' => $file->name,
						'title'    => $file->title,
						'size'     => $file->size,
						'type'     => self::filetype($filepath),
						'tag'      => '',
					);
					$success = $this->saveFile($data);
					
					if ($success)
					{
						$id_list[] = $this->getLastInsertID();
					}
				}
				
				if ($id_list) return $id_list;
				
			}
		}
		return FALSE;
	}
	
	public function saveFile($data)
	{
		$filepath = UPLOAD_DIR . $data['filename'];
		if ( ! file_exists($filepath)) return FALSE;
		
		$data['type'] = ORGM_Filer::filetype($filepath);
		
		$result = $this->insert($data);
		if ($result)
		{
			$this->saveHash();
		}
		return $result;
	}

	/**
	 * uploadDir をチェックし、
	 * ・登録されていないファイルのDB登録
	 * ・登録されているが存在しないファイルをDBから削除
	 * ・sqlite のVACUUM を行う
	 */
	public function checkUploadDir()
	{
		
	}
	

	public function editFile($data)
	{
		$filepath = UPLOAD_DIR . $data['filename'];
		if ( ! file_exists($filepath)) return FALSE;
		
		$data['type'] = ORGM_Filer::filetype($filepath);
		$data['size'] = filesize($filepath);
		$data['tag'] = sprintf(',%s,', self::tagTrim($data['tag']));

		return $this->update($data);
		
	}
	
	public function duplicateFile($id)
	{
		$srcfile = $this->findById($id);
		
		$newfilename = $srcfile['filename'];
		
		while(is_file($this->uploadDir . $newfilename))
		{
			$newfilename = $this->handler->upcount_name($newfilename);
		}
		
		$srcfilepath = $this->uploadDir . $srcfile['filename'];
		$newfilepath = $this->uploadDir . $newfilename;

		if (copy($srcfilepath, $newfilepath))
		{
			$newfile = array_merge($srcfile, array(
				'filename' => $newfilename,
				'star'     => 0
			));
			
			$this->saveHash();

			$result = $this->insert($newfile);
			if ($result)
			{
				$this->saveHash();
			}
			return $result;
		}
		return FALSE;
	}

	/**
	 * 指定した場所に画像を作成する。
	 */
	public function createImage($srcimgpath, $newimgpath, $options = array())
	{
		if ( ! $this->imageEditable) return FALSE;
		
		if ( ! $options)
		{
			if ($srcimgpath === $newimgpath)
			{
				return FALSE;
			}
			else
			{
				//copy image
			}
		}
		
		
		//幅、高さが指定されていればリサイズ
		//crop オプションがあればクロップも行う
		
		list($src_width, $src_height) = @getimagesize($srcimgpath);
		
		$new_width = isset($options['width']) ? $options['width'] : $src_width;
		$new_height = isset($options['height']) ? $options['height'] : $src_height;
		
		$crop_x = $crop_y = 0;
		$crop_w = $src_width;
		$crop_h = $src_height;
		if (isset($options['crop']))
		{
			$crop_x = $options['crop']['x'];
			$crop_y = $options['crop']['y'];
			$new_width = $crop_w = $options['crop']['w'];
			$new_height = $crop_h = $options['crop']['h'];
		}
		
		$rotate = isset($options['rotate']) ? $options['rotate'] : 0;

		//max_width, max_height
        if (isset($options['inverse']) && $options['inverse'])
        {
	        $func = 'max';
        }
        else
        {
	        $func = 'min';
        }
        $scale = $func(
            $options['max_width'] / $new_width,
            $options['max_height'] / $new_height
        );
        
        if ($scale < 1)
        {
	        $new_width = $new_width * $scale;
	        $new_height = $new_height * $scale;
        }
		
		//crop and resize
		$new = imagecreatetruecolor($new_width, $new_height);

        switch (strtolower(substr(strrchr($newimgpath, '.'), 1)))
        {
            case 'jpg':
            case 'jpeg':
                $src_img = @imagecreatefromjpeg($srcimgpath);
                $write_image = 'imagejpeg';
                $image_quality = isset($options['jpeg_quality']) ?
                    $options['jpeg_quality'] : 75;
                break;
            case 'gif':
                @imagecolortransparent($new, @imagecolorallocate($new, 0, 0, 0));
                $src_img = @imagecreatefromgif($srcimgpath);
                $write_image = 'imagegif';
                $image_quality = NULL;
                break;
            case 'png':
                @imagecolortransparent($new, @imagecolorallocate($new, 0, 0, 0));
                @imagealphablending($new, false);
                @imagesavealpha($new, true);
                $src_img = @imagecreatefrompng($srcimgpath);
                $write_image = 'imagepng';
                $image_quality = isset($options['png_quality']) ?
                    $options['png_quality'] : 9;
                break;
            default:
                $src_img = NULL;
        }

/*
		$success = $src_img && @imagecopyresampled(
			$new,
			$src_img,
			0, 0, $crop_x, $crop_y,
			$new_width,
			$new_height,
			$crop_w,
			$crop_h
		) && $write_image($new, $newimgpath, $image_quality);
*/
		
		$success = FALSE;
		if ($src_img && @imagecopyresampled(
			$new,
			$src_img,
			0, 0, $crop_x, $crop_y,
			$new_width,
			$new_height,
			$crop_w,
			$crop_h
		))
		{
			if ($rotate !== 0)
			{
				$new = imagerotate($new, $rotate, 0);
			}
			$success = $write_image($new, $newimgpath, $image_quality);
		}
        @imagedestroy($src_img);
        @imagedestroy($new);

        chmod($newimgpath, 0666);

		return $success;

	}
	
	public function deleteFile($id)
	{
		$file = $this->findById($id);
		if ($this->delete($id))
		{
			foreach ($this->uploadDirs as $dir => $option)
			{
				$dir = ($dir === '') ? $dir : ($dir . '/');
				unlink(UPLOAD_DIR . $dir . $file['filename']);
			}

			$this->saveHash();
			return TRUE;
		}
		return FALSE;
	}
	
	public function createHash()
	{
		$md5 = md5(join(',', glob($this->uploadDir . '*')));
		return $md5;
	}
	
	public function saveHash()
	{
		$md5 = $this->createHash();
		file_put_contents($this->hashFile, $md5);
	}
	
	public function checkHash()
	{

		if ( ! file_exists($this->hashFile))
		{
			$this->saveHash();
			return FALSE;
		}
		if ( ! is_writable($this->hashFile))
		{
			throw new AccessControlException(sprintf(__('ファイルに書き込み権限がありません：%s'), $this->hashFile));
		}

		$saved_md5 = file_get_contents($this->hashFile);
		
		$now_md5 = $this->createHash();
		
		//更新を感知
		if ($saved_md5 !== $now_md5)
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	public function findFiles($conditions = array(), $order = NULL, $limit = NULL)
	{
		$order = $order ? $order : self::ORDER;
		$limit = $limit ? $limit : self::LIMIT;
		
		$files = $this->find($conditions, $order, $limit);
		
		
		$tmp_yearmonth = '';
		foreach ($files as $i => $file)
		{
			$thumbdir = $this->imageEditable ? 'thumbnail/' : '';
			$files[$i]['thumbnail'] = $this->uploadDir . $thumbdir . $file['filename'];
			$files[$i]['filepath'] = $this->uploadDir . $file['filename'];
			$files[$i]['url'] = $this->uploadUrl . $file['filename'];
			if ($files[$i]['type'] === 'image')
			{
				$imagesize = getimagesize($files[$i]['filepath']);
				$files[$i]['imagesize'] = sprintf("%d x %d", $imagesize[0], $imagesize[1]);
				$files[$i]['width'] = $imagesize[0];
				$files[$i]['height'] = $imagesize[1];
			}
			$tags = self::tagTrim($files[$i]['tag']);
			$files[$i]['tag'] = $tags === '' ? array() : explode(',', $tags);
			$files[$i]['filesize'] = ($file['size'] > 1024 * 1024) ? (round($file['size'] / 1024 / 1024, 1) . 'MB') : (ceil($file['size'] / 1024) . 'KB');
			$files[$i]['created'] = date('Y-m-d', strtotime($file['created']));

			$files[$i]['yearmonth'] = '';
			if ($files[$i]['created'] == date('Y-m-d'))
			{
				if ($tmp_yearmonth != $this->timeline['today'])
				{
					$files[$i]['yearmonth'] = $tmp_yearmonth = $this->timeline['today'];
				}
			}
			else if (strtotime($file['created']) >= strtotime('-8 day'))
			{
				if ($tmp_yearmonth != $this->timeline['lastweek'])
				{
					$files[$i]['yearmonth'] = $tmp_yearmonth = $this->timeline['lastweek'];
				}
			}
			else
			{
				$yearmonth = date($this->timeline['month'], strtotime($file['created']));
				if ($yearmonth != $tmp_yearmonth)
				{
					$files[$i]['yearmonth'] = $tmp_yearmonth = $yearmonth;
				}
			}
		}


		
		return $files;
	}
	
	public function findFile($id)
	{
		$conditions = array('id' => $id);
		$files = $this->findFiles($conditions, NULL, 1);
		return array_pop($files);
	}
	
	/**
	 * upload フォルダ内のファイルとDBをチェックし、
	 * 未登録のファイルを登録、
	 * 登録済みだが、ファイルが存在しないレコードを削除。
	 */
	public function checkFile()
	{
		$count = $this->count(1);
		$limit_length = 1000;
		$it = ceil($count / $limit_length);
		$sql_format = 'SELECT id,filename FROM files ORDER BY id LIMIT %s';
		
		$add_count = 0;
		$del_count = 0;
		$chmod_count = 0;
		$file_size = filesize($this->dbFile);
		
		$delete_files = array();
		for ($i = 0; $i < $it; $i++)
		{
			
			$limit = $i * $limit_length . ',' . $limit_length;
			$sql = sprintf($sql_format, $limit);
			
			$result = $this->query($sql);
			$files = $result->fetchAll(PDO::FETCH_ASSOC);
			
			foreach ($files as $file)
			{
				$filepath = $this->uploadDir . $file['filename'];
				
				if ( ! file_exists($filepath))
				{
					$this->deleteFile($file['id']);
					$del_count++;
				}
				else if (fileperms($filepath) & 0x666 !== 0666)
				{
					chmod($filepath, 0666);
					$chmod_count++;
				}
			}
		}
		
		$dh = opendir($this->uploadDir);
		while ($entry = readdir($dh))
		{
			if ($entry == '.' || $entry == '..' || $entry == '.htaccess' || ! is_file($this->uploadDir . $entry))
				continue;
			
			if ( ! $this->find(array('filename'=>$entry), 'id ASC', 1))
			{
				$filepath = $this->uploadDir . $entry;
				$title = basename($entry, '.' . pathinfo($filepath, PATHINFO_EXTENSION));
				$filename = $this->handler->trim_file_name($entry, $this->handler->get_file_type($filepath), 0, NULL);

				if ($entry !== $filename)
				{
					$filename = $this->handler->get_file_name($filename, $this->handler->get_file_type($filepath), 0, NULL);
					rename($filepath, $this->uploadDir . $filename);
					$filepath = $this->uploadDir . $filename;
				}

				$data = array(
					'filename' => $filename,
					'title'    => $title,
					'size'     => filesize($filepath),
					'type'     => self::filetype($filepath),
					'tag'      => '',
				);
				
				//サムネイルを作る
				if ($this->imageEditable && $data['type'] === 'image')
				{
					foreach ($this->uploadDirs as $dir => $options)
					{
						if ($dir !== '')
						{
							$result = $this->handler->create_scaled_image($filename, $dir, $options);
						}
					}
				}
				
				$this->saveFile($data);
				$add_count++;
			}
		}
		
		foreach ($this->uploadDirs as $dir => $option)
		{
			if ($dir)
			{
				$path = $this->uploadDir . $dir;
				if ((fileperms($path) & 0777) !== 0777)
				{
					chmod($path, 0777);
					$chmod_count++;
				}
			}
		}
		
		$this->saveHash();
		$this->vacuum();
		
		$file_size = $file_size - filesize($this->dbFile);
		
		$file_size = number_format($file_size) . ' byte';
		
		$message = sprintf(__('%d 個のファイルを追加しました。<br>ファイルが見つからない、%d 個のデータを削除しました。<br>%d 個のファイル権限を修正しました。<br>データファイルのサイズが %s 小さくなりました。'), $add_count, $del_count, $chmod_count, $file_size);
		
		return $message;
	}

	public function getPDODataType($field, $value)
	{
		$data_type = PDO::PARAM_STR;
		if (strpos($this->schema[$field], 'INTEGER') !== FALSE)
		{
			$data_type = PDO::PARAM_INT;
		}
		
		return $data_type;
	}

	
	// ! ■■■■ Static Functions ■■■■
	public static function filetype($filepath)
	{
		if ( ! file_exists($filepath) OR is_dir($filepath))
		{
			return FALSE;
		}

		$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
		
		switch ($ext)
		{
			case 'pdf':
			case 'doc':
			case 'docx':
			case 'xls':
			case 'xlsx':
			case 'ppt':
			case 'pptx':
			case 'pages':
			case 'numbers':
			case 'keynote':
				return 'doc';
				break;
				
			case 'mp3':
			case 'wav':
			case 'aiff':
			case 'aif':
			case 'm4a':
				return 'audio';
				break;
	
			case 'mov':
			case 'mpeg':
			case 'mpg':
			case 'mp4':
			case 'ogv':
			case 'webm':
				return 'video';
				break;
	
			case 'png':
			case 'jpeg':
			case 'jpg':
			case 'gif':
			case 'bmp':
				return 'image';
				break;
		}
	
		return 'other';		
	}
	
	public static function tagTrim($tags)
	{
		return trim($tags, " \t\n\r\0\x0B,");
	}
	
}


class ORGM_UploadHandler extends UploadHandler{
	
	function __construct($options = null, $initialize = true) {
		parent::__construct($options, $initialize);
	}
	
	public function convert_mb_name($name)
	{
		$ext = '';
		if (strpos($name, '.') !== FALSE)
		{
			$ext = strrchr($name, '.');
			$name = mb_substr($name, 0, -mb_strlen($ext));
		}
		if ( ! preg_match('/^[0-9a-zA-Z_.-]+$/', $name))
		{
//echo $name, ' -&gt; ', substr(md5($name), 0, 8), "\n";
			//先頭の8文字を使う
			$name = substr(md5($name), 0, 8);
		}
		$name = $name . $ext;
		return $name;		
	}

    public function trim_file_name($name, $type, $index, $content_range) {


	    $name = $this->convert_mb_name($name);
		
        return parent::trim_file_name($name, $type, $index, $content_range);
    }

	protected function upcount_name_callback($matches) {
		$index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
		$ext = isset($matches[2]) ? $matches[2] : '';
		return '-'.$index.$ext;
	}
	
	public function upcount_name($name) {
		return preg_replace_callback(
			'/(?:(?:-([\d]+))?(\.[^.]+))?$/',
			array($this, 'upcount_name_callback'),
			$name,
			1
		);
	}
	
	public function get_file_name($name, $type, $index, $content_range)
	{
		return parent::get_file_name($name, $type, $index, $content_range);
	}

    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error,
            $index = null, $content_range = null) {
        $file = new stdClass();
        $file->title = strpos($name, '.') !== FALSE ? substr($name, 0, strrpos($name, '.')) : $name;
        $file->name = $this->get_file_name($name, $type, $index, $content_range);
        $file->size = $this->fix_integer_overflow(intval($size));
        $file->type = $type;
        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $this->handle_form_data($file, $index);
            $upload_dir = $this->get_upload_path();
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, $this->options['mkdir_mode'], true);
            }
            $file_path = $this->get_upload_path($file->name);
            $append_file = $content_range && is_file($file_path) &&
                $file->size > $this->get_file_size($file_path);
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = $this->get_file_size($file_path, $append_file);
            if ($file_size === $file->size) {
                if ($this->options['orient_image']) {
                    $this->orient_image($file_path);
                }
                $file->url = $this->get_download_url($file->name);
                foreach($this->options['image_versions'] as $version => $options) {
                    if ($this->create_scaled_image($file->name, $version, $options)) {
                        if (!empty($version)) {
                            $file->{$version.'_url'} = $this->get_download_url(
                                $file->name,
                                $version
                            );
                        } else {
                            $file_size = $this->get_file_size($file_path, true);
                        }
                    }
                }
            } else if (!$content_range && $this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = 'abort';
            }
            $file->size = $file_size;
            $this->set_file_delete_properties($file);
        }
        return $file;
    }


	public function get_file_type($file_path)
	{
		return parent::get_file_type($file_path);
	}
	public function create_scaled_image($file_name, $version, $options)
	{
		return parent::create_scaled_image($file_name, $version, $options);
	}
}


/* End of file filer.inc.php */
/* Location: /haik-contents/plugin/filer.inc.php */