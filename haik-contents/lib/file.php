<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: file.php,v 1.72 2006/06/11 14:42:09 henoheno Exp $
// Copyright (C)
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// File related functions

// RecentChanges
define('PKWK_MAXSHOW_ALLOWANCE', 10);
define('PKWK_MAXSHOW_CACHE', 'recent.dat');
define('ORGM_UPDATE_CACHE', 'update.dat');
define('QHM_TINYURL_TABLE', 'tinyurl.dat');
define('QHM_LASTMOD', 'qhm_lastmod.dat');

define('QBLOG_MAX_RECENT_COMMENTS', 50);

// AutoLink
define('PKWK_AUTOLINK_REGEX_CACHE', 'autolink.dat');

// Get source(wiki text) data of the page
function get_source($page = NULL, $lock = TRUE, $join = FALSE)
{
	$result = $join ? '' : array();

	if (is_page($page)) {
		$path  = get_filename($page);

		if ($lock) {
			$fp = @fopen($path, 'r');
			if ($fp == FALSE) return $result;
			flock($fp, LOCK_SH);
		}

		if ($join) {
			// Returns a value
			$buff_length = filesize($path);
			$buff_length = $buff_length ? $buff_length : 2048;
			$result = str_replace("\r", '', fread($fp, $buff_length));
		} else {
			// Returns an array
			// Removing line-feeds: Because file() doesn't remove them.
			$result = str_replace("\r", '', file($path));
		}

		if ($lock) {
			flock($fp, LOCK_UN);
			@fclose($fp);
		}
	}

	return $result;
}

// Get last-modified filetime of the page
function get_filetime($page)
{
	return is_page($page) ? filemtime(get_filename($page)) - LOCALZONE : 0;
}

// Get physical file name of the page
function get_filename($page)
{
	return DATA_DIR . encode($page) . '.md';
}

// Put a data(wiki text) into a physical file(diff, backup, text)
function page_write($page, $postdata, $notimestamp = FALSE)
{
	if (PKWK_READONLY) return; // Do nothing

	$postdata = make_str_rules($postdata);

	// Create and write diff
	$oldpostdata = is_page($page) ? join('', get_source($page)) : '';
	$diffdata    = do_diff($oldpostdata, $postdata);
	file_write(DIFF_DIR, $page, $diffdata);

	// Create backup
	make_backup($page, $postdata == ''); // Is $postdata null?

	// Create wiki text
	file_write(DATA_DIR, $page, $postdata, $notimestamp);

	links_update($page);
}

// Modify original text with user-defined / system-defined rules
function make_str_rules($source)
{
	global $str_rules, $fixed_heading_anchor;

	$lines = explode("\n", $source);
	$count = count($lines);

	$modify    = TRUE;
	$multiline = 0;
	$matches   = array();
	for ($i = 0; $i < $count; $i++) {
		$line = & $lines[$i]; // Modify directly

		// Ignore null string and preformatted texts
		if ($line == '' || $line{0} == ' ' || $line{0} == "\t") continue;

		// Modify this line?
		if ($modify) {
			if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			    $multiline == 0 &&
			    preg_match('/^#[^{]*(\{\{+)\s*$/', $line, $matches)) {
			    	// Multiline convert plugin start
				$modify    = FALSE;
				$multiline = strlen($matches[1]); // Set specific number
			}
		} else {
			if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			    $multiline != 0 &&
			    preg_match('/^\}{' . $multiline . '}\s*$/', $line)) {
			    	// Multiline convert plugin end
				$modify    = TRUE;
				$multiline = 0;
			}
		}
		if ($modify === FALSE) continue;

		// Replace with $str_rules
		foreach ($str_rules as $pattern => $replacement)
			$line = preg_replace('/' . $pattern . '/', $replacement, $line);
		
		// Adding fixed anchor into headings
		if ($fixed_heading_anchor &&
		    preg_match('/^(\*{1,3}.*?)(?:\[#([A-Za-z][\w-]*)\]\s*)?$/', $line, $matches) &&
		    (! isset($matches[2]) || $matches[2] == '')) {
			// Generate unique id
			$anchor = generate_fixed_heading_anchor_id($matches[1]);
			$line = rtrim($matches[1]) . ' [#' . $anchor . ']';
		}
	}

	// Multiline part has no stopper
	if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
	    $modify === FALSE && $multiline != 0)
		$lines[] = str_repeat('}', $multiline);

	return implode("\n", $lines);
}

// Generate ID
function generate_fixed_heading_anchor_id($seed)
{
	// A random alphabetic letter + 7 letters of random strings from md()
	return chr(mt_rand(ord('a'), ord('z'))) .
		substr(md5(uniqid(substr($seed, 0, 100), TRUE)),
		mt_rand(0, 24), 7);
}

// Read top N lines as an array
// (Use PHP file() function if you want to get ALL lines)
function file_head($file, $count = 1, $lock = TRUE, $buffer = 8192)
{
	$array = array();

	$fp = @fopen($file, 'r');
	if ($fp === FALSE) return FALSE;
	set_file_buffer($fp, 0);
	if ($lock) flock($fp, LOCK_SH);
	rewind($fp);
	$index = 0;
	while (! feof($fp)) {
		$line = fgets($fp, $buffer);
		if ($line != FALSE) $array[] = $line;
		if (++$index >= $count) break;
	}
	if ($lock) flock($fp, LOCK_UN);
	if (! fclose($fp)) return FALSE;

	return $array;
}

