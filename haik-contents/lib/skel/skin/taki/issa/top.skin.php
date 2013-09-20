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

<div id="orgm_container">

	<header>

		<!-- Navbar
		================================================== -->
		<div id="orgm_navbar" class="navbar navbar-inverse navbar-fixed-top">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<div id="logo" class="pull-right">#{$logo}</div>
				</div>
				<nav class="navbar-collapse collapse">
					#{$site_navigator}
				</nav>
			</div>
		</div>
	
		<!-- EyeCatch
		================================================== -->
		<div id="orgm_eyecatch" class="">#{$eyecatch}</div>

	</header>

	<!-- Body
	================================================== -->
	<div id="contents" class="container">
		<div class="row">
			<div class="col-sm-12 marketing" role="main">
				#{$body}
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
			<div>
				#{$site_footer}
			</div>

			<div id="licence">
				#{$licence_tag}
			</div>
		</div>
	</footer>

</div>



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