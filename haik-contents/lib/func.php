<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: func.php,v 1.73 2006/05/15 16:41:39 teanan Exp $
// Copyright (C)
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// General functions

function is_interwiki($str)
{
	global $InterWikiName;
	return preg_match('/^' . $InterWikiName . '$/', $str);
}

function is_qblog()
{
	global $enable_qblog;
	
	if ($enable_qblog === 0)
	{
		return FALSE;
	}
	
	return FALSE;
	
}

function is_pagename($str)
{
	global $BracketName;

	$is_pagename = (! is_interwiki($str) &&
		  preg_match('/^(?!\/)' . $BracketName . '$(?<!\/$)/', $str) &&
		! preg_match('#(^|/)\.{1,2}(/|$)#', $str));

	if (defined('SOURCE_ENCODING')) {
		switch(SOURCE_ENCODING){
		case 'UTF-8': $pattern =
			'/^(?:[\x00-\x7F]|(?:[\xC0-\xDF][\x80-\xBF])|(?:[\xE0-\xEF][\x80-\xBF][\x80-\xBF]))+$/';
			break;
		case 'EUC-JP': $pattern =
			'/^(?:[\x00-\x7F]|(?:[\x8E\xA1-\xFE][\xA1-\xFE])|(?:\x8F[\xA1-\xFE][\xA1-\xFE]))+$/';
			break;
		}
		if (isset($pattern) && $pattern != '')
			$is_pagename = ($is_pagename && preg_match($pattern, $str));
	}

	return $is_pagename;
}

function is_url($str, $only_http = FALSE, $omit_protocol = FALSE)
{
	$scheme = $only_http ? 'https?' : 'https?|ftp|news';
	$scheme = $omit_protocol ? ('(('. $scheme . '):)?') : ('(' . $scheme . '):');
	return preg_match('/^(' . $scheme . ')(\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]*)$/', $str);
}

function is_email($str)
{
	$email_match = '/^([a-z0-9_]|\-|\.|\+)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/i';
	return preg_match($email_match, $str);
}

function is_image($str)
{
	return preg_match('/\.(gif|png|jpe?g)$/i', $str);
}

// If the page exists
function is_page($page, $clearcache = FALSE)
{
	if ($clearcache) clearstatcache();
	return file_exists(get_filename($page));
}

function is_editable($page)
{
	global $cantedit;
	static $is_editable = array();

	if (! isset($is_editable[$page])) {
		$is_editable[$page] = (
			is_pagename($page) &&
			! is_freeze($page) &&
			! in_array($page, $cantedit)
		);
	}

	return $is_editable[$page];
}

function is_freeze($page, $clearcache = FALSE)
{
	global $function_freeze;
	static $is_freeze = array();

	if ($clearcache === TRUE) $is_freeze = array();
	if (isset($is_freeze[$page])) return $is_freeze[$page];

	if (! $function_freeze || ! is_page($page)) {
		$is_freeze[$page] = FALSE;
		return FALSE;
	} else {
		$fp = fopen(get_filename($page), 'rb') or
			die('is_freeze(): fopen() failed: ' . h($page));
		flock($fp, LOCK_SH) or die('is_freeze(): flock() failed');
		rewind($fp);
		$buffer = fgets($fp, 9);
		flock($fp, LOCK_UN) or die('is_freeze(): flock() failed');
		fclose($fp) or die('is_freeze(): fclose() failed: ' . h($page));

		$is_freeze[$page] = ($buffer != FALSE && rtrim($buffer, "\r\n") == '#freeze');
		return $is_freeze[$page];
	}
}

/**
 *   iPhone, iPod, android からのアクセスかどうか判定する
 */
function is_smart_phone() {
	return
		strpos(UA_NAME, 'iPhone') !== FALSE ||
		strpos(UA_NAME, 'iPod')   !== FALSE ||
		strpos(UA_NAME, 'Mobile Safari') !== FALSE;
}



/**
 * 現在のリクエストがSSLかどうかを判定して返す
 */
function is_ssl()
{
	static $is_ssl;
	if ( is_null($is_ssl))
	{
		foreach(array(
				'HTTPS' => 'on',
				'SERVER_PORT' => '443',
				'HTTP_X_FORWARDED_PROTO' => 'https', //例 : ロリポップ
			) as $k=>$v){
			
			if( isset($_SERVER[$k]) &&  $_SERVER[$k]==$v ){
				$is_ssl = TRUE;
				return $is_ssl;
			}
		}
		$is_ssl = FALSE;
	}
	return $is_ssl;
}

function is_ajax()
{
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']);
}


// Handling $non_list
// $non_list will be preg_quote($str, '/') later.
function check_non_list($page = '')
{
	global $non_list;
	static $regex;

	if (! isset($regex)) $regex = '/' . $non_list . '/';

	return preg_match($regex, $page);
}

// Auto template
function auto_template($page)
{
	global $auto_template_func, $auto_template_rules;

	if (! $auto_template_func) return '';

	$body = '';
	$matches = array();
	foreach ($auto_template_rules as $rule => $template) {
		$rule_pattrn = '/' . $rule . '/';

		if (! preg_match($rule_pattrn, $page, $matches)) continue;

		$template_page = preg_replace($rule_pattrn, $template, $page);
		if (! is_page($template_page)) continue;

		$body = join('', get_source($template_page));

		// Remove fixed-heading anchors
		$body = preg_replace('/^(\*{1,3}.*)\[#[A-Za-z][\w-]+\](.*)$/m', '$1$2', $body);

		// Remove '#freeze'
		$body = preg_replace('/^#freeze\s*$/m', '', $body);

		$count = count($matches);
		for ($i = 0; $i < $count; $i++)
			$body = str_replace('$' . $i, $matches[$i], $body);

		break;
	}
	return $body;
}

// Expand all search-words to regexes and push them into an array
function get_search_words($words = array(), $do_escape = FALSE)
{
	static $init, $mb_convert_kana, $pre, $post, $quote = '/';

	if (! isset($init)) {
		// function: mb_convert_kana() is for Japanese code only
		if (LANG == 'ja' && function_exists('mb_convert_kana')) {
			$mb_convert_kana = create_function('$str, $option',
				'return mb_convert_kana($str, $option, SOURCE_ENCODING);');
		} else {
			$mb_convert_kana = create_function('$str, $option',
				'return $str;');
		}
		if (SOURCE_ENCODING == 'EUC-JP') {
			// Perl memo - Correct pattern-matching with EUC-JP
			// http://www.din.or.jp/~ohzaki/perl.htm#JP_Match (Japanese)
			$pre  = '(?<!\x8F)';
			$post =	'(?=(?:[\xA1-\xFE][\xA1-\xFE])*' . // JIS X 0208
				'(?:[\x00-\x7F\x8E\x8F]|\z))';     // ASCII, SS2, SS3, or the last
		} else {
			$pre = $post = '';
		}
		$init = TRUE;
	}

	if (! is_array($words)) $words = array($words);

	// Generate regex for the words
	$regex = array();
	foreach ($words as $word) {
		$word = trim($word);
		if ($word == '') continue;

		// Normalize: ASCII letters = to single-byte. Others = to Zenkaku and Katakana
		$word_nm = $mb_convert_kana($word, 'aKCV');
		$nmlen   = mb_strlen($word_nm, SOURCE_ENCODING);

		// Each chars may be served ...
		$chars = array();
		for ($pos = 0; $pos < $nmlen; $pos++) {
			$char = mb_substr($word_nm, $pos, 1, SOURCE_ENCODING);

			// Just normalized one? (ASCII char or Zenkaku-Katakana?)
			$or = array(preg_quote($do_escape ? h($char) : $char, $quote));
			if (strlen($char) == 1) {
				// An ASCII (single-byte) character
				foreach (array(strtoupper($char), strtolower($char)) as $_char) {
					if ($char != '&') $or[] = preg_quote($_char, $quote); // As-is?
					$ascii = ord($_char);
					$or[] = sprintf('&#(?:%d|x%x);', $ascii, $ascii); // As an entity reference?
					$or[] = preg_quote($mb_convert_kana($_char, 'A'), $quote); // As Zenkaku?
				}
			} else {
				// NEVER COME HERE with mb_substr(string, start, length, 'ASCII')
				// A multi-byte character
				$or[] = preg_quote($mb_convert_kana($char, 'c'), $quote); // As Hiragana?
				$or[] = preg_quote($mb_convert_kana($char, 'k'), $quote); // As Hankaku-Katakana?
			}
			$chars[] = '(?:' . join('|', array_unique($or)) . ')'; // Regex for the character
		}

		$regex[$word] = $pre . join('', $chars) . $post; // For the word
	}

	return $regex; // For all words
}

