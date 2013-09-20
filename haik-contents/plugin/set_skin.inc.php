<?php
/**
 *   ORGM Set Design Plugin
 *   -------------------------------------------
 *   plugin/set_skin.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 
 *   modified :
 *   
 *   指定したデザインを使用します。
 *   
 *   Usage :
 *   
 */

function plugin_set_skin_convert()
{
	global $vars, $include_skin_file_path;
	$qm = get_qm();
	
	$args = func_get_args();
	if(count($args) < 1){
		return $qm->replace('fmt_err_cvt', 'set_skin', $qm->m['plg_set_skin']['err_usage']);
	}
	
	$skin_file = array_pop($args);
	
	if( file_exists(SKIN_DIR.$skin_file) )
	{
		$include_skin_file_path = $skin_file;	
	}
	else
	{
		return $qm->replace('fmt_err_cvt', 'set_skin', $qm->replace('plg_set_skin.err_not_found', $skin_file));
	}
}
?>
