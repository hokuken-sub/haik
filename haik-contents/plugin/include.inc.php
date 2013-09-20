<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: include.inc.php,v 1.21 2004/12/30 13:26:43 henoheno Exp $
//
// Include-once plugin

//--------
//	| PageA
//	|
//	| // #include(PageB)
//	---------
//		| PageB
//		|
//		| // #include(PageC)
//		---------
//			| PageC
//			|
//		--------- // PageC end
//		|
//		| // #include(PageD)
//		---------
//			| PageD
//			|
//		--------- // PageD end
//		|
//	--------- // PageB end
//	|
//	| #include(): Included already: PageC
//	|
//	| // #include(PageE)
//	---------
//		| PageE
//		|
//	--------- // PageE end
//	|
//	| #include(): Limit exceeded: PageF
//	| // When PLUGIN_INCLUDE_MAX == 4
//	|
//	|
//-------- // PageA end

// ----

// Default value of 'title|notitle' option
define('PLUGIN_INCLUDE_WITH_TITLE', FALSE);	// Default: TRUE(title)

// Max pages allowed to be included at a time
define('PLUGIN_INCLUDE_MAX', 10);

function plugin_include_convert()
{
	global $script, $vars, $get, $post, $menubar;
	static $included = array();
	static $count = 1;
	$qm = get_qm();
	$qt = get_qt();

	if (func_num_args() == 0) return $qm->m['plg_include']['err_usage']. "\n";;

	// $menubar will already be shown via menu plugin
	if (! isset($included[$menubar])) $included[$menubar] = TRUE;

	// Loop yourself
	$root = isset($vars['page']) ? $vars['page'] : '';
	$included[$root] = TRUE;

	// Get arguments
	$args = func_get_args();
	// strip_bracket() is not necessary but compatible
	$page = isset($args[0]) ? get_fullname(strip_bracket(array_shift($args)), $root) : '';
	
	//キャッシュのために、追加
	if(!in_array($page, $qt->get_rel_pages()))
		$qt->set_rel_page($page);
	
	$with_title = PLUGIN_INCLUDE_WITH_TITLE;
	if (isset($args[0])) {
		switch(strtolower(array_shift($args))) {
		case 'title'  : $with_title = TRUE;  break;
		case 'notitle': $with_title = FALSE; break;
		}
	}

	$s_page = h($page);
	$r_page = rawurlencode($page);
	$link = '<a href="' . $script . '?' . $r_page . '">' . $s_page . '</a>'; // Read link

	// I'm stuffed
	if (isset($included[$page])) {
		return $qm->replace('plg_include.err_already_include', $link) . "\n";
	} if (! is_page($page)) {
		return $qm->replace('plg_include.err_no_page', $s_page) . "\n";
	} if ($count > PLUGIN_INCLUDE_MAX) {
		return $qm->replace('plg_include.err_limit', $link) . "\n";
	} else {
		++$count;
	}

	// One page, only one time, at a time
	$included[$page] = TRUE;

	// Include A page, that probably includes another pages
	$get['page'] = $post['page'] = $vars['page'] = $page;
	if (check_readable($page, false, false, true)) {
		$body = convert_html(get_source($page));
	} else {
		$body = str_replace('$1', $page, $qm->m['plg_include']['err_restrict']);
	}
	$get['page'] = $post['page'] = $vars['page'] = $root;

	// Put a title-with-edit-link, before including document
	if ($with_title) {
		$link = '<a href="' . h($script . '?' . $r_page) .	'">' . $s_page . '</a>';
		if ($page == $menubar) {
			$body = '<span align="center"><h5 class="side_label">' .
				$link . '</h5></span><small>' . $body . '</small>';
		} else {
			$body = '<h2>' . $link . '</h2>' . "\n" . $body . "\n";
		}
	}

	//編集状態の場合、hover でメッセージを表示
	if (check_editable($vars['page'], false, false))
	{
		$goto_page = '読込ページへ移動';
		$goto_url  = $script . '?' . $r_page;
		$html  = '<div class="orgm-include-wrapper">';
		$html .= '<a href="'. h($goto_url) .'" class="orgm-include">'. h($goto_page). '</a>';
		$html .= $body . '</div>';
		return $html;
	}

	return $body;
}

/* End of file include.inc.php */
/* Location: /app/haik-contents/plugin/include.inc.php */