// Read top N lines as an array
// (Use PHP file() function if you want to get ALL lines)
function file_slice($file, $offset = 0, $count = 1, $lock = TRUE, $buffer = 8192)
{
	$array = array();

	$fp = @fopen($file, 'r');
	if ($fp === FALSE) return FALSE;
	set_file_buffer($fp, 0);
	if ($lock) flock($fp, LOCK_SH);
	rewind($fp);
	$index = 0;
	while (! feof($fp)) {
		$line = fgets($fp, $buffer);

		//index がoffset 未満の時はcontinue
		if ($index < $offset)		
		{
			$index++;
			continue;
		}
		else
		{
			//index がoffset 以上の時は配列に格納
			if ($line)
				$array[] = $line;
		}

		//index+1 がcount+offset 以上の時は、break
		if (++$index >= $count+$offset) break;
	}
	if ($lock) flock($fp, LOCK_UN);
	if (! fclose($fp)) return FALSE;

	return $array;
}


// Output to a file
function file_write($dir, $page, $str, $notimestamp = FALSE)
{
	global $notify, $notify_diff_only, $notify_subject;
	global $whatsdeleted, $maxshow_deleted;
	global $qblog_page_re;
	$qm = get_qm();

	if (PKWK_READONLY) return; // Do nothing
	if ($dir != DATA_DIR && $dir != DIFF_DIR) die($qm->m['file']['err_invalid_dir']);
  
  $extension = '.txt';
  if ($dir === DATA_DIR)
  {
      $extension = '.md';
  }

	$page = strip_bracket($page);
	$file = $dir . encode($page) . $extension;
	$file_exists = file_exists($file);
	
	app_put_lastmodified();

	// ----
	// Delete?

	if ($dir == DATA_DIR && $str === '') {
		// Page deletion
		if (! $file_exists) return; // Ignore null posting for DATA_DIR

		// Update RecentDeleted (Add the $page)
		add_recent($page, $whatsdeleted, '', $maxshow_deleted);

		//QBlog 記事 であれば、削除処理を呼び出す
		if (preg_match($qblog_page_re, $page))
		{
			qblog_remove_post($page);
		}

		// Remove the page
		unlink($file);

		// Update RecentDeleted, and remove the page from RecentChanges
		lastmodified_add($whatsdeleted, $page);
		orgm_lastmodified_add($whatsdeleted, $page);

		// Clear is_page() cache
		is_page($page, TRUE);

		return;

	} else if ($dir == DIFF_DIR && $str === " \n") {
		return; // Ignore null posting for DIFF_DIR
	}

	// ----
	// File replacement (Edit)

	if (! is_pagename($page))
		die_message(str_replace('$1', h($page),
		            str_replace('$2', 'WikiName', $qm->m['fmt_err_invalidiwn'])));

	$str = rtrim(preg_replace('/' . "\r" . '/', '', $str)) . "\n";
	$timestamp = ($file_exists && $notimestamp) ? filemtime($file) : FALSE;

	$fp = fopen($file, 'a') or die($qm->replace('file.err_not_writable', h(basename($dir)), encode($page)));
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	ftruncate($fp, 0);
	rewind($fp);
	fputs($fp, $str);
	flock($fp, LOCK_UN);
	fclose($fp);

	if ($timestamp) pkwk_touch_file($file, $timestamp);

	// Optional actions
	if ($dir == DATA_DIR) {
		// Update RecentChanges (Add or renew the $page)
		if ($timestamp === FALSE) lastmodified_add($page);
		orgm_lastmodified_add($page);

		add_tinycode($page);

		// Command execution per update
		if (defined('PKWK_UPDATE_EXEC') && PKWK_UPDATE_EXEC)
			system(PKWK_UPDATE_EXEC . ' > /dev/null &');

	} else if ($dir == DIFF_DIR && $notify) {
		if ($notify_diff_only) $str = preg_replace('/^[^-+].*\n/m', '', $str);
		$footer['ACTION'] = 'Page update';
		$footer['PAGE']   = & $page;
		$footer['URI']    = get_script_uri() . '?' . rawurlencode($page);
		$footer['USER_AGENT']  = TRUE;
		$footer['REMOTE_ADDR'] = TRUE;

		if(isset($_SESSION['usr']))
			$str .= "\n\n ". $qm->replace('file.lbl_editor', $_SESSION['usr']). "\n";

		pkwk_mail_notify($notify_subject, $str, $footer) or
			die($qm->m['file']['err_mail_failed']);
	}

	is_page($page, TRUE); // Clear is_page() cache
}

// Update RecentDeleted
function add_recent($page, $recentpage, $subject = '', $limit = 0)
{
	if (PKWK_READONLY || $limit == 0 || $page == '' || $recentpage == '' ||
	    check_non_list($page)) return;
	
	$qm = get_qm();

	// Load
	$lines = $matches = array();
	foreach (get_source($recentpage) as $line)
		if (preg_match('/^-(.+) - (\[\[.+\]\])$/', $line, $matches))
			$lines[$matches[2]] = $line;

	$_page = '[[' . $page . ']]';

	// Remove a report about the same page
	if (isset($lines[$_page])) unset($lines[$_page]);

	// Add
	array_unshift($lines, '-' . format_date(UTIME) . ' - ' . $_page .
		h($subject) . "\n");

	// Get latest $limit reports
	$lines = array_splice($lines, 0, $limit);

	// Update
	$fp = fopen(get_filename($recentpage), 'w') or
		die_message($qm->replace('file.err_not_wraitable', h($recentpage)));
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	rewind($fp);
	fputs($fp, ':::freeze:::'    . "\n");
	fputs($fp, ':::noindex:::'  . "\n");
	fputs($fp, join('', $lines));
	flock($fp, LOCK_UN);
	fclose($fp);

}

