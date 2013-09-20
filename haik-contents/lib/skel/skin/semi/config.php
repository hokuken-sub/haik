<?php
$config = array(
	'name' => 'semi',
	'version' => '1.0.0',
	'thumbnail' => 'img/thumbnail.png',
	'style_file' => 'css/docs.css',
	'templates' => array(
		'top' => array(
			'filename' => 'top.skin.php',
			'layouts' => array('SiteNavigator', 'SiteFooter'),
			'thumbnail' => 'img/thumbnail.top.png',
		),
		'content' => array(
			'filename' => 'content.skin.php',
			'layouts' => array('SiteNavigator', 'SiteFooter', 'MenuBar'),
			'thumbnail' => 'img/thumbnail.content.png',
		)
	),
	'default_template' => 'content',
	'eyecatch'   => array(
		'wrap' => 'eyecatch.wrap.php',
		'item' => 'eyecatch.item.php',
	),
	'colors' => array(
		'hanada'            => 'css/color.hanada.css',
		'grayshblue'        => 'css/color.grayshblue.css',
		'wakatake'          => 'css/color.wakatake.css',
		'white'             => 'css/color.white.css',
		'black-emerald'     => 'css/color.black-emerald.css',
		'black-rose'        => 'css/color.black-rose.css',
		'black-orange'      => 'css/color.black-orange.css',
		'black-blue'        => 'css/color.black-blue.css',
		'black-green'       => 'css/color.black-green.css',
	),
	'textures' => array(
		'hemp-light'   => 'css/texture.hemp-light.css',
		'hemp-dark'    => 'css/texture.hemp-dark.css',
		'square'       => 'css/texture.square.css',
		'wood'         => 'css/texture.wood.css',
		'rainbow'      => 'css/texture.rainbow.css',
	),
	'sample_style_file' => 'css/samples.css',
);
