<?php
/**
 *   Haik Installer FTP Form
 *   -------------------------------------------
 *   Template.php
 *
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *
 *   created  : 13/09/26
 *   modified :
 *
 *   Description
 *   
 *   
 *   Usage :
 *   
 */
?>
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html lang="ja" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="ja" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="ja" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="ja" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="ja" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta name="description" content="">
  <meta name="author" content="">
  <title>Haik Installer</title>
  
  <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
  <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <!--[if lt IE 9]>
    <script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

  <style>
	  body {
		background-color: #f3f3f3;
		min-height: 100%;
		height: 100%;
		line-height: 18px;
		font-family: helvetica,arial,sans-serif;
		font-size: 13px;
		font-style: normal;
		font-variant: normal;
		font-weight: normal;
		text-align: left;
		text-decoration: none;
		text-indent: 0;
		text-justify: auto;
		text-outline: none;
		text-overflow: clip;
		text-shadow: none;
		text-transform: none;
		text-wrap: normal;
		margin: 0;
		padding: 0;
		border: 0;
		font: inherit;
		vertical-align: baseline;
	}
	.wrapper {
		background: #f3f3f3;
	}
	
	.container-narrow {
		width: auto;
		border: none;
		background: none;
		z-index: 2;
		margin: 0;
		padding: 0px;
		min-height: 100%;
		height: 100%;
	}

	.heading {
		font-family: caecilia-light,helvetica,arial,sans-serif;
		text-align: center;
		color: #aaaaaa;
		-webkit-font-smoothing: antialiased;
		margin: 64px 0 60px 0;
	}

	.heading .branding {
		background: url("<?php echo $download_files['logo'] ?>") no-repeat center top;
		-webkit-background-size: 50px 50px;
		-moz-background-size: 50px 50px;
		-o-background-size: 50px 50px;
		background-size: 50px 50px;
		height: 50px;
		margin-bottom: 28px;
	}

	.heading h1 {
		font-size: 48px;
		line-height: 52px;
		margin-bottom: 6px;
		color: #747474;
	}
	.heading h2 {
		font-size: 16px;
		font-style: italic;
		color: #747474;
	}

	.container-narrow .content-wrapper {
		position: relative;
		margin-left: auto;
		margin-right: auto;
		padding: 0 10px 0;
	}
	
	.container-narrow .content-wrapper #orgm_body {
		max-width: 400px;
		margin: 0px auto 32px auto;
		border: 1px solid #dedede;
		-webkit-border-radius: 6px;
		-moz-border-radius: 6px;
		border-radius: 6px;
		background-color: #ffffff;
		text-align: left;
		font-size: 13px;
	}
	
	.container-narrow .content-wrapper #orgm_body form{
		margin: 32px;
	}
	
	.btn-primary {
		color: #fff;
		background-color: #747474;
		border-color: #666;
	}
	
	.btn-primary:hover,
	.btn-primary:focus,
	.btn-primary:active,
	.btn-primary.active,
	.open .dropdown-toggle.btn-primary {
		color: #fff;
		background-color: #555;
		border-color: #4a4a4a;
	}

	.btn-primary.disabled,
	.btn-primary[disabled],
	.btn-primary.disabled:hover,
	.btn-primary[disabled]:hover,
	.btn-primary.disabled:focus,
	.btn-primary[disabled]:focus,
	.btn-primary.disabled:active,
	.btn-primary[disabled]:active,
	.btn-primary.disabled.active,
	.btn-primary[disabled].active {
		color: #fff;
		background-color: #747474;
		border-color: #666;
	}
	
	.btn.disabled,
	.btn[disabled]{
		pointer-events: none;
		cursor: not-allowed;
		opacity: .65;
		filter: alpha(opacity=65);
		-webkit-box-shadow: none;
		box-shadow: none;
	}
	
/*
	.container-narrow .content-wrapper {
		min-height: 100%;
	}
	.row .content-wrapper, .content-wrapper.editor-wrapper {
		border-right: 1px solid rgba(0, 0, 0, 0.07);
		border-left: 1px solid rgba(0, 0, 0, 0.07);
		background: none repeat scroll 0 0 #fcfbf6;
		border-radius: 4px;
		min-height: 400px;
		margin-left: 0px;
		height: 100%;
	}
	.container-narrow {
		max-width: 800px;
		min-height: 100%;
		height: 100%;
	}
  	.container-narrow {
		position: fixed;
		width: 640px;
		height: 480px;
		margin-top: -240px;
		top: 50%;
		left: 50%;
		margin-left: -320px;
		min-height: inherit;
		height: auto;
  	}
  	.content-wrapper .page-header {
		margin-top: 20px;
		text-align: center;
		margin-left: auto;
		margin-right: auto;
		width: 100%;
		font-size: 30px;
	}
	div.page-header {
		color: #333;
		font-weight: 300;
		font-size: 27px;
		line-height: 34px;
		display: inline-block;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		margin-top: 50px;
		border-style: none;
	}
	.content-wrapper form {
		width: 250px;
		margin-right: auto;
		margin-left: auto;
	}
*/
/*
	.content-wrapper .breadcrumb>li+li:before {
		content: ">";
	}
	.content-wrapper .breadcrumb {
		border-radius: 0;
		color: #777;
	}
	.content-wrapper .breadcrumb .active{
		color: #333;
		font-weight: bold;
	}
	
	.content-wrapper .step-breadcrumb {
		color: #777;
		text-align: center;
		background-color: #f5f5f5;
		border-radius: 4px;
		padding: 8px 15px;
		margin-bottom: 20px;
	}
	.content-wrapper .step-breadcrumb .active{
		color: #333;
		font-weight: bold;
	}
	.content-wrapper .step-breadcrumb > span+span:before {
		content: ">";
		padding: 0 5px;
		color: #ccc;
	}

	#orgm_body {
		padding-bottom: 10px;
	}
*/

  </style>