function orgm_lastmodified_add($update = '', $remove = '')
{
	global $maxshow;
	$qm = get_qm();

	if ($update == '' && $remove == '')
		return; // No need

	$file = CACHE_DIR . ORGM_UPDATE_CACHE;

	// Open
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message($qm->replace('fmt_err_open_cachedir', PKWK_MAXSHOW_CACHE));
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);

	// Read (keep the order of the lines)
	$recent_pages = $matches = array();
	foreach(file_head($file, $maxshow + PKWK_MAXSHOW_ALLOWANCE, FALSE) as $line)
		if (preg_match('/^([0-9]+)\t(.+)/', $line, $matches))
			$recent_pages[$matches[2]] = $matches[1];

	// Remove if it exists inside
	if (isset($recent_pages[$update])) unset($recent_pages[$update]);
	if (isset($recent_pages[$remove])) unset($recent_pages[$remove]);

	// Add to the top: like array_unshift()
	if ($update != '')
		$recent_pages = array($update => get_filetime($update)) + $recent_pages;

	// Check
	
	$recent_pages = array_slice($recent_pages, 0, $maxshow);
	
	// Write
	ftruncate($fp, 0);
	rewind($fp);
	foreach ($recent_pages as $_page=>$time)
		fputs($fp, $time . "\t" . $_page . "\n");

	flock($fp, LOCK_UN);
	fclose($fp);

	return;

}

// Update PKWK_MAXSHOW_CACHE itself (Add or renew about the $page) (Light)
// Use without $autolink
function lastmodified_add($update = '', $remove = '')
{
	global $maxshow, $whatsnew, $autolink;
	$qm = get_qm();

	// AutoLink implimentation needs everything, for now
	if ($autolink) {
		put_lastmodified(); // Try to (re)create ALL
		return;
	}

	if (($update == '' || check_non_list($update)) && $remove == '')
		return; // No need
	$page_meta = meta_read($update);
	if (isset($page_meta['close']) && ($page_meta['close'] === 'closed' OR
		($page_meta['close'] === 'redirect' && $page_meta['redirect'] !== '')))
	{
		return;
	}

	$file = CACHE_DIR . PKWK_MAXSHOW_CACHE;
	if (! file_exists($file)) {
		put_lastmodified(); // Try to (re)create ALL
		return;
	}

	// Open
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message($qm->replace('fmt_err_open_cachedir', PKWK_MAXSHOW_CACHE));
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);

	// Read (keep the order of the lines)
	$recent_pages = $matches = array();
	foreach(file_head($file, $maxshow + PKWK_MAXSHOW_ALLOWANCE, FALSE) as $line)
		if (preg_match('/^([0-9]+)\t(.+)/', $line, $matches))
			$recent_pages[$matches[2]] = $matches[1];

	// Remove if it exists inside
	if (isset($recent_pages[$update])) unset($recent_pages[$update]);
	if (isset($recent_pages[$remove])) unset($recent_pages[$remove]);

	// Add to the top: like array_unshift()
	if ($update != '')
		$recent_pages = array($update => get_filetime($update)) + $recent_pages;

	// Check
	$abort = count($recent_pages) < $maxshow;

	if (! $abort) {
		// Write
		ftruncate($fp, 0);
		rewind($fp);
		foreach ($recent_pages as $_page=>$time)
			fputs($fp, $time . "\t" . $_page . "\n");
	}

	flock($fp, LOCK_UN);
	fclose($fp);

	if ($abort) {
		put_lastmodified(); // Try to (re)create ALL
		return;
	}



	// ----
	// Update the page 'RecentChanges'

	$recent_pages = array_splice($recent_pages, 0, $maxshow);
	$file = get_filename($whatsnew);

	// Open
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message($qm->replace('file.err_cannot_open', h($whatsnew)));
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);

	// Recreate
	ftruncate($fp, 0);
	rewind($fp);
	foreach ($recent_pages as $_page=>$time)
		fputs($fp, '-' . h(format_date($time)) .
			' - ' . '[[' . h($_page) . ']]' . "\n");
	fputs($fp, ':::freeze:::'    . "\n");
	fputs($fp, ':::noindex:::'  . "\n");

	flock($fp, LOCK_UN);
	fclose($fp);
}

/**
 * Record last modified date for QHM cache func.
 */
