<?php
/**
 *   Set Google Search Plugin
 *   -------------------------------------------
 *   plugin/gsearch.inc.php
 *   
 *   Copyright (c) 2010 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 
 *   modified : 2010-10-15
 *   
 *   Google 検索、サイト内検索フォームを設置する
 *   
 *   Usage :
 *     #gsearch([検索対象サイトURL])
 *   
 */

function plugin_gsearch_convert()
{
	global $script;
	$qm = get_qm();
	
	$args = func_get_args();
	if (count($args) > 0) {
		$url = array_pop($args);
	} else {
		$url = dirname($script);
	}
	

	return '<!-- SiteSearch Google -->
<center>
<form method="get" action="http://www.google.co.jp/search">
<table bgcolor="#FFFFFF"><tr valign="top"><td>
<a href="http://www.google.co.jp/">
<img src="http://www.google.com/logos/Logo_40wht.gif"
border="0" alt="Google" align="absmiddle" /></a>
</td>
<td>
<div class="form-inline">
	<input type="text" name="q" size="31" maxlength="255" value="" class="form-control" style="width:auto;">
	<input type="hidden" name="ie" value="UTF-8" />
	<input type="hidden" name="oe" value="UTF-8" />
	<input type="hidden" name="hl" value="ja" />
	<input type="submit" name="btnG" value="'. $qm->m['plg_gsearch']['btn_gsearch'] .'" class="btn btn-default" />
	<span style="font-size:smaller">
	<input type="hidden" name="domains" value="'.$url.'" /><br />
	<label><input type="radio" name="sitesearch" value="" /> '. $qm->m['plg_gsearch']['label_wsearch'] .'</label>
	<label><input type="radio" name="sitesearch" value="'.$url.'" checked="checked" /> '. $qm->replace('plg_gsearch.label_ssearch', $url). '</label>
	</span>
</div>
</td></tr></table>
</form>
</center>
<!-- SiteSearch Google -->';

}
?>