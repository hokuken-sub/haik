<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: search.inc.php,v 1.13 2005/11/29 18:19:51 teanan Exp $
//
// Search plugin

// Allow search via GET method 'index.php?plugin=search&word=keyword'
// NOTE: Also allows DoS to your site more easily by SPAMbot or worm or ...
define('PLUGIN_SEARCH_DISABLE_GET_ACCESS', 0); // 1, 0

define('PLUGIN_SEARCH_MAX_LENGTH', 80);
define('PLUGIN_SEARCH_MAX_BASE',   16); // #search(1,2,3,...,15,16)

// Show a search box on a page
function plugin_search_convert()
{
	static $done;
	$qm = get_qm();

// search プラグインを何回呼んでもいいようにコメントアウト
//	if (isset($done)) {
//		return $qm->replace('fmt_err_already_called', '#search'). "\n";
//	} else {
		$done = TRUE;
		$args = func_get_args();
		return plugin_search_search_form('', '', $args);
//	}
}

function plugin_search_action()
{
	global $post, $vars;
	$qm = get_qm();

	if (PLUGIN_SEARCH_DISABLE_GET_ACCESS) {
		$s_word = isset($post['word']) ? h($post['word']) : '';
	} else {
		$s_word = isset($vars['word']) ? h($vars['word']) : '';
	}
	if (strlen($s_word) > PLUGIN_SEARCH_MAX_LENGTH) {
		unset($vars['word']);
		die_message($qm->m['plg_search']['err_toolong']);
	}

	$type = isset($vars['type']) ? $vars['type'] : '';
	$base = isset($vars['base']) ? $vars['base'] : '';

	if ($s_word != '') {
		// Search
		$msg  = str_replace('$1', $s_word, $qm->m['plg_search']['title_result']);
		$body = plugin_search2_do_search($vars['word'], $type, FALSE, $base);
	} else {
		// Init
		unset($vars['word']);
		$msg  = $qm->m['plg_search']['title_search'];
		$body = '<br />' . "\n" . $qm->m['plg_search']['note'] . "\n";
	}

	

	// Show search form
	$bases = ($base == '') ? array() : array($base);
	$body .= plugin_search_search_form($s_word, $type, $bases);

	return array('msg'=>$msg, 'body'=>$body);
}

function plugin_search_search_form($s_word = '', $type = '', $bases = array())
{
	global $script;
	$qm = get_qm();

	$width_class = 'col-sm-7';
	foreach ($bases as $base)
	{
		if (is_numeric($base))
		{
			if ($base > 0 && $base < 13)
			{
				$width_class = 'col-sm-'.trim($base);
			}
			else {
				$width_class = 'col-sm-12';
			}
		}		
	}

	return '
<div class="orgm-search">
	<form action="'.$script.'" method="get" class="form-inline">
		<input type="hidden" name="cmd" value="search" />
		<div class="input-group '.$width_class.'">
			<input type="text"  name="word" value="'.$s_word.'" class="form-control" placeholder="'.__("検索ワード").'" />
			<div class="input-group-btn">
				<input class="btn btn-default" type="submit" value="'.__('検索').'" />
			</div>
		</div>
	</form>
</div>
';
}

