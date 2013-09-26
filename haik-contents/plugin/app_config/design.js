$(function(){

	// ! デザイン変更
	var $designerModal = $("#orgm_designer")
	.on("show.bs.modal", function(){

		var tmpl = "#tmpl_conf_style_name";
        $(tmpl).tmpl(ORGM.options).appendTo($("#orgm_designer .modal-body").empty());

	})
	.on("click", "li.orgm-template-item", function(e){

		var	skin = $(".thumbnail_name > span",this).text()
			,item = 'style_name';

		$("input:hidden[name="+item+"]").val(skin);
		$(this).addClass("active").siblings().removeClass("active");

		var data = {
			cmd: 'app_config_design',
			phase: 'get_skin_data',
			style_name: skin
		};
		
		$.post(ORGM.baseUrl, data, function(res){
			if (res.error)
			{
				ORGM.notify(res.error, 'error');
				return false;
			}
			//set skin name
			var $div = $("[data-style_name]", $designerModal);
			var orgSkin = $div.data("style_name")
			  , clsName = "sample-style-name-" + orgSkin;
			$div.attr("data-style-name", skin).data("style_name", skin).removeClass(clsName).addClass("sample-style-name-" + skin);
			$div.find("ul[data-type=style_color]").html(res.color.html);
			$div.find("ul[data-type=style_texture]").html(res.texture.html)
				.find(".sample-style-texture-custom").each(function(){
					var filename, filepath
					  , $input = $("input:hidden[name='style_custom_bg[filename]']")
					  , text = $(this).text();

					 $(this).empty().append('<i class="orgm-icon orgm-icon-wrench"></i>').append('<span class="sr-only"></span>').find('.sr-only').text(text);
					
					if ($input.val().length > 0) {
						filepath = $input.data("filepath");
						$(this).css("background-image", "url("+filepath+")").removeClass("undefined");
					}
					else {
						$(this).css("background-image", "none").addClass("undefined");
					}
				});

			

			//load sample style file
			if (res.sample_style) {
				if (res.sample_style.type == "text/less") {
					var link  = document.createElement('link');
					link.rel  = "stylesheet";
					link.type = res.sample_style.type;
					link.href = res.sample_style.file;
					less.sheets.push(link);
					less.refresh();
				}
				else {
					var link  = document.createElement('link');
					link.rel  = "stylesheet";
					link.type = res.sample_style.type;
					link.href = res.sample_style.file;
					$("body").append(link);
				}
			}

//				ORGM.notify(res.success);
			return false;
			
		}, 'json');
		return false;

	})
	.on("click", "[data-style_name] li.sample-cut", function(){

		var $ul = $(this).closest('ul');
		var value = $(this).children('a').data($ul.data('type'));

		$(this).addClass("active").siblings().removeClass("active");
		$ul.next().val(value);

		return false;

	})
	.on("click", "#preview_skin", function(e){

		e.preventDefault();
		
		var $configbox = $(this).closest('form').parent();
		
		var style_custom_bg = {};
		$("input:hidden[name^=style_custom_bg]", $configbox).each(function(){
			var key, value;
			key = $(this).attr("name").replace(/style_custom_bg\[(.+)\]/, "$1");
			value = $(this).val();
			style_custom_bg[key] = value;
		});
		
		var data = $.extend({}, $(this).data(), {
			cmd : 'app_config_design',
			phase : 'preview_design',
			refer : ORGM.page,
			style_name : $("li.orgm-template-item.active .thumbnail_name > span", $configbox).text(),
			style_color : $("input:hidden[name=style_color]", $configbox).val(),
			style_texture : $("input:hidden[name=style_texture]", $configbox).val(),
			style_custom_bg : style_custom_bg
		});
		
		$.post(ORGM.baseUrl, data, function(res){
			
			if (res.error) {
				ORGM.notify(res.error, 'error');
			}
			if (res.redirect) {
				location.href = res.redirect;
			}
		}, "json");

	})
	.on("click", ".sample-style-texture-custom", function(e){
		
		var $self = $(this);
		//filer 起動
		
		var $filer = $("#orgm_filer_selector");
		
		$filer.find("iframe").data({
			search_word: ":image",
			select_mode: "exclusive"
		});
		$filer
		.on("show.bs.modal", function(){
			$(document).on("selectFiles.design", function(e, selectedFiles){
				if (selectedFiles.length > 0) {
					var fileinfo = selectedFiles[0];
					
					//画像を入れ替え
					$self.css("background-image", "url("+fileinfo.filepath+")")
					.removeClass("undefined");
					
					//input:hidden のデータ入れ替え
					$designerModal.find("input:hidden[name='style_custom_bg[filename]']")
					.val(fileinfo.filename).data("filepath", fileinfo.filepath);
					
					$filer.modal("hide");
				}
			});
		})
		.on("hidden.bs.modal", function(){
			$(document).off("selectFiles.design");
		})
		.data("footer", "")
		.modal();
		
	});


	// set preview value to options
	if (typeof ORGM.previewSkin !== "undefined") {
		ORGM.options = $.extend({}, ORGM.options, ORGM.previewSkin);
	}


});