// 'Search' main function
function do_search($word, $type = 'AND', $non_format = FALSE, $base = '')
{
	global $script, $whatsnew, $non_list, $search_non_list;
	global $search_auth, $show_passage;

	$retval = array();

	$b_type = ($type == 'AND'); // AND:TRUE OR:FALSE
	$word = mb_convert_encoding($word, SOURCE_ENCODING, 'auto');
	$word = mb_ereg_replace("　", " ", $word);
	$keys = get_search_words(preg_split('/\s+/', $word, -1, PREG_SPLIT_NO_EMPTY));
	foreach ($keys as $key=>$value)
		$keys[$key] = '/' . $value . '/S';

	$pages = get_existpages();

	// Avoid
	if ($base != '') {
		$pages = preg_grep('/^' . preg_quote($base, '/') . '/S', $pages);
	}
	if (! $search_non_list) {
		$pages = array_diff($pages, preg_grep('/' . $non_list . '/S', $pages));
	}
	$pages = array_flip($pages);
	unset($pages[$whatsnew]);

	$count = count($pages);
	foreach (array_keys($pages) as $page) {
		$b_match = FALSE;

		// Search for page name
		if (! $non_format) {
			foreach ($keys as $key) {
				$b_match = preg_match($key, $page);
				if ($b_type xor $b_match) break; // OR
			}
			if ($b_match) continue;
		}

		// Search auth for page contents
		if ($search_auth && ! check_readable($page, false, false, true)) {
			unset($pages[$page]);
			--$count;
		}

		// Search for page contents
		foreach ($keys as $key) {
			$b_match = preg_match($key, get_source($page, TRUE, TRUE));
			if ($b_type xor $b_match) break; // OR
		}
		if ($b_match) continue;

		unset($pages[$page]); // Miss
	}
	if ($non_format) return array_keys($pages);

	$r_word = rawurlencode($word);
	$s_word = h($word);
	if (empty($pages))
		return str_replace('$1', $s_word, __('$1 を含むページは見つかりませんでした。'));

	ksort($pages);

	$retval = '<ul>' . "\n";
	foreach (array_keys($pages) as $page) {
		$r_page  = rawurlencode($page);
		$s_page  = h($page);
		$passage = $show_passage ? ' ' . get_passage(get_filetime($page)) : '';
		$retval .= ' <li><a href="' . $script . '?cmd=read&amp;page=' .
			$r_page . '&amp;word=' . $r_word . '">' . $s_page .
			'</a>' . $passage . '</li>' . "\n";
	}
	$retval .= '</ul>' . "\n";

	$retval .= str_replace('$1', $s_word, str_replace('$2', count($pages),
		str_replace('$3', $count, $b_type ? __('$1 のすべてを含むページは <strong>$3</strong> ページ中、 <strong>$2</strong> ページ見つかりました。') : __('$1 のいずれかを含むページは <strong>$3</strong> ページ中、 <strong>$2</strong> ページ見つかりました。'))));

	return $retval;
}

// Argument check for program
function arg_check($str)
{
	global $vars;
	return isset($vars['cmd']) && (strpos($vars['cmd'], $str) === 0);
}

// Encode page-name
function encode($key)
{
	return ($key == '') ? '' : strtoupper(bin2hex($key));
	// Equal to strtoupper(join('', unpack('H*0', $key)));
	// But PHP 4.3.10 says 'Warning: unpack(): Type H: outside of string in ...'
}

// Decode page name
function decode($key)
{
	return hex2bin($key);
}

// PHP 5.4.1 移行では組み込み関数
if ( ! function_exists('hex2bin'))
{
	// Inversion of bin2hex()
	function hex2bin($hex_string)
	{
		// preg_match : Avoid warning : pack(): Type H: illegal hex digit ...
		// (string)   : Always treat as string (not int etc). See BugTrack2/31
		return preg_match('/^[0-9a-f]+$/i', $hex_string) ?
			pack('H*', (string)$hex_string) : $hex_string;
	}
}

// Remove [[ ]] (brackets)
function strip_bracket($str)
{
	$match = array();
	if (preg_match('/^\[\[(.*)\]\]$/', $str, $match)) {
		return $match[1];
	} else {
		return $str;
	}
}

// Create list of pages
function page_list($pages, $cmd = 'read', $withfilename = FALSE)
{
	global $script, $list_index, $vars;
	global $pagereading_enable;
	$qm = get_qm();
	
	// ソートキーを決定する。 ' ' < '[a-zA-Z]' < 'zz'という前提。
	$symbol = ' ';
	$other = 'zz';

	$retval = '';

	if($pagereading_enable) {
		mb_regex_encoding(SOURCE_ENCODING);
		$readings = get_readings($pages);
	}

	$list = $matches = array();

	// Shrink URI for read
	if ($cmd == 'read') {
		$href = $script . '?';
	} else {
		$href = $script . '?cmd=' . $cmd . '&amp;page=';
	}

	foreach($pages as $file=>$page)
	{
		$r_page  = rawurlencode($page);
		$s_page  = h($page, ENT_QUOTES);
		$passage = get_pg_passage($page);

		//customized by hokuken.com
		$t_page = get_page_title($s_page);
		$t_page = ($t_page == '' || $t_page == $s_page) ? '' : ' ('.$t_page.')';

		$str = '   <li><a href="' . $href . $r_page . '">' .
			$s_page . $t_page . '</a>' . $passage;

		if ($withfilename) {
			$s_file = h($file);
			$str .= "\n" . '    <ul><li>' . $s_file . '</li></ul>' .
				"\n" . '   ';
		}
		$str .= '</li>';

		// WARNING: Japanese code hard-wired
		if($pagereading_enable) {
			if(mb_ereg('^([A-Za-z])', mb_convert_kana($page, 'a'), $matches)) {
				$head = $matches[1];
			} elseif (isset($readings[$page]) && mb_ereg('^([ァ-ヶ])', $readings[$page], $matches)) { // here
				$head = $matches[1];
			} elseif (mb_ereg('^[ -~]|[^ぁ-ん亜-熙]', $page)) { // and here
				$head = $symbol;
			} else {
				$head = $other;
			}
		} else {
			$head = (preg_match('/^([A-Za-z])/', $page, $matches)) ? $matches[1] :
				(preg_match('/^([ -~])/', $page, $matches) ? $symbol : $other);
		}

		$list[$head][$page] = $str;
	}
	ksort($list);
	
	$tmparr1 = isset($list[$symbol])? $list[$symbol]: null;
	unset($list[$symbol]);
	$list[$symbol] = $tmparr1;
	

	$cnt = 0;
	$arr_index = array();
	$retval .= '<div class="panel"><ul class="nav nav-list">' . "\n";
	foreach ($list as $head => $ppages) {
		if (is_null($ppages)) {
			continue;
		}
	
		if ($head === $symbol) {
			$head = $qm->m['func']['list_symbol'];
		} else if ($head === $other) {
			$head = $qm->m['func']['list_other'];
		}

		if ($list_index) {
			++$cnt;
			$arr_index[] = '<li><a href="#head_' . $cnt . '"><strong>'.$head .'</strong></a></li>';
			$retval .= ' <li class="nav-header orgm-pagelist-header" id="head_'.$cnt.'"><strong>'.$head.'</strong>'."\n  \n";
		}
		ksort($ppages);
		$retval .= join("\n", $ppages);
		if ($list_index)
			$retval .= "\n  \n </li>\n";
	}
	$retval .= "</ul></div>\n";

	if ($list_index && $cnt > 0) {
		$top = array();
		while (! empty($arr_index))
		{
			$top[] = join("\n", array_splice($arr_index, 0, 16))."\n";
		}

		$retval = '
<div class="plugin_list">
	<div id="orgm_pagelist_top">
		<ul class="pagination pagination-mini">'
			. join('', $top) .'
		</ul>
	</div>'
	. $retval .'
</div>
';
	}
	return $retval;
}

