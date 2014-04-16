! (function($){
	
	$(function(){

		var updated = false;
		//画面遷移時に変更を破棄するか確認
		if (ORGM.options.unload_confirm) {
			$("[data-edit-type]").click(function(){
				$(window).off(".orgm");
			});
		
			$(window).on("beforeunload.orgm", function(e) {
				if (updated) {
					return 'このページから移動すると、変更した内容は保存されません。';
				}
			});
		}

		// !ナビの置換え
		$("#admin_nav > .container").replaceWith($("#orgm_eyecatch_nav"));
		
		
		// !編集
		var $editModal = $("#orgm_eyecatch_edit")
		.on('show.bs.modal', function(){
			var index = 0;
			var data = {
				id: '',
				image:'',
				title: '',
				titleColor: '',
				titleSize: '',
				content:'',
				contentColor: '',
				contentSize: '',
				mode: 'edit'
			}

			if ($editModal.data('mode') == 'add') {
				// 追加時は何もしない
				data.mode = 'add';
				data.id = ORGM.eyecatch.images.length;
			}
			else {
				index = $(this).data('id');
				data = $.extend(data, ORGM.eyecatch.images[index]);
				if (typeof data === "undefined") {
					return false;
				}
				data.id = index;
			}
			// 表示
			$('#tmpl_eyecatch_edit_body').tmpl(data).appendTo($('#orgm_eyecatch_edit .modal-content').empty()).find('textarea').exnote({css:{height:"2em"}, agWrapperClass:"exnote-agwrapper"});

			$("input:text[name$=color]", $editModal).colorpalette();

			
		})
		.on("shown.bs.modal", function(){
			//backdrop を下のモーダルより上に
 			$(this).data("bs.modal").$backdrop.css("z-index", 1059);

			$editModal.find("input:text:first").focus().select();
		})
		.on("hide.bs.modal", function(){
			$editModal.data("mode", "");
		})
		.on('click', '[data-save]', function(){
			var data = {};
			$editModal.find('input,textarea').each(function(){
				var name = $(this).attr('name');
				data[name] = $(this).val();
			});

			// アイキャッチを変更
			ORGM.eyecatch.images[data.id] = data;

			$.post(ORGM.eyecatch.previewUrl, ORGM.eyecatch, function(res){
				if (res.error) {
					return false;
				}
				// アイキャッチを変更
				$('#orgm_eyecatch').html(res.eyecatch).find('.carousel').carousel(parseInt(data.id, 10)).carousel('cycle').carousel('pause');
				ORGM.eyecatch.images[data.id] = $.extend(ORGM.eyecatch.images[data.id], res.images[data.id]);

				// アイキャッチのエディタ制御
				$(document).trigger("bgUpdate");
				
			}, 'json');
			
			$editModal.modal('hide');
			$manageModal.modal('hide');

		}).on('click', '[data-image-update]', function(e){
			e.preventDefault();
			$("#orgm_filer_selector").modal().data('context', this);

		})
		.on("click", "[data-image-delete]", function(){
			var $self = $(this);

			if ( ! confirm('この画像を削除してもよろしいですか？')) {
				return false;
			}

			$editModal.find("input:hidden[name=image]").val("");
			$(this).prev().attr('src', "").closest("div").addClass("hide").next().removeClass("image-hide");

			return false;
		});
		
		// !削除
		var $deleteModal = $("#orgm_eyecatch_delete")
		.on('show.bs.modal', function(){
			
			var index = 0;
			if ($("#orgm_eyecatch .carousel").length) {
				$("#orgm_eyecatch .carousel .item").each(function(i){
					if ($(this).hasClass('active')) {
						index = i;
						return false;
					}
				});
			}
			$deleteModal.find('input:hidden[name=id]').val(index);
		})
		.on("shown.bs.modal", function(){
			//backdrop を下のモーダルより上に
			$(this).data("bs.modal").$backdrop.css("z-index", 1059);
		})
		.on('click', '[data-delete]', function(){
			var data = {};
			$deleteModal.find('input').each(function(){
				var name = $(this).attr('name');
				data[name] = $(this).val();
			});
			
			// アイキャッチを変更
			ORGM.eyecatch.images.splice(data.id, 1);

			$.post(ORGM.eyecatch.previewUrl, ORGM.eyecatch, function(res){
				if (res.error) {
					return false;
				}
	
				// アイキャッチを変更
				$('#orgm_eyecatch').html(res.eyecatch);
				
				if (ORGM.eyecatch.images.length == 0 && $manageModal.hasClass("in")) {
					$manageModal.modal('hide');
				}

				$(document).trigger("bgUpdate");
				
			}, 'json');
			
			$deleteModal.modal('hide');
			
			if ($manageModal.is(".in")) {
				$(".orgm-eyecatch-pane[data-id="+data.id+"]").remove();
			}

		});
	
		// !スライドの管理
		var $manageModal = $("#orgm_eyecatch_manage")
		.on('show.bs.modal', function(){
			data = ORGM.eyecatch.images;
			
			for (var i = 0; i < data.length; i++) {
				data[i].id = i;
			}

			// 表示
			$('#tmpl_eyecatch_manage_body').tmpl(data).appendTo($('#orgm_eyecatch_manage .modal-body ul').empty());

			var $ul = $(".modal-body ul", $manageModal).sortable({
				axis: "y",
				opacity: 0.5,
				scroll: true,
				containment: "parent",
				tolerance: "pointer",
				zIndex: 100,
				start: function(e, obj){
				},
				update: function(e, obj){
					var $ul = $(".modal-body ul", $manageModal);
					var order = $ul.children().map(function(i){
						return $(this).find("[data-id]").data("id");
					}).toArray();
		
					var newOrder = [];
					
					for (var i = 0; i < order.length; i++) {
						newOrder.push(ORGM.eyecatch.images[order[i]]);
					}
					
					ORGM.eyecatch.images = newOrder;
					
					$(".orgm-eyecatch-pane", $manageModal).each(function(i){
						$(this).attr("data-id", i).data("id", i);
					});
					
					$.post(ORGM.eyecatch.previewUrl, ORGM.eyecatch, function(res){
						if (res.error) {
							return false;
						}
			
						// アイキャッチを変更
						$('#orgm_eyecatch').html(res.eyecatch);
						
						$(document).trigger("bgUpdate");
						
						
						
					}, "json");

					
				}
			});


			
		})
		.on("hide.bs.modal", function(){
			$(".modal-body ul", $manageModal).sortable('destroy');
		})
		.on("click", "button[data-target]", function(e){
			var id = $(this).closest(".orgm-eyecatch-pane").data("id");
			$editModal.data('id', id);
/* 			$('#orgm_eyecatch').find('.carousel').carousel(parseInt(id, 10)).carousel('cycle').carousel('pause'); */
		});




		// ! 背景設定
		var $backgroundModal = $("#orgm_eyecatch_background")
		.on("show.bs.modal", function(){

			$("#tmpl_eyecatch_background_body").tmpl(ORGM.eyecatch,
			{hasBgImage: function(background){
				if ($.isPlainObject(background)) {
					if (background.image != "none")
						return true;
				}
				return false;
			}}).replaceAll("#orgm_eyecatch_background .modal-body");

			var value = '';
			if (typeof ORGM.eyecatch.background.size == 'undefined') {
				value = ORGM.eyecatch.background.repeat;
			}
			else {
				value = ORGM.eyecatch.background.size;
			}
			
			value = (!value) ? 'cover' : value;
			
			$("select[name=repeat]", $backgroundModal).val(value);
			
			
			
			// 背景色のパレットを表示
			$("input:text[name=color]", $backgroundModal).colorpalette();
			
			// 高さをplaceholderに入れる
			if ( ! ORGM.eyecatch.height.length) {
				var height = $("#orgm_eyecatch").height();
				$("input:text[name=height]", $backgroundModal).attr('placeholder', height);
			}
		})
		.on("click", "[data-save],[data-reset]", function(e){
			var $modal = $(this).closest('#orgm_eyecatch_background')
				,reset = $(this).is('[data-reset]')
				,data;
			
			if (reset) {
				ORGM.eyecatch.background = false;
				ORGM.eyecatch.height = '';
				$modal.trigger("bgUpdate").modal("hide");
			}
			else {
				data = $modal.find('input, select').serializeArray();
				
				var bg = {};
				var height = '';
				for (var i = 0; i < data.length; i++) {
					if (data[i].name === "repeat" && data[i].value.match(/(contain|cover)/)) {
						data[i].name = "size";
						data[i].value = RegExp.$1;
						bg.repeat = "repeat";
						
						bg[data[i].name] = data[i].value;
					}
					else if (data[i].name === "image" && data[i].value.length === 0) {
						data[i].value = "none";
					}
					
					if (data[i].name === "height") {
						if (/^([0-9]+)(px)?$/.test(data[i].value)) {
							height = parseInt(RegExp.$1, 10);
						}
					}
					else {
						bg[data[i].name] = data[i].value;
					}

				}
				
				ORGM.eyecatch.background = bg;
				ORGM.eyecatch.height = height;

				$modal.trigger("bgUpdate");
				$modal.modal("hide");
			}
			

			return false;
	
		})
		.on('click', '[data-image-update]', function(e){
			e.preventDefault();
			$("#orgm_filer_selector").modal().data('context', this);

		})
		.on("click", "[data-image-delete]", function(){
			var $self = $(this);

			if ( ! confirm('この画像を削除してもよろしいですか？')) {
				return false;
			}

			$backgroundModal.find("input:hidden[name=image]").val("");
			$(this).prev().attr('src', "").closest("div").addClass("hide").next().removeClass("hide");

			return false;
		});


		// !document
		$(document)
		.on("selectFiles", function(e, files){
			var $self = $($('#orgm_filer_selector').data('context'));

			if ($self.is('[data-image-update]'))
			{
				$modal = $editModal;
				if ($self.hasClass('img-background')) {
					// 背景設定
					$modal = $backgroundModal;
				}

				$modal.find("input:hidden[name=image]").val(files[0].filepath);
				if ($self.hasClass('img-add')) {
					$self.parent().addClass('image-hide').prev().removeClass('image-hide').find("img").attr('src', files[0].filepath);
				}
				else {
					$self.find("img").attr('src', files[0].filepath);
				}
			}

			$('#orgm_filer_selector').modal('hide')

		})
		.on("bgUpdate", function(e){
			
			var $elem = $("#orgm_eyecatch >");

			updated = true;

			switchEyecatchPanel();

			
			if ( ! $.isPlainObject(ORGM.eyecatch.background)) {
				$elem.removeAttr("style");
				$("#orgm_eyecatch_style").remove();
				return;
			}

			$elem
			.css({
				background: "none",
				backgroundColor: "transparent",
				backgroundImage: "none",
				backgroundRepeat: "no-repeat",
				backgroundSize: "auto",
				backgroundPosition: "0 0"
			});
			
			var value;
			for (var key in ORGM.eyecatch.background) {
				if (key === 'image' && ! ORGM.eyecatch.background[key].match(/^(?:none)?$/)) {
					value = $.tmpl('url("${value}")', {value:ORGM.eyecatch.background[key]}).text();
				}
				else {
					value = ORGM.eyecatch.background[key];
				}
				$elem.css("background-" + key, value);
			}
			
			// ! 高さ
			$elem = $("#orgm_eyecatch .carousel .item");
			if (ORGM.eyecatch.height) {
				$elem.height(ORGM.eyecatch.height)
			}
			else {
				$elem.height(ORGM.eyecatch.orgHeight)
			}
		})
		.on("click", "[data-edit-type]", function(e){
			e.preventDefault();
			
			var $self = $(this);
			var write = $self.is("[data-edit-type=write]");
			
			if (write) {
				
			    if (typeof ORGM.loadingIndicator !== "undefined") {
			        ORGM.loadingIndicator = true;
			    }
				
				$.post(ORGM.eyecatch.updateUrl, ORGM.eyecatch, function(res){
					
					if (res.error) {
						//error
					    if (typeof ORGM.loadingIndicator !== "undefined") {
					        ORGM.loadingIndicator = false;
					    }
					}
					
					location.href=ORGM.baseUrl + "?" + encodeURIComponent(ORGM.page);
					
				}, 'json');
				
			}
			else {
				location.href=ORGM.baseUrl + "?" + encodeURIComponent(ORGM.page);
			}
		})
		.on("click","[data-add]", function(){
			$editModal.data('mode', 'add').modal();
			return false;
		})

		// !carousel
		var carouselOption = {pause:"false", interval: false};
		$('#orgm_eyecatch').find('.carousel').carousel(carouselOption)
		.end().find('[data-slide]').data(carouselOption);

		// !ナビクリックの封印
		$("#haik_nav,#logo,#license").on("click", "a", function(e){e.preventDefault()});
		$("#orgm_eyecatch_controls .navbar-brand").on("click", function(e){e.preventDefault()});


		function switchEyecatchPanel()
		{
			// !1枚もない時
			if (ORGM.eyecatch.images.length == 0) {
				$("#tmpl_eyecatch_empty").tmpl({}).appendTo($("#orgm_eyecatch").empty());
				$(".orgm-eyecatch-controls").hide();
			}
			else {
				$(".orgm-eyecatch-controls").show();
			}
		}

		switchEyecatchPanel();


		// ナビ移動の際に変な表示になるのに対処
/* 		$(".orgm-eyecatch-controls").closest(".container").show(); */

		
		$("#orgm_eyecatch_editor")
		.on("click", "[data-edit], [data-save], [data-cancel]", function(e){
			var $self = $(this)
			  , $pane = $self.closest(".orgm-eyecatch-pane");

			var edit = $self.is("[data-edit]"),
				cancel = $self.is("[data-cancel]");
			var data = {
				'content':$pane.find('input, textarea').val(),
				'id': $pane.data('id')
			};
			
			if ( ! edit){
				$pane.find(".orgm-eyecatch-edit").hide();
				$pane.find(".orgm-eyecatch-content").show();
				
				$pane.find("[data-edit]").show();
				$pane.find("[data-save], [data-cancel]").hide();


				if ( ! cancel) {
					$.post(ORGM.eyecatch.updateUrl, data, function(res){
						if (res.error) {
							return false;						
						}
	
						// アイキャッチを変更
						$('#orgm_eyecatch').html(res.eyecatch);
	
						// パネルを更新
						$('#tmpl_eyecatch_pane').tmpl(res.image).find(".orgm-eyecatch-pane").replaceAll('[data-id='+res.image.id+']')
							.find('textarea').exnote({css:{height:"2em"}, agWrapperClass:"exnote-agwrapper"});
						
						$("[data-id="+res.image.id+"]").parent().removeAttr("data-draft");

						if ($("[data-draft]").length === 0 || $(".orgm-eyecatch-pane").not("[data-draft]").length > 1) {
							$ul.sortable("enable");
							$(".dragmark").show();
						}
	
					}, 'json');
				}
			}
			else
			{
				$pane.find(".orgm-eyecatch-edit").show();
				$pane.find("textarea").focus().select();
				$pane.find(".orgm-eyecatch-content").hide();
				
				$pane.find("[data-save], [data-cancel]").show();
				$self.hide();

				$ul.sortable("disable");
				$(".dragmark").hide();
			}

			$pane.find(".orgm-eyecatch-edit")[edit? "show" : "hide"]();
			$pane.find(".orgm-eyecatch-content")[edit? "hide" : "show"]();
			
			$pane.find(edit ? "[data-save]" : "[data-edit]").show();
			$self.hide();
		})
		.on("dblclick", ".orgm-eyecatch-content", function(){
			var $self = $(this)
			  , $pane = $self.closest(".orgm-eyecatch-pane");

			$("[data-edit]", $pane).click();
			return false;
		})
		.on("click", "[data-delete]", function(e){
			var $self = $(this)
			  , $pane = $self.prev();
			
			if ( ! confirm('このパネルを削除してもよろしいですか？')) {
				return false;
			}
			
			if ($pane.parent().is("[data-draft]")) {
				$pane.parent().remove();
				return false;
			}
			 
			var data = {id: $pane.data('id')};
			$.post(ORGM.eyecatch.deleteUrl, data, function(res){
				if (res.error) {
					return false;						
				}
				
				// アイキャッチを変更
				$('#orgm_eyecatch').html(res.eyecatch);

				// パネルを更新
				$pane.parent().remove();
				
				// IDのふり直し
				$('.orgm-eyecatch-pane').each(function(i){
					$(this).data('id', i);
					$(this).attr('data-id', i);
				});

			}, 'json');

			return false;
  
		})
		.on("click", "[data-image-delete]", function(){
			var $self = $(this)
			  , $pane = $self.closest(".orgm-eyecatch-pane");

			if ( ! confirm('この画像を削除してもよろしいですか？')) {
				return false;
			}

			var data = {
				id: $pane.data('id'),
				image: ''
			};
			
			$.post(ORGM.eyecatch.updateUrl, data, function(res){
				if (res.error) {
					return false;
				}

				// アイキャッチを変更
				$('#orgm_eyecatch').html(res.eyecatch);

				// パネルを更新
				$('#tmpl_eyecatch_pane').tmpl(res.image).find(".orgm-eyecatch-pane").replaceAll('[data-id='+res.image.id+']').find('textarea').exnote({css:{height:"2em"}, agWrapperClass:"exnote-agwrapper"});

			}, 'json');
			
			return false;
			  
		})
		.on("click", "[data-image-update]", function(e){

			e.preventDefault();

			$("#orgm_filer_selector").modal().data('context', this);
			
		})
		.on("click", "[data-add]", function(e){
			var $self = $(this)
			  , $pane = $self.closest(".orgm-eyecatch-pane");
		
			// パネルを更新
			var id = $(".orgm-eyecatch-pane").length;
			var data = {
				id: id,
				content: 'TITLE:タイトル',
				image: ''
			};
			
			$('#tmpl_eyecatch_pane').tmpl(data).attr("data-draft", 1).appendTo("#orgm_eyecatch_editor ul.panes")
				.find('textarea').exnote({css:{height:"2em"},agWrapperClass:"exnote-agwrapper"}).end().find("[data-edit]").click();
			
			$ul.sortable("disable");
			$(".dragmark").hide();
		
			return false;
		});
		
		// !編集ナビの位置を調節する
		var navbarAdjust = function(){
			if ($(window).height() > ORGM.navbarHTotal + $("#orgm_eyecatch").height() + $("#orgm_eyecatch_controls").height()) {
				$("#orgm_eyecatch_controls").removeClass("navbar-fixed-bottom");
			}
			else {
				$("#orgm_eyecatch_controls").addClass("navbar-fixed-bottom");
			}
		};
		$(window).on("resize", navbarAdjust);
		navbarAdjust();
		
	});
	
	
})(window.jQuery);