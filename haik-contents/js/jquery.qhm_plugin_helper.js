/**
 *   QHM Plugin Helper
 *   -------------------------------------------
 *   js/jquery.qhm_plugins.js
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 12/10/23
 *   modified : 13/06/20
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

!function($){

	/** PluginHelper Class definition */
	QHMPluginHelper = function(name, options){
		var helper = this;
		
		for (var key in options) {
			this[key] = options[key];
		}
		
		if (options.element) {
			$(options.element).on('click.qhmplugin', function(e){
				helper.exec();
				e.preventDefault();
			});
		}
		
		this.name = name;
	};
	
	QHMPluginHelper.init = function(element){
		var options = $(element).data();
		options = $.extend(ORGM.plugins[options.name], options);
		if (options) {
			options.element = element;
			var helper = new QHMPluginHelper(options.name, options);
			$(options.element).data("qhmPluginHelper", helper);
		}
	};
	QHMPluginHelper.directCall = function(options){
		if (options) {
			options = $.extend(ORGM.plugins[options.name], options);
			var helper = new QHMPluginHelper(options.name, options);
			helper.exec();
		}
	};	
	
	//Lorem Ipsum
	QHMPluginHelper.lorem = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
	
	/** Plugin List */
	QHMPluginHelper.maxFavorites = 0;//0: infinity
	QHMPluginHelper.favorites = [];// Array or false
	QHMPluginHelper.maxRecent = 10;
	QHMPluginHelper.recent = [];//Array or false
	
	QHMPluginHelper.saveList = function(target, list){
		if (typeof QHMPluginHelper[target] !== "undefined")
			localStorage.setItem($.camelCase("qhm-plugin-helper-" + target), JSON.stringify(list));
	};
	QHMPluginHelper.readList = function(target){
		if (typeof localStorage.getItem($.camelCase("qhm-plugin-helper-" + target)) !== "undefined") {
			var list = JSON.parse(localStorage.getItem($.camelCase("qhm-plugin-helper-" + target)));
			if ($.isArray(list)) {
				if (typeof QHMPluginHelper[target] !== "undefined")
					QHMPluginHelper[target] = list;
			}
			else {
				QHMPluginHelper.saveList(target, []);
				QHMPluginHelper[target] = [];
			}
		}
	};
	
	QHMPluginHelper.addToList = function(target, name){
	
	
		if (typeof ORGM.plugins !== "undefined" &&
			typeof ORGM.plugins[name] !== "undefined" &&
			typeof QHMPluginHelper[target] !== "undefined" &&
			QHMPluginHelper[target] !== false) {

			// ない
			var idx = QHMPluginHelper[target].indexOf(name);
			if (idx < 0) {
				var list = QHMPluginHelper[target],
					max = QHMPluginHelper[$.camelCase("max-" + target)];
				list.unshift(name);
				if (max > 0 && list.length > max) {
					list.splice(max, list.length - max);
				}
			}
			//ある
			else {
				var list = QHMPluginHelper[target];
				list.splice(idx, 1);
				list.unshift(name);
			}
			//update localstorage
			QHMPluginHelper.saveList(target, list);
			return true;
		}
		
		return false;
		
	};
	
	QHMPluginHelper.removeOfList = function(target, name){
		if (typeof ORGM.plugins !== "undefined" &&
			typeof ORGM.plugins[name] !== "undefined" &&
			typeof QHMPluginHelper[target] !== "undefined" &&
			QHMPluginHelper[target] !== false) {
			var list = QHMPluginHelper[target];
			
			var idx = list.indexOf(name);
			
			if (idx >= 0) {
				list.splice(idx, 1);
			}
			//update localstorage
			QHMPluginHelper.saveList(target, list);
			return true;
		}
		return false;
	}
	
	QHMPluginHelper.listElement = null;
	QHMPluginHelper.initList = function(){
		if (QHMPluginHelper.listElement !== null) return;
		if (typeof ORGM.pluginCategories === "undefined") return;
		
		var html = "";
		html += '<div class="modal fade" id="orgm_all_plugin_list"> <div class="modal-dialog"><div class="modal-content">';
		html += '<div class="modal-header">';
		html += '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times</button>';
		html += '<h3>機能リスト</h3></div>';//TODO: h3: ORGM.pluginListTitle 的なものにする
		html += '<div class="modal-body"><div class="tabbable tabs-left">';
		html += '<ul class="nav nav-tabs">{{html cats}}</ul><div class="tab-content">{{html catsContents}}</div>';
		html += '</div></div><div class="modal-footer"><a href="#" class="btn btn-default modal-close" data-dismiss="modal">閉じる</a></div></div></div>';

		var cats = [], catsContents = [];
		_.forEach(ORGM.pluginCategories, function(cat, i){
			var id = "haik_all_plugin_cat_" + i;
			var active = (i === 0) ? ' active' : '';

			cats.push('<li class="'+active+'"><a href="#'+id+'" data-toggle="tab">'+_.escape(cat.name)+'</a></li>');
			var pluginsHtml = '<div class="tab-pane'+ active +'" id="'+id+'"><ul class="nav nav-pills nav-stacked">';
			var plugins = [];
			
			_.forEach(cat.plugins, function(name, j){

				if (typeof ORGM.plugins[name] !== "undefined") {
					plugins.push('<li><a href="#" data-name="'+name+'" data-textarea="#msg">'+_.escape(ORGM.plugins[name].label)+'</a></li>');
				}
				
			});
			
			pluginsHtml += plugins.join("") + '</ul></div>';
			
			catsContents.push(pluginsHtml);
		});


		var $modal = $.tmpl(html, {cats: cats.join(""), catsContents: catsContents.join("")});


		
		$modal
		.on("show.bs.modal", function(){

			//set list
			if (QHMPluginHelper.favorites !== false) {
				
				
			}
			if (QHMPluginHelper.recent !== false) {
				var $helperRecent = $modal.find("div.modal-header div.plugin-helper-recent");
				if ($helperRecent.length > 0) {
					var $ul = $helperRecent.find("ul.dropdown-menu").empty();
					
					var list = [];
					
					_.forEach(QHMPluginHelper.recent, function(name, i){
						if (typeof ORGM.plugins[name] === "undefined") {
							return;
						}
						
						var num = (i + 1);
						num = "0" + num.toString();
						num = num.substr(num.length - 2);
						list.push('<li><a href="#" data-name="'+name+'" data-textarea="#msg">'+ num + ". " + _.escape(ORGM.plugins[name].label) +'</a></li>');
					});
					
					$ul.append(list.join(""))

				}
			}

			
		})
		.on("shown.bs.modal", function(){

		})
		.on("hidden.bs.modal", function(){
			
		})
		.on("click", "a[data-name]", function(e){
			e.preventDefault();
			
			$modal.modal("hide");

			var $a = $(this)
			  , callback = {
					name: "allPlugin",
					textarea: "#msg"
			    };

			if (typeof $a.data("qhmPluginHelper") === "undefined") {
				QHMPluginHelper.init(this);
				$a.data("qhmPluginHelper").setCancelCallback(callback).exec();
			}
			else {
				$a.data("qhmPluginHelper").setCancelCallback(callback);
			}
		});

		
		//init list
		if (QHMPluginHelper.favorites !== false) {
			
			
		}
		if (QHMPluginHelper.recent !== false) {
			
			var $dropdown = $('<div class="btn-group pull-right plugin-helper-recent"><a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="orgm-icon orgm-icon-time"></i> {action} <span class="caret"></span></a><ul class="dropdown-menu"></ul></div>'.replace("{action}", "履歴"));

			$modal.find("div.modal-header button.close").after($dropdown);
		}
				
		$modal.appendTo("body");
		QHMPluginHelper.listElement = $modal.get(0);

	};
	QHMPluginHelper.openList = function(){
		if (QHMPluginHelper.listElement === null) return;
		$(QHMPluginHelper.listElement).modal();
	};
	
	QHMPluginHelper.prototype = {
	
		constructor: QHMPluginHelper,
		
		name: "",
		label: "",
		
		closeLabel: "閉じる",
		cancelLabel: "戻る",
		completeLabel: "挿入",
		
		value: "",
		format: "",
		caret: null,
		
		element: null,
		//textarea selector
		textarea: "textarea:eq(0)",
		
		//dialog contents
		dialog: false,
		dialogElement: null,
		
		focus: "input:text:first",
		
		//callback plugin on cancel
		cancelCallback: false,
		
		//addable to recent or favorites list
		addable: true,
		
		disabled: false,
		
		showDialog: function() {
			var helper = this;
			this.onDialogShow();

			var $modal = $('<div></div>', {id: "orgm_plugin_modal", "data-plugin": this.name});
			this.dialogElement = $modal.get(0);

			$modal.addClass("modal orgm-plugin-modal")
			.on("shown.bs.modal", function(){
				if (typeof helper.focus === "string" && helper.focus.length > 0) {
					$(helper.focus, $modal).focus().select();
				}
			})
			.append('<div class="modal-dialog"><div class="modal-content"><div class="modal-header"></div><div class="modal-body"></div><div class="modal-footer"></div></div></div>')
				.find('div.modal-header')
				.append('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times</button>')
				.append('<h3></h3>')
					.find("h3").text(this.label)
				.end()
			.end()
				.find("div.modal-body")//.css("text-align", "left")
				.append(this.dialog)
			.end()
				.find("div.modal-footer")
				.append('<a href="#" class="btn btn-default modal-close"></a>')
				.append('<a href="#" class="btn btn-primary modal-complete"></a>')
					.find("a.modal-close").text(this.cancelCallback ? this.cancelLabel : this.closeLabel).click(function(e){e.preventDefault()})
						.next().text(this.completeLabel).click(function(e){e.preventDefault()});
			
			this.onDialogOpen();
			$modal.appendTo("body").modal();
			
			$modal
			.on("click", ".modal-close", function(){
				$modal.modal("hide");
				return false;
			})
			.on("click", ".modal-complete", function(){
				helper.cancelCallback = false;
				$modal.trigger("complete");
				return false;
			})
			.on("complete", function(){
				helper.cancelCallback = false;
				QHMPluginHelper.addToList("recent", helper.name);
				helper.onComplete();
				$modal.modal("hide");
			})
			.on("hide.bs.modal", function(){
				$(helper.textarea).focus();
				if (helper.cancelCallback !== false) {
					QHMPluginHelper.directCall(helper.cancelCallback);
					helper.cancelCallback = false;
				}
			})
			.on("hidden.bs.modal", function(){
				$modal.remove();
			})
			.on("submit", "form", function(e){
				e.preventDefault();
				$(".modal-complete", $modal).click();
			})
			.on("keydown", "input", function(e){
				if (e.shiftKey && e.which === 13) {
					e.preventDefault();
					$(".modal-complete", $modal).click();
				}
			});
		},
		
		insert: function(value, caret) {
			//ExpansionNote(exnote) が必要
			exnote = $(this.textarea).data('exnote');
			if ( ! exnote) {
				return;
			}
			exnote.insert(value, caret);
		},

		exec: function() {
			if (this.disabled) return;
			
			if (this.dialog !== false) {
				if (this.onStart() === false) return;
		
				this.showDialog();
			}
			else {
				if (this.addable) {
					QHMPluginHelper.addToList("recent", this.name);
				}
				this.cancelCallback = false;
				if (this.onStart() === false) return;

				this.insert(this.value, this.caret);
				this.onComplete();
			}
		},
		
		disable: function(){
			this.disabled = true;
		},
		enable: function(){
			this.disabled = false;
		},
		
		/**
		 * if return false, interapt process.
		 */
		onStart: function() {},
		onDialogShow: function(){},
		onDialogOpen: function(){},
		onDialogClose: function(){},
		onComplete: function(){},
		
		complete: function(){
			$(this.dialogElement).trigger("complete");
		},
		
		addToRecent: function(){
			QHMPluginHelper.addToList("recent", this.name);
		},
		addToFavorites: function(){
			QHMPluginHelper.addToList("favorites", this.name);
		},
		
		replaceFormat: function(key, rpl){
			rpl = rpl || "";
			var value = this.format, re;
			if ($.isPlainObject(key)) {
				var data = key;
				for (key in data) {
					re = new RegExp("\{"+key+"\}", "g");
					value = value.replace(re, data[key]);
				}
			} else {
				re = new RegExp("\{"+key+"\}", "g");
				value = value.replace(re, rpl);
			}
			return value;
		},
		
		// option: same of directCall's option
		setCancelCallback: function(option){
			this.cancelCallback = option;
			return this;
		},
		
		getLorem: function(length){
			if (length)
				return QHMPluginHelper.lorem.substr(0, length);
			else
				return QHMPluginHelper.lorem;
		}
		
	};

	
	// !on ready
	$(function(){
		if (typeof ORGM != "undefined" && typeof ORGM.plugins != "undefined") {
			$("[data-qhm-plugin]").each(function(){
				QHMPluginHelper.init(this);
			});

			QHMPluginHelper.readList("recent");
//			QHMPluginHelper.readList("favorites");
		}
	});
		
}(window.jQuery);