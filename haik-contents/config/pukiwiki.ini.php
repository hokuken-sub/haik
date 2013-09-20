<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: pukiwiki.ini.php,v 1.140 2006/06/11 14:35:39 henoheno Exp $
// Copyright (C)
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// PukiWiki main setting file


// !1. 決定的Define ================================================================================
$defines = array(

/////////////////////////////////////////////////
// Functionality settings

// PKWK_OPTIMISE - Ignore verbose but understandable checking and warning
//   If you end testing this PukiWiki, set '1'.
//   If you feel in trouble about this PukiWiki, set '0'.
	'PKWK_OPTIMISE' => 0,

/////////////////////////////////////////////////
// Security settings

// PKWK_READONLY - Prohibits editing and maintain via WWW
//   NOTE: Counter-related functions will work now (counter, attach count, etc)
	'PKWK_READONLY' => 0, // 0 or 1

// PKWK_SAFE_MODE - Prohibits some unsafe(but compatible) functions 
	'PKWK_SAFE_MODE' => 0,

// PKWK_DISABLE_INLINE_IMAGE_FROM_URI - Disallow using inline-image-tag for URIs
//   Inline-image-tag for URIs may allow leakage of Wiki readers' information
//   (in short, 'Web bug') or external malicious CGI (looks like an image's URL)
//   attack to Wiki readers, but easy way to show images.
	'PKWK_DISABLE_INLINE_IMAGE_FROM_URI' => 0,

// PKWK_QUERY_STRING_MAX
//   Max length of GET method, prohibits some worm attack ASAP
//   NOTE: Keep (page-name + attach-file-name) <= PKWK_QUERY_STRING_MAX
	'PKWK_QUERY_STRING_MAX' => 640, // Bytes, 0 = OFF

/////////////////////////////////////////////////
// Experimental features

// Multiline plugin hack (See BugTrack2/84)
// EXAMPLE(with a known BUG):
//   #plugin(args1,args2,...,argsN){{
//   argsN+1
//   argsN+1
//   #memo(foo)
//   argsN+1
//   }}
//   #memo(This makes '#memo(foo)' to this)
	'PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK' => 0, // 1 = Disabled

/////////////////////////////////////////////////
// Directory settings I (ended with '/', permission '777')

// You may hide these directories (from web browsers)
// by setting DATA_HOME at index.php.
	'DATA_DIR'        =>  DATA_HOME . 'wiki/',      // Latest wiki texts
	'DIFF_DIR'        =>  DATA_HOME . 'diff/',      // Latest diffs
	'BACKUP_DIR'      =>  DATA_HOME . 'backup/',    // Backups
	'CACHE_DIR'		  =>  DATA_HOME . 'cache/',     // Some sort of caches
	'PLUGIN_DIR'	  =>  DATA_HOME . 'plugin/',    // Plugin directory
	'META_DIR'        =>  DATA_HOME . 'meta/',      // Page meta directory 
	
//for session (Don't change here [related qhmcommu])
	'APP_SESSION_NAME'=>  strtoupper(APP_NAME).'_SSID', // Plugin directory

	'SKIN_DIR'        =>  DATA_HOME . 'skin/',         //hokuken style directory

// Skin files (SKIN_DIR/*.skin.php) are needed at
// ./DATAHOME/SKIN_DIR from index.php, but
// CSSs(*.css) and JavaScripts(*.js) are needed at
// ./SKIN_DIR from index.php.

// Static image files
	'IMAGE_DIR'        =>  DATA_HOME . 'img/',
// js dir
	'JS_DIR'           =>  DATA_HOME . 'js/',
// css dir
	'CSS_DIR'          =>  DATA_HOME . 'css/',

// Keep this directory shown via web browsers like
// ./IMAGE_DIR from index.php.

// to upload
	'UPLOAD_DIR'       => DATA_HOME . 'upload/',

// store skel
	'SKEL_DIR'       => LIB_DIR . 'skel/',
	
// Splitter of backup data (NOTE: Too dangerous to change)
	'PKWK_SPLITTER'        =>  '>>>>>>>>>>',

/////////////////////////////////////////////////
// Command execution per update
	'PKWK_UPDATE_EXEC'        =>  '',

// Sample: Namazu (Search engine)
//$target     = '/var/www/wiki/';
//$mknmz      = '/usr/bin/mknmz';
//$output_dir = '/var/lib/namazu/index/';
//define('PKWK_UPDATE_EXEC',
//	$mknmz . ' --media-type=text/pukiwiki' .
//	' -O ' . $output_dir . ' -L ja -c -K ' . $target);

);


