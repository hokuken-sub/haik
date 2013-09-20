<head>
	#{$meta_content_type}
	<title>#{$page_title}</title>
	#{$viewport}
	<meta name="author" content="">
	
	<link rel="alternate" type="application/rss+xml" title="RSS" href="#{$rss_link}">
	
	<!-- Le styles -->
	#{$bootstrap_css}


	#{$style_css}
	
	#{$head_tag}
	#{$plugin_head}
	#{$user_head}
                            
</head>

<body data-spy="scroll" data-target=".bs-docs-sidebar">

#{$body_first}
#{$sr_link}

<!-- Body
================================================== -->
<div class="iframe-container" id="contents">
	<div class="row">
		<div class="col-sm-12 content-wrapper" role="main">
			#{$body}
		</div>
	</div>
</div>

<aside>
	#{$summary}
</aside>


#{$body_last}

<!-- Script
================================================== -->
#{$jquery_script}
#{$bootstrap_script}
#{$common_script}
#{$admin_script}

#{$plugin_script}
#{$user_script}
<script type="text/javascript" src="#{$style_path}skin.js"></script>

</body>
</html>