<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: pukiwiki.php,v 1.11 2005/09/11 05:58:33 henoheno Exp $
//
// PukiWiki 1.4.*
//  Copyright (C) 2002-2005 by PukiWiki Developers Team
//  http://pukiwiki.sourceforge.jp/
//
// PukiWiki 1.3.*
//  Copyright (C) 2002-2004 by PukiWiki Developers Team
//  http://pukiwiki.sourceforge.jp/
//
// PukiWiki 1.3 (Base)
//  Copyright (C) 2001-2002 by yu-ji <sng@factage.com>
//  http://factage.com/sng/pukiwiki/
//
// Special thanks
//  YukiWiki by Hiroshi Yuki <hyuki@hyuki.com>
//  http://www.hyuki.com/yukiwiki/
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (! defined('DATA_HOME')) define('DATA_HOME', '');

/////////////////////////////////////////////////
// Include subroutines

if (! defined('LIB_DIR')) define('LIB_DIR', '');

require(APP_HOME . 'vendor/autoload.php');

require(LIB_DIR . 'func.php');
require(LIB_DIR . 'file.php');
require(LIB_DIR . 'plugin.php');
require(LIB_DIR . 'html.php');
require(LIB_DIR . 'backup.php');

require(LIB_DIR . 'markdown_parser.php');
//require(LIB_DIR . 'convert_html.php');
require(LIB_DIR . 'make_link.php');
require(LIB_DIR . 'diff.php');
require(LIB_DIR . 'config.php');
require(LIB_DIR . 'link.php');
require(LIB_DIR . 'auth.php');
require(LIB_DIR . 'proxy.php');
require(LIB_DIR . 'exception.php');
require(LIB_DIR . 'i18n.php');
if (! extension_loaded('mbstring')) {
	require(LIB_DIR . 'mbstring.php');
}

// Defaults
$notify = 0;

$disable_site_auth = 0;

// Load *.ini.php files and init PukiWiki
require(LIB_DIR . 'init.php');

// Load authorization library
require(LIB_DIR .'ss_authform.php');
ss_auth_start();



//strip session_name from $vars['page']
if($vars['page'] === session_name().'='.session_id())
	$vars['page'] = $defaultpage;

// Load optional libraries
require(LIB_DIR . 'mail.php'); // Mail notification

require(PLUGIN_DIR . 'secedit.inc.php');


/////////////////////////////////////////////////
//load QHM Messages
//Load QHM Template
$phpver = intval(phpversion());
require(LIB_DIR. 'qhm_message.php');
require(LIB_DIR. 'qhm_template.php');
$qm = QHM_Message::get_instance();
$qt = QHM_Template::get_instance();

//キャッシュ有効フラグをQHM Template にセット
$qt->enable_cache = $enable_cache;
//ページ名をセット
if (!$qt->set_page) {
	$qt->set_page(isset($vars['page'])  ? $vars['page']  : '');
}

//フラッシュメッセージをQHM Template にセット
if (isset($_SESSION['notices']) && is_array($_SESSION['notices']))
{
	$qt->setv('notices', $_SESSION['notices']);
	unset($_SESSION['notices']);
}


/////////////////////////////////////////////////
// App Start
if ($app_start)
{
	$vars['cmd'] = 'app_start';
}


use Silex\Application;

$app = new Silex\Application();
$app['debug'] = true;


$app->get('/login', function() use ($app)
{
    return $app->redirect('/?cmd=login');
});
$app->get('/logout', function() use ($app)
{
    return $app->redirect('/?cmd=logout');
});

$callback = function($pageName) use ($vars)
{
    global $defaultpage;
    if ($pageName === '') $pageName = $defaultpage;
    $vars['page'] = $pageName;
    return require LIB_DIR . 'main.php';
};
$app->get('/{pageName}', $callback)->assert('pageName', '.*');
$app->post('/{pageName}', $callback)->assert('pageName', '.*');

$app->error(function(\Exception $e, $code)
{
    return 'error: ' . $code . '<br>' . $e->getMessage();
});

$app->run();
return;