//////////////////////////////////////
// Do define
foreach($defines as $key=>$val) {
	if(! defined($key) )	define($key, $val);
}





// !2. 変更される変数 ============================================================================

/////////////////////////////////////////////////
// Skin settings


$style_type    = 'text';	// Skins / Stylesheets
$style_name    = '';		// 使用するスタイル
$style_color   = '';		// 使用する色
$style_texture = '';		// 使用する背景テクスチャ
$logo_image    = '';		// 使用するロゴ画像
$logo_title    = 'haik';		// 使用するロゴ文字

$viewport = 'width=device-width,initial-scale=1.0,maximum-scale=2.0';//<meta:viewport>


/////////////////////////////////////////////////
// Title of your Wikisite (Name this)
// Also used as RSS feed's channel name etc
$site_title = 'haik';
$site_title_delim = ' | ';

$display_login = 1;	// ログインリンク表示方法 0:非表示, 1:表示
$reg_exp_host = '';		//外部リンク除外の正規表現(ホスト名)

$site_close_all = 0;    // site close

// site admin ID & Passwd
$username = ''; //mailaddress
$passwd = ''; //PHPASS


// Specify PukiWiki URL (default: auto)
// Shorten $script: Cut its file name (default: not cut)
//$script_directory_index = 'index.php';
$script = '';


////////////////////////////////////////////////
// Mail related settings

// Mail Encoding
$mail_encode = 'ISO-2022-JP';

// Send mail per update of pages
$notify = 0;

// Send diff only
$notify_diff_only = 1;

// SMTP server (Windows only. Usually specified at php.ini)
$smtp_server = '';

// Mail recipient (To:) and sender (From:)
$notify_to   = '';	// To:
$notify_from = '';	// From:

// Subject: ($page = Page name wll be replaced)
$notify_subject = '[QHM Update] $page';

// Mail header
// NOTE: Multiple items must be divided by "\r\n", not "\n".
$notify_header = '';

/////////////////////////////////////////////////
// Mail: POP / APOP Before SMTP

// Do POP/APOP authentication before send mail
$smtp_auth = 0;

$pop_server = '';
$pop_port   = 110;
$pop_userid = '';
$pop_passwd = '';

// Use APOP instead of POP (If server uses)
//   Default = Auto (Use APOP if possible)
//   1       = Always use APOP
//   0       = Always use POP
// $pop_auth_use_apop = 1;


////////////////////////////////////////////////
// Session save path : by hokuken.com
$session_save_path = '';

////////////////////////////////////////////////
// Default Language
$default_lang = 'ja';
$default_country = 'JP';


/////////////////////////////////////////////////
// Wiki cache
$enable_cache = 0;


// AutoLink minimum length of page name
$autolink = 8; // Bytes, 0 = OFF (try 8)

/////////////////////////////////////////////////
// Change Timestamp at all times of updating page

$change_timestamp = 1;



/////////////////////////////////////////////////
// QBlog Vars

$enable_qblog = 0;
$qblog_comment_check = 1;
$qblog_default_cat = 'ブログ';

/////////////////////////////////////////////////
// Video Autoload
$video_autoload = 1;


/////////////////////////////////////////////////
// Open Graph Protocal Tag
$ogp_tag = 0;

/////////////////////////////////////////////////
// Site Tracking Javascript
$tracking_script = '';
$ga_tracking_id = ''; // Google Analytics Tracking ID


/////////////////////////////////////////////////
// Initialize Flag

$app_start = 0;


// !3. require qhm.ini.php ========================================================================
$app_ini_path = CONFIG_DIR . 'haik.ini.php';

if (file_exists($app_ini_path))
{
	require($app_ini_path);
	extract($config);
}
else
{
	$app_start = 1;
}

if ( ! $app_start && ($username === '' OR $passwd === ''))
{
	$app_start = 2;
}


// !4. 後に変えるかも変数 ============================================================================


/////////////////////////////////////////////////
// Ignore list

