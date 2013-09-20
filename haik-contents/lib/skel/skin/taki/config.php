<?php
$config = array(
	'name' => 'taki',
	'version' => '1.0.0',
	'thumbnail' => 'img/thumbnail.png',
	'style_file' => 'css/docs.css',
	'templates' => array(
		'top' => array(
			'filename' => 'top.skin.php',
			'layouts' => array('SiteNavigator', 'SiteFooter'),
			'thumbnail' => 'img/thumbnail.top.png',
		),
	),
	'default_template' => 'top',
	'eyecatch'   => array(
		'wrap' => 'eyecatch.wrap.php',
		'item' => 'eyecatch.item.php',
	),
	'colors' => array(
		'black'     => 'css/color.black.css',
		'turquoise' => 'css/color.turquoise.css',
		'ruby'      => 'css/color.ruby.css',
		'lapis'     => 'css/color.lapis.css',
		'brown'     => 'css/color.brown.css',
		'green'     => 'css/color.green.css',
		'orange'    => 'css/color.orange.css',
		'mosgreen'  => 'css/color.mosgreen.css',
		'amethyst'  => 'css/color.amethyst.css',
	),
	'textures' => array(
		'hemp'      => 'css/texture.hemp.css',
		'arches'    => 'css/texture.arches.css',
		'shattered' => 'css/texture.shattered.css',
		'wood'      => 'css/texture.wood.css',
		'wood2'     => 'css/texture.wood2.css',
		'grass'     => 'css/texture.grass.css',
		'tree'      => 'css/texture.tree.css',
		'sea'       => 'css/texture.sea.css',
		'cloth'     => 'css/texture.cloth.css',
		'paper'     => 'css/texture.paper.css',
		'custom'      => 'css/texture.custom.css',
	),
	'sample_style_file' => 'css/samples.css',
);