</head>
<body class="wrapper">

<div class="container-narrow wrapper" id="contents">
	<div class="heading">
		<div class="branding"></div>
		<h1><?php echo h($title) ?></h1>
	</div>

	<div class="row">
		<div class="col-sm-12 content-wrapper" role="main">
			<!-- BODYCONTENTS START> -->
			<div id="orgm_body">
<!--
				<div class="row">
					<div class="step-breadcrumb">
						<span class="active">Step 1</span>
						<span>Step 2</span>
						<span>Step 3</span>
					</div>
				</div>				
-->

				<div>

					<!--ftp login_form start-->
					<form id="FtpConnectForm" method="post" action="install.php" data-ftp-enabled="<?php echo $installer->local_is_writable ? 0 : 1?>">
					
						<div class="form-group hide">
							<label class="control-label">FTPサーバー</label>
							<div class="row">
								<div class="col-sm-12">
									<input type="text" name="ftp[hostname]" id="ftpHostname" size="30" tabindex="1" maxlength="128" value="<?php echo h($ftp_config['hostname'])?>" id="ftp_hostname" class="form-control" disabled>
								</div>
							</div>
						</div>
						
						<div class="form-group hide">
							<label class="control-label">FTPユーザー (FTPアカウント)</label>
							<div class="row">
								<div class="col-sm-12">
									<input type="text" name="ftp[username]" id="ftpUsername" size="30" tabindex="2" maxlength="128" value="<?php echo h($ftp_config['username'])?>" id="ftp_username"  class="form-control" disabled>
								</div>
							</div>
						</div>
						
						<div class="form-group">
							<label class="control-label">FTPパスワード</label>
							<div class="row">
								<div class="col-sm-12">
									<input type="password" name="ftp[password]" id="ftpPassword" size="30" tabindex="3" value="<?php echo h($ftp_config['password'])?>" id="ftp_password" class="form-control" disabled>
								</div>
							</div>
						</div>
						
						<div class="form-group hide">
							<label class="control-label">設置先フォルダ（フルパス）</label>
							<div class="row">
								<div class="col-sm-12">
									<input type="text" name="ftp[dir]" id="ftpDir" size="30" tabindex="4" value="<?php echo h($ftp_config['dir'])?>" id="install_dir" class="form-control" disabled>
								</div>
							</div>
						</div>
						
						<br />
					
						<input type="hidden" name="mode" value="ftp_connect">
					
						<div class="form-group">
							<div class="row">
								<div class="col-sm-12  text-center">
									<input type="submit" name="ftp_connect" id="ftpConnectSubmit" tabindex="5" value="インストールする" class="btn btn-primary" disabled>
							</div>
						</div>
					</form>
					
					
				</div>
			</div>
			<!-- BODYCONTENTS END> -->
		</div>
		
	</div>
</div>

<script>

// ! background download
$.ajax({
	url: "install.php",
	type: "POST",
	data: {mode: "download"},
	dataType: "json",
	success: function(){
		//TODO: メッセージも出す
		$("input:submit").prop("disabled", false).removeClass("disabled");
	},
	error: function(){
//		console.log(arguments);
	}
});

$("#FtpConnectForm")
.each(function(){
	if ($(this).data("ftpEnabled")) {
		$("input:text,input:password").prop("disabled", false).removeClass("disabled");
	}
	else
	{
		$("input:text,input:password").prop("disabled", true).addClass("disabled").closest(".form-group").hide();
	}
})
.on("submit", function(e){
	e.preventDefault();
	
	var data = $(this).serialize();
	
	$.ajax({
		url: "install.php",
		type: "POST",
		data: data,
		dataType: "json",
		
		beforeSend: function(){
			$("input:text,input:password").prop('disabled', true).addClass("disabled");
		},
		complete: function(){
			$("input:text,input:password").prop('disabled', false).removeClass("disabled");
		},
		
		success: function(res){
			
			if (res.error) {
//				console.log(res.error);
				$('form').find("div.alert").remove();
				$('form').prepend('<div class="alert alert-danger"></div>').find('.alert').text(res.error + " (" + res.errorCode + ")");
				
				if (res.errorCode.substr(0, 3) == '100') {
					$(".form-group.hide").removeClass("hide");
				}
				
				return;
			}
			
			$('form').prepend('<div class="alert alert-success"></div>').find('.alert').text(res.message);
			location.href = res.redirect;
			
		},
		error: function(){
//			console.log(arguments);
		}
		
	});
	
});

</script>

</body>
</html>