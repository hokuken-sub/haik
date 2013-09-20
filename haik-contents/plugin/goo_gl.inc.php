<?php
/**
 *   goo.gl URL Shortener Plugin
 *   -------------------------------------------
 *   plugin/goo_gl.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/02/28
 *   modified :
 *   
 *   goo.gl のAPIを使い、短縮URLを取得する。
 *   Simple API KEY をセットした場合、ユニークな短縮URLを作成可能。
 *
 *   TODO: キャッシュする（1日 100,000,000 リクエストまで）→必要ないか。。
 *   
 *   Usage :
 *   
 */

define('PLUGIN_GOO_GL_API_URL', 'https://www.googleapis.com/urlshortener/v1/url');

function plugin_goo_gl_inline()
{
	global $script, $vars, $defaultpage;
	
	$format = '<input type="text" value="%1s" readonly onclick="this.focus();this.select()" class="form-control">';

	if ($vars['page'] === $defaultpage)
	{
		$url = $script;
	}
	else
	{
		$url = $script . '?' . rawurlencode($vars['page']);
	}

	
	$args = func_get_args;
	foreach ($args as $arg)
	{
		$arg = trim($arg);
		
		//URL指定でそちらを優先
		if (is_url($arg))
		{
			$url = $arg;
			continue;
		}

		switch ($arg)
		{
			case 'copy':
				$format = '<input type="text" value="%1s" readonly onclick="this.focus();this.select()" class="form-control">';
				break;
			case 'link':
				$format = '<a href="%1$s">%1$s</a>';
				break;
			case 'plain':
			case 'text':
				$format = '%1$s';
		}
		
	}

	$surl = plugin_goo_gl_get_shortened($url);

	if ($surl === FALSE)
	{
		return '<div class="alert alert-danger">&goo_gl; 短縮URLを利用できません。</div>';
	}
	return sprintf($format, $surl);
}

function plugin_goo_gl_get_shortened($url)
{
	global $google_api_key;

	$apiurl = PLUGIN_GOO_GL_API_URL;
	if ($google_api_key)
	{
		$apiurl = PLUGIN_GOO_GL_API_URL . '?key=' . $google_api_key;
	}
	
	$headers = 'Content-Type: application/json' . "\r\n";
	$data = array(
		'longUrl' => $url,
	);
	$json = json_encode($data);
	
	$res = http_request($apiurl, 'POST', $headers, $json);

	$data = json_decode($res['data']);
	
	if (isset($data->error))
	{
		return FALSE;
	}
	else
	{
		return $data->id;
	}
}

/* End of file goo_gl.inc.php */
/* Location: /haik-contents/plugin/goo_gl.inc.php */