function app_put_lastmodified()
{
	file_put_contents(CACHE_DIR . QHM_LASTMOD, date('Y-m-d H:i:s'));
}
// Re-create PKWK_MAXSHOW_CACHE (Heavy)
function put_lastmodified()
{
	global $maxshow, $whatsnew, $autolink;
	$qm = get_qm();

	if (PKWK_READONLY) return; // Do nothing

	// Get WHOLE page list
	$pages = get_existpages();

	// Check ALL filetime
	$recent_pages = array();
	foreach($pages as $page)
		if ($page != $whatsnew && ! check_non_list($page))
			$recent_pages[$page] = get_filetime($page);

	// Sort decending order of last-modification date
	arsort($recent_pages, SORT_NUMERIC);

	// Cut unused lines
	// BugTrack2/179: array_splice() will break integer keys in hashtable
	$count   = $maxshow + PKWK_MAXSHOW_ALLOWANCE;
	$_recent = array();
	foreach($recent_pages as $key=>$value) {
		unset($recent_pages[$key]);
		$_recent[$key] = $value;
		if (--$count < 1) break;
	}
	$recent_pages = & $_recent;

	// Re-create PKWK_MAXSHOW_CACHE
	$file = CACHE_DIR . PKWK_MAXSHOW_CACHE;
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message($qm->replace('fmt_err_open_cachedir', PKWK_MAXSHOW_CACHE));
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	ftruncate($fp, 0);
	rewind($fp);
	foreach ($recent_pages as $page=>$time)
		fputs($fp, $time . "\t" . $page . "\n");
	flock($fp, LOCK_UN);
	fclose($fp);

	// Create RecentChanges
	$file = get_filename($whatsnew);
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message($qm->replace('file.err_cannot_open', h($whatsnew)));
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	ftruncate($fp, 0);
	rewind($fp);
	foreach (array_keys($recent_pages) as $page) {
		$page_meta = meta_read($page);
		if (isset($page_meta['close']) && ($page_meta['close'] === 'closed' OR
			($page_meta['close'] === 'redirect' && $page_meta['redirect'] !== '')))
		{
			continue;
		}

		$time      = $recent_pages[$page];
		$s_lastmod = h(format_date($time));
		$s_page    = h($page);
		$pagetitle  = get_page_title($page);
		fputs($fp, '* ' . $s_lastmod . ' - [' .$pagetitle. '](' . $page . ')' . "\n");
	}
	fputs($fp, ':::freeze:::'    . "\n");
	fputs($fp, ':::noindex:::'  . "\n");
	flock($fp, LOCK_UN);
	fclose($fp);

	// For AutoLink
	if ($autolink) {
		list($pattern, $pattern_a, $forceignorelist) =
			get_autolink_pattern($pages);

		$file = CACHE_DIR . PKWK_AUTOLINK_REGEX_CACHE;
		pkwk_touch_file($file);
		$fp = fopen($file, 'r+') or
			die_message($qm->replace('fmt_err_open_cachedir', PKWK_AUTOLINK_REGEX_CACHE));
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		ftruncate($fp, 0);
		rewind($fp);
		fputs($fp, $pattern   . "\n");
		fputs($fp, $pattern_a . "\n");
		fputs($fp, join("\t", $forceignorelist) . "\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}
}

// Get elapsed date of the page
function get_pg_passage($page, $sw = TRUE)
{
	global $show_passage;
	if (! $show_passage) return '';

	$time = get_filetime($page);
	$pg_passage = ($time != 0) ? get_passage($time) : '';

	return $sw ? '<small>' . $pg_passage . '</small>' : ' ' . $pg_passage;
}

// Last-Modified header
function header_lastmod($page = NULL)
{
	global $lastmod;

	if ($lastmod && is_page($page)) {
		pkwk_headers_sent();
		header('Last-Modified: ' .
			date('D, d M Y H:i:s', get_filetime($page)) . ' GMT');
	}
}

// Get a page list of this wiki
function get_existpages($dir = DATA_DIR, $ext = '.md')
{
	$aryret = array();
	$qm = get_qm();
	
	$pattern = '((?:[0-9A-F]{2})+)';
	if ($ext != '') $ext = preg_quote($ext, '/');
	$pattern = '/^' . $pattern . $ext . '$/';

	$dp = @opendir($dir) or
		die_message($qm->replace('fmt_err_not_found_or_readable', $dir));
	$matches = array();
	while ($file = readdir($dp))
		if (preg_match($pattern, $file, $matches))
			$aryret[$file] = decode($matches[1]);
	closedir($dp);

	return $aryret;
}

/**
 * ユーザーによって異なる閲覧可能ページをチェックし、
 * ユーザー毎の閲覧可能ページリストを作成し、返す。
 * {username: [{filename: pagename}, ...], ...} //一般アクセスは usernameを空文字とする
 *
 * キャッシュ機構あり。
 */
function get_readable_pages($user = '')
{
	$pages = get_existpages();
	$filtered_cache = CACHE_DIR . 'readable_pages.dat';
	
	//main array
	$pages_by_users = array();
	
	//read cache
	if (FALSE && file_exists($filtered_cache))
	{
		if (is_readable($filtered_cache))
		{
			$pages_by_users = unserialize(file_get_contents($filtered_cache));
			
			if (isset($pages_by_users[$user])
				&& filemtime($filtered_cache) > filemtime(CACHE_DIR . QHM_LASTMOD))
			{
				return $pages_by_users[$user];
			}
		}
		else
		{
//			chmod($filtered_cache, 0666);
		}
	}
	else
	{
		touch($filtered_cache);
//		chmod($filtered_cache, 0666);
	}
	
	$filtered = array();
	
	foreach ($pages as $filename => $page)
	{
		$close = meta_read($page, 'close');
		if ($close === 'public'
			OR $close === NULL
			&& check_readable($page, FALSE, FALSE))
			$filtered[$filename] = $page;
	}
	
	$pages_by_users[$user] = $filtered;
	file_put_contents($filtered_cache, serialize($pages_by_users), LOCK_EX);
	

	return $filtered;
}

// Get PageReading(pronounce-annotated) data in an array()
function get_readings()
{
	global $pagereading_enable, $pagereading_kanji2kana_converter;
	global $pagereading_kanji2kana_encoding, $pagereading_chasen_path;
	global $pagereading_kakasi_path, $pagereading_config_page;
	global $pagereading_config_dict;
	$qm = get_qm();

	$pages = get_existpages();

	$readings = array();
	foreach ($pages as $page) 
		$readings[$page] = '';

	$deletedPage = FALSE;
	$matches = array();
	foreach (get_source($pagereading_config_page) as $line) {
		$line = chop($line);
		if(preg_match('/^-\[\[([^]]+)\]\]\s+(.+)$/', $line, $matches)) {
			if(isset($readings[$matches[1]])) {
				// This page is not clear how to be pronounced
				$readings[$matches[1]] = $matches[2];
			} else {
				// This page seems deleted
				$deletedPage = TRUE;
			}
		}
	}

	// If enabled ChaSen/KAKASI execution
	if($pagereading_enable) {

		// Check there's non-clear-pronouncing page
		$unknownPage = FALSE;
		foreach ($readings as $page => $reading) {
			if($reading == '') {
				$unknownPage = TRUE;
				break;
			}
		}

		// Execute ChaSen/KAKASI, and get annotation
		if($unknownPage) {
			switch(strtolower($pagereading_kanji2kana_converter)) {
			case 'chasen':
				if(! file_exists($pagereading_chasen_path))
					die_message($qm->replace('file.err_chasen_notfound', $pagereading_chasen_path));

				$tmpfname = tempnam(realpath(CACHE_DIR), 'PageReading');
				$fp = fopen($tmpfname, 'w') or
					die_message($qm->replace('file.err_cannot_write_tmpfile', $tmpfname));
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;
					fputs($fp, mb_convert_encoding($page . "\n",
						$pagereading_kanji2kana_encoding, SOURCE_ENCODING));
				}
				fclose($fp);

				$chasen = "$pagereading_chasen_path -F %y $tmpfname";
				$fp     = popen($chasen, 'r');
				if($fp === FALSE) {
					unlink($tmpfname);
					die_message($qm->replace('file.err_chasen_failed', $chasen));
				}
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$line = fgets($fp);
					$line = mb_convert_encoding($line, SOURCE_ENCODING,
						$pagereading_kanji2kana_encoding);
					$line = chop($line);
					$readings[$page] = $line;
				}
				pclose($fp);

				unlink($tmpfname) or
					die_message($qm->replace('file.err_cannot_remove_tmpfile', $tmpfname));
				break;

			case 'kakasi':	/*FALLTHROUGH*/
			case 'kakashi':
				if(! file_exists($pagereading_kakasi_path))
					die_message('KAKASI not found: ' . $pagereading_kakasi_path);

				$tmpfname = tempnam(realpath(CACHE_DIR), 'PageReading');
				$fp       = fopen($tmpfname, 'w') or
					die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;
					fputs($fp, mb_convert_encoding($page . "\n",
						$pagereading_kanji2kana_encoding, SOURCE_ENCODING));
				}
				fclose($fp);

				$kakasi = "$pagereading_kakasi_path -kK -HK -JK < $tmpfname";
				$fp     = popen($kakasi, 'r');
				if($fp === FALSE) {
					unlink($tmpfname);
					die_message($qm->replace('file.err_kakasi_failed', $kakasi));
				}

				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$line = fgets($fp);
					$line = mb_convert_encoding($line, SOURCE_ENCODING,
						$pagereading_kanji2kana_encoding);
					$line = chop($line);
					$readings[$page] = $line;
				}
				pclose($fp);

				unlink($tmpfname) or
					die_message($qm->replace('file.err_cannot_remove_tmpfile', $tmpfname));
				break;

			case 'none':
				$patterns = $replacements = $matches = array();
				foreach (get_source($pagereading_config_dict) as $line) {
					$line = chop($line);
					if(preg_match('|^ /([^/]+)/,\s*(.+)$|', $line, $matches)) {
						$patterns[]     = $matches[1];
						$replacements[] = $matches[2];
					}
				}
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$readings[$page] = $page;
					foreach ($patterns as $no => $pattern)
						$readings[$page] = mb_convert_kana(mb_ereg_replace($pattern,
							$replacements[$no], $readings[$page]), 'aKCV');
				}
				break;

			default:
				die_message($qm->replace('file.err_unknown_kk_converter', $pagereading_kanji2kana_converter));
				break;
			}
		}

		if($unknownPage || $deletedPage) {

			asort($readings); // Sort by pronouncing(alphabetical/reading) order
			$body = '';
			foreach ($readings as $page => $reading)
				$body .= '-[[' . $page . ']] ' . $reading . "\n";

			page_write($pagereading_config_page, $body);
		}
	}

	// Pages that are not prounouncing-clear, return pagenames of themselves
	foreach ($pages as $page) {
		if($readings[$page] == '')
			$readings[$page] = $page;
	}

	return $readings;
}

