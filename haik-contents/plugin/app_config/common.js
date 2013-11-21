
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
			
			if ($.fn.passwdcheck) {
				$('input[name=new_passwd]', $block).passwdcheck($.extend({}, ORGM.passwdcheck.options, {placeholderClass:"col-sm-3"}));
			}
			
			$("[data-exnote=onshown]", $block).exnote();
			
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
		})

		.on("submit", "> li", function(e, data){
			var $configbox = $(this).next()
			  , callback = function(){};

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
			
			if (typeof data.item !== "undefined") {

				// mc_api_key の場合、mc_lists を options へセットする
				if (data.item === "mc_api_key") {
					data.options = $.extend(data.options, {mc_lists: data.mc_lists});
				}
				else if (data.item === "mc_list_id" && data.mc_list_id.length > 0) {
					$("#mc_form_confirm").filter(".in").collapse("hide");
				}
				// passwd 変更した場合、ログアウト状態になるので、
				// redirect_to へ転送する
				else if (data.item === "passwd" && typeof data.redirect_to !== "undefinded") {
					
					callback = function(){
						location.href = data.redirect_to;
					};
				}
				
			}
			


			ORGM.options = $.extend(ORGM.options, data.options);

			$('.current', this).text(data.value);
			$(this).configblock('hide');

			ORGM.notify(data.message, "success", callback);

		})
		.on("submitStart", "> li", function(e){
			var $configbox = $(this).next();
			
			$('.form-group').filter('.has-error').removeClass('has-error')
				.find('[data-error-message]').remove();
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
	
		// set mc_lists value to options
		if (typeof ORGM.mcLists !== "undefined") {
			ORGM.options = $.extend({}, ORGM.options, {mc_lists: ORGM.mcLists});
		}

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