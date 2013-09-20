<head prefix="#{$head_prefix}">
	#{$meta_content_type}
	<title>#{$page_title}</title>
	#{$viewport}
	<meta name="keywords" content="#{$keywords}">
	<meta name="description" content="#{$description}">
	<link rel="alternate" type="application/rss+xml" title="RSS" href="#{$rss_link}">

	#{$bootstrap_css}
	#{$style_css}
	
	#{$head_tag}
	#{$plugin_head}
	#{$user_head}
	
	#{$tracking_script}

</head>

<body data-spy="scroll" data-target=".bs-docs-sidebar">
#{$body_first}

<!-- Navbar
================================================== -->
<div class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<div class="nav-collapse collapse">
				#{$site_navigator}
			</div>
			<div id="logo" class="pull-right">#{$logo}</div>
		</div>
	</div>
</div>

<!-- EyeCatch
================================================== -->
<div id="orgm_eyecatch">
	#{$eyecatch}
</div>

<!-- Body
================================================== -->
<div class="container" id="contents">
	<div class="row">
		<div class="col-sm-12 marketing">
			#{$body}
		</div>
	</div>
</div>


#{$summary}


<!-- Footer
================================================== -->
<footer class="footer">
	<div class="container">
		#{$site_footer}
	</div>
	<div id="licence" class="container">
		#{$licence_tag}
	</div>
</footer>

#{$admin_nav}

#{$body_last}

<!-- Script
================================================== -->
#{$jquery_script}
#{$bootstrap_script}
#{$common_script}
#{$admin_script}

#{$plugin_script}
#{$user_script}

</body>
</html>