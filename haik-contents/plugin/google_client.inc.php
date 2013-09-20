<?php

require_once LIB_DIR . 'google_api/Google_Client.php';

define('PLUGIN_GOOGLE_CLIENT_TOKEN', 'google_client_token');

function plugin_google_client_action()
{
	$client = plugin_google_client_connect();

	if ($client->getAccessToken()) {
		$_SESSION[PLUGIN_GOOGLE_CLIENT_TOKEN] = $client->getAccessToken();
	}
	else
	{
		$authUrl = $client->createAuthUrl();
		return array(
			'msg' => 'Google Authentiaction',
			'body'=>'<a href="'.$authUrl.'" class="login btn btn-primary">Connect Me!</a>'
		);
	}

	return;	
}

function plugin_google_client_connect($code = NULL, $logout = FALSE)
{
	global $vars, $script;
	
	$client = new Google_Client();

	if ($logout)
	{
		unset($_SESSION[PLUGIN_GOOGLE_CLIENT_TOKEN]);
	}
	
	if ( ! is_null($code))
	{
		$client->authenticate();
		$_SESSION[PLUGIN_GOOGLE_CLIENT_TOKEN] = $client->getAccessToken();
		$redirect = $script . '?cmd=google_client';
		header('Location: ' . $redirect);
		exit;
	}
	
	if (isset($_SESSION[PLUGIN_GOOGLE_CLIENT_TOKEN]))
	{
		$client->setAccessToken($_SESSION[PLUGIN_GOOGLE_CLIENT_TOKEN]);
	}
	
	return $client;
	
	if ($client->getAccessToken()) {
		/*

		$props = $service->management_webproperties->listManagementWebproperties("~all");
		print "<h1>Web Properties</h1><pre>" . print_r($props, true) . "</pre>";
		
		$accounts = $service->management_accounts->listManagementAccounts();
		print "<h1>Accounts</h1><pre>" . print_r($accounts, true) . "</pre>";
		
		$segments = $service->management_segments->listManagementSegments();
		print "<h1>Segments</h1><pre>" . print_r($segments, true) . "</pre>";
		
		$goals = $service->management_goals->listManagementGoals("~all", "~all", "~all");
		print "<h1>Segments</h1><pre>" . print_r($goals, true) . "</pre>";
*/
		
		$_SESSION[PLUGIN_GOOGLE_CLIENT_TOKEN] = $client->getAccessToken();
//		echo $_SESSION[PLUGIN_GOOGLE_CLIENT_TOKEN];
		return TRUE;
	}
	else
	{
		return FALSE;
		$authUrl = $client->createAuthUrl();
		return array('msg' => 'Google Authentiaction', 'body'=>'<a href="'.$authUrl.'" class="login btn btn-primary">Connect Me!</a>');
	}
}