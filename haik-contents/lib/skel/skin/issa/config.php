<?php
$config = array(
	'name' => 'issa',
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
		'white'     => 'css/color.white.css',
		'beige'     => 'css/color.beige.css',
		'blue'      => 'css/color.blue.css',
		'yellow'    => 'css/color.yellow.css',
		'olive'     => 'css/color.olive.css',
		'brown'     => 'css/color.brown.css',
		'red'       => 'css/color.red.css',
	),
	'textures' => array(
		'square'          => 'css/texture.square.css',
		'dust'            => 'css/texture.dust.css',
		'cloth'           => 'css/texture.cloth.css',
		'project_paper'   => 'css/texture.project_paper.css',
	),
	'sample_style_file' => 'css/samples.css',
);
