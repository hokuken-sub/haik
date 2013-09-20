<?php
/**
 *   MailChimp Form
 *   -------------------------------------------
 *   mc_form.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/02/25
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
 
include_once(LIB_DIR . 'html_helper.php');
include_once(LIB_DIR . 'MCAPI.class.php');


function plugin_mc_form_action()
{
	global $script, $vars;
	global $mc_api_key, $mc_list;

	
	
	if ($vars['_token'] !== $_SESSION['_token'])
	{
		header("Content-Type: application/json; charset=UTF-8");
		echo '{"error":1}';
		exit;
	}

	$api = new MCAPI($mc_api_key);

	$mc_merge_tags = plugin_mc_form_get_tags();
	$merge_vars = array();
	foreach ($mc_merge_tags as $tag)
	{
		if (isset($vars[$tag['tag']]))
		{
			$merge_vars[$tag['tag']] = $vars[$tag['tag']];
		}
	}


	$ret = $api->listSubscribe($mc_list['id'], $vars['EMAIL'], $merge_vars);
	
	if ($api->errorCode)
	{
		print_json(array('errorCode'=>$api->errorCode, 'errorMessage'=>$api->errorMessage));
		exit;
	}
	else
	{
		print_json(array('success'=>1));
		exit;
	}
}

function plugin_mc_form_convert()
{
	global $script;
	global $mc_api_key, $mc_list;
	
	$args   = func_get_args();

	$notitle = FALSE;
	$compact = FALSE;
	$formtype = 'horizontal';
	$color = 'primary';
	$text = __('登録する');
	
	foreach ($args as $arg)
	{
		$arg = trim($arg);
		if (strpos($arg, 'submit') === 0)
		{
			list($tmp, $text) = explode('=', $arg, 2);
			$text = trim($text);
		}
		else if (strpos($arg, 'color') === 0)
		{
			list($tmp, $color) = explode('=', $arg, 2);
			$color = trim($color);
		}
		switch(trim($arg)) {
			case 'notitle' :
				$notitle = TRUE;
				break;
			case 'compact' :
				$compact = TRUE;
				break;
			case 'vertical' :
				$formtype = 'vertical';
		}
	}

	$helper = new HTML_Helper();

	$mc_merge_tags = plugin_mc_form_get_tags();
	if (count($mc_merge_tags) == 0)
	{
		return $helper->alert(__('MailChimpと通信ができません。設定をご確認ください'), 'error');
	}


	$qt = get_qt();
	$plugin_script = '	
<link rel="stylesheet" href="'.JS_DIR.'datepicker/css/datepicker.css" />
<script src="'.JS_DIR.'datepicker/js/bootstrap-datepicker.js"></script>
<script src="'.JS_DIR.'datepicker/js/bootstrap-datepicker.ja.js"></script>
<script type="text/javascript">
$(function(){
	$(".datepicker").datepicker({
		language: "ja"
	});
});
</script>
';
	$qt->appendv_once('plugin_mc_form_convert', 'plugin_script', $plugin_script);
	
	

	$html  = '<div class="orgm-mc-form row">';
	if ( ! $notitle)
	{
		$html .= '<h2>'.$mc_list['name'].'</h2>';
	}

	$html .= $helper->form($formtype, array('method'=>'POST', 'action'=>$script.'?cmd=mc_form'));
	foreach ($mc_merge_tags as $tag)
	{
		if ($compact && ! $tag['req']) {
			continue;
		}
		
		$default = ($tag['default']) ? $tag['default'] : '';
		$tagname = h($tag['name']) . ($tag['req'] ? '<span class="orgm-require-mark">*</span>' : '');

 		switch ($tag['field_type'])
		{
			case 'email':
			case 'text':
			case 'address':
			case 'phone':
			case 'number':	// number???
			case 'url':
			case 'imageurl': // imageurl ???
				$html .= $helper->input($tag['tag'], 
										array(
											'type'  => 'text',
											'label' => $tagname,
											'value' => h($default),
											'required' => $tag['req'] ? '' : FALSE,
											'help'  => h($tag['helptext']),
											'escape' => FALSE,
										));
										
				break;

			case 'dropdown':
				$tag['field_type'] = 'select';

			case 'radio':
				$selectdata = array();
				foreach ($tag['choices'] as $key => $val)
				{
					if (trim($default) == trim($val))
					{
						$default = $key;
					}
					$selectdata[] = array('label' => h($val), 'value' => h($val));
				}

				$html .= $helper->input($tag['tag'], 
										array(
											'type'  => $tag['field_type'],
											'label' => $tagname,
											'data'  => $selectdata,
											'value' => h($default),
											'help'  => h($tag['helptext']),
											'escape' => FALSE,
										));
				break;

			case 'date':
			case 'birthday':
				$html .= $helper->input($tag['tag'], 
										array(
											'type'  => 'text',
											'label' => $tagname,
											'value' => h($default),
											'class' => 'datepicker',
											'data-date-format' => 'yyyy-mm-dd',
											'data-date' => date('Y-m-d'),
											'help'  => h($tag['helptext']),
											'escape' => FALSE,
										));
				break;
		}
	}

	$btnclass = get_bs_style($color);
	$html .= $helper->submit(
		$text,
		array(
			'class' => $btnclass
		)
	);
	
	$token = md5(get_source($vars['page'], TRUE, TRUE).date('Ymd His'));
	$_SESSION['_token'] = $token;
	$html .= $helper->input('_token', array('type'=>'hidden', 'value'=>$token));

	$html .= $helper->form_end();
	$html .= '</div>';

	return $html;

}

function plugin_mc_form_get_tags()
{
	global $mc_api_key, $mc_list;
	
	$mc_cachefile = 'mc_form.dat';
	$mc_merge_tags = cache_read($mc_cachefile);
	if ( ! $mc_merge_tags)
	{
		// Mailchimpから取得
		$api = new MCAPI($mc_api_key);
		$mc_merge_tags = $api->listMergeVars($mc_list['id']);
		if ($api->errorCode)
		{
			return array();
		}
		else
		{
			//キャッシュを作成
			cache_write($mc_cachefile, $mc_merge_tags);
		}
	}
	
	return $mc_merge_tags;
}

?>