// Show text formatting rules
function catrule()
{

	if (! is_page('FormattingRules')) {
		return '<p>Sorry, page \'' . h('FormattingRules') .
			'\' unavailable.</p>';
	} else {
		return convert_html(get_source('FormattingRules'));
	}
}

// Show (critical) error message
function die_message($msg)
{
	$title = $page = 'Runtime error';
	$body = <<<EOD
<h3>Runtime error</h3>
<strong>Error message : $msg</strong>
EOD;

	pkwk_common_headers();
	header('Content-Type: text/html; charset=utf-8');
	echo <<<EOD
<!DOCTYPE html>
<html>
 <head>
  <meta charset="UTF-8">
  <title>$title</title>
 </head>
 <body>
 $body
 </body>
</html>
EOD;
	exit;
}

// Have the time (as microtime)
function getmicrotime()
{
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$sec + (float)$usec);
}

// Get the date
function get_date($format, $timestamp = NULL)
{
	$format = preg_replace('/(?<!\\\)T/',
		preg_replace('/(.)/', '\\\$1', ZONE), $format);

	$time = ZONETIME + (($timestamp !== NULL) ? $timestamp : UTIME);

	return date($format, $time);
}


// Format date string
function format_date($val, $paren = FALSE)
{
	global $date_format, $time_format, $weeklabels;

	$val += ZONETIME;

	$date = date($date_format, $val) .
		' (' . $weeklabels[date('w', $val)] . ') ' .
		date($time_format, $val);

	return $paren ? '(' . $date . ')' : $date;
}

// Get short string of the passage, 'N seconds/minutes/hours/days/years ago'
function get_passage($time, $paren = TRUE)
{
	static $units = array('m'=>60, 'h'=>24, 'd'=>1);

	$time = max(0, (UTIME - $time) / 60); // minutes

	foreach ($units as $unit=>$card) {
		if ($time < $card) break;
		$time /= $card;
	}
	$time = floor($time) . $unit;

	return $paren ? '(' . $time . ')' : $time;
}

// Hide <input type="(submit|button|image)"...>
function drop_submit($str)
{
	return preg_replace('/<input([^>]+)type="(submit|button|image)"/i',
		'<input$1type="$2" disabled', $str);
}

// Generate AutoLink patterns (thx to hirofummy)
function get_autolink_pattern(& $pages)
{
	global $WikiName, $autolink, $nowikiname;

	$config = new Config('AutoLink');
	$config->read();
	$ignorepages      = $config->get('IgnoreList');
	$forceignorepages = $config->get('ForceIgnoreList');
	unset($config);
	$auto_pages = array_merge($ignorepages, $forceignorepages);

	foreach ($pages as $page)
		if (preg_match('/^' . $WikiName . '$/', $page) ?
		    $nowikiname : strlen($page) >= $autolink)
			$auto_pages[] = $page;

	if (empty($auto_pages)) {
		$result = $result_a = $nowikiname ? '(?!)' : $WikiName;
	} else {
		$auto_pages = array_unique($auto_pages);
		sort($auto_pages, SORT_STRING);

		$auto_pages_a = array_values(preg_grep('/^[A-Z]+$/i', $auto_pages));
		$auto_pages   = array_values(array_diff($auto_pages,  $auto_pages_a));

		$result   = get_autolink_pattern_sub($auto_pages,   0, count($auto_pages),   0);
		$result_a = get_autolink_pattern_sub($auto_pages_a, 0, count($auto_pages_a), 0);
	}
	return array($result, $result_a, $forceignorepages);
}

function get_autolink_pattern_sub(& $pages, $start, $end, $pos)
{
	if ($end == 0) return '(?!)';

	$result = '';
	$count = $i = $j = 0;
	$x = (mb_strlen($pages[$start]) <= $pos);
	if ($x) ++$start;

	for ($i = $start; $i < $end; $i = $j) {
		$char = mb_substr($pages[$i], $pos, 1);
		for ($j = $i; $j < $end; $j++)
			if (mb_substr($pages[$j], $pos, 1) != $char) break;

		if ($i != $start) $result .= '|';
		if ($i >= ($j - 1)) {
			$result .= str_replace(' ', '\\ ', preg_quote(mb_substr($pages[$i], $pos), '/'));
		} else {
			$result .= str_replace(' ', '\\ ', preg_quote($char, '/')) .
				get_autolink_pattern_sub($pages, $i, $j, $pos + 1);
		}
		++$count;
	}
	if ($x || $count > 1) $result = '(?:' . $result . ')';
	if ($x)               $result .= '?';

	return $result;
}

