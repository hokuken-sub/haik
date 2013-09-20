<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: index.php,v 1.9 2006/05/13 07:39:49 henoheno Exp $
// Copyright (C) 2001-2006 PukiWiki Developers Team
// License: GPL v2 or (at your option) any later version

// Error reporting
//error_reporting(0); // Nothing
error_reporting(E_ERROR | E_PARSE); // Avoid E_WARNING, E_NOTICE, etc
//error_reporting(E_ALL); // Debug purpose
ini_set('display_errors', 'Off');

// Special
//define('PKWK_READONLY',  1);
//define('PKWK_SAFE_MODE', 1);
//define('PKWK_OPTIMISE',  1);
//define('TDIARY_THEME',   'digital_gadgets');

// Directory definition
// (Ended with a slash like '../path/to/pkwk/', or '')
define('DATA_HOME',	'haik-contents/');
define('APP_HOME', dirname(__FILE__) . '/');
define('CONFIG_DIR', DATA_HOME. 'config/');
define('LIB_DIR',	 DATA_HOME. 'lib/');

require(LIB_DIR . 'pukiwiki.php');

/* End of file index.php */
/* Location: /app/index.php */