<head prefix="#{$head_prefix}">
	#{$meta_content_type}
	<title>#{$page_title}</title>
	#{$viewport}
	<meta name="keywords" content="#{$keywords}">
	<meta name="description" content="#{$description}">
	<meta name="author" content="">
	<link rel="alternate" type="application/rss+xml" title="RSS" href="#{$rss_link}">

	#{$bootstrap_css}
	#{$style_css}
	
	#{$head_tag}
	#{$plugin_head}
	#{$user_head}
	
	#{$tracking_script}

</head>

<body>
#{$body_first}
#{$sr_link}

<header>
	<!-- Navbar
	================================================== -->
	<div class="navbar-wrapper content-skin">
		<div class="container">
			<div id="orgm_navbar" class="navbar navbar-inverse">
				<div class="container">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<div id="logo" class="pull-right">#{$logo}</div>
					<nav class="navbar-collapse collapse">
						#{$site_navigator}
					</nav>
				</div>
			</div>
		</div>	
	</div>
	
	<!-- EyeCatch
	================================================== -->
	<div id="orgm_eyecatch">#{$eyecatch}</div>
</header>

<!-- Body
================================================== -->
<div class="container" id="contents">
	<div class="row">
		<div class="col-sm-9 col-sm-push-3 marketing" role="main">
			#{$body}
		</div>
		<div class="col-sm-3 col-sm-pull-9">
			#{$menu}
		</div>
	</div>
</div>

<aside>
	#{$summary}
</aside>


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
<script src="#{$style_path}skin.js"></script>

</body>
</html>