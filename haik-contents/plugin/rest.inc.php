<?php

function plugin_rest_action()
{
	global $vars;
//	var_dump($vars);
	
	$method = strtolower($_SERVER['REQUEST_METHOD']);
	$params = $vars['params'];
	
	$ret_type = 'json';
	if (preg_match('/\.(json|php)$/', $params, $mts))
	{
		$ret_type = $mts[1];
		$params = substr($params, 0, strrpos($params, '.'.$ret_type));
	}
	
	$params = explode('/', $params);
	unset($vars['params']);
	
	var_dump($vars);

	exit;

}

/* End of file rest.inc.php */
/* Location: plugin/rest.inc.php */