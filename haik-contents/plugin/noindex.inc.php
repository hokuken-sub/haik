<?php
/**
 *   haik NoIndex Plugin
 *   -------------------------------------------
 *   /haik-contents/plugin/noindex.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 
 *   modified : 2013/11/28
 *   
 *   Output <meta name="robots" content="NOINDEX,NOFOLLOW">
 *   or prohibit it
 *   
 *   Usage :
 *     #noindex
 *     #noindex(false)
 *   
 */

// $Id: nofollow.inc.php,v 1.1 2005/05/23 14:22:30 henoheno Exp $
// Copyright (C) 2005 PukiWiki Developers Team
// License: The same as PukiWiki
//
// No plugin

// Output contents with "nofollow,noindex" option
function plugin_noindex_convert()
{
    global $vars, $noindex;

    $args = func_get_args()
    array_pop($args);
    
    if (count($args) > 0)
    {
        $noindex = -1;
    }
    else
    {
        $noindex = 1;
    }

    return '';
}

/* End of file noindex.inc.php */
/* Location: /haik-contents/plugin/noindex.inc.php */