// Get absolute-URI of this script
function get_script_uri($init_uri = '')
{
	global $script_directory_index;
	static $script;

	if ($init_uri == '') {
		// Get
		if (isset($script)) return $script;

		// Set automatically
		$msg     = 'get_script_uri() failed: Please set $script at INI_FILE manually';

		$script  = (SERVER_PORT == 443 ? 'https://' : 'http://'); // scheme
		$script .= SERVER_NAME;	// host
		$script .= ((SERVER_PORT == 80 || SERVER_PORT == 443) ? '' : ':' . SERVER_PORT);  // port

		// SCRIPT_NAME が'/'で始まっていない場合(cgiなど) REQUEST_URIを使ってみる
		$path    = SCRIPT_NAME;
		if ($path{0} != '/') {
			if (! isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI']{0} != '/')
				die_message($msg);

			// REQUEST_URIをパースし、path部分だけを取り出す
			$parse_url = parse_url($script . $_SERVER['REQUEST_URI']);
			if (! isset($parse_url['path']) || $parse_url['path']{0} != '/')
				die_message($msg);

			$path = $parse_url['path'];
		}
		$script .= $path;

		if (! is_url($script, TRUE) && php_sapi_name() == 'cgi')
			die_message($msg);
		unset($msg);

	} else {
		// Set manually
		if (isset($script)) die_message('$script: Already init');
		if (! is_url($init_uri, TRUE)) die_message('$script: Invalid URI');
		$script = $init_uri;
	}

	// Cut filename or not
	if (isset($script_directory_index)) {
		if (! file_exists($script_directory_index))
			die_message('Directory index file not found: ' .
				h($script_directory_index));
		$matches = array();
		if (preg_match('#^(.+/)' . preg_quote($script_directory_index, '#') . '$#',
			$script, $matches)) $script = $matches[1];
	}

	return $script;
}

// Remove null(\0) bytes from variables
//
// NOTE: PHP had vulnerabilities that opens "hoge.php" via fopen("hoge.php\0.txt") etc.
// [PHP-users 12736] null byte attack
// http://ns1.php.gr.jp/pipermail/php-users/2003-January/012742.html
//
// 2003-05-16: magic quotes gpcの復元処理を統合
// 2003-05-21: 連想配列のキーはbinary safe
//
function input_filter($param)
{
	static $magic_quotes_gpc = NULL;
	if ($magic_quotes_gpc === NULL)
	    $magic_quotes_gpc = get_magic_quotes_gpc();

	if (is_array($param)) {
		return array_map('input_filter', $param);
	} else {
		$result = str_replace("\0", '', $param);
		if ($magic_quotes_gpc) $result = stripslashes($result);
		return $result;
	}
}

// Compat for 3rd party plugins. Remove this later
function sanitize($param) {
	return input_filter($param);
}

// Explode Comma-Separated Values to an array
function csv_explode($separator, $string)
{
	$retval = $matches = array();

	$_separator = preg_quote($separator, '/');
	if (! preg_match_all('/("[^"]*(?:""[^"]*)*"|[^' . $_separator . ']*)' .
	    $_separator . '/', $string . $separator, $matches))
		return array();

	foreach ($matches[1] as $str) {
		$len = strlen($str);
		if ($len > 1 && $str{0} == '"' && $str{$len - 1} == '"')
			$str = str_replace('""', '"', substr($str, 1, -1));
		$retval[] = $str;
	}
	return $retval;
}


//// Compat ////

// is_a --  Returns TRUE if the object is of this class or has this class as one of its parents
// (PHP 4 >= 4.2.0)
if (! function_exists('is_a')) {

	function is_a($class, $match)
	{
		if (empty($class)) return FALSE; 

		$class = is_object($class) ? get_class($class) : $class;
		if (strtolower($class) == strtolower($match)) {
			return TRUE;
		} else {
			return is_a(get_parent_class($class), $match);	// Recurse
		}
	}
}

// array_fill -- Fill an array with values
// (PHP 4 >= 4.2.0)
if (! function_exists('array_fill')) {

	function array_fill($start_index, $num, $value)
	{
		$ret = array();
		while ($num-- > 0) $ret[$start_index++] = $value;
		return $ret;
	}
}

// md5_file -- Calculates the md5 hash of a given filename
// (PHP 4 >= 4.2.0)
if (! function_exists('md5_file')) {

	function md5_file($filename)
	{
		if (! file_exists($filename)) return FALSE;

		$fd = fopen($filename, 'rb');
		if ($fd === FALSE ) return FALSE;
		$data = fread($fd, filesize($filename));
		fclose($fd);
		return md5($data);
	}
}

// sha1 -- Compute SHA-1 hash
// (PHP 4 >= 4.3.0, PHP5)
if (! function_exists('sha1')) {
	if (extension_loaded('mhash')) {
		function sha1($str)
		{
			return bin2hex(mhash(MHASH_SHA1, $str));
		}
	}
}

//strip Google Adwords Code by HOKUKEN.COM 7/25 2007
// Remove Parameter of ad code, (exp. Google Adwords, Google Analytics, ... )
//
// 制限事項
//
// &以下は確実に削除できるが、WikiNameの位置にパラメータがセットされた場合、
// $adcodeで定義していない名前以外、WikiNameとして扱われる
// 基本的に、WikiNameまで入れたパスで、コードを作るようにして下さいな。
// 
function strip_adcode($str)
{
	global $adcode, $defaultpage;

	$adcode[] = APP_SESSION_NAME;
	
	$tokens = explode('&', $str);
	$str = $tokens[0];

	if (count($tokens) > 0)
	{
		foreach ($adcode as $var)
		{
			$ptn = '/^' . preg_quote($var, '/') . '=/';

			if (preg_match($ptn, $str))
			{
				return $defaultpage;
			}
		}

		return $str;
	}
	else
	{
		return $str;
	}
}

//force output message function for some plugin
//
// Show (critical) error message
function force_output_message($title, $page, $body)
{
	pkwk_common_headers();
	if (defined('SKIN_FILE') && file_exists(SKIN_FILE) && is_readable(SKIN_FILE))
	{
		catbody($title, $page, $body);
	}
	else
	{
		header('Content-Type: text/html; charset=utf-8');
		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
  <title>$title</title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
 </head>
 <body>
 $body
 </body>
</html>
EOD;
	}
	exit;
}

if( !function_exists('file_put_contents') ){
	function file_put_contents($filename, $data, $flag=FALSE )
	{
		$mode = $flag ? 'a' : 'w';
		$fp = fopen($filename, $mode); if($fp===FALSE) return FALSE;
		flock($fp, LOCK_EX);
		fputs($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
		return TRUE;
	}
}


function output_site_close_message($site_name, $login_url)
{
	global $display_login, $pkwk_dtd, $style_name, $admin_style_name, $site_close_all;
	
	$qt = get_qt();
	
	$qt->setv('meta_content_type', '<meta charset="UTF-8">');
	
	$app_sign = ($display_login > 0) ? '<a href="'. h($login_url) . '" rel="nofollow">'.h(APP_NAME).'</a>' : h(APP_NAME);

	//output 503 Status
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	
	$qm = get_qm();
	$closetitle = __('閉鎖中');
	$closesubtitle = __('このサイトは、現在閉鎖中です。');
	$closemsg = __('お手数ですが、公開されるまで、<br>今しばらくお待ち下さい。');
	
	//日付指定の場合
	$openmsg = '';
	if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $site_close_all))
	{
		$openmsg = sprintf(__('<strong>%s</strong> に公開予定です。'), $site_close_all);
//		var_dump($site_close_all);
	}
	
	$generator = h(APP_NAME . ' ' . APP_VERSION);

	qhm_output_dtd($pkwk_dtd);
	
	$style_name = $admin_style_name;
	$style_config = style_config_read($admin_style_name);
	$skin_file = SKIN_DIR."{$admin_style_name}/".$style_config['templates']['dialogue']['filename'];

	$style_files = array(
		CSS_DIR . 'bootstrap.min.css',
		CSS_DIR . 'origami.css',
		SKIN_DIR . $admin_style_name . '/' . $style_config['style_file'],
	);
	
	$style_css = '';
	foreach ($style_files as $css_file)
	{
		$style_css .= '<link rel="stylesheet" href="'. h($css_file).'" type="text/css">';
	}
	
	$qt->setv('style_css', $style_css);
	$qt->setv('style_path', SKIN_DIR . $admin_style_name . '/');
	$qt->setv('template_name', 'dialogue');
	$qt->setv('page_title', $closetitle);
	
	$include_script = '
<script type="text/javascript" src="'.JS_DIR.'jquery.js"></script>
<script type="text/javascript" src="'.JS_DIR.'bootstrap.min.js"></script>
<script type="text/javascript" src="'.JS_DIR.'origami.js"></script>
';
	$qt->setv('plugin_script', $include_script);
	
	
	if (exist_plugin('notify'))
	{
		do_plugin_convert("notify");
	}
	
	//favicon
	if (file_exists('favicon.ico'))
	{
		$qt->appendv('style_css', "\n\t".'<link rel="shortcut icon" href="favicon.ico">');
	}
	else if (file_exists(IMAGE_DIR . 'favicon.ico'))
	{
		$qt->appendv('style_css', "\n\t".'<link rel="shortcut icon" href="'.IMAGE_DIR.'favicon.ico">');
	}
	
	$body = <<< EOD

	<div class="heading">
		<h1>{$closetitle}</h1>
		<h2>{$closesubtitle}</h2>
	</div>

	<div class="row">
		<div class="col-sm-12 content-wrapper" role="main">
			<!-- BODYCONTENTS START> -->
			<div id="orgm_body">
				<div class="orgm-site-close-message">
					<p>{$closemsg}</p>
					<p>{$openmsg}</p>
				</div>
			</div>
			<div class="orgm-site-close-license text-center muted">
				<div id="login">powered by {$app_sign}</div>
			</div>
		</div>
	</div>
EOD;


	$qt->setv('body', $body);
	$qt->read($skin_file);
	exit;
}

function print_json($data)
{
	global $debug;
	$json = json_encode($data);
	
	if ( ! $debug)
	{
		header("Content-Type: application/json; charset=utf-8");
		header("X-Content-Type: nosniff");
	}
	
	echo $json;
	exit;
}

function wikiescape($string){
	//今のところ、#html{{ と、 #html2 だけ	
	$ret = '';
	$lines = explode("\n", $string);
	foreach($lines as $line){
		$ret .= preg_replace('/^#html/', "# html", $line) ."\n";
	}

	return $ret;	
}

