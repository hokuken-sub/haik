<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: menu.inc.php,v 1.8 2004/11/27 12:23:02 henoheno Exp $
//

// サブメニューを使用する
define('MENU_ENABLE_SUBMENU', FALSE);

// サブメニューの名称
define('MENU_SUBMENUBAR', 'MenuBar');

function plugin_menu_convert()
{
    global $vars, $menubar;
    static $menu = NULL;
    $qm = get_qm();

    $num = func_num_args();
    if ($num > 0)
    {
        // Try to change default 'MenuBar' page name (only)
        if ($menu !== NULL)
        {
        		return $qm->replace('plg_menu.err_already_set', h($menu));
        }

        $args = func_get_args();
        if (! is_page($args[0]))
        {
            return $qm->replace('plg_menu.err_no_page', h($args[0]));
        }
        else
        {
            $menu = $args[0]; // Set
            return '';
        }
    }
    else
    {
        // Output menubar page data
        $page = ($menu === NULL) ? $menubar : $menu;

        if (MENU_ENABLE_SUBMENU)
        {
            $path = explode('/', strip_bracket($vars['page']));
            while(! empty($path))
            {
                $_page = join('/', $path) . '/' . MENU_SUBMENUBAR;
                if (is_page($_page))
                {
                    $page = $_page;
                    break;
                }
                array_pop($path);
            }
        }

        if (! is_page($page))
        {
            return '';
        }
        else if (isset($vars['preview']) && $vars['page'] == $page)
        {
            // Cut fixed anchors
            $menutext = preg_replace('/^(\*{1,3}.*)\[#[A-Za-z][\w-]+\](.*)$/m', '$1$2', $vars['msg']);
            return convert_html($menutext);
        }
        else if ($vars['page'] == $page)
        {
            return '<!-- '. $qm->replace('plg_menu.ntc_already_view', h($page)).' -->';
        }
        else
        {
            // Cut fixed anchors
            $menutext = preg_replace('/^(\*{1,3}.*)\[#[A-Za-z][\w-]+\](.*)$/m', '$1$2', get_source($page));
            return convert_html($menutext);
        }
    }
}

/* End of file menu.inc.php */
/* Location: /haik-contents/plugin/menu.inc.php */