$(function(){

	if (ORGM.former.iframe && window.parent !== window) {//debug
	
		$("#former_add_trigger").attr("target", "former");
		
		ORGM.former = _.extend(ORGM.former, {
			detectNew: function(id){
				window.location.hash = "#!id="+id;
				window.location.reload();
			}
		});
		
	}
	

    // ! ↓ form list
	$("#orgm_former_list")
	.on("click", "a[data-former]", function(){
		
		var id = $(this).closest("li").data('id');

		if (ORGM.former.iframe && window.parent !== window) {
			window.parent.ORGM.getSelectedForm(id);
		}
		else {
			location.href = ORGM.former.editUrl + '&id=' + id;
		}
		
		return false;

	}).on('mouseenter', "a[data-former]", function(){
		if (ORGM.former.deletable)
			$(this).find("span.delete").show();
		if (ORGM.former.log_viewable)
			$(this).find("span.showlog").show();

	}).on('mouseleave', "a[data-former]", function(){

		$(this).find("span.delete").hide();
		$(this).find("span.showlog").hide();

	}).on('click', 'span.delete', function(e){

		e.preventDefault();
		e.stopPropagation();

		var id = $(this).closest('li').data('id');


		if ( ! ORGM.former.forms[id].deletable) return;
		if ( ! confirm("このフォームを削除してもよろしいですか？\n投稿ログがある場合は、投稿ログも削除します。")) return false;

		var data = {id: id};
		
		$.post(ORGM.former.deleteUrl, data, function(res){
			if (res.error) {
				ORGM.notify(res.message, "error");
				return;
			}
			
			ORGM.notify(res.message);
			$("[data-id="+id+"]").remove();
			
		}, "json");
		
		return false;
	
	}).on('click', 'span.showlog', function(e){
	
		e.preventDefault();
		e.stopPropagation();

		var id = $(this).closest('li').data('id');
		location.href = ORGM.former.logUrl+id;
	
	});

	// ! ↓ form edit
	
	ORGM.loadingIndicator = true;

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
		$(document).on("change", "input, select, textarea", function(e){
			updated = true;
		});
	}
	
	// ! 更新・キャンセル
	$("#admin_nav").on("click", "[data-edit-type]", function(e){
		
		var type = $(this).data("editType");
		
		if (type === "cancel") {
			location.href = ORGM.former.cancelUrl;
			return;
		}


		var data = $("input[name^='form['],textarea[name^='form['],select[name^='form[']").serializeArray();
		
		var callback = function(){

//			$("#orgm_loading_indicator").addClass("in");

			$.post(ORGM.former.echoPostUrl, data)
			.then(function(res, status){

				try {				
					if ( ! $.isPlainObject(res))
						res = JSON.parse(res);
				}
				catch (ex) {
					res = {error: "通信エラーが発生しました。(5100292)"};
				}

				if (res.error) {
					ORGM.notify(res.error, 'error');
	
					return new $.Deferred().reject();
				}
				
				ORGM.former.form = _.extend(ORGM.former.form, res.form);
				
				$("#orgm_former_parts_wrapper ul > li[data-name]").each(function(i){
					var name = $(this).data("name");
					ORGM.former.form.parts[name].order = i;
				});
				
				var data = {
					form : ORGM.former.form
				};
				return $.post(ORGM.former.updateUrl, data);
			})
			.then(function(res){
				try {				
					if ( ! $.isPlainObject(res))
						res = JSON.parse(res);
				}
				catch (ex) {
					res = {error: "通信エラーが発生しました。(5100293)"};
				}
				
				if (res.error) {
					ORGM.notify(res.error, 'error');
					return new $.Deferred().reject();
	
				}
				else {
					
					if (window.opener && ! window.opener.closed) {
						window.opener.ORGM.former.detectNew(res.id);
						window.close();
						return new $.Deferred().resolve();
					}
					else {
						var url = ORGM.baseUrl + '?cmd=former';
						if (res.refer !== false) {
							url = ORGM.baseUrl + '?' + encodeURIComponent(res.refer);
						}
						location.href = url;
						return new $.Deferred().resolve();
						
					}
				}
				
			}, function(){
//				$("#orgm_loading_indicator").removeClass("in");
				
			})
			.fail(function(){
//				$("#orgm_loading_indicator").removeClass("in");
			});
			
		};


		ORGM.check_login.check(
			//always
			function(){},
			//done
			function(res){
				//切れていればlogout notifier 表示
				if (res.status == ORGM.check_login.LOGOUT) {
				
					ORGM.check_login.callback = callback;
					
					ORGM.check_login.open();
					return;						
				}
				callback.call();			
			},
			//fail
			callback
		);
		
		
		
	});

	// !ナビの置換え
	$("#admin_nav > .container").replaceWith($("#orgm_former_controls > .container"));

	// scroll spy
	$("body").attr({"data-spy":"scroll", "data-target": ".navbar"});
	$("#admin_nav").scrollspy();

	$(".orgm-former-controls a[href^=#]").on("click", function(e){
		e.preventDefault();
		ORGM.scroll($(this).attr("href"));
	});

	$("#orgm_former_parts")
	.on("mousedown click", ".part-preview", function(e){
		e.preventDefault();
		
		var $li = $(this).closest(".orgm-form-part");
		var name = $li.data("name");
		var data = ORGM.former.form.parts[name];
			data.id = name;
			
		if ($li.hasClass("active")) return;
		$li.addClass("active");
		
		$("#tmpl_form_part_config").tmpl(data).appendTo($li.find(".part-config"));
		
		$($li.find(".part-config select[name=type]").val(data.type).change());
		$($li.find(".part-config select[name=validation]").val(data.validation));
		$($li.find(".part-config select[name=size]").val(data.size));
		$($li.find(".part-config :checkbox[name=required]").prop("checked", data.required));
		$($li.find(".part-config :text[name=id]").val(name));//EMAIL の場合、IDが変わらない場合があるため
		
		var $config = $li.find(".part-config");
		var position = $li.offset().top, winheight = $(window).height();
		var previewOffsetT = $config.offset().top,
			scrollVal = $li.height() <= winheight ? position : $config.height() < winheight ? previewOffsetT - ( winheight - $config.height() ) : previewOffsetT;
		
		$("html, body").animate( { scrollTop : scrollVal }, "fast" );
		
		$li.find(".part-config input[name='id']").focus();	
	})
	.on("click", "[data-copy]", function(){
		// ! 複製
		var $li = $(this).closest(".orgm-form-part")
			,html = $li.find('.part-preview').html()
			,name = $li.data("name")
			,type = $li.data("type")
			,data = {
				html: html,
				name: name,
				type: type
			}
			var newId = genNewId(name);
			ORGM.former.form.parts[newId] = _.clone(ORGM.former.form.parts[name], true);

			$("#tmpl_form_part_list").tmpl(data).insertAfter($li);
			$li.next()
			.data("name", newId).attr("data-name", newId)
			.find(".part-preview").click();
			
	})
	.on("click", "[data-delete]", function(){
		// ! 削除
		if ( ! confirm('このパーツを削除しますか？'))
		{
			return false;
		}
		var $li = $(this).closest(".orgm-form-part");
		var name = $li.data('name');

		$li.remove();
		delete ORGM.former.form.parts[name];
	})
	.on("click", "[data-edit]", function(e){
		// ! 編集
		$(this).closest(".orgm-form-part").find(".part-preview").click();
	})
	.on("click", "[data-update]", function(){
		// ! 反映する
		var $li = $(this).closest(".orgm-form-part")
		  , $preview = $li.find(".part-preview")
		  , $config = $li.find(".part-config")
		  , orgId = $li.data("name")
		  , $id = $config.find("input[name=id]")
		  , id = $id.val().replace(/^\s+|\s+$/, "")
		  , formStyle = $("#orgm_former_style").val();

		
		if (id.length === 0) {
			id = genNewId(orgId);
		}
		//ID validation
		else if ($('select[name=type]' ,$config).val() !== 'email' && id === 'EMAIL')
		{
			//error
			ORGM.notify('メールアドレス以外のフォームIDに「EMAIL」は使えません。', 'error');
			$id.closest(".form-group").addClass("error");
			$id.focus().select();
			return;
		}
		else if (orgId !== id) {
			if (_.has(ORGM.former.form.parts, id)) {
				//error
				var msg = 'このIDは既に使われています。';
				ORGM.notify(msg, 'error');
				$id.closest(".form-group").addClass("error");
				$id.focus().select();
				return;
			}
			else if ( ! /^[0-9A-Z_-]+$/.test(id)) {
				//error
				ORGM.notify('IDは半角英数字、ハイフン、アンダースコアでご指定ください。', 'error');
				$id.closest(".form-group").addClass("error");
				$id.focus().select();
				return;
			}
		}
		
		var data = {
			preview: {},
			"class": formStyle
		};
		data.preview[id] = {};
		
		$li.find(".part-config").find(".form-group:visible").find("input,select,textarea").each(function(){
			var $$ = $(this);
			var name = $$.attr("name");
			var value = $$.val();
			
			if ($$.is(":checkbox")) {
				value = $$.prop("checked") ? 1 : 0;
			}
			if (/^(.*)\[\]$/.test(name)) {
				//値が空文字の時は扱わない
				if (value.length === 0) {
					return;
				}
				name = RegExp.$1;
				if ( ! _.isArray(data.preview[id][name])) {
					data.preview[id][name] = [];
				}
				data.preview[id][name].push(value);
			}
			else
			{
				data.preview[id][name] = value;
			}
		});
		
		$.post(ORGM.former.getPartsUrl, data, function(res){
			ORGM.former.form.parts[id] = _.extend(ORGM.former.form.parts[orgId], data.preview[id]);
			id !== orgId && delete ORGM.former.form.parts[orgId];
			
			$("#tmpl_form_part_list").tmpl(res).replaceAll($li);
			updateUtility();
			
			updated = true;

		}, "json");
		
		
		
		$li.removeClass("active");
		$li.find(".part-config").empty();
		
	})
	.on("click", "[data-cancel]", function(){
		var $li = $(this).closest(".orgm-form-part");
		$li.removeClass("active");
		$li.find(".part-config").empty();
		
		ORGM.scroll($li);
	})
	.on("change", ".part-config select[name=type]", function(e){
		// ! フォームタイプの項目変更
		var $config = $(this).closest(".part-config");
		var options = ORGM.former.partsOptions[$(this).val()]["ui"];
		$("[data-block]", $config).hide();

		if (options.length == 0) {
			return;
		}
		
		for (var i in options) {
			$("[data-block="+options[i]+"]", $config).show();
		}
		
		if ($(this).val() == 'email')
		{
			var id = genNewId('EMAIL');
			$('input[name=id][value!="EMAIL"]', $config).val(id);
		}
	})
	.on("click", "[data-add-options]", function(e){
		// ! select checkbox radio のオプション追加
		var $controls = $(this).parent().prev();
		$controls.append('<div class="col-sm-6 part-config-option-item"><input type="text" name="options[]" class="form-control" placeholder="オプション名" value=""></div>');
		setTimeout(function(){
			$controls.find(":last-child").focus();
		}, 25);
		return false;
	})
	.on("click", "[data-add-parts]", function(){
		// ! フォームパーツの追加
		var	data = {}
		var newId = genNewId();
		ORGM.former.form.parts[newId] = _.clone(ORGM.former.partsOptions.text.default, true);
		var data = {
			preview:{},
			"class":$("#orgm_former_style").val()
		};
		data.preview[newId] = ORGM.former.form.parts[newId];

		$.post(ORGM.former.getPartsUrl, data, function(res){
			
			$("#tmpl_form_part_list").tmpl(res).appendTo("#orgm_former_parts ul");
			$("#orgm_former_parts ul li:last-child")
				.find(".part-preview").click();
			updateUtility(true);

		}, "json");

		return false;
	})
	.on("blur", "input[name=id]", function(){
		$(this).val($(this).val().toUpperCase());
	})
	.on("keydown", ".part-config-option-item input:text", function(e){
		
		if ((e.keyCode == 13 || e.keyCode == 9 && ! e.shiftKey) && $(this).parent().is(":last-child")) {
			
			$(this).closest(".form-group").find("[data-add-options]").click();
		}
		
	});

	$("#orgm_former_style").on("change", function(){
		var value = $(this).val();
		
		var data = {
			preview:  ORGM.former.form.parts,
			"class": value
		};
		
		//order
		$("#orgm_former_parts_wrapper ul > li[data-name]").each(function(i){
			var name = $(this).data("name");
			ORGM.former.form.parts[name].order = i;
		});

		$.post(ORGM.former.getPartsUrl, data, function(res){
			
			$("#orgm_former_parts_wrapper").attr("class", value);
			
			$("#tmpl_form_part_list").tmpl(res).appendTo($("#orgm_former_parts ul").empty());
			updateUtility(true);

		}, "json");
		
	});
	
	$(".parts-buttons").on("click", "[data-value]", function(e){
		e.preventDefault();
		
		var value = $(this).data("value");
		
		var target = $(this).data("target") || $(this).closest(".parts-buttons").data("target");
		$(this).data("target", target);
		
		$(target).exnote("insert", value);
	});
	
	$("#orgm_former_postdata")
	.on('click', '[data-add]', function(){
		var data = {
			idx : $("#orgm_former_postdata .postdata input[name^='form[post][data]']").length / 2
		};
		
		setTimeout(function(){
			$('#tmpl_former_postdata_add').tmpl(data).appendTo('#orgm_former_postdata .postdata');
			setTypeahead();
			
			$("#orgm_former_postdata .postdata input.postdata-key").select().focus();
		}, 25);
		
		return false;
	})
	.on("keydown", "input.postdata-value", function(e){
		if ((e.keyCode == 13 && e.shiftKey || e.keyCode == 9 && ! e.shiftKey) && $(this).closest(".postdata-option").is(":last-child")) {
//		if ((e.keyCode == 13 && e.shiftKey || e.keyCode == 9 && ! e.shiftKey)) {
			$(this).closest(".postdata-config").find("[data-add]").click();
		}
		
	});

	// !data set
	if (typeof ORGM.former.forms !== "undefined") {

		$('#tmpl_former_list').tmpl(ORGM.former.forms).appendTo('#orgm_former_list');

		var id = window.location.hash.match(/^#!id=(.+)$/) && RegExp.$1;
		if (id){
			$("[data-id="+id+"]").addClass("new");
		}
		

	}
	else if (typeof ORGM.former.form !== "undefined") {

		$("#orgm_former_config")
		.find("[name*=log]").prop("checked", ORGM.former.form.log).end()
		.find("[name*=class]").val(ORGM.former.form.class);
	
		var data = {
			preview : ORGM.former.form.parts,
			"class" : $("#orgm_former_style").val()
		};
	
		$.post(ORGM.former.getPartsUrl, data, function(res){
			
			$("#tmpl_form_part_list").tmpl(res).appendTo("#orgm_former_parts ul");
			updateUtility(true);
			
		}, "json");
		
		//form[post][data]
		
		var rows = [];
		_.forEach(ORGM.former.form.post.data, function(value, idx){
			//console.log(value);
			var data = {
				idx: idx,
				key: value.key,
				value: value.value
			};
			
			rows.push($('#tmpl_former_postdata_add').tmpl(data));
		});
		
		rows.push($('#tmpl_former_postdata_add').tmpl({
			idx: rows.length,
			key: "",
			value: ""
		}));
		
		$(rows).appendTo('#orgm_former_postdata .postdata');
		updateUtility(true);
		
		return false;
		
	}
	
	function updateUtility(sort) {

		sort = sort || false;
	
		setTypeahead();
		setPartsButtons();
		updateElements();
		
		if (sort)
		{
			setSortable();			
		}
	}
	
	function setSortable() {

		$("#orgm_former_parts_wrapper ul").sortable({
			axis: "y",
			opacity: 0.5,
			scroll: true,
			containment: "parent",
			tolerance: "pointer",
			handle: ".part-dragmark",
			zIndex: 100,
			start: function(e, obj){
			},
			update: function(e, obj){
			}
		});

	}
	
	function setTypeahead() {

		var ids = $("#orgm_former_parts [data-name]").map(function(){
						var label = ORGM.former.form.parts[$(this).data("name")].label;
						var name = $(this).data("name").toUpperCase();
						var value = '*|'+ name + '|*';
						return {'value': value, 'tokens': [value, label, name], 'label': label, 'name' : name};
					}).get();

		if (ids.length === 0) return;

//return;//debug
		$(".typeahead").typeahead("destroy");
		$(".typeahead").typeahead({
			"local": ids,
			"engine": ORGM.tmpl,
			"template": '<p><strong>${label}</strong><small>${value}</small></p>'
		});

		return;
				
	}
	
	function setPartsButtons() {

		var ids = $("#orgm_former_parts [data-name]").map(function(){
						var label = ORGM.former.form.parts[$(this).data("name")].label;
						var name = $(this).data("name").toUpperCase();
						return {'value': '*|'+ name + '|*', 'label': label};
					}).get();
		
		ids.unshift({
			value: "*|ALL_POST_DATA|*",
			label: "すべての投稿データ"
		});
		
		var html = "";
		_.forEach(ids, function(value, idx){
			html += '<li><a href="#" data-value="'+_.escape(value.value)+'">'+_.escape(value.label)+'</a></li>';
		});
		
		$(".parts-buttons ul").html(html);
		
	}
	
	function updateElements() {
		
		if (_.has(ORGM.former.form.parts, 'EMAIL')) {
			$("#formMailNotifyWarning").addClass("hide").attr("aria-hidden", true);
		}
		else {
			$("#formMailNotifyWarning").removeClass("hide").attr("aria-hidden", false);
		}


		
	}

	function genNewId(seed) {
		newId = seed || 'ID';
		
		while (_.has(ORGM.former.form.parts, newId)) {
			if (/^(.*_)(\d+)$/.test(newId)) {
				newId = RegExp.$1 + (parseInt(RegExp.$2, 10) + 1);
			}
			else {
				newId += "_1";
			}
		}
		
		return newId;
	}
	

});

