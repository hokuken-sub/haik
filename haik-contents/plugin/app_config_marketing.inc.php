<?php
/**
 *   Application Config Apps Plugin
 *   -------------------------------------------
 *   plugin/app_config_apps.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/02/06
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

include(LIB_DIR . 'MCAPI.class.php');

function plugin_app_config_marketing_init()
{
	
	if (exist_plugin('app_config'))
	{
		do_plugin_init('app_config');
	}

}


function plugin_app_config_marketing_action()
{
	global $script, $vars;
	global $google_api_key, $mc_api_key, $mc_list, $ga_tracking_id, $tracking_script;

	
	$qt = get_qt();
	$helper = new HTML_Helper();
		
	$title = __('マーケティング');
	$description = __('マーケティングに関わる設定を行います。');
	
	$mc_lists = plugin_app_config_marketing_getlist();

	
	if (isset($vars['phase']) && $vars['phase'] === 'save')
	{
		$fields = array('google_api_key', 'mc_api_key', 'mc_list', ',mc_list_id', 'ga_tracking_id', 'tracking_script');
		$res = array();
		
		if (isset($vars['google_api_key']))
		{
			$res = array('value'=> $vars['google_api_key'] ? $vars['google_api_key'] : '');
		}

		if (isset($vars['ga_tracking_id']))
		{
			$res = array('value'=> $vars['ga_tracking_id'] ? $vars['ga_tracking_id'] : '');
		}

		if (isset($vars['tracking_script']))
		{
			$res = array('value'=> $vars['tracking_script'] ? __('指定済み') : '指定なし');
		}

		if (isset($vars['mc_api_key']))
		{
			$res = array(
				'value' => $vars['mc_api_key'] ? $vars['mc_api_key'] : '',
				'item'  => 'mc_api_key'
			);
			
			//mc_list を再取得
			$res['mc_lists'] = plugin_app_config_marketing_getlist($res['value']);
		}

		if (isset($vars['mc_list_id']))
		{
			foreach ($mc_lists as $mcl)
			{
				if ($mcl['id'] == $vars['mc_list_id'])
				{
					$vars['mc_list'] = $mcl;
					break;
				}
			}

			$res = array(
				'value'=> $vars['mc_list_id'] ? ($vars['mc_list']['name'].'（'.$vars['mc_list']['id'].'）') : '',
				'item' => 'mc_list_id',
				'mc_list_id' => $vars['mc_list_id'] ? $vars['mc_list']['id'] : ''
			);

			
		}

		$data = array_intersect_key($vars, array_flip($fields));
		$res['options'] = $data;
		if (isset($data['mc_list_id']))
		{
			unset($data['mc_list_id']);
		}
		orgm_ini_write($data);

		$res['message'] = '設定を更新しました';
		
		print_json($res);
		exit;
	}
	else if (isset($vars['phase']) && $vars['phase'] === 'get')
	{
		if (isset($vars['mc_list_id']))
		{
			
			$wiki = '#mc_form';
			$mc_form = convert_html($wiki);
			$mc_form = preg_replace('/(<form [^>]*class=")(.*?)(">)/', '$1$2 form-demo$3', $mc_form);
			
			$json = array(
				array(
					'html' => $mc_form
				)
			);
			
			print_json($json);
		}
		
	}

	//form をゲット
	$wiki = '#mc_form';
	$mc_form = convert_html($wiki);
	$mc_form = preg_replace('/(<form [^>]*class=")(.*?)(">)/', '$1$2 form-demo$3', $mc_form);


	$tmpl_file = PLUGIN_DIR . 'app_config/marketing.html';
	ob_start();
	include($tmpl_file);
	$body = ob_get_clean();

	//mc_lists をセット
	$qt->setjsv('mcLists', $mc_lists);
	
	return array('msg' => $title, 'body' => $body);

}

function plugin_app_config_marketing_getlist($apikey = NULL)
{
	global $mc_api_key, $mc_list;

	$mc_api_key = is_null($apikey) ? $mc_api_key : $apikey;

	//リスト一覧の取得
	$lists = array();
	
	$api = new MCAPI($mc_api_key);
	$retval = $api->lists();
	//error
	if ($api->errorCode)
	{
		return $lists;
	}
	else
	{
		$mc_lists = $retval['data'];
		foreach ($mc_lists as $list)
		{
			$lists[] = array(
				'name' => $list['name'],
				'id' => $list['id'],
			);
		}
	}
	
	return $lists;
}


/* End of file orgm_setting.inc.php */
/* Location: /haik-contents/plugin/orgm_setting.inc.php */