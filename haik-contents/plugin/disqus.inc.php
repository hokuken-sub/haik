<?php
/**
 *   DISQUS Plugin
 *   -------------------------------------------
 *   /plugin/disqus.inc.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/05/01
 *   modified : 13/06/24
 *   
 *   Put a Disqus forum
 *   
 *   Usage : #disqus([shortname],[id])
 *   
 */

function plugin_disqus_inline()
{
	global $disqus_shortname;
	
	$args = func_get_args();
	array_pop($args);
	
	$shortname = $id = '';
	if (isset($disqus_shortname) && $disqus_shortname)
	{
		$shortname = $disqus_shortname;
	}

	foreach ($args as $arg)
	{
		$arg = trim($arg);
		if ($shortname === '')
		{
			$shortname = $arg;
		}
		else
		{
			$id = $arg;
		}
	}
		
	if ($shortname === '')
	{
		return '<strong>'. __('error: &disqus(shortname);') . '</strong>';
	}

	return plugin_disqus_count($shortname, $id);
	
}

function plugin_disqus_convert()
{
	global $disqus_shortname;
	static $called = FALSE;
	
	if ($called)
	{
		return '<div class="alert">'. __('error: #disqus was already called in this page.') .'</div>';
	}
	
	$args = func_get_args();
	
	$shortname = $id ='';
	if (isset($disqus_shortname))
	{
		$shortname = $disqus_shortname;
	}

	foreach ($args as $arg)
	{
		$arg = trim($arg);
		if ($shortname === '')
		{
			$shortname = $arg;
		}
		else
		{
			$id = $arg;
		}
	}

	
	if ($shortname === '')
	{
		return '<div class="alert">'. __('error: #disqus(shortname)') .'</div>';
	}

	$called = TRUE;
	return plugin_disqus_html($shortname, $id);

}

function plugin_disqus_html($shortname = '', $id = '')
{
	global $vars, $script;
	
	//identifier はページ名か指定されたものを使用する
	$id = ($id === '') ? $vars['page'] : $id;
	$s_id = addcslashes($id, "\\'");
	
	ob_start();
	

?>

    <div id="disqus_thread"></div>
    <script type="text/javascript">
        /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
        var disqus_shortname = '<?php echo $shortname?>'; // required: replace example with your forum shortname
        var disqus_identifier = '<?php echo $s_id?>';

        /* * * DON'T EDIT BELOW THIS LINE * * */
        (function() {
            var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
            dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
            (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
        })();
    </script>
    <noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
    <a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>
<?php

	$html = ob_get_clean();
	
	return $html;
	
}

function plugin_disqus_count($shortname = '', $id = '')
{
	global $vars, $script, $plugin_disqus_threads;
	
	$page = $vars['page'];

	$id = ($id === '') ? $page : $id;
	
	if (is_page($id))
	{
		$r_page = rawurlencode($id);
	}
	else
	{
		$r_page = rawurlencode($page);
	}
	
	$qt = get_qt();
	
	ob_start();
?>
    <script type="text/javascript">
    /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
    var disqus_shortname = '<?php echo h($shortname)?>'; // required: replace example with your forum shortname

    /* * * DON'T EDIT BELOW THIS LINE * * */
    (function () {
        var s = document.createElement('script'); s.async = true;
        s.type = 'text/javascript';
        s.src = '//' + disqus_shortname + '.disqus.com/count.js';
        (document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);
    }());
    </script>
    
<?php

	$html = ob_get_clean();

	$qt->appendv_once('plugin_disqus_count', 'body_last', $html);
	
	return '<a href="'. h($script.'?'.$r_page) . '#disqus_thread" data-disqus-identifier="'. h($id) .'"></a>';

}

/* End of file disqus.inc.php */
/* Location: /app/haik-contents/plugin/disqus.inc.php */