// Get a list of encoded files (must specify a directory and a suffix)
function get_existfiles($dir, $ext)
{
	$qm = get_qm();
	$pattern = '/^(?:[0-9A-F]{2})+' . preg_quote($ext, '/') . '$/';
	$aryret = array();
	$dp = @opendir($dir) or die_message($qm->replace('fmt_err_not_found_or_readable', $dir));
	while ($file = readdir($dp))
		if (preg_match($pattern, $file))
			$aryret[] = $dir . $file;
	closedir($dp);
	return $aryret;
}

// Get a list of related pages of the page
function links_get_related($page)
{
	global $vars, $related;
	static $links = array();

	if (isset($links[$page])) return $links[$page];

	// If possible, merge related pages generated by make_link()
	$links[$page] = ($page == $vars['page']) ? $related : array();

	// Get repated pages from DB
	$links[$page] += links_get_related_db($vars['page']);

	return $links[$page];
}

// _If needed_, re-create the file to change/correct ownership into PHP's
// NOTE: Not works for Windows
function pkwk_chown($filename, $preserve_time = TRUE)
{
	static $php_uid; // PHP's UID
	$qm = get_qm();

	if (! isset($php_uid)) {
		if (extension_loaded('posix')) {
			$php_uid = posix_getuid(); // Unix
		} else {
			$php_uid = 0; // Windows
		}
	}

	// Lock for pkwk_chown()
	$lockfile = CACHE_DIR . 'pkwk_chown.lock';
	$flock = fopen($lockfile, 'a') or
		die($qm->replace('file.err_pkwk_chown_cannot_open', basename(h($lockfile))));
	flock($flock, LOCK_EX) or die($qm->m['file']['err_pkwk_chown_lock_failed']);

	// Check owner
	$stat = stat($filename) or
		die($qm->replace('err_pkwk_chown_stat_failed', basename(h($filename))));
	if ($stat[4] === $php_uid) {
		// NOTE: Windows always here
		$result = TRUE; // Seems the same UID. Nothing to do
	} else {
		$tmp = $filename . '.' . getmypid() . '.tmp';

		// Lock source $filename to avoid file corruption
		// NOTE: Not 'r+'. Don't check write permission here
		$ffile = fopen($filename, 'r') or
			die($qm->replace('file.err_pkwk_chown_cannot_open', basename(h($filename))));

		// Try to chown by re-creating files
		// NOTE:
		//   * touch() before copy() is for 'rw-r--r--' instead of 'rwxr-xr-x' (with umask 022).
		//   * (PHP 4 < PHP 4.2.0) touch() with the third argument is not implemented and retuns NULL and Warn.
		//   * @unlink() before rename() is for Windows but here's for Unix only
		flock($ffile, LOCK_EX) or die($qm->m['file']['err_pkwk_chown_lock_failed']);
		$result = touch($tmp) && copy($filename, $tmp) &&
			($preserve_time ? (touch($tmp, $stat[9], $stat[8]) || touch($tmp, $stat[9])) : TRUE) &&
			rename($tmp, $filename);
		flock($ffile, LOCK_UN) or die($qm->m['file']['err_pkwk_chown_lock_failed']);

		fclose($ffile) or die($qm->m['file']['err_pkwk_chown_fclose_failed']);

		if ($result === FALSE) @unlink($tmp);
	}

	// Unlock for pkwk_chown()
	flock($flock, LOCK_UN) or die($qm->m['file']['err_pkwk_chown_lock_failed']);
	fclose($flock) or die($qm->m['file']['err_pkwk_chown_lock_failed']);

	return $result;
}

