<?php
// $Id: newpage.inc.php,v 1.15 2005/02/27 09:35:16 henoheno Exp $
//
// Newpage plugin

function plugin_newpage_action()
{
	global $script, $vars, $style_name, $admin_style_name;
	global $BracketName;

	$qm = get_qm();
	$qt = get_qt();

	$newpage = '';
	if ($vars['page'])
	{
		$newpage = $vars['page'];
	}
	if (! preg_match('/^' . $BracketName . '$/', $newpage)) $newpage = '';

	if (PKWK_READONLY) return ''; // Show nothing
	if ( ! ss_admin_check() || check_editable($page, FALSE, FALSE))
	{
		set_flash_msg(__('ページの追加はできません'), 'error');
		redirect($script);
		exit;
	}
	
	// スタイルを利用するため、一時退避
	$user_style_name = $style_name;
	$style_name = $admin_style_name;
	$qt->setv('template_name', 'narrow');


	$s_page    = h(isset($vars['refer']) ? $vars['refer'] : $vars['page']);
	$s_newpage = h($newpage);

	if (isset($vars['phase']) && $vars['phase'] == 'save')
	{
		$page    = strip_bracket($vars['page']);
		$r_page  = rawurlencode(isset($vars['refer']) ? get_fullname($page, $vars['refer']) : $page);
		$r_refer = rawurlencode($vars['refer']);
		$r_template_name = rawurlencode($vars['template_name']);

		pkwk_headers_sent();
		header('Location: ' . get_script_uri() .
			'?cmd=read&page=' . $r_page . '&refer=' . $r_refer. '&template_name='. $r_template_name);
		exit;
	}

	$retvars['msg']  = $qm->m['plg_newpage']['label'];
	$retvars['body'] = plugin_newpage_form($_newpage, $_page, $user_style_name);

	if (preg_match('/id="([^"]+)"/', $retvars['body'], $ms)) {
		$domid = $ms[1];

		$addscript = '
<script type="text/javascript">
$(function(){
	$("#'.$domid.'").focus().select();
});
</script>
';
		$qt->appendv_once('plugin_newpage_action', 'plugin_script', $addscript);
	}

	return $retvars;
}

function plugin_newpage_form($s_newpage, $s_page, $user_style_name)
{
	global $script, $vars, $style_name;
	
	$qm = get_qm();

	$title = __('新規ページ作成');
	$description = ('新規ページを作成します。');

	$config = style_config_read($user_style_name);
	$templates = $config['templates'];

	$template_name = $config['default_template'];
	$body = '
<div class="page-header">'.h($title).'</div>

<div class="container plugin-newpage">
	<form action="'.h($script).'" method="post">
		<input type="hidden" name="plugin" value="newpage" />
		<input type="hidden" name="refer"  value="'.$s_page.'" />
		<input type="hidden" name="phase" value="save" />
		<div class="form-group">
			<label class="control-label">'.$qm->m['plg_newpage']['label'].'</label>
			<div class="row">
				<div class="col-sm-6">
					<input type="text" name="page" id="_p_newpage" value="'.$s_newpage.'" size="30" class="form-control" />
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label">レイアウト</label>
			<div class="row">
				<ul class="thumbnails">
';
			foreach ($templates as $key => $row)
			{
				$body .= '<li class="col-sm-3 orgm-template-item'. (($template_name == $key) ? ' active' : '').'" data-template="'.$key.'">';
				$body .= '<div class="thumbnail">';
				if ($row['thumbnail'])
				{
					$thumbnail = SKIN_DIR . $user_style_name . '/' . $row['thumbnail'];
			 		$body .= '<img src="'.h($thumbnail).'" alt="'. h(sprintf(__('%s のサムネール'), $template_name)) .'">';
				}
				else
				{
					$body .= '<div class="no-thumbnail"><i class="orgm-icon orgm-icon-image"></i> '. h(__("画像がありません")) .'</div>';
				}
				$body .= '<div class="thumbnail_name"><span>'.h($key).'</span></div>';
				$body .= '</div>';
				$body .= '</li>';
			}
			$body .= '
				</ul>
				<input type="hidden" name="template_name" value="'.$template_name.'" />
			</div>
		</div>
		<div class="form-group">
			<input type="submit" class="btn btn-primary" value="'.$qm->m['plg_newpage']['btn_create'].'" />
		</div>
	</form>
</div>
';
	
	return $body;

}

?>