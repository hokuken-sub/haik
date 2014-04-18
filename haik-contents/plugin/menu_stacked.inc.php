<?php
/**
 *   Stacked Menubar
 *   -------------------------------------------
 *   menu_stacked.inc.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/06/28
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
function plugin_menu_stacked_convert()
{
    global $vars, $script;
    $qt = get_qt();

    $args = func_get_args();
    $body = trim(array_pop($args));
    $body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    $body = convert_html($body);

    $the_url = get_page_url($vars['page']);

    $regx = array(
        '/<ul class="list1"/',
        '/<ul class="list[2-3]"/',
        '/<li>(<a href="'. h(preg_quote($the_url, '/')) .'")/',
        '/<li>(<a [^>]+)/',
        '/<\/li>/',
        '/<\/ul>/',
    );
    $replace = array(
        '<div class="list-group"',
        '<div /',
        '\1 class="list-group-item active"',
        '\1 class="list-group-item"',
        '',
        '</div>',
    );
    $body = preg_replace($regx, $replace, $body);

    $addscript = '
<script type="text/javascript">
$(function(){
$("#orgm_menu ul.nav-stacked").each(function(){
	var $lis = $("> li", this);
	
	$lis.each(function(){
		$("> a", this).prepend("<i class=\"icon-chevron-right pull-right\"></i>");
	});
});

});
</script>
';
    $qt->appendv_once('plugin_menu_stacked', 'plugin_script', $addscript);

    return $body;
}

?>