function get_page_title($pagename, $lines=10){
	if ( ! file_exists(get_filename($pagename))) {
		return FALSE;
	}
	$title = meta_read($pagename, 'title');
	
	if ( ! $title) $title = $pagename;
	
	return $title;
}

function get_page_url($page)
{
	global $script, $defaultpage;
	
	return $script . ($defaultpage !== $page ? ('?' . rawurlencode($page)) : '');
}

function create_page_description($page, $length = 120, $source = NULL)
{
	global $ignore_plugin, $strip_plugin, $strip_plugin_inline;
	
	if ($source === NULL)
	{
		$source = get_source($page);
	}
	else if (is_string($source))
	{
		$source = explode("\n", $source);
	}
			
	foreach($source as $k => $l)
	{
		if (preg_match($ignore_plugin, $l))
		{
			unset($source[$k]);
			continue;
		}
		
		if (preg_match($strip_plugin, $l))
		{
			unset($source[$k]);
			continue;
		}
		
		if (preg_match('/^\*{1,3}/', $l))
		{
			unset($source[$k]);
			continue;
		}
	}
	
	//html(noskinを避ける)
	if (count($source) > 0)
	{
		$source = str_replace('#html(noskin)', '#html()', $source);
		$source = preg_replace($strip_plugin_inline, '', $source); // 行内のプラグインを説明から省く
	}
	
	$usr = FALSE;
	if (isset($_SESSION['usr']))
	{
		$usr = $_SESSION['usr'];
		unset($_SESSION['usr']);
	}
	$contents = mb_strimwidth( preg_replace('/\s+/', ' ', strip_htmltag( convert_html( $source ) )), 0, $length , '...');
	if ($usr !== FALSE)
	{
		$_SESSION['usr'] = $usr;
	}
	
	$contents = str_replace(array("\r", "\n"), '', $contents);
	
	return trim($contents);
}


/**
 * Redirect to URL or Page
 */