// Regex of ignore pages
$non_list = '^\:|PukiWiki*|MenuBar|MenuBar2|SiteNavigator|SiteFooter|RecentDeleted|InterWiki|InterWikiName';

// Search ignored pages
$search_non_list = 0;


// Always output "nofollow,noindex" attribute
$nofollow = 0; // 1 = Try hiding from search engines


// Ignere plugin search and rss
$ignore_plugin  = '/^#(autoclose|login)/';

// strip plugin search and rss
$strip_plugin = '/^(#(menu|category|contents|search_menu_key|notify|ssl|redirect|br)|CLASS|STYLE|\/\/)/';

// strip plugin search and rss
$strip_plugin_inline = '/&(tag)(\(.*?\))?;/';

// pages for layout
$layout_pages = array(
	'MenuBar'        => 'メニュー',
	'MenuBar2'       => 'メニュー2',
	'QBlogMenuBar'   => 'ブログメニュー',
	'SiteNavigator'  => 'ナビ',
	'SiteFooter' => 'フッター',
);

/////////////////////////////////////////////////
// HTTP proxy setting (for TrackBack etc)

// Use HTTP proxy server to get remote data
$use_proxy = 0;

$proxy_host = 'proxy.example.com';
$proxy_port = 8080;

// Do Basic authentication
$need_proxy_auth = 0;
$proxy_auth_user = 'username';
$proxy_auth_pass = 'password';

// Hosts that proxy server will not be needed
$no_proxy = array(
	'localhost',	// localhost
	'127.0.0.0/8',	// loopback
//	'10.0.0.0/8'	// private class A
//	'172.16.0.0/12'	// private class B
//	'192.168.0.0/16'	// private class C
//	'no-proxy.com',
);

// Use Less: CSS Meta Language
$use_less = FALSE;




// !5. 変えないだろう変数 ===============================================================================

// Default page name
$defaultpage  = 'FrontPage';     // Top / Default page
$whatsnew     = 'RecentChanges'; // Modified page list
$whatsdeleted = 'RecentDeleted'; // Removeed page list
$interwiki    = 'InterWikiName'; // Set InterWiki definition here
$menubar      = 'MenuBar';       // Menu
$menubar2     = 'MenuBar2';       // Menu
$adminpass    = $passwd;         // Admin password for this Wikisite
$site_nav     = 'SiteNavigator';
$site_footer  = 'SiteFooter';

// Admin Config
$admin_style_name = 'setting'; // 設定画面で使用するスタイル


// TrackBack feature
$nowikiname = 1;// _Disable_ WikiName auto-linking
$function_freeze = 1;// Enable Freeze / Unfreeze feature

/////////////////////////////////////////////////
// Allow to use 'Do not change timestamp' checkbox
// (0:Disable, 1:For everyone,  2:Only for the administrator)
$notimeupdate = 1;

/////////////////////////////////////////////////
//QBlog Consts

$qblog_defaultpage = 'QBlog';
$qblog_menubar     = 'QBlogMenuBar';
$qblog_page_prefix = 'QBlog-';
$qblog_page_format = $qblog_page_prefix . 'YYYYMMDD-#'; //ex: QBlog-20120725-2
$qblog_page_re     = '/^' . preg_quote($qblog_page_prefix) . '(\d{4})(\d{2})(\d{2})-(\d+)$/';
$qblog_date_format = 'Y.m.d';
$qblog_datetime_format = 'Y.m.d H:i:s';


/////////////////////////////////////////////////
// Change default Document Type Definition

// Some web browser's bug, and / or Java apprets may needs not-Strict DTD.
// Some plugin (e.g. paint) set this PKWK_DTD_XHTML_1_0_TRANSITIONAL.

$pkwk_dtd = PKWK_DTD_HTML_5;
//$pkwk_dtd = PKWK_DTD_XHTML_1_1; // Default
//$pkwk_dtd = PKWK_DTD_XHTML_1_0_STRICT;
//$pkwk_dtd = PKWK_DTD_XHTML_1_0_TRANSITIONAL;
//$pkwk_dtd = PKWK_DTD_HTML_4_01_STRICT;
//$pkwk_dtd = PKWK_DTD_HTML_4_01_TRANSITIONAL;



