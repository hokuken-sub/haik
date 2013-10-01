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

	.heading .branding img{
		max-height: 50px;
		max-width: 50px;
	}

	.heading .branding {
/*
		background: url("<?php echo $download_files['logo'] ?>") no-repeat center top;
		-webkit-background-size: 50px 50px;
		-moz-background-size: 50px 50px;
		-o-background-size: 50px 50px;
		background-size: 50px 50px;
		height: 50px;
		margin-bottom: 28px;
*/
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
  </style>

</head>
<body class="wrapper">

<div class="container-narrow wrapper" id="contents">
	<div class="heading">
		<div class="branding">
			<img id="icon_here">
			<script>
				var data = 'data:image/jpeg;base64,'+
				    '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'+
				    '81FR6EbkVH+2/wD/2Q==';
				var icon_elem = document.getElementById("icon_here");
				icon_elem.src = data;
		    </script>
		</div>
		<h1><?php echo h($title) ?></h1>
		<h2><?php echo h($subtitle) ?></h2>
	</div>

	<div class="row">
		<div class="col-sm-12 content-wrapper" role="main">
			<!-- BODYCONTENTS START> -->
			<div id="orgm_body">
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