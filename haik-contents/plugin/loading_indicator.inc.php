<?php
/**
 *   Loading Indicator Plugin
 *   -------------------------------------------
 *   /haik-contens/plugin/loading_indicator.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/07/26
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */



function plugin_loading_indicator_set()
{
	
	$qt = get_qt();
	
	$qt->setjsv('loadingIndicator', false);

	$body = '
<div id="orgm_loading_indicator">
	<span class="orgm-load-indicator"><i class="orgm-icon orgm-icon-spinner-2"></i></span>
</div>
';

	$qt->appendv('body_last', $body);
}

/* End of file loading_indicator.inc.php */
/* Location: /haik-contents/plugin/loading_indicator.inc.php */