<?php

// Setup autoloading
require './vendor/autoload.php';

// Setup Pukiwiki-haik
define('APP_HOME', './');
define('DATA_HOME',	'haik-contents/');
define('DATA_DIR',	'haik-contents/wiki/');
define('CONFIG_DIR', DATA_HOME. 'config/');
define('LIB_DIR',	 DATA_HOME. 'lib/');
define('PLUGIN_DIR',	 DATA_HOME. 'plugin/');

require(LIB_DIR . 'func.php');
require(LIB_DIR . 'file.php');
require(LIB_DIR . 'plugin.php');
require(LIB_DIR . 'html.php');