/////////////////////////////////////////////////
// Page-reading feature settings
// (Automatically creating pronounce datas, for Kanji-included page names,
//  to show sorted page-list correctly)

// Enable page-reading feature by calling ChaSen or KAKASHI command
// (1:Enable, 0:Disable)
$pagereading_enable = 0;

// Specify converter as ChaSen('chasen') or KAKASI('kakasi') or None('none')
$pagereading_kanji2kana_converter = 'none';

// Specify Kanji encoding to pass data between PukiWiki and the converter
$pagereading_kanji2kana_encoding = 'EUC'; // Default for Unix
//$pagereading_kanji2kana_encoding = 'SJIS'; // Default for Windows

// Absolute path of the converter (ChaSen)
$pagereading_chasen_path = '/usr/local/bin/chasen';
//$pagereading_chasen_path = 'c:\progra~1\chasen21\chasen.exe';

// Absolute path of the converter (KAKASI)
$pagereading_kakasi_path = '/usr/local/bin/kakasi';
//$pagereading_kakasi_path = 'c:\kakasi\bin\kakasi.exe';

// Page name contains pronounce data (written by the converter)
$pagereading_config_page = ':config/PageReading';

// Page name of default pronouncing dictionary, used when converter = 'none'
$pagereading_config_dict = ':config/PageReading/dict';



/////////////////////////////////////////////////
// Read auth (0:Disable, 1:Enable)
$read_auth = 1;

$read_auth_pages = array(
	// Regex		   Username
//	'#HogeHoge#'		=> 'hoge',
//	'#(NETABARE|NetaBare)#'	=> 'foo,bar,hoge',
);

$auth_method_type	= 'pagename';	// Authentication method (pagename|contents)


/////////////////////////////////////////////////
// Edit auth (0:Disable, 1:Enable)
$edit_auth = 1;

// Edit auth regex
$edit_auth_pages = array(
	'/^.*$/' => $username,
);



/////////////////////////////////////////////////
// Search auth
// 0: Disabled (Search read-prohibited page contents)
// 1: Enabled  (Search only permitted pages for the user)
$search_auth = 0;


/////////////////////////////////////////////////
// $whatsnew: Max number of RecentChanges
$maxshow = 60;

// $whatsdeleted: Max number of RecentDeleted
// (0 = Disabled)
$maxshow_deleted = 60;


/////////////////////////////////////////////////
// HTTP: Output Last-Modified header
$lastmod = 0;

/////////////////////////////////////////////////
// Date format
$date_format = 'Y-m-d';

// Time format
$time_format = 'H:i:s';

/////////////////////////////////////////////////
// Max number of RSS feed
$rss_max = 15;

/////////////////////////////////////////////////
// Backup related settings

// Enable backup
$do_backup = 1;

// When a page had been removed, remove its backup too?
$del_backup = 0;

// Bacukp interval and generation
$cycle  =   3; // Wait N hours between backup (0 = no wait)
$maxage = 120; // Stock latest N backups

// NOTE: $cycle x $maxage / 24 = Minimum days to lost your data
//          3   x   120   / 24 = 15


/////////////////////////////////////////////////
// Template setting

$auto_template_func = 1;
$auto_template_rules = array(
	'((.+)\/([^\/]+))' => '\2/template'
);

/////////////////////////////////////////////////
// Automatically add fixed heading anchor
$fixed_heading_anchor = 0;

/////////////////////////////////////////////////
// Remove the first spaces from Preformatted text
$preformat_ltrim = 1;

/////////////////////////////////////////////////
// Convert linebreaks into <br />
$line_break = 1;

/////////////////////////////////////////////////
// Use date-time rules (See rules.ini.php)
$usedatetime = 1;



/////////////////////////////////////////////////
// User-Agent settings
//
// If you want to ignore embedded browsers for rich-content-wikisite,
// remove (or comment-out) all 'keitai' settings.
//
// If you want to to ignore desktop-PC browsers for simple wikisite,
// copy keitai.ini.php to default.ini.php and customize it.

