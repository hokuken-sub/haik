$(function () {


    $('#fileupload').fileupload({
    	url: ORGM.filer.postUrl,
        dataType: 'json',
        dropZone: '#contents',
        add: function(e, data){
		    if (typeof ORGM.loadingIndicator !== "undefined") {
		        ORGM.loadingIndicator = true;
		    }

	        data.submit();

        },
        fail: function(e){
        },
        done: function (e, data) {
		    if (typeof ORGM.loadingIndicator !== "undefined") {
		        ORGM.loadingIndicator = false;
		    }

			if (data.result.error) {
				ORGM.notify(data.result.error, "danger");
				return;
			}

			var method, target;
		    // 既に同じ日付ヘッダーがある場合は、ヘッダーを削除
		    if ($('#orgm_filer_list li:first-child').data('date-title') == data.result[0].yearmonth) {
				    data.result[0].yearmonth = '';
				    method = "insertAfter";
				    target = '#orgm_filer_list li:first-child';
			}
			else {
				method = "prependTo";
				target = "#orgm_filer_list";
			}

		    $('#tmpl_filelist').tmpl(data.result, {'json':function(){
				return JSON.stringify(this.data);
			}})[method](target).filter("[data-id]").addClass('new');
			
			//選択した状態にする
			if (ORGM.filer.iframe && window.parent !== window
			&& ! ORGM.filer.fullscreen) {
				_.forEach(data.result, function(file, i){
					$("[data-id="+file.id+"] a").trigger("mouseenter").trigger("click").trigger("mouseleave");
				});
			}


        }
    });
    
	$(document).on('dragover', function (e) {
	    var dropZone = $('#contents'),
	        timeout = window.dropZoneTimeout;
	    if (!timeout) {
	        dropZone.addClass('in');
	        
	        var lineHeight = $(".filer-list-wrapper").height().toString();
	        if (lineHeight < 120) lineHeight = 120;
	        $("#fileupload_trigger").css({"line-height": lineHeight + "px"});
	    } else {
	        clearTimeout(timeout);
	    }
	    if (e.target === dropZone[0]) {
	        dropZone.addClass('hover');
	    } else {
	        dropZone.removeClass('hover');
	    }
	    window.dropZoneTimeout = setTimeout(function () {
	        window.dropZoneTimeout = null;
	        dropZone.removeClass('in hover');

	        $("#fileupload_trigger").css("line-height", "");
	    }, 100);
	});    
    $("#fileupload_trigger").on("click", function(){
	    $("#fileupload").click();
    });
    
	$("#orgm_filer_list")
    // ! show file detail
	.on("click", "a[data-filer]", function(e){
		e.preventDefault();
		
		var filergrid = $(this).data("filer-grid");
		
		// iframe 内では編集はさせず、チェックさせる
		if ( ! ORGM.filer.fullscreen && ORGM.filer.iframe) {

			e.stopPropagation();
			e.stopImmediatePropagation();
			

			var $self = $(this),
				$check = $self.find("span.check"),
				$li = $self.parent();
			
			$li.toggleClass("checked");
			$check.toggleClass("active");
			
			if (ORGM.filer.selectExclusive) {
				$li.siblings(".checked").removeClass("checked").find("span.check").removeClass("active");
			}
			
			var files = $("#orgm_filer_list li.checked").map(function(){
				return $(this).children("a").data("filer");
			}).toArray();
			
			if ( ! ORGM.filer.fullscreen && ORGM.filer.iframe && window.parent !== window) {
				window.parent.ORGM.getSelectedFiles(files);
			}

			return;
		}



	})
	.on('mouseenter', "a[data-filer]", function(){
		var selector = "span.star", $a = $(this);
		if (ORGM.filer.checkable) {
			selector += ", span.check";
		}
		if (ORGM.filer.deletable) {
			selector += ", span.delete";
		}
		
		$a.find(selector).show();

		//popover
		if ( ! ORGM.filer.fullscreen && ORGM.filer.iframe && window.parent !== window && typeof $a.data("popover-init") === "undefined") {
			var $li = $a.closest("li")
			  , file = $a.data("filer")
			  , content = $("#tmpl_filer_popover").tmpl(file);
			$a.popover({
				animation: false,
				placement: "right",
				trigger: "hover",
				title: file.title,
				html : true,
				content: content
			}).popover("show").data("popover-init", true)
		}


	})
	.on('mouseleave', "a[data-filer]", function(){
		$(this).find("span.delete, span.star:not(.active), span.check:not(.active)").hide();

	})
	.on('click', 'span.delete', function(e){

		e.preventDefault();
		e.stopPropagation();

		if ( ! ORGM.filer.deletable) return;
		
		if ( ! confirm("ファイルを削除してもよろしいですか？")) return false;

		var data = $(this).parent().data('filer'),
			id = data.id;
		
		$.post(ORGM.filer.deleteUrl, data, function(res){
			if (res.error) {
				ORGM.notify(res.message, "error");
				return;
			}
			
			ORGM.notify(res.message);
			$("[data-id="+id+"]").remove();
			
		}, "json");
		
		return false;
	})
	.on("click", "span.star", function(e){
		e.preventDefault();
		e.stopPropagation();
		
		var $self = $(this);
		$self.toggleClass("active")
			.find("i").toggleClass("orgm-icon-star-2").toggleClass("orgm-icon-star");
		
		var data = $self.parent().data("filer");
		
		data.star = parseInt(data.star, 10) ? 0 : 1;
		data.overwrite = 1;
		
		$.post(ORGM.filer.updateUrl, data, function(res){
			
			if (typeof res.error !== "undefined") {
				$self.toggleClass("active")
					.find("i").toggleClass("orgm-icon-star-2").toggleClass("orgm-icon-star");
			}
			
		}, "json");

	})
	.on("shown.filergrid", "a.filer-grid", function(e){
		var $a = $(this);
		var fileinfo = $a.data("filer"),
			filergrid = $a.data("filer-grid");

		$("img.preview", filergrid.$expander).each(function(){//debug
			var $self = $(this);
			
			var boxWidth = Math.floor($self.closest(".file-preview").width() * 0.9)
			  , boxHeight = filergrid.detailHeight - 100;//margin * 2
			
			$self.Jcrop({
				boxWidth: boxWidth,
				boxHeight: boxHeight,
				width: fileinfo.width,
				height: fileinfo.height,
				trueSize: [fileinfo.width, fileinfo.height],
				fixedSupport: false,
				onSelect: function(c){
					$("#imagecrop").each(function(){
						$("input[name^=crop]").each(function(){
							if ($(this).attr("name").match(/crop\[(.*?)\]/)) {
								$(this).val(c[RegExp.$1]);
							}
						});

						$('.cropsize', this).text(Math.round(c.w) + ' x ' + Math.round(c.h));
						
						$("form", this).removeClass("hide");

					});
					
				},
				onRelease: function(){
					$a.trigger("revert");
					$("#imagecrop form").addClass("hide");
				}
			}, function(){
				this.disable();
				$a.data("Jcrop", this);
				
				if ($('#edit').is(':visible')) {
					// ! Jcropの再起動
					$('a[href=#info]', filergrid.$expander).click();
					$('a[href=#edit]', filergrid.$expander).click();
				}
				
				$(".file-loading", filergrid.$expander).addClass("hide");
			});

		});		

		// Prev Nextボタンの表示
		if (! $(this).closest(".filer-grid-item").nextAll(".filer-grid-item").length)
		{
			filergrid.$expander.find('a[data-slide=next]').hide();
		}
		if (! $(this).closest(".filer-grid-item").prevAll(".filer-grid-item").length)
		{
			filergrid.$expander.find('a[data-slide=prev]').hide();
		}



	})
	.on("revert", function(e){
		if ($(e.target).is(".field-edit-pane")) {
			$("[data-revert]", e.target).each(function(){
				$(this).val($(this).data("revert"));
			});
		}
	})
	.on("click", "[data-toggle=edit-pane]", function(e){
		
		var $parent = $(this).closest(".field-editable")
		  , filergrid = $(this).closest(".filer-grid-item").children(".filer-grid").data("filer-grid")
		  , $target = $parent.next();
		
		$parent.hide();
		$target.show().find("input:text:first").focus().select();
		
		
		filergrid.$expander.trigger("imageset");
		
	})
	.on("click", "[data-dismiss=field-edit-pane]", function(e){
		var $editPane = $(this).closest(".field-edit-pane"),
			filergrid = $(this).closest(".filer-grid-item").children(".filer-grid").data("filer-grid")
			$viewPane = $editPane.prev();
		$editPane.hide();
		$viewPane.show();

		$editPane.trigger("revert");

		filergrid.$expander.trigger("imageset");

	})
	.on("click", "[data-download]", function(e){
		e.preventDefault();
		var $a = $(this).closest(".filer-grid-item").children(".filer-grid");
		
		var fileinfo = $a.data("filer");
		var downloadUrl = ORGM.filer.downloadUrl + encodeURIComponent(fileinfo.filename);
		
		location.href = downloadUrl;
		return false;

	})
	.on("change blur", ".imagesize", function(){

		if ( ! $('#keep_ratio').is(':checked')) {
			return;
		}

		var filergrid = $(this).closest(".filer-grid-item").children(".filer-grid").data("filer-grid");

		var $self = $(this)
			, fileInfo = filergrid.$element.data("filer")
			, orgWidth = fileInfo.width
			, orgHeight = fileInfo.height
			, Jcrop = filergrid.$element.data("Jcrop");

		
		var width, height, scale;
		
		if ($self.attr("name") === "width") {
			width = $self.val();
			scale = width / orgWidth;
			height = Math.floor(orgHeight * scale);
			height = Math.round(orgHeight * scale);
			$("#imagesize input[name=height]").val(height);
		}
		else {
			height = $self.val();
			scale = height / orgHeight;
			width = Math.round(orgWidth * scale);
			$("#imagesize input[name=width]").val(width);
		}
		
	})
	// !slide button
	.on('click', 'a[data-slide]',function(e){
		e.preventDefault();
		
		var id = $('#orgm_filer_carousel').data('id');
		var $li = $('#orgm_filer_list').find('[data-id='+id+']');

		if ($(this).data('slide') == 'prev') {
			$li.prevAll("[data-id]").filter(":first").find('a').click();
		}
		else {
			$li.nextAll("[data-id]").filter(":first").find('a').click();
		}
	})
	.on("keydown", "input", function(e){
		e.stopPropagation();
	})
	.on("click", "input.copyfield", function(e){
		$(this).focus().select();
		e.preventDefault();
	})
	.on("change", "input:radio[name=ratio]", function(e){
		var ratio = parseFloat($(this).val())
		  , filergrid = $(this).closest(".filer-grid-item").children(".filer-grid").data("filer-grid");
		  
		var Jcrop = filergrid.$element.data("Jcrop");
		if ( ! Jcrop || ratio === 0) return;
		
		var options = {
			aspectRatio: ratio
		};
		Jcrop.setOptions(options);

		var rect = Jcrop.tellSelect();
		Jcrop.setSelect([rect.x, rect.y, rect.x2, rect.y2]);

	})
	.on("click", "#keep_ratio", function(e){
		if ($(this).prop('checked')) {
			$('.keep-ratio').addClass('lock').find('i').addClass('orgm-icon-lock').removeClass('orgm-icon-unlocked');
		}
		else {
			$('.keep-ratio').removeClass('lock').find('i').addClass('orgm-icon-unlocked').removeClass('orgm-icon-lock');
		}
	})
	.on("click", "i.orgm-icon-lock,i.orgm-icon-unlocked", function(){
		$('#keep_ratio').click();
	})
	.on("click", "[data-rotate]", function(e){
		e.preventDefault();
		var $self = $(this)
		  , rotate = $self.data("rotate")
		  , $form = $self.closest("form");
		
		$form.find("[name=rotate]").val(rotate);
		$form.submit();
		
	})
	.on("click", "[data-copy]", function(e){
		e.preventDefault();
		
		$(this).parent().prev().submit();
		
	})
	.on("shown.bs.tab", "a[data-toggle=tab]", function(e){
		var $a = $(e.target)
		  , filergrid = $a.closest(".filer-grid-item").children(".filer-grid").data("filer-grid");


		var Jcrop = filergrid.$element.data("Jcrop");
		if ( ! Jcrop) return;
		
		if ($a.attr("href") === "#edit")
		{
			Jcrop.enable();
			Jcrop.release();
		}
		else {
			Jcrop.setSelect([0,0,0,0]);
			Jcrop.release();
			Jcrop.disable();
		}

	})
	// ! update file
	.on("submit", "form", function(e){
		e.preventDefault();
		var $form = $(this)
		  , filergrid = $form.closest(".filer-grid-item").children(".filer-grid").data("filer-grid");
		var data = $form.serializeArray();
		var overwrite;

		for (var i = 0; i < data.length; i++) {
			if (data[i].name == "overwrite") overwrite = parseInt(data[i].value, 10);
		}

		$.post(ORGM.filer.updateUrl, data, function(res){

			if (typeof res.error === "undefined") {				
				// 上書き以外の時
				if ( ! overwrite) {

				    // 既に同じ日付ヘッダーがある場合は、ヘッダーを削除
				    if ($('#orgm_filer_list li:first-child').data('date-title') == res.yearmonth) {
						    res.yearmonth = '';
						    method = "insertAfter";
						    target = '#orgm_filer_list li:first-child';
					}
					else {
						method = "prependTo";
						target = "#orgm_filer_list";
					}

		
				    $('#tmpl_filelist').tmpl(res, {'json':function(){
						return JSON.stringify(this.data);
					}})[method](target).filter("[data-id]").addClass('new');

					filergrid.hide(function(){
						ORGM.scroll(".filer-grid-item.new");
					});

					return;
				}
				
				//モーダル更新
				$("[data-field]", filergrid.$expander).each(function(){
					var $self = $(this);
					var field = $self.attr("data-field");
					if (typeof res[field] !== "undefined") {
						$self.text(res[field]).val(res[field]);
					}
				});
				$(".field-editable:not(:visible)", filergrid.$expander).show().next().hide();

			    // 既に同じ日付ヘッダーがある場合は、ヘッダーを削除
			    if ($('[data-date-title="'+res.yearmonth+'"]').length) {
				    res.yearmonth = '';
			    }

				//リスト更新
				$("#tmpl_filelist").tmpl(res, {'json':function(){
					return JSON.stringify(this.data);
				}}).replaceAll($("li[data-id="+res.id+"]")).find(".filer-grid").click();
				
				//情報タブを表示
				$("a[href='#info']", filergrid.$expander).click();
				
				//画像の更新
				if (res.type === "image") {
					
					var timestamp = new Date().getTime();
					$("li[data-id="+res.id+"] img.cut_image").attr("src", res.thumbnail + "?" + timestamp);
					filergrid.$expander.find("img.preview").attr("src", res.filepath + "?" + timestamp);
					
					var Jcrop = filergrid.$element.data("Jcrop");
					
					if (Jcrop) {
						Jcrop.destroy();
						filergrid.$element
						.data("Jcrop", undefined).data("file", res)
							.find("img.preview").attr("style", "");
						
						filergrid.$element.trigger("shown.filergrid");
					}
				}
			}
			
		}, "json");		
	});
	
				
	// ! load more	
	ORGM.filer.active = false;
	$(window).on("scroll.filer loadmore", function(e){
		if (ORGM.filer.active) {
			return;
		}
		var content = $('#orgm_filer_list');
		if (content.offset().top + content.height() < $(document).scrollTop() + $(window).height()) {
			ORGM.filer.active = true;
		    $.get(ORGM.filer.more, function(res){
			    if (typeof res.error === "undefined" && res.files.length > 0) {
	
				    $("#orgm_filer_list").append('<li class="orgm-load-indicator cut"><i class="orgm-icon orgm-icon-spinner-2"></i></li>');
				    
				    // 既に同じ日付ヘッダーがある場合は、ヘッダーを削除
				    if ($('[data-date-title="'+res.files[0].yearmonth+'"]').length) {
					    res.files[0].yearmonth = '';
				    }
				    
				    setTimeout(function(){
				    	$("#orgm_filer_list").find(".orgm-load-indicator").remove();
					    $('#tmpl_filelist').tmpl(res.files, {'json':function(){
							return JSON.stringify(this.data);
						}}).appendTo('#orgm_filer_list');
					    ORGM.filer.more = res.more;
					    ORGM.filer.active = false;
					    
				    }, 500);
			    }
			    else {
				    $(window).off("scroll.filer");
				    $("#load_more").remove();
			    }
			    
		    }, 'json');
		}
	});
	
	$("#load_more").on("click", function(e){
		e.preventDefault();
		$(document).trigger("loadmore");
	});


	//key bind
	$(document)
	.on("keydown", function(e){
		
		if (e.keyCode == 39) {
			$("#orgm_filer_list .carousel-control.right").click();
		}
		else if (e.keyCode == 37) {
			$("#orgm_filer_list .carousel-control.left").click();
		}
		
	});
	
	// !すべて表示のリンク
	var qsa = location.search.substring(1).split('&')
	var isSearch = false, isStar = false;
	for (var i=0; i<qsa.length; i++) {
		var pair = qsa[i].split('=');
		if (pair[0] == 'search_word' && pair[1] != '') {
			isSearch = true;
			if (pair[1].match(new RegExp(encodeURIComponent(":star")))) {
				isStar = true;
			}
			break;
		}
	}
	if (isSearch){
		$(".filer-all-link").show();
	}
	else {
		$(".filer-all-link").hide();
	}
	if (isStar) {
		$(".filer-star-link").hide();
	}

	// !check hash
	if ( ! ORGM.filer.iframe || window.parent === window) {
		$.getJSON(ORGM.filer.hashCheckUrl, function(res){
			if (res.result === "changed") {
				location.href = ORGM.filer.checkUrl;
			}
		});
	}
	
	// !typeahead
	(function(){
		$("#orgm_filer_search").typeahead({
			name: "filer-search",
			local: ORGM.filerSuggestionData,
			engine: ORGM.tmpl,
			template: '<p><strong>${label}</strong><small>${value}</small></p>'
		});
	})();

	// !data set
	if (typeof ORGM.filer.files !== "undefined") {
		$('#tmpl_filelist').tmpl(ORGM.filer.files, {'json':function(){
			return JSON.stringify(this.data);
		}}).appendTo('#orgm_filer_list');
	}
	if (typeof ORGM.filer.folders !== "undefined") {
		$('#tmpl_folderlist').tmpl(ORGM.filer.folders).appendTo('#orgm_folder_list');
	}


});