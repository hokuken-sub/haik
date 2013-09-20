
! (function($){
	
	$(function(){
	
		$(".setting_list")
		.on("show", "> li", function(e){
//			console.log("show");
		})
		.on("shown", "> li", function(e, $block){

			if ($(".datepicker", $block).length) {
				$(".datepicker").datepicker({language: 'ja'});
			}
			
			$block.find('input:radio, select').each(function(){
				var item = $(this).attr('name');
				if ($(this).is(':radio'))
				{
					$block.find("input:radio[name="+item+"]").val([ORGM.options[item]]);
				}
				
				if ($(this).is('select') && $(this).is(':not[data-selected]'))
				{
					$block.find("select[name="+item+"]").val(ORGM.options[item]);
				}
			});
			
			if ($block.hasClass("config-general-style-name")) {
				$(".orgm-template-item.active", $block).click();
			}
			
			if ($.fn.passwdcheck) {
				$('input[name=new_passwd]', $block).passwdcheck($.extend({}, ORGM.passwdcheck.options, {placeholderClass:"col-sm-3"}));
			}
			
//			console.log("shown");
//			console.log($block);
		})
		.on("hide", "> li", function(e, $block){
//			console.log("hide");
//			console.log($block);

			ORGM.scroll(this);
		})
		.on("hidden", "> li", function(e, $block){
//			console.log("hidden");
//			console.log($block);
			var $li = $(this);
			// ! プレビューの時は、プレビューの解除をする
			if ($block.hasClass('config-general-style-name')) {
				$.post(ORGM.cancelPreviewUrl, {}, function(res){
					$li.find('.current').text(res.value);
				},'json');
				
			}


		})

		.on("submit", "> li", function(e, data){
			var $configbox = $(this).next();

			if (data.error) {
				$configbox.find("input, select, textarea").filter("[name="+data.item+"]")
					.after('<span class="help-block" data-error-message>'+data.error+'</span>')
					.closest(".form-group").addClass("has-error");
				ORGM.notify(data.error, "error");
				
				// パスワードの場合は、新パスワードと確認欄を消す
				if (data.item == 'new_passwd' || data.item == 're_passwd') {
					$configbox.find("input, select, textarea").filter("[name$=_passwd]").val('');
				}

				return false;
			}
			
			// mc_api_key の場合、mc_lists を options へセットする
			if (typeof data.item !== "undefined" && data.item === "mc_api_key") {
				data.options = $.extend(data.options, {mc_lists: data.mc_lists});
			}
			else if (typeof data.item !== "undefined" && data.item === "mc_list_id" && data.mc_list_id.length > 0) {
				$("#mc_form_confirm").filter(".in").collapse("hide");
			}
			


			ORGM.options = $.extend(ORGM.options, data.options);

			$('.current', this).text(data.value);
			$(this).configblock('hide');

			ORGM.notify(data.message);

		})
		.on("submitStart", "> li", function(e){
			var $configbox = $(this).next();
			
			$('.form-group').filter('.has-error').removeClass('has-error')
				.find('[data-error-message]').remove();
		})
		.on("click", "li.orgm-template-item", function(e){
			var $configbox = $(this).closest('form').parent();
			var $li = $configbox.prev();
			var	skin = $(".thumbnail_name > span",this).text()
				,item = $("[data-edit]", $li).data("edit");

			$("input:hidden[name="+item+"]").val(skin);
			$(this).addClass("active").siblings().removeClass("active");

			var data = {
				cmd: 'app_config_general',
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
				var $div = $("[data-style_name]", $configbox);
				var orgSkin = $div.data("style_name")
				  , clsName = "sample-style-name-" + orgSkin;
				$div.attr("data-style-name", skin).data("style_name", skin).removeClass(clsName).addClass("sample-style-name-" + skin);
				$div.find("ul[data-type=style_color]").html(res.color.html);
				$div.find("ul[data-type=style_texture]").html(res.texture.html)
					.find(".sample-style-texture-custom").each(function(){
						var filename, filepath
						  , $input = $("input:hidden[name='style_custom_bg[filename]']");
						
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
				cmd : 'app_config_general',
				phase : 'preview_design',
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
			var $configbox = $(this).closest('form').parent();
			
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
						$configbox.find("input:hidden[name='style_custom_bg[filename]']")
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
			
		})
		.on('click', '[data-image]', function(e){
			e.preventDefault();
			var self = this
				,$filer = $("#orgm_filer_selector")
				,$image = $(this);

			$filer.find("iframe").data({search_word: ":image", select_mode: "exclusive"});

			$filer
			.on("show.bs.modal", function(){
				$(document).on("selectFiles.pluginAppConfigLogImage", function(e, selectedFiles){
					if (selectedFiles.length > 0) {
						$image.html('<img src="'+selectedFiles[0].filepath+'" alt="" /><span class="triangle-btn triangle-right-top red show" data-image-delete><i class="orgm-icon orgm-icon-close"></i></span>');
						$image.data('image', selectedFiles[0].filename).removeClass('img-add');
						$image.next().val(selectedFiles[0].filename);
						$filer.modal("hide");
					}
				});
			})
			.on("hidden.bs.modal", function(){
				$(document).off("selectFiles.pluginAppConfigLogImage");
			});

			$filer.data("footer", "").modal();

		})
		.on('click', '[data-image-delete]', function(e){
			e.preventDefault();
			$image = $(this).parent();
			$image.addClass('img-add').html("クリックで画像選択").next().val("");
			return false;
			
		})
		.on('click', '[data-confirm]', function(e){
			e.preventDefault();

			var mode = $(this).data('confirm');

			if (mode == 'maintenance') {
				
				if (confirm('メンテナンスを実行しますか？')) {
					var data = {
						cmd: 'app_config_system',
						phase: 'exec',
						mode: mode
					};
					
					$.post(ORGM.baseUrl, data, function(res){
						if (res.error)
						{
							ORGM.notify(res.error, 'error');
							return false;
						}
						
						ORGM.notify(res.success);
						return false;
						
					}, 'json');
				}
			}
		});
		
		
	
		$(document).on("click", ".mailcheck", function(){
			
			var username = $(this).closest('.controls').find('input[name=username]').val();
			
			data = {
				cmd: 'app_config_auth',
				phase: 'mailcheck',
				username: username
			};

			$.post(ORGM.baseUrl, data, function(res){
				if (res.error)
				{
					ORGM.notify(res.error, 'error');
					return false;
				}

				ORGM.notify(res.success);
				return false;
			}, 'json');
			
		})
		// MCフォームを更新する
		.on("show.bs.collapse", "#mc_form_confirm", function(e){
		
			var $this = $(this);
			if ($this.data("mc_list_id") === ORGM.options.mc_list.id) return;
		
			var data = {
				cmd: "app_config_marketing",
				phase: "get",
				mc_list_id: ORGM.options.mc_list.id
			};
			$.ajax({
				url: ORGM.baseUrl,
				data: data,
				dataType: "json",
				type: "POST",
				success: function(res){
					$this.html(res[0].html).data("mc_list_id", ORGM.options.mc_list.id);
					
				}
			});
			
			
		});
	
		
		// set preview value to options
		if (typeof ORGM.previewSkin !== "undefined") {
			ORGM.options = $.extend({}, ORGM.options, ORGM.previewSkin);
		}
		// set mc_lists value to options
		if (typeof ORGM.mcLists !== "undefined") {
			ORGM.options = $.extend({}, ORGM.options, {mc_lists: ORGM.mcLists});
		}
		

/*
		ORGM.Facebook.login(function(){
		
			$("#fb_login").addClass("hide");
			$("#fb_auth").removeClass("hide");
		
			$("#fb_group_select").each(function(){
				var $select = $(this);
				FB.api('/me/groups', function(response){
					if (typeof response.data !== "undefined") {
						for (var i = 0; i < response.data.length; i++) {
							var $option = $('<option></option>');
							$option.val(response.data[i].id).text(response.data[i].name);
							$select.append($option);
						}
						$select.val($select.attr("data-default-value"));
					}
				});
			});

		});
		
		$("input:radio[name=qblog_social_widget][value=html]").parent().after($("textarea[name=qblog_social_html]").parent().hide());
		$("input:radio[name=qblog_social_widget]").click(function(){
			var $textarea = $("textarea[name=qblog_social_html]").parent();
			if ($(this).val() == 'html') {
				$textarea.show();
			}
			else {
				$textarea.hide();
			}
		});
*/

	});
	

	
})(window.jQuery);


// !ConfigBlock
!function ($) {

  "use strict"; // jshint ;_;


 /* ConfigBlock CLASS DEFINITION
  * ====================== */

  var ConfigBlock = function(element, options) {
    this.options = options;
    this.$element = $(element);//li element
    this.$element
    .on('click.configblock', '[data-edit]', $.proxy(this.show, this))


  }

  ConfigBlock.prototype = {

      constructor: ConfigBlock

    , toggle: function () {
        return this[!this.isShown ? 'show' : 'hide']()
      }

    , show: function (e) {
    	e && e.preventDefault();
    	
        var that = this
          , e = $.Event('show')
          , $a = this.$element.find("[data-edit]")
          , tmpl = "#tmpl_conf_" + $a.data("edit");

        this.$element.trigger(e);

        if (this.isShown || e.isDefaultPrevented()) return

/*
        var template_data = $.extend({}, ORGM.options);
        if (typeof this.options.btnName != 'undefined') {
	        template_data = $.extend(template_data, {btn_name: this.options.btnName});
        }
        this.$block = $(tmpl).tmpl(template_data);
*/

        this.$block = $(tmpl).tmpl(ORGM.options, {unixtime: function(){return "hoge"}});
        this.$block.insertAfter(this.$element);
        this.$block
        .on('click.dismiss.configblock', '[data-dismiss="configblock"]', $.proxy(this.hide, this))
        .on('submit.configblock', $.proxy(this.submit, this))

        this.isShown = true
        this.$block.addClass('in')
//          .attr('aria-hidden', false)


        this.$element.addClass('in')

        this.$element.trigger("shown", [this.$block]);
      }

    , hide: function (e) {
        e && e.preventDefault()
        
        var that = this

        e = $.Event('hide')

        this.$element.trigger(e, [this.$block])

        if (!this.isShown || e.isDefaultPrevented()) return

        $(document).off('focusin.configblock')

        this.isShown = false
        this.$block
          .removeClass('in')
 //         .attr('aria-hidden', true)

 		this.$element.removeClass("in")

        $.support.transition && this.$block.hasClass('fade') ?
          this.hideWithTransition() :
          this.hideConfig()
      }

    , hideWithTransition: function () {
        var that = this
          , timeout = setTimeout(function () {
              that.$block.off($.support.transition.end)
              that.hideConfig()
            }, 500)

        this.$block.one($.support.transition.end, function () {
          clearTimeout(timeout)
          that.hideConfig()
        })
      }

    , hideConfig: function (that) {
        this.$block
          .hide().remove()
        this.$element
          .trigger('hidden', [this.$block])

      }
    , submit: function (e) {
    
	    e && e.preventDefault()
	    
	    var data = this.$block.find("form").serialize()
	      , that = this
	      
	    this.$element.trigger("submitStart");
	    
	    $.ajax({
		    url: ORGM.baseUrl,
		    type: "POST",
		    data: data,
		    dataType: "json",
		    success: function(res){
			    that.$element.trigger("submit", [res])
		    }
	    });
/*
	    $.post(ORGM.baseUrl, data, function(res){
		    that.$element.trigger("submit", [res])
	    }, "json")
*/
    }

  }


 /* MODAL PLUGIN DEFINITION
  * ======================= */

  $.fn.configblock = function (option) {
    return this.each(function () {
      var $this = $(this)
        , data = $this.data('configblock')
        , options = $.extend({}, $.fn.configblock.defaults, $this.data(), typeof option == 'object' && option)
      if (!data) $this.data('configblock', (data = new ConfigBlock(this, options)))
      if (typeof option == 'string') data[option]()
      else if (options.show) data.show()
    })
  }

  $.fn.configblock.defaults = {
    show: false
  }

  $.fn.configblock.Constructor = ConfigBlock;


 /* MODAL DATA-API
  * ============== */

  $(function(){
  	$(".setting_list > li").each(function(){$(this).configblock();});

	if (location.hash && $("[data-edit='"+location.hash.substr(1)+"']").length > 0) {
		$("[data-edit='"+location.hash.substr(1)+"']").click();
	}
	

  })

}(window.jQuery);