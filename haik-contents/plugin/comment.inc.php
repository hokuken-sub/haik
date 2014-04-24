<?php
/**
 *   Comment
 *   -------------------------------------------
 *   /plugin/comment.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/06/05
 *   modified :
 *   
 *   Description
 *   
 *   Usage : #comment
 *   
 */

define('PLUGIN_COMMENT_DIRECTION_DEFAULT', '0'); // 1: above 0: below

define('PLUGIN_COMMENT_FORMAT_MSG',  '$msg');
define('PLUGIN_COMMENT_FORMAT_NAME', '$name');
define('PLUGIN_COMMENT_FORMAT_NOW',  '$now');
define('PLUGIN_COMMENT_FORMAT_STRING', "\x08NAME\x08 \x08NOW\x08 \n\x08MSG\x08 \n----");
define('PLUGIN_COMMENT_AUTH', TRUE);

function plugin_comment_action()
{
	global $script, $vars, $now;
	
	if (PKWK_READONLY) die_message(__('PKWK_READONLY prohibits editing'));

	if ($vars['msg'] == '') return array('msg'=>'', 'body'=>''); // Do nothing

	$vars['msg'] = str_replace("\n", '&br;', $vars['msg']); // Cut LFs

	$head = '';	
	$match = array();
	if (preg_match('/^(-{1,2})-*\s*(.*)/', $vars['msg'], $match))
	{
		$head        = $match[1];
		$vars['msg'] = $match[2];
	}

	$comment  = str_replace('$msg', $vars['msg'], PLUGIN_COMMENT_FORMAT_MSG);
	if (isset($vars['name']) || ($vars['nodate'] != '1'))
	{
		$_name = (! isset($vars['name']) || $vars['name'] == '') ? '' : $vars['name'];
		$_name = ($_name == '') ? '' : str_replace('$name', $_name, PLUGIN_COMMENT_FORMAT_NAME);
		$_now  = ($vars['nodate'] == '1') ? '' :
			str_replace('$now', $now, PLUGIN_COMMENT_FORMAT_NOW);
		$comment = str_replace("\x08MSG\x08",  $comment, PLUGIN_COMMENT_FORMAT_STRING);
		$comment = str_replace("\x08NAME\x08", $_name, $comment);
		$comment = str_replace("\x08NOW\x08",  $_now,  $comment);
	}
	$comment = '-' . $head . ' ' . $comment;

	$postdata    = '';
	$comment_no  = 0;
	$above       = (isset($vars['above']) && $vars['above'] == '1');

	foreach (get_source($vars['refer']) as $line)
	{
		if (! $above) $postdata .= $line;
		if (preg_match('/^#comment/i', $line) && $comment_no++ == $vars['comment_no']) {
			if ($above)
			{
				$postdata = rtrim($postdata) . "\n" .
					$comment . "\n" .
					"\n";  // Insert one blank line above #commment, to avoid indentation
			}
			else
			{
				$postdata = rtrim($postdata) . "\n" .
					$comment . "\n"; // Insert one blank line below #commment
			}
		}
		if ($above) $postdata .= $line;
	}

	$title = __('$1を更新しました。');
	$body = '';
	if (md5(@join('', get_source($vars['refer']))) !== $vars['digest'])
	{
		$title = '$1で【更新の衝突】が起きました。';
		$body  = 'あなたがコメントを書いている間に、他の人が同じページを更新してしまったようです。<br>コメントを追加しましたが、違う位置に挿入されているかもしれません。<br>' . make_pagelink($vars['refer']);
	}

	if ($vars['authcode_master'] === $vars['authcode'])
	{
		$noupdate = $vars['noupdate']==1 ? true : false;
		page_write($vars['refer'], $postdata, $noupdate);
	}
	else
	{
		$vars['comment_error'] = 'error!!!';
	}
	
	$retvars['msg']  = $title;
	$retvars['body'] = $body;
	
	
	$vars['page'] = $vars['refer'];

	return $retvars;
}

function plugin_comment_convert()
{
	global $vars, $digest, $script;
	static $numbers = array();

	$qt = get_qt();

	$msg = $name = '';

	if (PKWK_READONLY) return ''; // Show nothing

	if (! isset($numbers[$vars['page']])) $numbers[$vars['page']] = 0;
	$comment_no = $numbers[$vars['page']]++;

	$options = (func_num_args() > 1) ? func_get_args() : array();
	
	if (isset($vars['comment_error']))
	{
		$name = $vars['name'];
	}
	
	$nodate = in_array('nodate', $options) ? '1' : '0';
	$noupdate = in_array('noupdate', $options) ? '1' : '0';
	$above  = in_array('above',  $options) ? '1' :
		(in_array('below', $options) ? '0' : PLUGIN_COMMENT_DIRECTION_DEFAULT);
	
	$authcode = '' . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9);
	
	if (isset($vars['comment_error']))
	{
		$authcode_msg = sprintf(__('認証コードを入力してください（%d）'), h($authcode));
		$msg = str_replace('&br;', "\n", $vars['msg']);
		$authcode_cls = 'text-error';
	}
	else
	{
		$authcode_msg = sprintf(__('認証コード（%d）'), h($authcode));
		$authcode_cls = '';
	}
	
	$r_page = rawurlencode($vars['page']);
	
	$body = '
<form action="'. h(get_page_url($vars['page'])) .'" method="post">
<div class="panel panel-default orgm-comment" data-comment-no="'.h($comment_no).'">
		<div class="comment-body">
			<textarea type="text" rows="3" name="msg" class="form-control" placeholder="'. __('コメントをどうぞ'). '">'.h($msg).'</textarea>
		</div>
		<div class="panel-footer comment-footer form-inline">
		
			<div class="row">
				<div class="col-sm-3 comment-footer-col">
					<label for="orgm_comment_'.h($comment_no).'_name" class="sr-only">'.__('お名前').'</label>
					<input type="text" id="orgm_comment_'.h($comment_no).'_name" name="name" class="form-control input-sm" value="'. h($name) .'" placeholder="'.__('お名前').'">
				</div>
				<div class="col-sm-4 col-sm-offset-2 comment-footer-col">
					<div class="row">
						<div class="col-xs-7 text-right">
							<label for="orgm_comment_'.h($comment_no).'_authcode" class="'. $authcode_cls .'">'. $authcode_msg .'</label>
						</div>
						<div class="col-xs-5">
							<input type="text" id="orgm_comment_'.h($comment_no).'_authcode" name="authcode" class="form-control input-sm" value="">
						</div>
					</div>
				</div>
				<div class="col-sm-3 comment-footer-col">
					<input type="submit" name="comment" class="btn btn-primary btn-sm btn-block" value="'. __('コメントの挿入'). '">
				</div>
			</div>
		
		</div>
		<input type="hidden" name="plugin" value="comment">
		<input type="hidden" name="refer" value="'.h($vars['page']).'">
		<input type="hidden" name="comment_no" value="'.h($comment_no).'">
		<input type="hidden" name="nodate" value="'.h($nodate).'">
		<input type="hidden" name="above" value="'.h($above) .'">
		<input type="hidden" name="digest" value="'.h($digest) .'">
		<input type="hidden" name="noupdate" value="'.h($noupdate).'">
		<input type="hidden" name="authcode_master" value="'.h($authcode).'">
</div>
</form>
';


	return $body;
}
/* End of file comment.inc.php */
/* Location: /plugin/comment.inc.php */