// touch() with trying pkwk_chown()
function pkwk_touch_file($filename, $time = FALSE, $atime = FALSE)
{
	$qm = get_qm();
	// Is the owner incorrected and unable to correct?
	if (! file_exists($filename) || pkwk_chown($filename)) {
		if ($time === FALSE) {
			$result = touch($filename);
		} else if ($atime === FALSE) {
			$result = touch($filename, $time);
		} else {
			$result = touch($filename, $time, $atime);
		}
		return $result;
	} else {
		die($qm->replace('file.err_pkwk_touch_invalid_uid', h(basename($filename))));
	}
}

//regist tinyurl table
function add_tinycode($page)
{
	global $whatsnew;
	
	if($page=='')
		return false;
	$qm = get_qm();
	
	$file = CACHE_DIR.QHM_TINYURL_TABLE;
	
	if( !file_exists( $file ) )
	{
		$pages = array_diff(get_existpages(), array($whatsnew));
		$str = '';
		$table = array();
		foreach($pages as $k=>$v)
		{
			$tname = get_random_string(6);
			while( isset($table[$tname]) )  // prob is X/62^6 !!
			{
				$tname = get_random_string(6); 
			}
			
			$table[$tname] = '';
			$str .= $tname.','.$v."\n";
		}
				
		$fp = fopen($file, 'w') or
			die_message($qm->replace('file.err_cannot_open', h($file)));
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		
		fputs($fp, $str);
		
		flock($fp, LOCK_UN);
		fclose($fp);
	}
	else
	{
	
		$table = get_tiny_table();
		
		$r_table = array_flip($table);
		if(isset($r_table[$page]))
			return '';
		
		$tname = get_random_string(6);
		while( isset($table[$tname]) )  // prob is X/62^6 !!
		{
			$tname = get_random_string(6); 
		}
		
		$str = $tname.','.$page."\n";

		$fp = fopen($file, 'a') or
			die_message($qm->replace('file.err_cannot_open', h($file)));
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		
		fputs($fp, $str);
		
		flock($fp, LOCK_UN);
		fclose($fp);

	}
}

function del_tinycode($page)
{
	$qm = get_qm();
	
	$table = get_tiny_table(false);
	unset($table[$page]);
	
	$str = '';
	foreach($table as $key=>$val)
	{
		$str .= $val.','.$key."\n";
	}

	$file = CACHE_DIR.QHM_TINYURL_TABLE;
	$fp = fopen($file, 'w') or
		die_message($qm->replace('file.err_cannot_open', h($file)));
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	
	fputs($fp, $str);
	
	flock($fp, LOCK_UN);
	fclose($fp);
}