// 'Search' main function
function plugin_search2_do_search($word, $type = 'AND', $non_format = FALSE, $base = '')
{
	global $script, $whatsnew, $non_list, $search_non_list;
	global $search_auth, $show_passage, $username;
	$qm = get_qm();

	$retval = array();

	$b_type = ($type == 'AND'); // AND:TRUE OR:FALSE
	mb_language('Japanese');
	$word = mb_convert_encoding($word,SOURCE_ENCODING,"UTF-8,EUC-JP,SJIS,ASCII,JIS");
	$word = mb_ereg_replace("　", " ", $word);
	$keys = get_search_words(preg_split('/\s+/', $word, -1, PREG_SPLIT_NO_EMPTY));
	foreach ($keys as $key=>$value)
		$keys[$key] = '/' . $value . '/S';

	$user = is_login() ? $_SESSION['usr'] : '';
	$pages = $user === $username ? get_existpages() : get_readable_pages($user);

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
	// Search for page contents
	global $ignore_plugin, $strip_plugin, $strip_plugin_inline;

	$titles = array();
	$head10s = array();

	foreach (array_keys($pages) as $page) {
		$b_match = FALSE;

		// Search auth for page contents
		if ( ! check_readable($page, false, false, TRUE)) {
			unset($pages[$page]);
			continue;
		}

		$lines = get_source($page, TRUE, TRUE);	
		
		//--- 検索専用のデータの作成、更新 ---
		if ( ! ss_admin_check())
		{
			$srh_fname = CACHE_DIR . encode($page).'_search.txt';

			if ( ! file_exists($srh_fname) ||
				( filemtime($srh_fname) < filemtime(get_filename($page))))
			{
				foreach ($keys as $key)
				{
					foreach($lines as $k => $l)
					{
						if (preg_match($ignore_plugin, $l))
						{	// 省く
							$lines = array();
							break;
						}
						if(preg_match($strip_plugin, $l, $ms))
						{	// 省く
							unset($lines[$k]);
						}

						if (preg_match('/^(\*){1,3}(.*)\[#\w+\]\s?/', $l, $ms))
						{
							$p_heads .=  trim($ms[2]).' ';
							unset($lines[$k]);
						}

					}
					$lines = preg_replace($strip_plugin_inline, '', $lines); // 省く
					$p_body = strip_tags(convert_html($lines));
					
					$p_title = get_page_title();
					$p_title = ($page == $p_title) ?  $p_title : $p_title.' '.$page;

					$p_body = (count($lines) > 0) ? $p_title."\n".$p_heads."\n".$p_body : '';
					file_put_contents($srh_fname, $p_body);
				}
			}
			else
			{
				$fp = fopen($srh_fname, "r");
				flock($fp, LOCK_SH);
				$lines = file($srh_fname);
				flock($fp, LOCK_UN);
				fclose($fp);
				
				$p_title = trim($lines[0]);
				unset($lines[0]);
				
				$p_heads = trim($lines[1]);
				unset($lines[1]);
				
				$p_body = implode('', $lines);
			}

			//////////////////////////////////////////////
			//
			//  検索スタート！
			//
			///////////////////////////////////////////////
			$match_title = 0;
			$match_heads = 0;
			$match_body = 0;

			//--- ページタイトル検索 ---
			$point = 0; $ok = false;
			if ( ! $non_format) {

				foreach ($keys as $key) {
					$b_match = preg_match($key, $p_title);				
					if( ! $b_match){
						$ok = false; break;
					}
					else{
						$ok = true;	$point += 15;
					}
				}
				if($ok){ $match_title = $point; }
			}

			//--- ヘッダー検索 ---
			$point = 0; $ok = false;
			foreach ($keys as $key) {
				$b_match = preg_match_all($key, $p_title, $ms);
				if(!$b_match){
					$ok = false; break;
				}
				else{
					$ok = true;	$point += 10;
				}
			}
			if($ok){ $match_heads = $point; }

			//--- コンテンツ検索 ---
			foreach ($keys as $key) {
				$b_match = preg_match_all($key, $p_body, $ms);
				if(!$b_match){
					$ok = false; break;
				}
				else{
					$ok = true;	$point += count($ms[0]);
				}
			}
			if($ok){ $match_body = $point; }

			//検索結果
			$total = $match_title + $match_heads + $match_body;
			
			
			if ($total == 0)
			{
				unset($pages[$page]); // Miss
			}
			else
			{
				$pages[$page] = $total;
				$titles[$page] = $p_title;
				$head10s[$page] = mb_substr($p_body, 0 , 60*3);
			}
		}
		else
		{
			//管理者の場合
			$p_title = get_page_title($page);
			$p_search_title = ($page == $p_title) ?  $p_title : $p_title.' '.$page;

			$b_match = count($keys);
			foreach ($keys as $key) {
				$b_match = ($b_match && preg_match($key, $lines));
				if ( ! $b_match)
				{
					$b_match = preg_match($key, $p_search_title);				
				}
			}
			if ( ! $b_match)
			{
				unset($pages[$page]);
				continue;
			}
			$titles[$page] = $p_title;
		}

	}
	if ($non_format) return array_keys($pages);


	$r_word = rawurlencode($word);
	$s_word = h($word);
	if (empty($pages))
	{
		return str_replace('$1', $s_word, __('$1 を含むページは見つかりませんでした。'));
	}

	arsort($pages);

	$retval = '<div class="container"><ul class="nav nav-list">' . "\n";
	foreach ($pages as $page=>$v) {
		$s_page  = h($titles[$page]);
		
		$r_page  = rawurlencode($page);
		$passage = $show_passage ? ' ' . get_passage(get_filetime($page)) : '';
		
		$tmp_li = ' <li><a href="' . $script . '?cmd=read&amp;page=' .
			$r_page . '&amp;word=' . $r_word . '" style="font-weight:bold;">' . $s_page .
			'</a><span class="muted">'.$head10s[$page].'</span></li>' . "\n";
		
		$retval .= $tmp_li;
	}
	$retval .= '</ul><p>' . "\n";
	$retval .= str_replace('$1', $s_word, str_replace('$2', count($pages),
		str_replace('$3', $count, $b_type ? __('$1 のすべてを含むページは <strong>$3</strong> ページ中、 <strong>$2</strong> ページ見つかりました。') : __('$1 のいずれかを含むページは <strong>$3</strong> ページ中、 <strong>$2</strong> ページ見つかりました。'))));
	$retval .= '</p></div>';

	return $retval;

}
?>