$agents = array(
// pattern: A regular-expression that matches device(browser)'s name and version
// profile: A group of browsers

    // iPhone iPod Touch 2.0
        //iPhone
        array('pattern'=>'#\b(iPhone+)#', 'profile'=>'default'),

	// Android
	array('pattern'=>'#\b(Mobile Safari)#', 'profile'=>'default'),

    // Embedded browsers (Rich-clients for PukiWiki)

	// Windows CE (Microsoft(R) Internet Explorer 5.5 for Windows(R) CE)
	// Sample: "Mozilla/4.0 (compatible; MSIE 5.5; Windows CE; sigmarion3)" (sigmarion, Hand-held PC)
	array('pattern'=>'#\b(?:MSIE [5-9]).*\b(Windows CE)\b#', 'profile'=>'default'),

	// ACCESS "NetFront" / "Compact NetFront" and thier OEM, expects to be "Mozilla/4.0"
	// Sample: "Mozilla/4.0 (PS2; PlayStation BB Navigator 1.0) NetFront/3.0" (PlayStation BB Navigator, for SONY PlayStation 2)
	// Sample: "Mozilla/4.0 (PDA; PalmOS/sony/model crdb/Revision:1.1.19) NetFront/3.0" (SONY Clie series)
	// Sample: "Mozilla/4.0 (PDA; SL-A300/1.0,Embedix/Qtopia/1.1.0) NetFront/3.0" (SHARP Zaurus)
	array('pattern'=>'#^(?:Mozilla/4).*\b(NetFront)/([0-9\.]+)#',	'profile'=>'default'),

    // Embedded browsers (Non-rich)

	// Windows CE (the others)
	// Sample: "Mozilla/2.0 (compatible; MSIE 3.02; Windows CE; 240x320 )" (GFORT, NTT DoCoMo)
	array('pattern'=>'#\b(Windows CE)\b#', 'profile'=>'keitai'),

	// ACCESS "NetFront" / "Compact NetFront" and thier OEM
	// Sample: "Mozilla/3.0 (AveFront/2.6)" ("SUNTAC OnlineStation", USB-Modem for PlayStation 2)
	// Sample: "Mozilla/3.0(DDIPOCKET;JRC/AH-J3001V,AH-J3002V/1.0/0100/c50)CNF/2.0" (DDI Pocket: AirH" Phone by JRC)
	array('pattern'=>'#\b(NetFront)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#\b(CNF)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#\b(AveFront)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#\b(AVE-Front)/([0-9\.]+)#',	'profile'=>'keitai'), // The same?

	// NTT-DoCoMo, i-mode (embeded Compact NetFront) and FOMA (embedded NetFront) phones
	// Sample: "DoCoMo/1.0/F501i", "DoCoMo/1.0/N504i/c10/TB/serXXXX" // c以降は可変
	// Sample: "DoCoMo/2.0 MST_v_SH2101V(c100;TB;W22H12;serXXXX;iccxxxx)" // ()の中は可変
	array('pattern'=>'#^(DoCoMo)/([0-9\.]+)#',	'profile'=>'keitai'),

	// Vodafone's embedded browser
	// Sample: "J-PHONE/2.0/J-T03"	// 2.0は"ブラウザの"バージョン
	// Sample: "J-PHONE/4.0/J-SH51/SNxxxx SH/0001a Profile/MIDP-1.0 Configuration/CLDC-1.0 Ext-Profile/JSCL-1.1.0"
	array('pattern'=>'#^(J-PHONE)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#^(Vodafone)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#^(SoftBank)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#^(MOT-V980)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#^(MOT-C980)/([0-9\.]+)#',	'profile'=>'keitai'),

	// Openwave(R) Mobile Browser (EZweb, WAP phone, etc)
	// Sample: "OPWV-SDK/62K UP.Browser/6.2.0.5.136 (GUI) MMP/2.0"
	array('pattern'=>'#\b(UP\.Browser)/([0-9\.]+)#',	'profile'=>'keitai'),

	// Opera, dressing up as other embedded browsers
	// Sample: "Mozilla/3.0(DDIPOCKET;KYOCERA/AH-K3001V/1.4.1.67.000000/0.1/C100) Opera 7.0" (Like CNF at 'keitai'-mode)
	array('pattern'=>'#\b(?:DDIPOCKET|WILLCOM)\b.+\b(Opera) ([0-9\.]+)\b#',	'profile'=>'keitai'),

	// Planetweb http://www.planetweb.com/
	// Sample: "Mozilla/3.0 (Planetweb/v1.07 Build 141; SPS JP)" ("EGBROWSER", Web browser for PlayStation 2)
	array('pattern'=>'#\b(Planetweb)/v([0-9\.]+)#', 'profile'=>'keitai'),

	// DreamPassport, Web browser for SEGA DreamCast
	// Sample: "Mozilla/3.0 (DreamPassport/3.0)"
	array('pattern'=>'#\b(DreamPassport)/([0-9\.]+)#',	'profile'=>'keitai'),

	// Palm "Web Pro" http://www.palmone.com/us/support/accessories/webpro/
	// Sample: "Mozilla/4.76 [en] (PalmOS; U; WebPro)"
	array('pattern'=>'#\b(WebPro)\b#',	'profile'=>'keitai'),

	// ilinx "Palmscape" / "Xiino" http://www.ilinx.co.jp/
	// Sample: "Xiino/2.1SJ [ja] (v. 4.1; 153x130; c16/d)"
	array('pattern'=>'#^(Palmscape)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#^(Xiino)/([0-9\.]+)#',	'profile'=>'keitai'),

	// SHARP PDA Browser (SHARP Zaurus)
	// Sample: "sharp pda browser/6.1[ja](MI-E1/1.0) "
	array('pattern'=>'#^(sharp [a-z]+ browser)/([0-9\.]+)#',	'profile'=>'keitai'),

	// WebTV
	array('pattern'=>'#^(WebTV)/([0-9\.]+)#',	'profile'=>'keitai'),

    // Desktop-PC browsers

	// Opera (for desktop PC, not embedded) -- See BugTrack/743 for detail
	// NOTE: Keep this pattern above MSIE and Mozilla
	// Sample: "Opera/7.0 (OS; U)" (not disguise)
	// Sample: "Mozilla/4.0 (compatible; MSIE 5.0; OS) Opera 6.0" (disguise)
	array('pattern'=>'#\b(Opera)[/ ]([0-9\.]+)\b#',	'profile'=>'default'),

	// MSIE: Microsoft Internet Explorer (or something disguised as MSIE)
	// Sample: "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)"
	array('pattern'=>'#\b(MSIE) ([0-9\.]+)\b#',	'profile'=>'default'),

	// Mozilla Firefox
	// NOTE: Keep this pattern above Mozilla
	// Sample: "Mozilla/5.0 (Windows; U; Windows NT 5.0; ja-JP; rv:1.7) Gecko/20040803 Firefox/0.9.3"
	array('pattern'=>'#\b(Firefox)/([0-9\.]+)\b#',	'profile'=>'default'),

    	// Loose default: Including something Mozilla
	array('pattern'=>'#^([a-zA-z0-9 ]+)/([0-9\.]+)\b#',	'profile'=>'default'),

	array('pattern'=>'#^#',	'profile'=>'default'),	// Sentinel
);