function get_tiny_table($key_is_code=true)
{
	$file = CACHE_DIR.QHM_TINYURL_TABLE;
	
	$table = array();

	if (file_exists($file))
	{
		$lines = explode("\n",file_get_contents($file));
		foreach($lines as $line)
		{
			if( trim($line) != '')
			{
				$arr = explode(',', $line);
				
				if($key_is_code)
				{
					$table[ trim($arr[0]) ] = trim($arr[1]);
				}
				else
				{
					$table[ trim($arr[1]) ] = trim($arr[0]);			
				}	
			}
		}
	}
	
	return $table;
}

function get_tiny_code($page)
{
	$table = get_tiny_table(false);

	if( isset($table[$page]) )
	{
		return $table[$page];
	}
	else
	{
		return null;
	}
}

function get_tiny_page($code)
{
	$table = get_tiny_table();
	if(isset($table[$code]))
		return $table[$code];
	else
		return '';
}

function get_random_string($length=6)
{
	static $seed = '0123456789abcdefghifklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$max = strlen($seed)-1;
	
	$str = '';
	for($i=0; $i<$length; $i++)
		$str .= substr($seed, rand(0, $max), 1);

	return $str;
}

function chk_script($script){
	
	$file = CACHE_DIR.'qhm_script.txt';
	if(file_exists($file)){
		$past_script = trim(file_get_contents($file));
		if($script == $past_script){
			return true;
		}
	}

	file_put_contents($file, $script);

	$lm_file = CACHE_DIR . QHM_LASTMOD;
	file_put_contents($lm_file, date('Y-m-d H:i:s'));

}

function array_merge_deep($arr1, $arr2)
{
	if ( ! is_array($arr1) OR ! is_array($arr2))
	{
		return $arr2; 
	}
	foreach ($arr2 as $key2 => $val2)
	{
		if (isset($arr1[$key2]))
		{
			$arr1[$key2] = array_merge_deep($arr1[$key2], $val2);
		}
		else
		{
			$arr1[$key2] = $val2;
		}
	}
	return $arr1;
}

/**
* HTTPの実行ユーザーで書き込み可能かチェックする
*/
function local_is_writable()
{
	
	$dirname = '';
	for($i=0; $i<20; $i++) //20回試行する
	{
		for($j=0; $j<10; $j++) //長さ10のランダムな数字のフォルダを作成
		{
			$dirname .= rand(0, 9);
		}
		
		if(! file_exists($dirname) )
		{
			break;
		}
		else
		{
			$dirname = '';
		}
	}
	
	if( $dirname == '')
	{
		die('error: エラーが発生しました。再読み込みをしてください。');
	}
	
	$is_w = FALSE;
	if (mkdir($dirname))
	{
		$fname = $dirname.'/'.$dirname.'.txt';
		if (file_put_contents($fname, 'hoge'))
		{
			unlink($fname);
			$is_w = TRUE;
		}
		
		rmdir($dirname);
	}
	
	return $is_w;
}

function yaml_read($filepath, $key = NULL)
{
	if ( ! file_exists($filepath))
	{
		throw new FileNotFoundException();
	}

  $yaml = new Symfony\Component\Yaml\Yaml();
  $inidata = $yaml->parse($filepath); 

	if ($inidata === FALSE)
	{
		throw new Exception('ファイルの形式が正しくありません：' . $filepath);
	}
	
	if ( ! is_null($key) && is_string($key))
	{
		$keys = explode('.', $key);
		foreach ($keys as $i => $key)
		{
			if (is_array($inidata) && isset($inidata[$key]))
			{
				$inidata = $inidata[$key];
			}
			else
			{
				return NULL;
			}
		}
		return $inidata;
	}
	
	return $inidata;
}



function ini_read($filepath, $key = NULL)
{
	if ( ! file_exists($filepath))
	{
		throw new FileNotFoundException();
	}
	
	include($filepath);
	
	$defined_vars = get_defined_vars();
	
	if (count($defined_vars) <= 2)
	{
		throw new Exception('このファイルには変数が定義されていません：' . $filepath);
	}
	
	$inidata = array_pop($defined_vars);
	
	if ( ! is_null($key) && is_string($key))
	{
		$keys = explode('.', $key);
		foreach ($keys as $i => $key)
		{
			if (is_array($inidata) && isset($inidata[$key]))
			{
				$inidata = $inidata[$key];
			}
			else
			{
				return NULL;
			}
		}
		return $inidata;
	}
	
	return $inidata;
}

function ini_write($filepath, $varname, $one, $two = NULL, $merge = TRUE)
{
	if (file_exists($filepath) && ! is_writable($filepath))
	{
		throw new FileException('ファイルに書き込み権限がありません：' . $filepath);
	}
	try
	{
		$inidata = ini_read($filepath);
	}
	catch (FileNotFoundException $e)
	{
		$inidata = array();
	}
	catch (Exception $e)
	{
		$inidata = array();
	}
	
	if (is_array($one))
	{
		if (is_array($two))
		{
			$data = array_combine($one, $two);
		}
		else
		{
			$data = $one;
		}

		if ($merge)
		{
			$inidata = array_merge_deep($inidata, $data);
		}
		else
		{
			$inidata = $data;
		}
	}
	else
	{
		if ($merge)
		{
			$data = array(
				$one => $two
			);
			$inidata = array_merge_deep($inidata, $data);
		}
		else
		{
			$inidata[$one] = $two;
		}
			
	}
	

	if ($inidata)
	{
		$phpstr = var_export($inidata, TRUE);
		$phpstr = '<?php' . "\n" . '$'. $varname .' = ' . $phpstr . ';';
		$result = file_put_contents($filepath, $phpstr, LOCK_EX);
		if ($result)
		{
//			chmod($filepath, 0666);
		}
		return $result;
	}
	
	return FALSE;
}

