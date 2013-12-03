<?php
/**
 *   Box Plugin
 *   -------------------------------------------
 *   /app/haik-contents/plugin/alert.inc.php
 *
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/06/25
 *   modified : 
 *
 *   Bootstrap に準拠した枠を出力する。
 *   対応しているのは、alert 系、well 系、標準 panel、jumbotron。
 *   jumbotron に関しては Bootstrap 2 系のhero-unit を継承しているため、hero 指定でも使えるようにしている。
 *   close と指定することで、閉じるボタンを追加できる。
 *   何故か、alert 以外でも動く。
 *   
 *   
 *   Usage :
 *     #box(success){{...}} // .alert.alert-success
 *     #box{{...}} // .well
 *     #box(danger,close){{...}} // .alert.alert-danger close ボタン付き
 *   
 */
function plugin_box_convert()
{
	$args = func_get_args();
	$body = trim(array_pop($args));
	
	$close = FALSE;
	$type = 'well';
	
	$wrapper = array('', '');
	$outer = array('', '');
	
	$height = FALSE;
	$cols = 12;//full width
	$offset = 0;
	
	foreach ($args as $arg)
	{
		$arg = trim($arg);
		switch($arg)
		{
			case 'close':
				$close = TRUE;
				break;
			case 'alert':
				$type = 'alert alert-warning';
				break;
			case 'danger':
			case 'success':
			case 'info':
			case 'theme':
			case 'warning':
				$prefix = substr($type, 0, 6);
				if ($prefix === 'panel ')
				{
					$type = $prefix . 'panel-' . $arg;
				}
				else if ($prefix === 'alert ')
				{
					$type = $prefix . 'alert-' . $arg;
				}
				else
				{
					$type = 'alert alert-' . $arg;
				}
				break;
			case 'hero':
			case 'hero-unit':
			case 'jumbotron':
				$type = 'jumbotron';
				break;
			case 'large':
			case 'lg':
				$type = 'well well-lg';
				break;
			case 'small':
			case 'sm':
				$type = 'well well-sm';
				break;
			case 'well':
				$type = 'well';
				break;
			case 'panel':
				$type = 'panel panel-default';
				$wrapper = array('<div class="panel-body">', '</div>');
				break;
			//primary は panel のみ
			case 'primary':
				$type = 'panel panel-primary';
				$wrapper = array('<div class="panel-body">', '</div>');
				break;
			default:
				//col, offset option
				if (preg_match('/^(\d+)(?:\+(\d+))?$/', $arg, $mts))
				{
					$cols = $mts[1];
					$offset = $mts[2] ? $mts[2] : $offset;
				}
				else if (preg_match('/^height=(.+)$/', $arg, $mts))
				{
					$height = $mts[1];
					if (is_numeric($height)) $height .= 'px';
				}
		}
	}
	
	//幅の変更があったら .col-sm-X で囲む
	if ($cols != 12)
	{
		$offset_class = '';
		if ($offset) $offset_class = 'col-sm-offset-' . $offset;
		$outer = array('<div class="row"><div class="col-sm-'.$cols.' '. $offset_class .'">', '</div></div>');
	}
	
	$close = $close ? '<button type="button" class="close" data-dismiss="alert">&times;</button>' : '';
	
	$scroll_style = $height ? 'style="max-height:'.h($height).';overflow-y:scroll;"' : '';
	
    $body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    $lines = explode("\n", $body);
    $body = convert_html($lines);
	
	$html = <<<EOD
{$outer[0]}
	<div class="{$type} orgm-box-block" {$scroll_style}>
		{$wrapper[0]}
		{$close}
		{$body}
		{$wrapper[1]}
	</div>
{$outer[1]}
EOD;

	return $html;
}

/* End of file alert.inc.php */
/* Location: /app/haik-contents/plugin/alert.inc.php */