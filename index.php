<?php
// Error reporting
//error_reporting(0); // Nothing
error_reporting(E_ERROR | E_PARSE); // Avoid E_WARNING, E_NOTICE, etc
error_reporting(E_ALL); // Debug purpose
ini_set('display_errors', 'On');

// Directory definition
// (Ended with a slash like '../path/to/pkwk/', or '')
define('DATA_HOME',	'haik-contents/');
define('APP_HOME', dirname(__FILE__) . '/');
define('CONFIG_DIR', DATA_HOME. 'config/');
define('LIB_DIR',	 DATA_HOME. 'lib/');

require LIB_DIR . 'pukiwiki.php';