/**
 *
 */
function orgm_ini_read($key = NULL)
{
	global $app_ini_path;
	
	try
	{
		$config = ini_read($app_ini_path, $key);
	}
	catch (FileNotFoundException $e)
	{
		if (is_null($key))
			$config = array();
		else
			$config = NULL;
	}
	catch (Exception $e)
	{
		if (is_null($key))
			$config = array();
		else
			$config = NULL;
	}	

	return $config;
	
}


function orgm_ini_write($key, $value = NULL, $merge = TRUE)
{
	global $app_ini_path;
	
	try
	{
		$result = ini_write($app_ini_path, 'config', $key, $value, $merge);
	}
	catch (FileException $e)
	{
		die(sprintf(__('ファイルに書き込み権限がありません。：%s'), $app_ini_path));
	}
	return $result;
	
}



function meta_read($page, $key = NULL)
{
	$meta_file = META_DIR . encode($page) . '.php';
	
	try
	{
		$meta = ini_read($meta_file, $key);
	}
	catch (FileNotFoundException $e)
	{
		if (is_null($key))
			$meta = array();
		else
			$meta = NULL;
	}
	catch (Exception $e)
	{
		if (is_null($key))
			$meta = array();
		else
			$meta = NULL;
	}

	return $meta;
}

function meta_write($page, $key, $value = NULL, $merge = TRUE)
{
	$meta_file = META_DIR . encode($page) . '.php';

	try
	{
		$result = ini_write($meta_file, 'meta', $key, $value, $merge);
	}
	catch (FileException $e)
	{
		var_dump($e);
		die(sprintf(__('ファイルに書き込み権限がありません。：%s'), $meta_file));
	}
	return $result;
}


/**
 * フォーム情報を読み込む
 */
function form_read($id = '')
{
	$form_file = CONFIG_DIR .'form_'.$id.'.php';
	
	try
	{
		$config = ini_read($form_file);
	}
	catch (FileNotFoundException $e)
	{
		$config = NULL;
	}
	catch (Exception $e)
	{
		$config = NULL;
	}

	return $config;
}

/**
 * フォーム情報を読み込む
 */
function form_write($id, $key, $value = NULL, $merge = FALSE)
{
	$form_file = CONFIG_DIR .'form_'.$id.'.php';

	try
	{
		$result = ini_write($form_file, 'config', $key, $value, $merge);
	}
	catch (FileException $e)
	{
		var_dump($e);
		die(sprintf(__('ファイルに書き込み権限がありません。：%s'), $meta_file));
	}
	return $result;
	
}

/**
 * スキン情報を読み込む
 */
function style_config_read_skel($style = '', $key = NULL)
{
	global $style_name;
	
	$style = ($style == '') ? $style_name : $style;
	
	$conf_file = SKEL_DIR . 'theme/' . $style . '/config.php';
	return _style_config_read($conf_file, $key);
}

/**
 * スキン情報を読み込む
 */
function style_config_read($style = '', $key = NULL)
{
	global $style_name;
	
	$style = ($style == '') ? $style_name : $style;

	$yaml_file = SKIN_DIR . $style . '/theme.yml';
	return _style_config_read($yaml_file, $key);

/*	
	$conf_file = SKIN_DIR . $style . '/config.php';
	return _style_config_read($conf_file, $key);
*/
}

/**
 * スキン情報を読み込む
 */
function _style_config_read($conf_file, $key = NULL)
{
	$config = array();
	try
	{
      $config = yaml_read($conf_file);
/* 		$config = ini_read($conf_file, $key); */
	}
	catch (FileNotFoundException $e)
	{
		if (is_null($key))
			$config = array();
		else
			$config = NULL;
	}
	catch (Exception $e)
	{
		if (is_null($key))
			$config = array();
		else
			$config = NULL;
	}	

	return $config;
}


/**
 * キャッシュファイルを保存する
 * 
 * @params string $filename
 * @params mixed $data
 * @params mixed $expiration expiration of cache file; eg: -1 -> infinity, NULL -> +1 day, string time 2012-01-01, timestamp
 */
function cache_write($filename, $data, $expiration = NULL)
{
	
	if ($expiration === NULL)
	{
		$expiration = strtotime('+1 day');
	}
	else if (is_string($expiration))
	{
		$expiration = strtotime($expiration);
	}
	else if ($expiration === -1)
	{
		//infinity
	}
	else if (ctype_digit($expiration))
	{
		//
	}
	
	//timestamp only
	if (ctype_digit($expiration))
	{
		$cache = sprintf("%s\n\n%s", $expiration, serialize($data));
		$filepath = CACHE_DIR . $filename;
		
		$result = file_put_contents($filepath, $cache, LOCK_EX);
		if ($result)
		{
//			chmod($filepath, 0666);
		}
		return $result;
	}
	else
	{
		return FALSE;
	}

}

function cache_read($filename)
{
	$filepath = CACHE_DIR . $filename;

	if (file_exists($filepath))
	{
		$cache = file_get_contents($filepath);
		
		list($expiration, $data) = explode("\n\n", $cache, 2);
		
		//期限切れのキャッシュは読み込まず、消す
		if ($expiration >= 0 && time() > $expiration)
		{
			unlink($filepath);
			return FALSE;
		}
		
		$data = unserialize($data);
		return $data;
		
	}
	
	return FALSE;

}
/* End of file file.php */
/* Location: ./lib/file.php */