function redirect($url = '', $msg = '', $refresh_sec = 2)
{
	global $script, $style_name, $vars;
	$qt = get_qt();
	
	if (is_url($url))
	{
		//
	}
	else if (is_page($url))
	{
		$url = $script . '?' . $url;
	}
	//デフォルトページ
	else
	{
		$url = $script;
	}
	
	if ($msg !== '')
	{
		$style_name = '../';
		$title = array_shift(explode("\n", $msg));
		$head = '<meta http-equiv="refresh" content="'. h($refresh_sec) .';URL='. h($url) .'" />';
		$qt->appendv('beforescript', $head);
		
		$vars['disable_toolmenu'] = TRUE;
		
		$body = convert_html('
* '. $msg. '

'. $refresh_sec .'秒後に移動します。
移動しない場合は[[ここをクリック>'. $url .']]
');
		force_output_message($title, '', $body);
	}
	else
	{
		header('Location: ' . $url);
	}
	exit;
}

/**
 * 画面遷移後、通知欄にメッセージを表示します。
 * @params string $msg notice message
 * @params string $type message type: success | info | error | warning(empty)
 * @params int $priority message position. it was set as height
 */
function set_flash_msg($msg = '', $type = 'success', $set_nav = false, $fade = true, $priority = 20)
{
	$notice = array(
		'message'  => $msg,
		'type'     => $type,
		'priority' => $priority,
		'set_nav'  => $set_nav,
		'fade'     => $fade,
	);
	
	if (isset($_SESSION['notices']) && is_array($_SESSION['notices']))
	{
		$_SESSION['notices'][] = $notice;
	}
	else
	{
		$_SESSION['notices'] = array(
			$notice
		);
	}

}

function set_notify_msg($msg = '', $type = 'success', $set_nav = false , $fade = true, $priority = 10)
{
	if (exist_plugin('notify'))
	{
		return plugin_notify_set_notice($msg, $type, $set_nav, $fade, $priority);
	}
	return FALSE;
}

function orgm_mail_send($subject, $message, $to = '', $merge_tags = NULL, $options = array())
{
	require_once(LIB_DIR . 'simplemail.php');
	
	global $username, $site_title;
	global $smtp_server, $smtp_auth, $pop_server, $mail_userid, $mail_passwd, $mail_encode;
	
	$mailaddress = ($mailaddress === '') ? $username : $mailaddress;
	
	$mail = new SimpleMail();
	$mail->language = MB_LANGUAGE;
	$mail->encoding = SOURCE_ENCODING;
	$mail->set_encode($mail_encode);
	
	$to_name = isset($options['to_name']) ? $options['to_name'] : '';
	$from_name = (isset($options['from_name']) && $options['from_name'] !== '') ? $options['from_name'] : $site_title;
	$from_email = (isset($options['from_email']) && $options['from_email'] !== '') ? $options['from_email'] : $username;
	
	$mail->set_params($from_name, $from_email);
	$mail->set_to($to_name, $to);
	
	$mail->set_smtp_server($smtp_server);
	$mail->set_smtp_auth($smtp_auth, $mail_userid, $mail_passwd, $pop_server);
	$mail->subject = $subject;
	
	return $mail->send($message, $merge_tags);
}

function orgm_mail_notify($subject, $message, $merge_tags = NULL, $mailaddress = '')
{
	require_once(LIB_DIR . 'simplemail.php');
	
	global $username;
	global $smtp_server, $smtp_auth, $pop_server, $mail_userid, $mail_passwd, $mail_encode;
	
	$mailaddress = ($mailaddress === '') ? $username : $mailaddress;
	
	$mail = new SimpleMail();
	$mail->set_encode($mail_encode);
	
	$mailer_name = __(APP_NAME.' 通知');
	
	$mail->set_params($mailer_name, $mailaddress);
	$mail->set_to($mailer_name, $mailaddress);
	
	$mail->set_smtp_server($smtp_server);
	$mail->set_smtp_auth($smtp_auth, $mail_userid, $mail_passwd, $pop_server);
	$mail->subject = $subject;
	
	return $mail->send($message, $merge_tags);
}

function h($string, $flags = ENT_QUOTES, $charset = 'UTF-8'){
	return htmlspecialchars($string, $flags, $charset);
}

function h_decode($string){
	return htmlspecialchars_decode($string, ENT_QUOTES);
}


function qhm_get_script_path() {
	$tmp_script = '';
	preg_match("/(.*?php)/", basename( $_SERVER['PHP_SELF'] ), $ms);
	$tmp_script = $ms[1];
	return $tmp_script;
}

/**
 * removes entities &lt; &gt; &amp; and eventually &quot; from HTML string
 *
 */
if (!function_exists("htmlspecialchars_decode")) {
   if (!defined("ENT_COMPAT")) { define("ENT_COMPAT", 2); }
   if (!defined("ENT_QUOTES")) { define("ENT_QUOTES", 3); }
   if (!defined("ENT_NOQUOTES")) { define("ENT_NOQUOTES", 0); }
   function htmlspecialchars_decode($string, $quotes=2) {
      $d = $quotes & ENT_COMPAT;
      $s = $quotes & ENT_QUOTES;
      return str_replace(
         array("&lt;", "&gt;", ($s ? "&quot;" : "&.-;"), ($d ? "&#039;" : "&.-;"), "&amp;"),
         array("<",    ">",    "'",                      "\"",                     "&"),
         $string
      );
   }
}

function get_file_path($filename)
{
	if ($filename === '') return '';
	
	if (is_url($filename)) return $filename;
	
	if (file_exists(UPLOAD_DIR . $filename))
	{
		return UPLOAD_DIR . $filename;
	}
	else if (file_exists(IMAGE_DIR . $filename))
	{
		return IMAGE_DIR . $filename;
	}
	
	return $filename;

}

/**
* 拡張子から、mime-typeを返す
*/
function get_mimetype($fname){
	$ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
	
	switch($ext){
	
		case 'txt' : return 'text/plain';
		case 'csv' : return 'text/csv';
		case 'html':
		case 'htm' : return 'text/html';

		//
		case 'pdf' : return 'application/pdf';
		case 'css' : return 'text/css';
		case 'js'  : return 'text/javascript';
		
		//image
		case 'jpg' :
		case 'jpeg': return 'image/jpeg';
		case 'png' : return 'image/png';
		case 'gif' : return 'image/gif';
		case 'bmp' : return 'image/bmp';
		
		//av
		case 'mp3' : return 'audio/mpeg';
		case 'm4a' : return 'audio/mp4';
		case 'wav' : return 'audio/x-wav';
		case 'mpg' :
		case 'mpeg': return 'video/mpeg';
		case 'wmv' : return 'video/x-ms-wmv';
		case 'swf' : return 'application/x-shockwave-flash';
		
		//archives
		case 'zip' : return 'application/zip';
		case 'lha' : 
		case 'lzh' : return 'application/x-lzh';
		case 'tar' :
		case 'tgz' :
		case 'gz'  : return 'application/x-tar';
		
		
		//office files
		case 'doc' :
		case 'dot' : return 'application/msword';
		case 'docx': return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		case 'xls' : 
		case 'xlt' : 
		case 'xla' : return 'application/vnd.ms-excel';
		case 'xlsx': return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		case 'ppt' : 
		case 'pot' : 
		case 'pps' :
		case 'ppa' : return 'application/vnd.ms-powerpoint';
		case 'pptx': return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
		
	}
	
	return 'application/octet-stream';
	
	
}

/**
 * Converts PHP variable or array into a "JSON" (JavaScript value expression
 * or "object notation") string.
 *
 * @compat
 *    Output seems identical to PECL versions. "Only" 20x slower than PECL version.
 * @bugs
 *    Doesn't take care with unicode too much - leaves UTF-8 sequences alone.
 *
 * @param  $var mixed  PHP variable/array/object
 * @return string      transformed into JSON equivalent
 */
if (!function_exists("json_encode")) {
   function json_encode($var, /*emu_args*/$obj=FALSE) {
   
      #-- prepare JSON string
      $json = "";
      
      #-- add array entries
      if (is_array($var) || ($obj=is_object($var))) {

         #-- check if array is associative
         if (!$obj) foreach ((array)$var as $i=>$v) {
            if (!is_int($i)) {
               $obj = 1;
               break;
            }
         }

         #-- concat invidual entries
         foreach ((array)$var as $i=>$v) {
            $json .= ($json ? "," : "")    // comma separators
                   . ($obj ? ("\"$i\":") : "")   // assoc prefix
                   . (json_encode($v));    // value
         }

         #-- enclose into braces or brackets
         $json = $obj ? "{".$json."}" : "[".$json."]";
      }

      #-- strings need some care
      elseif (is_string($var)) {
         if (!utf8_decode($var)) {
            $var = utf8_encode($var);
         }
         $var = str_replace(array("\\", "\"", "/", "\b", "\f", "\n", "\r", "\t"), array("\\\\", "\\\"", "\\/", "\\b", "\\f", "\\n", "\\r", "\\t"), $var);
         $var = json_encode_string($var);
         $json = '"' . $var . '"';
         //@COMPAT: for fully-fully-compliance   $var = preg_replace("/[\000-\037]/", "", $var);
      }

      #-- basic types
      elseif (is_bool($var)) {
         $json = $var ? "true" : "false";
      }
      elseif ($var === NULL) {
         $json = "null";
      }
      elseif (is_int($var) || is_float($var)) {
         $json = "$var";
      }

      #-- something went wrong
      else {
         trigger_error("json_encode: don't know what a '" .gettype($var). "' is.", E_USER_ERROR);
      }
      
      #-- done
      return($json);
   }
}


function json_encode_string($in_str) {
	//fb($in_str, "json_encode_string");
	$debug = 'before:'."\n" . $in_str;
	mb_internal_encoding("UTF-8");
	$convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
	$str = "";
	for($i=mb_strlen($in_str)-1; $i>=0; $i--)
	{
		$mb_char = mb_substr($in_str, $i, 1);
		if(mb_ereg("&#(\\d+);", mb_encode_numericentity($mb_char, $convmap, "UTF-8"), $match))
		{
			$str = sprintf("\\u%04x", $match[1]) . $str;
		}
		else
		{
			$str = $mb_char . $str;
		}
	}
	$debug .= "\n\nafter:\n" . $str;
	//file_put_contents("debug.txt", $debug);
	return $str;
}

if ( ! function_exists('json_decode'))
{
   function json_decode($json, $assoc=FALSE, /*emu_args*/$n=0,$state=0,$waitfor=0) {

      #-- result var
      $val = NULL;
      static $lang_eq = array("true" => TRUE, "false" => FALSE, "null" => NULL);
      static $str_eq = array("n"=>"\012", "r"=>"\015", "\\"=>"\\", '"'=>'"', "f"=>"\f", "b"=>"\b", "t"=>"\t", "/"=>"/");

      #-- flat char-wise parsing
      for (/*n*/; $n<strlen($json); /*n*/) {
         $c = $json[$n];

         #-= in-string
         if ($state==='"') {

            if ($c == '\\') {
               $c = $json[++$n];
               // simple C escapes
               if (isset($str_eq[$c])) {
                  $val .= $str_eq[$c];
               }

               // here we transform \uXXXX Unicode (always 4 nibbles) references to UTF-8
               elseif ($c == "u") {
                  // read just 16bit (therefore value can't be negative)
                  $hex = hexdec( substr($json, $n+1, 4) );
                  $n += 4;
                  // Unicode ranges
                  if ($hex < 0x80) {    // plain ASCII character
                     $val .= chr($hex);
                  }
                  elseif ($hex < 0x800) {   // 110xxxxx 10xxxxxx 
                     $val .= chr(0xC0 + $hex>>6) . chr(0x80 + $hex&63);
                  }
                  elseif ($hex <= 0xFFFF) { // 1110xxxx 10xxxxxx 10xxxxxx 
                     $val .= chr(0xE0 + $hex>>12) . chr(0x80 + ($hex>>6)&63) . chr(0x80 + $hex&63);
                  }
                  // other ranges, like 0x1FFFFF=0xF0, 0x3FFFFFF=0xF8 and 0x7FFFFFFF=0xFC do not apply
               }

               // no escape, just a redundant backslash
               //@COMPAT: we could throw an exception here
               else {
                  $val .= "\\" . $c;
               }
            }

            // end of string
            elseif ($c == '"') {
               $state = 0;
            }

            // yeeha! a single character found!!!!1!
            else/*if (ord($c) >= 32)*/ { //@COMPAT: specialchars check - but native json doesn't do it?
               $val .= $c;
            }
         }

         #-> end of sub-call (array/object)
         elseif ($waitfor && (strpos($waitfor, $c) !== false)) {
            return array($val, $n);  // return current value and state
         }
         
         #-= in-array
         elseif ($state===']') {
            list($v, $n) = json_decode($json, 0, $n, 0, ",]");
            $val[] = $v;
            if ($json[$n] == "]") { return array($val, $n); }
         }

         #-= in-object
         elseif ($state==='}') {
            list($i, $n) = json_decode($json, 0, $n, 0, ":");   // this allowed non-string indicies
            list($v, $n) = json_decode($json, 0, $n+1, 0, ",}");
            $val[$i] = $v;
            if ($json[$n] == "}") { return array($val, $n); }
         }

         #-- looking for next item (0)
         else {
         
            #-> whitespace
            if (preg_match("/\s/", $c)) {
               // skip
            }

            #-> string begin
            elseif ($c == '"') {
               $state = '"';
            }

            #-> object
            elseif ($c == "{") {
               list($val, $n) = json_decode($json, $assoc, $n+1, '}', "}");
//               if ($val && $n && !$assoc) {
               if ($val && $n && $assoc === FALSE) {
                  $obj = new stdClass();
                  foreach ($val as $i=>$v) {
                     $obj->{$i} = $v;
                  }
                  $val = $obj;
                  unset($obj);
               }
            }
            #-> array
            elseif ($c == "[") {
               list($val, $n) = json_decode($json, $assoc, $n+1, ']', "]");
            }

            #-> comment
            elseif (($c == "/") && ($json[$n+1]=="*")) {
               // just find end, skip over
               ($n = strpos($json, "*/", $n+1)) or ($n = strlen($json));
            }

            #-> numbers
            elseif (preg_match("#^(-?\d+(?:\.\d+)?)(?:[eE]([-+]?\d+))?#", substr($json, $n), $uu)) {
               $val = $uu[1];
               $n += strlen($uu[0]) - 1;
               if (strpos($val, ".")) {  // float
                  $val = (float)$val;
               }
               elseif ($val[0] == "0") {  // oct
                  $val = octdec($val);
               }
               else {
                  $val = (int)$val;
               }
               // exponent?
               if (isset($uu[2])) {
                  $val *= pow(10, (int)$uu[2]);
               }
            }

            #-> boolean or null
            elseif (preg_match("#^(true|false|null)\b#", substr($json, $n), $uu)) {
               $val = $lang_eq[$uu[1]];
               $n += strlen($uu[1]) - 1;
            }

            #-- parsing error
            else {
               // PHPs native json_decode() breaks here usually and QUIETLY
              trigger_error("json_decode: error parsing '$c' at position $n", E_USER_WARNING);
               return $waitfor ? array(NULL, 1<<30) : NULL;
            }

         }//state
         
         #-- next char
         if ($n === NULL) { return NULL; }
         $n++;
      }//for

      #-- final result
      return ($val);
   }
}

if (!function_exists("gzopen") && function_exists("gzopen64")) {
	function gzopen($file, $mode) {
		return gzopen64($file, $mode);
	}
}

function set_onetime_token()
{
	$seed = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"#$%&()-=^~';
	$length = 32;
	$token = sha1(md5(substr(str_shuffle($seed), 0, $length)));
	
	$_SESSION['token'] = $token;
}
function get_onetime_token()
{
	if ( ! isset($_SESSION['token'])) set_onetime_token();
	return $_SESSION['token'];
}
function clear_onetime_token()
{
	if (isset($_SESSION['token'])) unset($_SESSION['token']);
}


if (!function_exists('str_getcsv'))
{
	function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\")
	{
		$fiveMBs = 5 * 1024 * 1024;
		$fp = fopen("php://temp/maxmemory:$fiveMBs", 'r+');
		fputs($fp, $input);
		rewind($fp);
		
		$data = fgetcsv($fp, 1000, $delimiter, $enclosure); //  $escape only got added in 5.3.0

		fclose($fp);
		return $data;
	}
}

function create_password($length = 8)
{
	$pwd_strings =	array(
						"sletter" => range('a', 'z'),
						"cletter" => range('A', 'Z'),
						"number"  => range('0', '9'),
						"symbol"  => array_merge(range('!', '/'), range(':', '?'), range('{', '~'))
					);
 
    $pwd = array();
 
	while (count($pwd) < $length)
	{
		// 4種類必ず入れる
		if (count($pwd) < 4)
		{
			$key = key($pwd_strings);
			next($pwd_strings);
		}
		else
		{
			// 後はランダムに取得
			$key = array_rand($pwd_strings);
		}
		$pwd[] = $pwd_strings[$key][array_rand($pwd_strings[$key])];
	}
	
	// 生成したパスワードの順番をランダムに並び替え
	shuffle($pwd);
	
	return implode($pwd);
}


function get_admin_tools($page)
{
	global $script, $_LINK;
	global $username;
	
	$tools = array(
		'applyskinlink' => array(
			'name'   => __('確定'),
			'link'   => $_LINK['apply_preview_skin'],
			'style'  => 'margin-right:5px;',
			'class'  => '',
			'button' => 'btn btn-default haik-btn-primary navbar-btn',
			'visible'=> TRUE,
			'right'  => TRUE,
			'sub'    => array()
		),
		'changeskinlink' => array(
			'name'   => __('変更'),
			'link'   => '#orgm_designer',
			'style'  => 'margin-right:5px;',
			'class'  => '',
			'button' => 'btn btn-default haik-btn-info navbar-btn',
			'modal'  => TRUE,
			'visible'=> TRUE,
			'right'  => TRUE,
			'sub'    => array(
			)
		),
		'previewcancellink' => array(
			'name'   => __('キャンセル'),
			'link'   => $_LINK['cancel_preview_skin'],
			'style'  => '',
			'class'  => '',
			'button' => 'btn btn-default navbar-btn',
			'visible'=> TRUE,
			'right'  => TRUE,
			'sub'    => array(
			)
		),
		'editlink' => array(
			'name'   => __('編集'),
			'link'   => $_LINK['edit'],
			'style'  => '',
			'class'  => '',
			'button' => 'btn btn-default haik-btn-default navbar-btn',
			'visible'=> TRUE,
			'right'  => TRUE,
			'sub'    => array(),
		),
		'finishlink' => array(
			'name'   => __('完了'),
			'link'   => $script,
			'style'  => 'margin-right:5px;',
			'class'  => '',
			'button' => 'btn btn-default haik-btn-primary navbar-btn',
			'visible'=> TRUE,
			'right'  => TRUE,
			'sub'    => array()
		),
	);
	return $tools;
	
}

function get_admin_tools_html($tools)
{
	global $vars;

	$tools_str = '';
	$tools_right_str = '';
	$btns_right_str = '';

	foreach ($tools as $lv1key => $lv1)
	{
		$str = '';

		if ($lv1['visible'])
		{
			$btn_class = $tooltip_attr = $data_modal = '';
			if (isset($lv1['tooltip']) && $lv1['tooltip'])
			{
				$tooltip_attr .= ' data-tooltip';
				foreach ($lv1['tooltip'] as $name => $val)
				{
					$tooltip_attr .= ' data-' . h($name) . '="'. h($val) .'"';
				}
			}
			if (isset($lv1['button']) && $lv1['button'])
			{
				$btn_class = $lv1['button'];
			}
			
			if (isset($lv1['modal']))
			{
				$data_modal = ' data-toggle="modal"';
			}
			
			if ( ! isset($lv1['sub']))
			{
				$target = ($lv1key == 'helplink') ? ' target="help"' : '';
				$str .= '
					<li style="'.h($lv1['style']).'" class="'.h($lv1['class']).'" data-category="'.h($lv1key).'"'. $tooltip_attr .'>
						<a href="'.h($lv1['link']).'"'.$target.' id="'.h($lv1key).'" class="'.$btn_class.'"'.$data_modal.'>'.$lv1['name'].'</a>';
			}
			else
			{
				if ($btn_class)
				{
					$atag = '<div class="btn-group" style="'.h($lv1['style']).'" ><a href="'. $lv1['link'].'" class="'. $btn_class .'" id="'.$lv1key.'"'.$data_modal.'>'. $lv1['name'].'</a>';
					
					if (count($lv1['sub']) > 0)
					{
						$atag .= '<button class="'. $btn_class .' dropdown-toggle" data-toggle="dropdown" type="button"><span class="caret"></span></button>';
					}
				}
				else
				{
					$dropmark = '<b class="caret"></b>';
					$atag = '<a href="#" class="dropdown-toggle btn-link" data-toggle="dropdown" id="'. $lv1key.'">'.$lv1['name'].$dropmark.'</a>';
				}
				

				$str .= '
					<li class="dropdown '.h($lv1['class']).'" data-category="'.h($lv1key).'">' . $atag;
					
			}
		}
		else
		{
			// invisible
			$str .= '<li class="disabled"><a href="#">'.$lv1['name'].'</li>';
		}

		// sub menu
		if (isset($lv1['sub']) && count($lv1['sub']) > 0)
		{
			$str .= '<ul class="dropdown-menu">';
			foreach ($lv1['sub'] as $lv2key => $lv2)
			{
				// visible
				if ($lv2['visible'])
				{
					$data_target = '';
					$data_modal = '';
					if (isset($lv2['link']))
					{
						$data_target = $lv2['link'];
					}
					if (isset($lv2['modal']))
					{
						$data_modal = ' data-toggle="modal"';
					}
					if ( ! isset($lv2['class']))
					{
						$lv2['class'] = '';
					}
					
					
					if (isset($lv2['search']) && $lv2['search'])
					{
						$atag = '<form><div class="form-group col-sm-12"><input type="hidden" name="cmd" value="search"><div class="input-group input-group-sm"><span class="input-group-addon"><i class="orgm-icon orgm-icon-search"></i></span><input type="text" name="word" value="'. h(isset($vars['word']) ? $vars['word']: '') .'" placeholder="検索" class="form-control" id="orgm_nav_search"></div></div></form>';
					}
					else
					{
						$atarget = (isset($lv2['target']) && $lv2['target']) ? ('target="'.$lv2['target'].'"') : '';
						$atag = '<a href="'.h($lv2['link']).'" id="'.h($lv2key).'" '.$data_modal.' '.$atarget.'>'.$lv2['name'].'</a>';
					}
					
					$str .= '
						<li class="'.h(isset($lv2['class']) ? $lv2['class'] : '').'" style="'.h(isset($lv2['style']) ? $lv2['style'] : '').'">' . $atag;
				}
				// invisible
				else
				{
					$str .= '<li class="nouse"><a class="disabled">'.$lv2['name'].'</a>';
				}
				$str .= '</li>';
			}
			$str .= '</ul>'; // sub menu end
		}
		if ($btn_class) $str .= '</div>';

		$str .= '</li>';

		if (isset($lv1['right']) && $lv1['right'] === TRUE)
		{
			$tools_right_str .= $str;
		}
		else
		{
			$tools_str .= $str;
		}
	}

	if (trim($tools_str))
		$tools_str = '<ul class="nav navbar-nav admin-nav-toolbar">'.$tools_str.'</ul>';
	else
		$tools_str = '';
	
	if (strlen($tools_right_str) > 0)
	{
		$tools_right_str = '<ul class="nav navbar-nav admin-nav-toolbar pull-right">'.$tools_right_str.'</ul>';
	}

	return $tools_str . $tools_right_str;
}

function get_admin_slider_data()
{
	global $script, $_LINK;
	
	$data = array(
		'search' => array(
			'search_link' => array(
				'search' => TRUE,
				'name'   => __('検索'),
				'link'   => $_LINK['search'], 
				'visible'=> TRUE
			),
		),
		'edit' => array(
			'SiteNavigator_link' => array(
				'name'   => __('ナビ編集'), 
				'link'   => $_LINK['edit_nav'], 
				'visible'=> TRUE
			),
			'MenuBar_link' => array(
				'name'   => __('メニュー編集'),
				'link'   => $_LINK['edit_menu'],
				'visible'=> TRUE
			),
			'MenuBar2_link' => array(
				'name'   => __('メニュー2編集'),
				'link'   => $_LINK['edit_menu2'],
				'visible'=> TRUE
			),
			'SiteFooter_link' => array(
				'name'   => __('フッター編集'),
				'link'   => $_LINK['edit_footer'],
				'visible'=> TRUE
			),
		),
		'page' => array(
			'pageinfo_link' => array(
				'name'   => __('ページの詳細設定'),
				'link'   => '#orgm_meta_customizer',
				'modal'  => TRUE,
				'visible'=> TRUE
			),
			'new_link' => array(
				'name'   => __('ページの追加'),
				'link'   => $_LINK['new'],
				'visible'=> TRUE
			),
			'delete_link' => array(
				'name'   => __('ページの削除'),
				'link'   => $_LINK['delete'].'#contents',
				'visible'=> TRUE
			),
		),
		'misc' => array(
			'pagelist_link' => array(
				'name'   => __('ページ一覧'),
				'link'   => $_LINK['filelist'],
				'visible'=> TRUE
			),
			'filer_link' => array(
				'name'   => __('ファイル管理'),
				'link'   => $_LINK['filer'],
				'visible'=> TRUE
			),
		),
		'config' => array(
			'design_link' => array(
				'name'   => __('デザイン変更'),
				'link'   => '#orgm_designer',
				'modal'  => TRUE,
				'visible'=> TRUE
			),
			'config_link' => array(
				'name'   => __('設定'),
				'link'   => $_LINK['config'],
				'visible'=> TRUE,
			),
		),
		'haik' => array(
			'help_link' => array(
				'name'   => __('ヘルプ'),
				'link'   => $_LINK['help_site'],
				'visible'=> TRUE,
				'target' => 'help',
			),
			'logout_link'   => array(
				'name'   => __('ログアウト'),
				'link'   => $_LINK['logout'],
				'visible'=> TRUE
			),
		),
	);

	return $data;

}

function get_admin_slider_html($data)
{
	ob_start();
	include(LIB_DIR . 'tmpl/admin_slider.html');
	$html = ob_get_clean();

	return $html;
}


function get_qhm_toolbuttons()
{

	$buttons = array(
		'addnav',
		'header',
		'strong',
		array(
			'name'=>'show',
			'children' => array('show2', 'showdummy', 'file')
		),
		'link',
		'br',
		'recentPlugins',
		'allPlugin',
	);
	return $buttons;
}

function get_plugin_list()
{
	$plugins = array(
		array(
			'name' => 'テキスト',
			'plugins' => array(
				'deco',
				'ul',
				'ol',
				'align',
			)
		),
		array(
			'name' => 'レイアウト',
			'plugins' => array(
				'eyecatch',
				'hr',
				'cols2',
				'cols3',
				'cols4',
				'box',
				'section',
			)
		),
		array(
			'name' => '挿入',
			'plugins' => array(
				'contents',
				'table',
				'gmap',
				'slide',
				'video',
				'jplayer',
				'download',
				'file',
				'html',
			),
		),
		array(
			'name' => 'コンタクト',
			'plugins' => array(
				'form',
				'share_buttons',
				'fb_likebox',
				'fb_comments',
				'comment',
			)
		),
	);

	return $plugins;
}


function get_bs_style($color, $type = 'btn')
{
	$class = '';
	$color = strtolower($color);

	$type = strtolower($type);
	$type = ($type == 'button') ? 'btn' : $type;

	switch ($color)
	{
		case 'primary':
		case 'info':
		case 'success':
		case 'danger':
		case 'warning':
			$class = $type . '-' .$color;
			break;
		case 'blue':
		case 'skyblue':
			$class = $type . (($type == 'btn' && $color == 'blue') ? '-primary' : '-info');
			break;
		
		case 'green':
			$class = $type . '-success';
			break;

		case 'red':
		case 'error':
			if ($type == 'btn' OR $type == 'progress' OR $type == 'alert')
			{
				$class = $type . '-danger';
			}
			else if ($type == 'label' OR $type == 'badge')
			{
				$class = $type . '-important';
			}
			break;

		case 'orange':
		case 'yellow':
			if ($type != 'alert')
			{
				$class = $type . '-warning';
			}
			break;

		case 'link':
			if ($type == 'btn')
			{
				$class = $type . '-link';
			}
			break;
			
		case 'theme':
			$class = $type . '-theme';
			break;
			
		case 'default':
		case 'normal':
			$class = $type . '-default';
			break;
		default:
	}
	
	
	$class = ($class != '') ? ($type . ' ' . $class) : $type;
	
	return $class;
}

function manual_link($pagename, $hash = '', $tag = TRUE)
{
	if ($tag === TRUE)
	{
		$tag_format = '<a href="%s" target="help"><span class="glyphicon glyphicon-question-sign"></span></a>';
	}
	else if ($tag)
	{
		$tag_format = $tag;
	}
	
	$url = APP_MANUAL_SITE . 'index.php?' . rawurlencode($pagename) . ($hash ? '#' . $hash : '');
	
	return sprintf($tag_format, h($url));

}


/* End of file func.php */
/* Location: /haik-contents/lib/func.php */