// !6. 変数Define ===================================================================================


/////////////////////////////////////////////////
// Language / Encoding settings

// LANG - Internal content encoding ('en', 'ja', or ...)
if (! defined('LANG'))
	define('LANG', $default_lang);

// UI_LANG - Content encoding for buttons, menus,  etc
if (! defined('UI_LANG'))
	define('UI_LANG', LANG); // 'en' for Internationalized wikisite

/////////////////////////////////////////////////
// Local time setting

if ( ! defined('COUNTRY'))
	define('COUNTRY', $default_country);

switch (COUNTRY) { // or specifiy one
case 'JP':
	if (! defined('ZONE'))
		define('ZONE', 'JST');
	if (! defined('ZONETIME'))
		define('ZONETIME', 9 * 3600); // JST = GMT + 9
	break;
default  :
	define('ZONE', 'GMT');
	define('ZONETIME', 0);
	break;
}





// !7. 変数から生成される変数 ============================================================================


/////////////////////////////////////////////////
// Page names can't be edit via PukiWiki
$cantedit = array( $whatsnew, $whatsdeleted );


/////////////////////////////////////////////////
// User definition
$auth_users = array(                      //QQQ 要変更(ユーザー名とパスワード)
	$username	=> $passwd, // md5('bar_passwd')
);


/* End of file pukiwiki.ini.php */
/* Location: ./pukiwiki.ini.php */
