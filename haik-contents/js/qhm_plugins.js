/**
 *   QHM Plugins
 *   -------------------------------------------
 *   js/qhm_plugins.js
 *   
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 12/10/25
 *   modified :
 */

if (typeof ORGM == "undefined") {
	ORGM = {};
}


// !plugin を全て初期化
ORGM.plugins = {
	
	// ! cols シリーズ
	
	cols: {
		label: "段組み",
		format: "#cols{{{{\n{text}\n}}}}\n",
		options: {defval: "ここに文章を入れる\n"},
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.adjustSelection();

			var text = exnote.getSelectedText(), value;
			var offset = 0; // 見出しの後のカーソル位置
			if (text.length > 0) {
				this.value = this.format.replace("{text}", text);
				value = text;
			}
			else {
				this.value = this.format.replace("{text}", this.options.defval);
				value = this.options.defval;
			}

			var caret = {offset: -(value.length + 6), length:value.length};
			this.insert(this.value, caret);
			return false;
		}
	},
	colsDelimiter: {
		label: "段組み区切り",
		format: "====\n",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
			
			this.value = this.format;
		}
	},
	cols2: {
		label: "2段組み",
		format: "#cols({cols}){{{{\n{text}\n}}}}\n",
		options: {
			"default": {
				cols: "6,6",
				text: "\n====\n"
			},
			templates: {
				text: "\n{dummy1}\n* 見出し\n本文\n\n//↓ ==== は段組みの区切り線です。消さないでください。\n====\n\n{dummy2}\n* 見出し\n本文\n"
			},
			slider: {
				min: 1,
				max: 11,
				step: 1,
				value: 3,
				formater: function(value){
					return value.toString() + " : " + (12 - value);
				}
			}
		},
		dialog: '<div class="row"><form action="" class="form-horizontal"><div class="form-group"><div class="col-sm-10 col-sm-offset-1">' + 
				'<label class="radio-inline"><input type="radio" name="type" value="equal" checked> 均等</label></div></div>' +
				'<div class="form-group"><div class="col-sm-2 col-sm-offset-1"><label class="radio-inline"><input type="radio" name="type" value="custom"> 指定</label></div>' +
				'<div class="col-sm-9"><input type="text" name="leftcol" class="col-sm-12 slider"></div></div>' +
				'<div class="form-group"><div class="col-sm-10 col-sm-offset-1">' + 
				'<label class="checkbox-inline"><input type="checkbox" name="template" value="1" checked> 詳細な見本を挿入</label></div></div>'+
				'</form>'+
				'<hr><div class="container previewarea"></div></div>',

		onStart: function(){
		},
		onDialogOpen: function(){

			var self = this
			  , $modal = $(this.dialogElement)
			  , exnote = $(this.textarea).data("exnote");
			
			if (exnote.getSelectedText().length === 0)
			{
				$("label.checkbox", $modal).show();
			}


			setTimeout(function(){
				$("input[name=leftcol]", $modal).slider(self.options.slider).hide();
				$modal.trigger("change");
			}, 300);

			$modal
			.on("slideStart", function(){
				$("input[name=type][value=custom]", $modal).click();
			})
			.on("change slideStop", function(){
				
				var cols = $("input:radio:checked", $modal).val()
				  , leftcol, rightcol;
				if (cols !== "equal")
				{
					leftcol = parseInt($("input[name=leftcol]").data('slider').getValue(), 10);
					rightcol = 12 - leftcol;
				}
				else
				{
					leftcol = rightcol = 6;
				}
				$('.previewarea', $modal).empty()
				.append($('<div></div>', {class: "col-sm-"+leftcol}))
				.append($('<div></div>', {class: "col-sm-"+rightcol}))
					.find("div").text(self.getLorem());
			});	
			
		},
		onComplete: function(){
			
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote"),
				cols, leftcol, value, text;
			
			cols = $("input:radio:checked", $modal).val();
			
			if (cols !== "equal")
			{
				leftcol = parseInt($("input[name=leftcol]").data('slider').getValue(), 10);
				cols = leftcol.toString() + "," + (12 - leftcol)
			}
			else
			{
				cols = this.options.default.cols;
			}
			
			if ($("input[name=template]:checked").length > 0) {
				var dummy1 = ORGM.plugins.showdummy.getDummy()
				  , dummy2 = dummy1.replace(".hdummy", "0.hdummy");
				text = this.options.templates.text.replace("{dummy1}", dummy1).replace("{dummy2}", dummy2);
				
			}
			else {
			
				text = exnote.getSelectedText();
				if (text.length === 0) text = this.options.default.text;
				
			}
			
			value = this.format.replace("{cols}", cols).replace("{text}", text);
			exnote.moveToNextLine();
			this.insert(value);
			
		}
	},
	cols3: {
		label: "3段組み",
		format: "#cols(4,4,4){{{{\n{text}\n}}}}\n",
		options: {
			defval: "\n====\n\n====\n\n",
			templates: {
				text: "\n{dummy1}\n* 見出し\n本文\n\n//↓ ==== は段組みの区切り線です。消さないでください。\n====\n\n{dummy2}\n* 見出し\n本文\n\n====\n\n{dummy3}\n* 見出し\n本文\n"
			}
		},
		dialog: '<div class="row"><form action="" class="form-horizontal">' +
				'<div class="row"><div class="col-sm-10 col-sm-offset-1">' + 
				'<label class="checkbox inline"><input type="checkbox" name="template" value="1" checked> 詳細な見本を挿入</label></div></div>'+
				'</form>'+
				'<hr><div class="container previewarea"></div></div>',
		onStart: function(){
		},
		onDialogOpen: function(){

			var self = this
			  , $modal = $(this.dialogElement)
			  , exnote = $(this.textarea).data("exnote");
			
			if (exnote.getSelectedText().length === 0)
			{
				$("label.checkbox", $modal).show();
			}
			
			$('.previewarea', $modal).empty()
			.append($('<div></div>', {class: "col-sm-4"}))
			.append($('<div></div>', {class: "col-sm-4"}))
			.append($('<div></div>', {class: "col-sm-4"}))
			.find("div").text(self.getLorem());
			
		},
		onComplete: function(){
			
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote"),
				value, text;
			
			
			if ($("input[name=template]:checked").length > 0) {
				var dummy1 = ORGM.plugins.showdummy.getDummy()
				  , dummy2 = dummy1.replace(".hdummy", "0.hdummy")
				  , dummy3 = dummy1.replace(".hdummy", "00.hdummy");
				text = this.options.templates.text.replace("{dummy1}", dummy1).replace("{dummy2}", dummy2).replace("{dummy3}", dummy3);
				
			}
			else {
			
				text = exnote.getSelectedText();
				if (text.length === 0) text = this.options.defval;
				
			}
			
			value = this.format.replace("{text}", text);
			exnote.moveToNextLine();
			this.insert(value);
			
		}

	},
	cols4: {
		label: "4段組み",
		format: "#cols(3,3,3,3){{{{\n{text}\n}}}}\n",
		options: {
			defval: "\n====\n\n====\n\n====\n\n",
			templates: {
				text: "\n{dummy1}\n* 見出し\n本文\n\n//↓ ==== は段組みの区切り線です。消さないでください。\n====\n\n{dummy2}\n* 見出し\n本文\n\n====\n\n{dummy3}\n* 見出し\n本文\n\n====\n\n{dummy4}\n* 見出し\n本文\n"
			}
		},
		dialog: '<div class="row"><form action="" class="form-horizontal">' +
				'<div class="row"><div class="col-sm-10 col-sm-offset-1">' + 
				'<label class="checkbox inline"><input type="checkbox" name="template" value="1" checked> 詳細な見本を挿入</label></div></div>'+
				'</form>'+
				'<hr><div class="container previewarea"></div></div>',
		onStart: function(){
		},
		onDialogOpen: function(){

			var self = this
			  , $modal = $(this.dialogElement)
			  , exnote = $(this.textarea).data("exnote");
			
			if (exnote.getSelectedText().length === 0)
			{
				$("label.checkbox", $modal).show();
			}
			
			$('.previewarea', $modal).empty()
			.append($('<div></div>', {class: "col-sm-3"}))
			.append($('<div></div>', {class: "col-sm-3"}))
			.append($('<div></div>', {class: "col-sm-3"}))
			.append($('<div></div>', {class: "col-sm-3"}))
			.find("div").text(self.getLorem());
			
		},
		onComplete: function(){
			
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote"),
				value, text;
			
			
			if ($("input[name=template]:checked").length > 0) {
				var dummy1 = ORGM.plugins.showdummy.getDummy()
				  , dummy2 = dummy1.replace(".hdummy", "0.hdummy")
				  , dummy3 = dummy1.replace(".hdummy", "00.hdummy")
				  , dummy4 = dummy1.replace(".hdummy", "000.hdummy");
				  text = this.options.templates.text.replace("{dummy1}", dummy1).replace("{dummy2}", dummy2).replace("{dummy3}", dummy3).replace("{dummy4}", dummy4);
				
			}
			else {
			
				text = exnote.getSelectedText();
				if (text.length === 0) text = this.options.defval;
				
			}
			
			value = this.format.replace("{text}", text);
			exnote.moveToNextLine();
			this.insert(value);
			
		}

	},	colsGolden: {
		label: "黄金比段組み",
		format: "#cols(7,5){{{{\n{text}\n}}}}\n",
		options: {defval: "\n====\n"},
		onStart: function(){
			return ORGM.plugins.cols.onStart.call(this);
		},
	},
	colsSilver: {
		label: "白銀比段組み",
		format: "#cols(9,3){{{{\n{text}\n}}}}\n",
		options: {defval: "\n====\n"},
		onStart: function(){
			return ORGM.plugins.cols.onStart.call(this);
		},
	},
	
	// !見出し
	h1: {
		label: "#h1(先頭見出し）",
		format: "#h1({header})\n",
		options: {defval: "先頭見出し"},
		style: {
			fontSize: 20
		},
		onStart: function(){
			this.options = ORGM.plugins.header.options;
			ORGM.plugins.header.onStart.call(this);
			this.caret = {
				offset: -this.value.length + 4,
				length: this.value.length - 5
			};
		}

	
	},
	header: {
		label: "見出し",
		format: "* {header}\n",
		options: {defval: "大見出し"},
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), value = this.options.defval, text = exnote.getSelectedText();
			if (text.length > 0) {
				exnote.adjustSelection();
				text = exnote.getSelectedText();
				//multi line
				if (/\n/.test(text)) {
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						if (line.replace(/^\s+|\s+$/g, "").length === 0) continue;
						values.push(this.format.replace("{header}", line));
					}
					this.value = values.join("\n");
					return;
				}

				value = text;
			}
			else {
				exnote.moveToNextLine();
			}
			
			this.caret = {
				offset: - (value.length + 1),
				length: value.length
			};
			this.value = this.format.replace("{header}", value);
		}
	},
	header1: {
		label: "* 大見出し",
		style: {
			fontSize: 18
		},
		onStart: function(){
			this.format = ORGM.plugins.header.format;
			this.options = ORGM.plugins.header.options;
			ORGM.plugins.header.onStart.call(this);
		}

	},
	header2: {
		label: "** 中見出し",
		options: {defval: "中見出し"},
		style: {
			fontSize: 16
		},
		format: "** {header}\n",
		onStart: function(){
			ORGM.plugins.header.onStart.call(this);
		}

	},
	header3: {
		label: "*** 小見出し",
		options: {defval: "小見出し"},
		style: {
			fontSize: 14
		},
		format: "*** {header}\n",
		onStart: function(){
			ORGM.plugins.header.onStart.call(this);
		}
	},
	
	// !目次
	contents: {
		label: "目次",
		value: "#contents\n",
		dialog: '<p>例）</p><div class="orgm-toc" data-level="2" data-selector="h2,h3,h4" data-target="#orgm_body" data-flat="0"><ul><li><a href="#content_1_0">はじめまして</a><ul><li><a href="#content_1_1">お店の紹介</a></li><li><a href="#content_1_2">お店の地図</a></li><li><a href="#content_1_3">連絡先</a></li></ul></li></ul></div><p>※ もくじは見出しを基に作られます。</p>',
		onStart: function(){
		},
		onDialogOpen: function(){
			$(".orgm-toc a", this.dialogElement).click(function(){return false;});
		},
		onComplete: function(){
			$(this.textarea).data("exnote").moveToNextLine();
			this.insert(this.value);
		}

	},
	// !コメント欄
	comment: {
		label: "コメント欄",
		value: "#comment\n",
		dialog: '<div class="container orgm-comment"><p>以下のようなコメント欄を設置します</p><form class="form-dummy"><div class="panel"><div class="comment-body"><textarea type="text" rows="3" name="msg" class="form-control" placeholder="コメントをどうぞ"></textarea></div><div class="panel-footer comment-footer form-inline"><input type="text" name="name" class="form-control input-sm pull-left" value="" placeholder="お名前" style="width:auto"><span>認証コード(7318)</span>&nbsp;<input type="text" name="authcode" class="form-control input-sm" value="" size="4" style="width:auto;">&nbsp;&nbsp;<input type="button" name="comment" class="btn btn-primary btn-sm" value="コメントの挿入"></div></div></form></div>',
		onStart: function(){
			
		},
		onDialogOpen: function(){
			$("form.form-dummy", this.dialogElement)
			.on("focus", "input, textarea", function(){
				$(this).blur();
			})
		},
		onComplete: function(){
			$(this.textarea).data("exnote").moveToNextLine();
			this.insert(this.value);
		}
	},

	// !RSSの読込み
	showrss: {
		label: "RSSの読込み",
		format: "\n#showrss({url})\n",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">RSSのURL</label><div class="controls"><input type="text" name="url" placeholder="" /></div></div>' + 
				'</form>',
		onDialogOpen: function(){},
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var url = $modal.find("[name=url]").val();
			var value = this.format.replace("{url}", url);
			this.insert(value);
		}
	},	
	// !関連ページ
	related: {
		label: "関連ページ",
		value: "#related\n",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
		}
	},
	// !ブログの更新情報
	qblog_list: {
		label: "ブログの更新情報",
		value: "#qblog_list(line,10)\n",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
		}
	},
	
	
	// !フォーム
	form: {
		label: "フォーム",
		format: "#form({id})\n",
		init: false,
		onStart: function(){
			var self = this;
			var $former = $("#orgm_former_selector");
			
			$former.on("hidden", function(){
				$(document).off("selectForm.pluginForm");
			});
			
			$(document).on("selectForm.pluginForm", function(e, formId){
			
				var value = self.format.replace("{id}", formId);
				self.insert(value);
				$former.modal('hide');
			});
			
			$former.modal();
			
			return false;
		}
	},
	
	// !箇条書き
	ul: {
		label: "箇条書き",
		format: "- {text}",
		options: {
			defval: "箇条書き",
			lineNum: 3
		},
		dialog:'<div class="container"><p>例）</p><ul class="list1"><li>箇条書き</li><li>箇条書き</li><li>箇条書き</li></ul><p class="muted">※ 箇条書きの色や形は、お使いのデザインによって異なります</p></div>',
		onComplete :function(){
			var self = this;
			var $dialog = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			
			exnote.adjustSelection();
			var text = exnote.getSelectedText()
			  , lines = [], value = "", caret = null;
			
			if (text.length > 0) {
				var lines = text.split("\n");
				
				_.forEach(lines, function(v, i){
					lines[i] = v.replace(/^/, "- ");
				});
			}
			else {
				for (var i = 0; i < self.options.lineNum; i++) {
					lines.push(self.format.replace("{text}", self.options.defval));
				}
			}
			
			value = lines.join("\n");
			caret = {offset: -value.length, length: value.length};

			self.insert(value, caret);

		}
	},
	ol: {
		label: "番号付き箇条書き",
		format: "- {text}",
		dialog:'<div class="row"><p>例）</p><ol class="list1"><li>箇条書き</li><li>箇条書き</li><li>箇条書き</li></ol><p class="muted">※ 箇条書きの色や形は、お使いのデザインによって異なります</p></div>',
		onStart: function(){
			this.options = ORGM.plugins.ul.options;
			this.onComplete = ORGM.plugins.ul.onComplete;
		}
	},
	// !ブレット
	bullet: {
		label: "ブレット",
		format: "\n:>>|{text}\n",
		onStart: function(){
			var exnote, text, value = "", caret;
			exnote = $(this.textarea).data("exnote");
			text = exnote.getSelectedText();
			if (text.length == 0) {
				text = "ここにブレットを書く。";
				value = this.format.replace("{text}", text);
			} else if (/\n/.test(text)) {
				var lines = text.split("\n");
				for (var i in lines) {
					var line = lines[i];
					line = line.replace(/^\s+|\s+$/, "");
					if (line.length > 0) {
						value += this.format.replace("{text}", line);
					}
				}
				value = value.replace(/\n\n/g, "\n");
			}
			else {
				value = this.format.replace("{text}", text);
				caret = {
					offset: -1 - text.length,
					length: text.length
				};
			}

			exnote.insert(value, caret);
			return false;
		}
	},
	// !チェックマーク
	check: {
		label: "レ注目",
		format: "&check;",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();
			
			this.value = this.format;
			if (text.length == 0) {
				return;
			}
			else if (/\n/.test(text)) {
				exnote.moveToLinehead();
				text = exnote.getSelectedText();
				var lines = text.split("\n"), values = [];
				
				for (var i = 0; i < lines.length; i++) {
					values.push(this.format + lines[i]);
				}
				this.value = values.join("\n");
			}
			else {
				return;
			}
		}
	},

	
	// !改行
	br: {
		label: "改行",
		value: "&br;"
	},
	// !文字装飾
	strong: {
		label: "強調",
		format: "''{text}''",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), text = "";
			if (exnote.selectLength > 0) {
				text = exnote.getSelectedText();
				if (/\n/.test(text)) {
					this.value = "";
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						values.push(this.format.replace("{text}", line));
					}
					this.value = values.join("\n");
					return;
				}
			}
			this.value = this.format.replace("{text}", text);
		}
	},
	strong1: {
		label: "強調1",
		format: "''{text}''",
		style: {
			fontWeight: "bold"
		},
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), text = "";
			if (exnote.selectLength > 0) {
				text = exnote.getSelectedText();
				if (/\n/.test(text)) {
					this.value = "";
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						values.push(this.format.replace("{text}", line));
					}
					this.value = values.join("\n");
					return;
				}
			}
			this.value = this.format.replace("{text}", text);
		}
	},
	strong2: {
		label: "強調2",
		style: {
			fontWeight: "normal",
			textDecoration: "underline"
		},
		format: "%%%{text}%%%",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), text = "";
			if (exnote.selectLength > 0) {
				text = exnote.getSelectedText();
				if (/\n/.test(text)) {
					this.value = "";
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						values.push(this.format.replace("{text}", line));
					}
					this.value = values.join("\n");
					return;
				}
			}
			this.value = this.format.replace("{text}", text);
		}
	},
	strong3: {
		label: "強調3",
		style: {
			fontWeight: "bold",
			backgroundColor: "yellow"
		},
		format: "###{text}###",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), text = "";
			if (exnote.selectLength > 0) {
				text = exnote.getSelectedText();
				if (/\n/.test(text)) {
					this.value = "";
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						values.push(this.format.replace("{text}", line));
					}
					this.value = values.join("\n");
					return;
				}
			}
			this.value = this.format.replace("{text}", text);
		}
	},
	// !太字
	b: {
		label: "太字",
		format: "''{text}''",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), text = "";
			if (exnote.selectLength > 0) {
				text = exnote.getSelectedText();
				if (/\n/.test(text)) {
					this.value = "";
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						values.push(this.format.replace("{text}", line));
					}
					this.value = values.join("\n");
					return;
				}
			}
			this.value = this.format.replace("{text}", text);
		}
	},
	// !下線
	u: {
		label: "下線",
		format: "%%%{text}%%%",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), text = "";
			if (exnote.selectLength > 0) {
				text = exnote.getSelectedText();
				if (/\n/.test(text)) {
					this.value = "";
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						values.push(this.format.replace("{text}", line));
					}
					this.value = values.join("\n");
					return;
				}
			}
			this.value = this.format.replace("{text}", text);
		}
	},
	// !斜体
	i: {
		label: "斜体",
		format: "'''{text}'''",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), text = "";
			if (exnote.selectLength > 0) {
				text = exnote.getSelectedText();
				if (/\n/.test(text)) {
					this.value = "";
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						values.push(this.format.replace("{text}", line));
					}
					this.value = values.join("\n");
					return;
				}
			}
			this.value = this.format.replace("{text}", text);
		}
	},
	
	// !文字サイズ
	size: {
		label: "文字サイズ変更",
		format: "&size({size}){{text}};",
		dialog: '<form action="" class="form-horizontal"><div class="form-group"><label class="control-label">文字サイズ</label><div class="controls"><input type="text" name="size" placeholder="18" data-revert="18" /></div></div><div class="alert alert-warning hide">半角整数で指定してください。</div></form>',
		onDialogOpen: function(){
			var $modal = $(this.dialogElement),
				$alert = $("div.alert", $modal);
			
			$("input:text", $modal)
			.on("keyup", function(){
				var $$ = $(this),
					revert = $$.data("revert"),
					value = $$.val();
				if ( ! /^\d+$/.test(value)) {
					$$.val(revert);
					$alert.show();
				} else {
					if (revert != value) {
						$$.data("revert", value);
						$alert.hide();
					}
				}
			});
		},
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var size = $modal.find("input:text[name=size]").val();
			var text = $(this.textarea).data("exnote").getSelectedText();
			var value = "", values = [], caret = {offset: 0, length: 0};
			
			if (text.length > 0) {
				if (/\n/.test(text)) {
					var lines = text.split("\n");
					for (var i in lines) {
						values.push(this.format.replace("{size}", size).replace("{text}", lines[i]));
					}
					value = values.join("\n");
				} else {
					caret.offset = -(text.length + 2);
					caret.length = text.length;
					value = this.format.replace("{size}", size).replace("{text}", text);
				}
				
			}
			else {
				value = this.format.replace("{size}", size).replace("{text}", text);
				caret.offset = -2;
			}
			this.insert(value, caret);
		}
	},

	// !文字装飾プリセット
	penset: {
		label: "蛍光ペン・白抜き文字",
		format: "&deco({b},{color},{bgcolor}){{text}};",
		options: {
			pens: [
				{name: "蛍光ペン（黄）", b: "b", color: "", bgcolor: "yellow", help: "黄色の蛍光ペンを引きます。"},
				{name: "蛍光ペン（ピンク）", b: "b", color: "", bgcolor: "pink", help: "ピンク色の蛍光ペンを引きます。"},
				{name: "蛍光ペン（青）", b: "b", color: "", bgcolor: "paleturquoise", help: "青色の蛍光ペンを引きます。"},
				{name: "蛍光ペン（緑）", b: "b", color: "", bgcolor: "palegreen", help: "緑色の蛍光ペンを引きます。"},
				{name: "白抜き文字（赤）", b: "", color: "white", bgcolor: "red", help: "背景を赤色に文字を白色にします。"},
				{name: "白抜き文字（黒）", b: "", color: "white", bgcolor: "black", help: "背景を黒色に文字を白色にします。"}
			],
			templates: {button: '<li class="row" style="text-align:left"><a class="colorbtn" class="col-sm-12"></a></li>', help: '<span class="pull-right"></span>'}
		},
		dialog: '<div><ul class="nav nav-tabs nav-stacked"></ul></div>',
		onDialogOpen: function(){
			var self = this;
			var $modal = $(this.dialogElement),
				$pens = $modal.find("div.modal-body ul.nav");
				
			$modal.find("a.modal-close").hide();

			for (var i = 0; i < this.options.pens.length; i++) {
				var color = this.options.pens[i],
					$btn = $(this.options.templates.button);
				$btn
				.css({cursor: "pointer"})
					.find("a").text(color.name).data("color", color)
					.append(this.options.templates.help)
						.children().text(color.help)
						.css({color: color.color.length>0 ? color.color : "inherit", backgroundColor: color.bgcolor, fontWeight: color.b.length>0 ? "bold": ""});
				
				$pens.append($btn);
			}

			
			$modal.find("a.colorbtn")
			.on("click", function(){
				
				$(this).addClass("active");
				
				self.complete();
				
			});
			
		},
		onComplete: function(){

			var $modal = $(this.dialogElement);
			var size = $modal.find("input:text[name=size]").val();
			var text = $(this.textarea).data("exnote").getSelectedText();
			var value = "", values = [], caret = {offset: 0, length: 0};
			var data = $modal.find("a.colorbtn.active").data("color");
			data.text = text;
			
			if (text.length > 0) {
				if (/\n/.test(text)) {
					var lines = text.split("\n");
					for (var i in lines) {
						data.text = lines[i];
						values.push(this.replaceFormat(data));
					}
					value = values.join("\n");
				} else {
					caret.offset = -(text.length + 2);
					caret.length = text.length;
					value = this.replaceFormat(data);
				}
				
			}
			else {
				value = this.replaceFormat(data);
				caret.offset = -2;
			}
			this.insert(value, caret);
		}
	},
	
	
	// !取り消し線
	strike: {
		label: "取り消し線",
		format: "%%{text}%%",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"), text = "";
			if (exnote.selectLength > 0) {
				text = exnote.getSelectedText();
				if (/\n/.test(text)) {
					this.value = "";
					var lines = text.split("\n"), values = [];
					for (var i = 0; i < lines.length; i++) {
						var line = lines[i];
						values.push(this.format.replace("{text}", line));
					}
					this.value = values.join("\n");
					return;
				}
			}
			this.value = this.format.replace("{text}", text);
		}
	},
	

	// !文字装飾（deco）
	deco: {
		label: "文字装飾",
		format: "&deco({options}){{text}};",
		options: {sizePresets: ["12", "14", "18", "24", "small", "medium", "large"]},
		focus: false,
		dialog: '<div class=""><form action="" class="form-horizontal">'+
			'<div class="form-group"><label class="col-sm-3 control-label">装飾</label><div class="col-sm-9 checkbox"><label class="checkbox-inline" style="font-weight:bold;"><input type="checkbox" name="bold" /> 太字</label><label class="checkbox-inline" style="font-style:italic;"><input type="checkbox" name="italic" /> 斜体</label><label class="checkbox-inline" style="text-decoration:underline;"><input type="checkbox" name="underline" /> 下線</label></div></div>'+
			'<div class="form-group"><label class="col-sm-3 control-label">文字色</label><div class="col-sm-3"><input type="text" name="color" class="form-control input-sm" /></div></div>'+
			'<div class="form-group"><label class="col-sm-3 control-label">背景色</label><div class="col-sm-3"><input type="text" name="bgcolor" class="form-control input-sm" /></div></div>'+
			'<div class="form-group"><label class="col-sm-3 control-label">大きさ</label><div class="col-sm-3"><input type="text" name="size" class="form-control input-sm" data-revert="14" value="14" placeholder="14" autocomplete="off" class="typeahead"></div></div>'+
			'</form>'+
			'<div class="previewarea panel"><span>サンプルです。<br>Hello, world!<br>3.14159265</span></div></div>',
		onStart: function(){
			var $modal = $(this.dialog);
			$("div.previewarea", $modal).css({
				marginTop: 10,
				marginBottom: 10
			});
			
			$("input:checkbox", $modal).parent().css({
				display: "inline-block",
				marginRight: 10,
				cursor: "pointer"
			});
			
			this.dialog = $modal.wrap("<div></div>").parent().html();
		},
		onDialogOpen: function(){
			var $modal = $(this.dialogElement),
				$span = $("div.previewarea span", $modal);
			
			$modal.find("input:text[name=size]").data("source", this.options.sizePresets);
			
			$("input:text[name$=color]").colorpalette();
			
			$("input:text[name=size]").typeahead({
				name: "font-size",
				local: this.options.sizePresets
			});
			
			$modal
			.on("change", function(){
				var values = $("form", $modal).serializeArray();
				$span.attr("style", "");
				
				for (var i in values) {
					var name = values[i].name,
						value = values[i].value;
					switch (name) {
						case "bold":
							$span.css("font-weight", "bold");
							break;
						case "italic":
							$span.css("font-style", "italic");
							break;
						case "underline":
							$span.css("text-decoration", "underline");
							break;
						case "color":
							$span.css("color", value);
							break;
						case "bgcolor":
							$span.parent().css("background-color", value);
							break;
						case "size":
							value = /^\d+$/.test(value) ? (value + "px") : value;
							$span.css("font-size", value);
							break;
					}
				}
			})
			.on("hide.bs.modal", function(){
				$("input:text[name$=color]").colorpalette("hide")
			});

		},
		onComplete: function(){
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			var values = $("form", $modal).serializeArray();
			var text = exnote.getSelectedText();
			var options = "", value = "", caret = {offset:0, length:0};
			
			for (var i in values) {
				var name = values[i].name,
					value = values[i].value;
				
				switch (name) {
					case "bold":
					case "italic":
					case "underline":
						if (value.length > 0)
							options += name.substr(0,1) + ",";
						break;
					case "color":
					case "bgcolor":
					case "size":
						options += value + ",";
				}
			}
			
			
			if (text.length > 0) {
				if (/\n/.test(text)) {
					var lines = text.split("\n");
					values = [];
					for (var i in lines) {
						values.push(this.format.replace("{options}", options).replace("{text}", lines[i]));
					}
					value = values.join("\n");
				} else {
					caret.offset = -(text.length + 2);
					caret.length = text.length;
					value = this.format.replace("{options}", options).replace("{text}", text);
				}
				
			}
			else {
				value = this.format.replace("{options}", options).replace("{text}", "");
				caret.offset = -2;
			}
			this.insert(value, caret);
		}
	},
	
	// !文字揃え
	align: {
		label: "配置（左・中央・右）",
		format: "{align}:\n",
		init: false,
		dialog: '<div class="row plugin-align">' +
'<ul class="thumbnails">' +
'  <li class="col-sm-4" data-align="LEFT">' +
'    <div class="thumbnail">' +
'    	<p class="muted" style="text-align:left">Lorem ipsum augue<br>arcu pulvinar urna luctus<br>imperdiet mattis cubilia.</p>' +
'    </div>' +
'	<div class="title">左揃え</div>' +
'  </li>' +
'  <li class="col-sm-4" data-align="CENTER">' +
'    <div href="#" class="thumbnail">' +
'    	<p class="muted" style="text-align:center">Lorem ipsum augue<br>arcu pulvinar urna luctus<br>imperdiet mattis cubilia.</p>' +
'    </div>' +
'	<div class="title">中央</div>' +
'  </li>' +
'  <li class="col-sm-4" data-align="RIGHT">' +
'    <div href="#" class="thumbnail">' +
'    	<p class="muted" style="text-align:right">Lorem ipsum augue<br>arcu pulvinar urna luctus<br>imperdiet mattis cubilia</p>' +
'    </div>' +
'	<div class="title">右揃え</div>' +
'  </li>' +
'</ul>' +
'</div>',
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement);
			
			$('[data-align]', $dialog).css('cursor', 'pointer');
			
			$dialog.on('click', '[data-align]', function(){
				$("[data-align]").removeClass("selected");
				$(this).addClass("selected");
			});
			
		},
		onComplete: function(){

			var exnote = $(this.textarea).data("exnote")
				,value = this.format
				,text = exnote.getSelectedText()
				,$dialog = $(this.dialogElement);

			var align = $(".selected", $dialog).data('align');
			value = value.replace("{align}", align);

			if (text.length > 0) {
				exnote.adjustSelection();
				text = exnote.getSelectedText();
			}
			else {
				exnote.moveToNextLine();
			}

			value = value + text;
			exnote.insert(value);
		}
	},
	left: {
		label: "左揃え",
		value: "LEFT:\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToLinehead();
		}
	},
	center: {
		label: "中央揃え",
		value: "CENTER:\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToLinehead();
		}
	},
	right: {
		label: "右揃え",
		value: "RIGHT:\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToLinehead();
		}
	},
	// !表組み
	table: {
		label: "表を挿入",
		options: {rows: 4, cols: 7},
		init: false,
		dialog: '<div style="margin: 0 auto;text-align:left;width: 300px;"><div class="cells" style="margin-bottom: 10px;"><table><tbody></tbody></table></div><div class="form-group row"><div class="col-sm-5"><div class="input-group"><span class="input-group-addon input-sm">行</span><input type="text" name="rows" class="form-control input-sm"></div></div><div class="col-sm-5"><div class="input-group"><span class="input-group-addon input-sm">列</span><input type="text" name="cols" class="form-control input-sm"></div></div></div> <div class="form-group"><div class="checkbox"><label><input type="checkbox" name="header"> 1行目をヘッダー（タイトル行）にする</label></div></div></div>',
		onStart: function(){
		
			if (this.init) {
				return;
			}
			this.init = true;
			
			var $modal = $(this.dialog),
				$cells = $modal.find("div.cells > table > tbody");
			for (var i = 0; i < this.options.rows; i++) {
				var $tr = $("<tr></tr>");
				for (var j = 0; j < this.options.cols; j++) {
					$tr.append('<td data-x="'+(j+1)+'" data-y="'+(i+1)+'">&nbsp;</td>');
				}
				$cells.append($tr);
			}
			
			
			$("td", $cells).css({
				minHeight: 25,
				minWidth: 25,
				border: "2px solid #999",
				cursor: "pointer"
			});

			
			this.dialog = $modal.wrap("<div></div>").parent().html();
			
		},
		onDialogOpen: function(){
			var self = this;
			var $modal = $(this.dialogElement),
				$cells = $modal.find("div.cells > table > tbody");

			var clearCells = function(){
					$cells.find("td").css({
						backgroundColor: ""
					});
			};
			var applyCells = function(rows, cols){
				$cells.find("tr:nth-child(-n+"+rows+")")
				.find("td:nth-child(-n+"+cols+")")
				.css({
					backgroundColor: "navy"
				});
			};
			
			$modal.find("td")
			.hover(
				function(){
					var pos = $(this).data();
					applyCells(pos.y, pos.x);
				},
				clearCells
			)
			.click(function(){
				var pos = $(this).data();
				$modal.find("input:text[name=rows]").val(pos.y);
				$modal.find("input:text[name=cols]").val(pos.x);
			});
			

			
			$cells.hover(clearCells, function(){
				var rows = parseInt($modal.find("input:text[name=rows]").val(), 10)
				  , cols = parseInt($modal.find("input:text[name=cols]").val(), 10);
				
				rows = Math.min(rows, self.options.rows);
				cols = Math.min(cols, self.options.cols);

				applyCells(rows, cols);
			});
			
			$modal.find("input:text").change(function(){
				clearCells();
				
				var rows = parseInt($modal.find("input:text[name=rows]").val(), 10)
				  , cols = parseInt($modal.find("input:text[name=cols]").val(), 10);
				
				rows = Math.min(rows, self.options.rows);
				cols = Math.min(cols, self.options.cols);

				applyCells(rows, cols);
				
			});
		},
		onComplete: function(){
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
			
			var value = "", values = [],
				rows = parseInt($modal.find("input:text[name=rows]").val(), 10),
				cols = parseInt($modal.find("input:text[name=cols]").val(), 10);
			var header = $modal.find("input:checkbox[name=header]").is(":checked");
			
			for (var i = 0; i < rows; i++) {
				var line = "|";
				for (var j = 0; j < cols; j++) {
					if (header && i == 0) {
						line += "~ 見出し";
					}
					line += "セル  |";
				}
				if (header && i == 0) {
					line += "h";
				}
				values.push(line);
			}
			value = values.join("\n") + "\n";
			
			caret = {offset: -value.length + (header ? 3 : 2)};
			
			this.insert(value, caret);
		}
	},
	tableNextCell: {
		label: "次のセルへ移動",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"),
				value = exnote.getValue(),
				range = exnote.getRange();
			var c = "";
			
			while (c = value.substr(range.position, 1)) {
				if (c == "|") {
					var next_c = value.substr(range.position + 1, 1);
					var next_next_c = value.substr(range.position + 2, 1);
					if (next_c == "~") {
						exnote.attachFocus(range.position + 2);
						return;
					}
					else if (next_next_c == "\n" && /[ch]/.test(next_c)) {
						range.position += 3;
					}
					else if (next_c == "\n") {
						range.position += 2;
					}
					else {
						exnote.attachFocus(range.position + 1);
						return;
					}
				}
				else if (c == "\n") {
					return;
				}
				else {
					range.position++;
				}
			}
		}
	},
	tablePrevCell: {
		label: "前のセルへ移動",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"),
				value = exnote.getValue(),
				range = exnote.getRange();
			var c = "";
			
			while (c = value.substr(range.position - 1, 1)) {
				if (c == "|") {
					var prev_c = value.substr(range.position - 2, 1);
					var prev_prev_c = value.substr(range.position - 3, 1);
					if (prev_c == "~") {
						exnote.attachFocus(range.position - 2);
						return;
					}
					else if (prev_prev_c == "\n" && /[ch]/.test(prev_c)) {
						range.position -= 3;
					}
					else if (prev_c == "\n") {
						range.position -= 2;
					}
					else {
						exnote.attachFocus(range.position - 1);
						return;
					}
				}
				else if (c == "\n") {
					return;
				}
				else {
					range.position--;
				}
			}
		}
	},
	//TODO: 表の装飾
	
	// !HTML挿入
	// !TODO:再編集用に選択コードの読み込みを検討
	html: {
		label: "HTML挿入",
		format: "#html{{\n{html}\n}}\n",
		dialog: "<p>ここにHTMLソースを入力してください。</p><textarea name=\"html\" class=\"form-control\"></textarea>",
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement);
			$dialog.find("textarea[name=html]").exnote();

			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();

			if (text.length > 0) {
				var deftext = text.replace(/^#html\{{2,}\n/, "").replace(/\n\}{2,}\n*$/, "");
				var $modal = $(this.dialogElement);
				$modal.find("[name=html]").val(deftext);
			}
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement),
				html = $dialog.find("textarea[name=html]").val();
			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();
			if (text.length === 0)
				exnote.moveToNextLine();
			
			this.insert(this.format.replace("{html}", html));
		}
	},
	// !1行HTML挿入
	html2: {
		label: "1行HTML挿入",
		format: "#html2({html})\n",
		dialog: "挿入したいHTMLを入力してください。<br><textarea name=\"html\"></textarea>",
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement);
			$dialog.find("textarea[name=html]").exnote();

			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();

			if (text.length > 0) {
				var $modal = $(this.dialogElement);
				text = text.replace(/\n|\r/g,'');
				$modal.find("[name=html]").val(text);
			}
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var html = $dialog.find("textarea[name=html]").val();
			html = html.replace(/\n|\r/g,'');
			$(this.textarea).data("exnote").moveToNextLine();
			this.insert(this.format.replace("{html}", html));
		}
	},
	// !beforescript: その他のタグ
	beforescript: {
		label: "その他のタグ",
		format: "#beforescript{{\n{html}\n}}\n",
		dialog: "挿入したいメタタグ等を入力してください。<br /><textarea name=\"html\"></textarea>",
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement);
			$dialog.find("textarea[name=html]").exnote();

			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();

			if (text.length > 0) {
				var $modal = $(this.dialogElement);
				$modal.find("[name=html]").val(text);
			}
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var html = $dialog.find("textarea[name=html]").val();
			$(this.textarea).data("exnote").moveToNextLine();
			this.insert(this.format.replace("{html}", html));
		}
	},
	
	
	// !枠
	box: {
		label: "枠",
		format: "${br}#box(${width},${type},${height}){{${br}${text}${br}}}${br}",
		options: {
			width: [
				{label: "100%", value: "12"},
				{label: "80%", value: "10+1"},
				{label: "60%", value: "8+2"},
				{label: "50%", value: "6+3"}
			],
			type: [
				{"class": 'panel panel-default', style: "padding:15px;", value: "panel,default"},
				{"class": 'panel panel-primary', style: "padding:15px;", value: "panel,primary"},
				{"class": 'panel panel-success', style: "padding:15px;", value: "panel,success"},
				{"class": 'panel panel-info',    style: "padding:15px;", value: "panel,info"},
				{"class": 'panel panel-warning', style: "padding:15px;", value: "panel,warning"},
				{"class": 'panel panel-danger',  style: "padding:15px;", value: "panel,danger"},
				{"class": 'alert alert-success', style: "", value: "alert,success"},
				{"class": 'alert alert-info',    style: "", value: "alert,info"},
				{"class": 'alert alert-warning', style: "", value: "alert,warning"},
				{"class": 'alert alert-danger',  style: "", value: "alert,danger"},
				{"class": 'well',                style: "padding:15px;", value: "well"}
			]
		},
		focus: false,
		dialog: '<div class="container"><form action="" class="form-horizontal">' + 

				'<div class="form-group">' +
				'<label for="" class="col-sm-3 control-label">横幅</label>'+
				'<div class="col-sm-9"><select name="width" id="" class="form-control"></select></div></div>' +
				
				'<div class="form-group">'+
				'<label for="" class="col-sm-3 control-label">タイプ</label>'+
				'<div class="col-sm-9"><div class="row box-type"></div><input type="hidden" name="type" value="" data-class=""></div></div>' +

				'<div class="form-group">' +
				'<label for="" class="col-sm-3 control-label">高さ</label>'+
				'<div class="col-sm-9"><div class="row"><div class="col-sm-3"><input type="text" name="height" class="form-control input-sm" /></div></div><span class="help-block muted">スクロール付きのボックスにしたい時は指定してください。</span></div></div></form>'+
				'<hr><div class="cotainer plugin-box"><div class="previewarea"><h5>サンプル</h5><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p></div></div></div>',

		onStart: function(){
			var helper = this;
			if (typeof this.init == "undefined") {
				this.init = true;
				
				//make dialog
				var $dialog = $(this.dialog)
					.find("select").each(function(){
						var $this = $(this);
						var options = helper.options[$this.attr("name")];
						for (var i in options) {
							var option = options[i];
							$this.append("<option></option>")
								.find("option:last")
								.val(option.value)
								.text(option.label);
						}
					}).end()
				
				for (var i in this.options.type) {
					$('.box-type', $dialog).append('<div class="col-sm-3"></div>')
					.find('div:last').append('<div></div>').find('div')
					.addClass(this.options.type[i].class)
					.attr("style", this.options.type[i].style)
					.text('サンプル')
					.data("type", this.options.type[i].value)
					.attr("data-type", this.options.type[i].value);
				}
				
				this.dialog = $dialog.wrap("<div></div>").parent().html();
			}
		},
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement);
			
			$dialog.on('change', function(){
				var values = $('form', $dialog).serializeArray();
				var $prevarea = $(".previewarea", $dialog);
				$prevarea.attr("class", "previewarea");

				for (var i in values) {
					var name = values[i].name,
						value = values[i].value;
						
					switch(name) {
						case 'type' :
							var $input = $("input[name="+name+"]");
							$prevarea.addClass($input.attr("data-class"));
							break;
						case 'width':
							var szarr = value.split('+');
							var span = 'col-sm-'+szarr[0],
								offset = typeof szarr[1] != 'undefined' ? 'col-sm-offset-'+szarr[1] : '';
							$prevarea.addClass(span+' '+offset);
							break;
						case 'height':
							$prevarea.css("height", value);
							$prevarea.css("overflow-y", "scroll");
							break;
					}
				}
			})
			.on("click", "[data-type]", function(){
				$("input:hidden[name=type]").val($(this).data("type")).attr("data-class", $(this).attr("class"));
				$(this).parent().addClass("selected").siblings().removeClass("selected");
				$dialog.triggerHandler("change");
			});
			
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement)
				,exnote = $(this.textarea).data("exnote")
				,data = {br: "\n"};

			$('input,select', $dialog).each(function(){
				var key = $(this).attr('name'),
					value = $(this).val();

				switch(key) {
					case 'width':
						data.width = value;
						break;
					case 'height':
						if (value.length > 0) {
							data.height = 'height=' + value;
						}
						break;
					case 'type':
						data.type = value;
						break;
				}
			});
			
			data.text = exnote.getSelectedText();
			var value = $.tmpl(this.format, data).text();

			exnote.moveToNextLine();			
			this.insert(value);

		}
	},
	

	// !コンバージョン計測
	conversion: {
		label: "コンバージョン計測",
		format: "#conversion({step},{group})\n",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">ステップ番号</label><div class="controls"><input type="text" name="step" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">グループ名</label><div class="controls"><input type="text" name="group" placeholder="" /></div></div>' + 
				'</form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var step = $modal.find("[name=step]").val(),
				group = $modal.find("[name=group]").val();
			var value = this.format.replace("{step}", step).replace("{group}", group);
			this.insert(value);
		}
	},
	// !コンバージョン計測：外部リンク
	conversion_inline: {
		label: "コンバージョン計測：外部リンク",
		format: "&conversion({step},{group},{title},{url}){{text}};",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">ステップ番号</label><div class="controls"><input type="text" name="step" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">グループ名</label><div class="controls"><input type="text" name="group" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">結果表示名</label><div class="controls"><input type="text" name="title" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">リンク先URL</label><div class="controls"><input type="text" name="url" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">表示文字</label><div class="controls"><input type="text" name="text" placeholder="" /></div></div>' + 
				'</form>',
		onDialogOpen: function(){
			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();

			if (text.length > 0) {
				var $modal = $(this.dialogElement);
				text = text.replace(/\n|\r/g, '&br;');
				$modal.find("[name=text]").val(text);
			}
		},
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var step = $modal.find("[name=step]").val(),
				group = $modal.find("[name=group]").val(),
				title = $modal.find("[name=title]").val(),
				url = $modal.find("[name=url]").val(),
				text = $modal.find("[name=text]").val();
			var value = this.format.replace("{step}", step).replace("{group}", group).replace("{title}", title).replace("{url}", url).replace("{text}", text);
			this.insert(value);
		}
	},
	// !ABスプリット
	absplit2: {
		label: "ABスプリットテスト",
		format: "\n#absplit2({pagenameA},{pagenameB})\n",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">パターンAのページ名<br /></label><div class="controls"><input type="text" name="pagenameA" placeholder="ページ名" class="typeahead" autocomplete="off"></div></div>' +
				'<div class="form-group"><label for="" class="control-label">パターンBのページ名<br /></label><div class="controls"><input type="text" name="pagenameB" placeholder="ページ名" class="typeahead" autocomplete="off"></div></div>' +
				'</form>',
		onDialogOpen: function(){
			//オートコンプリート
			var $dialog = $(this.dialogElement);
			$.when(ORGM.getPages()).done(function(){
				$dialog.find("input[name=linkto]").data("source", ORGM.pages);
			}).fail(function(){});
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
			var pagenameA = $dialog.find("input[name=pagenameA]").val(),
				pagenameB = $dialog.find("input[name=pagenameB]").val();
			var value = this.format.replace("{pagenameA}", pagenameA).replace("{pagenameB}", pagenameB);
			this.insert(value);
		}

	},

	// !レイアウト
	// !回り込み解除
	clear: {
		label: "回り込み解除",
		value: "\n#clear\n"
	},
	// !水平線
	hr: {
		label: "水平線",
		value: "----\n",
		init: false,
		options: {start: 1, end: 35},
		dialog: '<p>例）</p><div class="container">この下に水平線が入ります<hr class="full_hr">この上の線が水平線です。</div>',
		onStart: function(){
		},
		onDialogOpen: function(){			
		},
		onComplete: function(){
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			
			exnote.moveToNextLine();
			this.insert(this.value);
		}
	},
	
	/* !シンプル枠 */
	/**
	 * シンプル枠を設置する。
	 * 押すごとにOn/Off する。
	 */
	pre: {
		label: "枠（書式無効）",
		format: " ",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.adjustSelection();
			var text = exnote.getSelectedText(),
				range = exnote.getRange(),
				lines = text.split("\n"), values = [], done = false;
			
			done = text.substr(0, 1) === " ";
			
			for (var i = 0; i < lines.length; i++) {
				var line = lines[i];
				if (done) {
					var re = new RegExp("^("+ this.format +")+");
					line = line.replace(re, "");
				} else {
					line = this.format + line;
				}
				values.push(line);
			}
			
			this.value = values.join("\n");
			var len = this.value.length;
			var caret = {offset: -len, length: len};
			this.insert(this.value, caret);
			return false;
		}
	},
	
	// !段組み
	style2: {
		label:"段組み",
		format: "#style2(L){{\n{textL}\n}}\n#style2(R){{\n{textR}\n}}\n",
		dialog: "<div class=\"row\"><div class=\"col-sm-6\">左側の情報<br /><textarea rows=\"5\" name=\"textL\"></textarea></div><div class=\"col-sm-6\">右側の情報<br /><textarea rows=\"5\" name=\"textR\"></textarea></div></div>",
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement);
			$dialog.find("textarea").exnote();
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var textL = $dialog.find("textarea[name=textL]").val();
			var textR = $dialog.find("textarea[name=textR]").val();
			$(this.textarea).data("exnote").moveToNextLine();
			this.insert(this.format.replace("{textL}", textL).replace("{textR}", textR));
		}
	},
	scrollbox: {
		label: "スクロール付きの枠",
		format: "#scrollbox({width},{height}){{\n{text}\n}}\n",
		options: {minHeight: 0, maxHeight: 1000},
		dialog: '<div><div class="infoarea"></div>横幅：<input type="text" name="width" class="input-sm" placeholder="px / %" /> 高さ：<input type="text" name="height" value="70" class="input-sm" /><br /><div class="previewarea">入力した高さがプレビューできます。<br />現在：<span class="heightholder"></span>px </div></div>',
		onStart: function(){
			if (this.init) return;
			this.init = true;
			
			var $modal = $(this.dialog);
			var contentWidth = $("#body").width();
			if (contentWidth) {
				$("div.infoarea", $modal).html("本文部分の横幅は <strong>" + contentWidth + "px</strong> です。").addClass("alert alert-info");
			}
			$("div.previewarea", $modal).addClass("well");
			
			this.dialog = $modal.wrap("<div></div>").parent().html();
			
		},
		onDialogOpen: function(){
			var helper = this;
			var $modal = $(this.dialogElement);
			
			$("input:text[name=height]").on("keyup", function(){
				var height = parseInt($(this).val(), 10);
				
				if (height > helper.options.minHeight && height < helper.options.maxHeight) {
					$("div.previewarea", $modal)
					.height(height)
						.find("span.heightholder").text(height);
				}
				
			}).triggerHandler("keyup");
			
			
			
		},
		onComplete: function(){
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote"),
				value="";
			
			var width = $("input:text[name=width]", $modal).val(),
				height = $("input:text[name=height]", $modal).val();
			//add unit
			width = /px|%/.test(width) ? width : width.toString() + "px";
			height = /px/.test(height) ? height: height.toString() + "px";
			
			exnote.adjustSelection();
			var text = exnote.getSelectedText();
			var caret = {offset: -4, length: 0};

			if (text.length > 0) {
				exnote.moveToLinehead();
				caret.offset -= text.length;
				caret.length = text.length;
			}
			
			value = this.format.replace("{width}", width).replace("{height}", height).replace("{text}", text);
			
			this.insert(value);
		}
	},
	// !アコーディオン
	accordion: {
		label: "アコーディオン",
		format: "\n#accordion{{\n{text}\n}}\n",
		options: {defval: "* 一つ目のタイトル\n見出し1がタイトルになります。\n\n* 二つ目のタイトル\nここに書いた文章が隠れます\n"},
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			var text = exnote.getSelectedText();
			var offset = 0; // 見出しの後のカーソル位置
			if (text.length > 0) {
				this.value = this.format.replace("{text}", text);
			}
			else {
				this.value = this.format.replace("{text}", this.options.defval);
				offset = 2;
			}
			exnote.moveToNextLine();

			var len = this.value.indexOf("{{\n") + 3 + offset; //3={{\n 
			var caret = {offset: -(this.value.length-len), length:0};
			this.insert(this.value, caret)
			return false;
		}
	},
	// !タブ
	tabbox: {
		label: "タブ",
		format:"\n#tabbox{{\n{text}\n}}\n",
		onStart: function(){
			this.options = ORGM.plugins.accordion.options;
			return ORGM.plugins.accordion.onStart.call(this);
		}
	},
	// !文字サイズ選択
	select_fsize: {
		label: "文字サイズ選択",
		value: "#select_fsize\n",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
		}
	},

	// !要素置き換え
	// !メニューを変更する
	menu: {
		label: "メニューを変更する",
		format: "#menu({pagename})\n",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">メニュー用ページ名<br /></label>' +
				'<div class="controls"><input type="text" name="pagename" placeholder="ページ名" class="typeahead" autocomplete="off"></div>' +
				'</div></form>',
		onDialogOpen: function(){
			//オートコンプリート
			var $dialog = $(this.dialogElement);
			$.when(ORGM.getPages()).done(function(){
				$dialog.find("input[name=linkto]").data("source", ORGM.pages);
			}).fail(function(){});
			
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
			var pagename = $dialog.find("input[name=pagename]").val();
			
			this.insert(this.format.replace("{pagename}", pagename));
		}
	},

	// !メニュー2を変更する
	menu2: {
		label: "メニュー2を変更する",
		format: "#menu2({pagename})\n",
		onStart: function(){
			this.dialog = ORGM.plugins.menu.dialog;
		},
		onDialogOpen: function(){
			ORGM.plugins.menu.onDialogOpen.call(this);
		},
		onComplete: function(){
			ORGM.plugins.menu.onComplete.call(this);
		}
	},
	
	include_skin: {
		label: "デザインを変更する",
		format: "#include_skin({skin})\n",
		init: false,
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">デザイン名<br /></label>' +
				'<div class="controls"><select name="skin"></select></div>' +
				'</div></form>',
		onStart: function(){
			if (this.init) return;
			this.init = true;
			
			var current = $("head > link[href*='main.css']:eq(0)").attr("href");
			
			var $modal = $(this.dialog),
				$select = $("select", $modal);
			for (var i = 0; i < ORGM.designs.length; i++) {
				var design = ORGM.designs[i];
				var disabled = current.indexOf(design) >= 0;
				$select.append("<option"+ (disabled ? "disabled" : "") +"></option>")
					.find("option:last")
					.text(design).val(design);
			}
			
			this.dialog = $modal.wrap("<div></div>").parent().html();
		},
		onDialogOpen: function(){
			
		},
		onComplete: function(){
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
			var skin = $modal.find("select[name=skin]").val();
			
			this.insert(this.format.replace("{skin}", skin));
		}
	},
	// !メインビジュアル
	// !ダイアログにするか検討
	main_visual: {
		label: "メインビジュアル",
		value: "\n#main_visual(画像ファイルのパス,画像の説明,表示位置)\n",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
		}
	},
	// !ロゴ画像の切替
	// !ダイアログにするか検討
	logo_image: {
		label: "ロゴ画像の切替",
		value: "#logo_image(画像ファイル名)\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	
	// !iframe
	iframe: {
		label: "iframe設置",
		format: "#iframe({url}{options})",
		dialog: '<form action="" class="form-horizontal">' +
			'<div class="form-group"><label for="" class="control-label">読み込むURL</label>' +
			'<div class="controls"><input type="text" name="url" placeholder="" /></div></div>' +
			'<div class="form-group"><label for="" class="control-label">サイズ</label>' +
			'<div class="controls">横幅：<input type="text" name="width" class="input-sm" /> 高さ：<input type="text" name="height" class="input-sm" /></div>' +
			'</div></form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var value = "";
			var url = $modal.find("[name=url]").val(), options = "",
				width = parseInt($modal.find("[name=width]").val(), 10),
				height = parseInt($modal.find("[name=height]").val(), 10);
			if (isNaN(width) || isNaN(height)) {
				options = "";
			} else {
				options = "," + height.toString() + "," + width.toString();
			}
			value = this.format.replace("{url}", url).replace("{options}", options);
			this.insert(value);
		}
	},

	// !ダウンロード
	download: {
		label: "ダウンロード",
		format: "&download(${file},${notify},${type}){${text}};",
		options: {
			defval: "ダウンロード",
			filer: {
				options: {search_word: "", select_mode: "exclusive"}
			}
		},
		dialog: '<div class="row"><form action="" class="form-horizontal"><div class="form-group"><label for="" class="col-sm-3 control-label">ファイル</label><div class="col-sm-9"><input type="text" name="file" placeholder="クリックしてファイル選択" class="form-control input-sm"></div></div><div class="form-group"><label for="" class="col-sm-3 control-label">ボタンの種類</label><div class="col-sm-9"><div class="form-group"><div class="col-sm-12"><label class="radio-inline"><input type="radio" name="type" value="" checked> <button type="button" class="btn btn-default btn-sm">ダウンロード</button></label><label class="radio-inline"><input type="radio" name="type" value="primary"> <button type="button" class="btn btn-primary btn-sm">ダウンロード</button></label><label class="radio-inline"><input type="radio" name="type" value="info"> <button type="button" class="btn btn-info btn-sm">ダウンロード</button></label></div></div><div class="form-group"><div class="col-sm-12"><label class="radio-inline"><input type="radio" name="type" value="success"> <button type="button" class="btn btn-success btn-sm">ダウンロード</button></label><label class="radio-inline"><input type="radio" name="type" value="danger"> <button type="button" class="btn btn-danger btn-sm">ダウンロード</button></label><label class="radio-inline"><input type="radio" name="type" value="warning"> <button type="button" class="btn btn-warning btn-sm">ダウンロード</button></label></div></div><div class="form-group"><div class="col-sm-12"><label class="radio-inline"><input type="radio" name="type" value="theme"> <button type="button" class="btn btn-default btn-theme btn-sm" data-toggle="tooltip" title="このボタンは、デザイン毎に色が変わります">ダウンロード</button></label><label class="radio-inline"><input type="radio" name="type" value="link"><button type="button" class="btn btn-link btn-sm">ダウンロード</button></label></div></div></div><div class="form-group notify"><label for="" class="col-sm-3 control-label"></label><div class="col-sm-9"><div class="checkbox"><label><input type="checkbox" name="notify" value="1" /> ダウンロードを通知する</label></div></div></div></form></div>',
		onStart: function(){},
		onDialogOpen: function(){
			var self = this
			  , $modal = $(this.dialogElement)
			  , $filer = $("#orgm_filer_selector");

			$filer
			.on("show.bs.modal", function(){
				$(document).on("selectFiles.pluginDownload", function(e, selectedFiles){
					if (selectedFiles.length > 0) {
						$("input[name=file]").val(selectedFiles[0].filename);
						$filer.modal("hide");
					}
				});
			})
			.on("hidden.bs.modal", function(){
				$(document).off("selectFiles.pluginDownload");
			});
			
			$("input[name=file]").click(function(){
				$filer.find("iframe").data(self.options.filer.options);
				$filer.data("footer", "").modal();
			});
			$("input[name=type]+button", $modal).on("click", function(){
				$(this).closest("label").click();
			});
			
			$('.btn-theme').tooltip({placement:'bottom'});

		},
		onComplete: function(){
			var exnote = $(this.textarea).data("exnote")
			  , $modal = $(this.dialogElement)
			  , value, text
			  , data = {};

			text = exnote.getSelectedText();
			text = text || this.options.defval;
			
			data.file = $("input[name=file]", $modal).val();
			data.notify = $("input[name=notify]").is(":checked") ? "notify" : "";
			data.type = $("input[name=type]:checked").val();
			data.text = text;
			value = $.tmpl(this.format, data).text();
			
			this.insert(value);
		}
	},

	// !TODO:ファイル選択ダイアログを検討
	dlbutton: {
		label: "ダウンロードボタン",
		value: "&dlbutton(ファイルパス);"
	},
	// !TODO:ファイル選択ダイアログを検討
	dllink: {
		label: "ダウンロードリンク",
		value: "&dllink(ファイルパス);"
	},
	
	// !マルチメディア
	show: {
		label: "画像",
		options: {
			defval: {
				text: "ここに文章を入れてください。"
			},
			formats: {
				normal: '&show({name},,{title});',
				float: '#show({name},aroundr,{title})\n{text}\n#clear\n',
				popup: '&show({name},popup,{title});'
			},
			filer: {
				options: {
					search_word: ":image",
					select_mode: ""
				},
				footer: '\
					<div class="btn-group" data-toggle="buttons">\
						<label class="btn btn-default active"><input type="radio" name="type" id="" value="normal" checked> 通常</label>\
						<label class="btn btn-default"><input type="radio" name="type" id="" value="float"> 回り込み</label>\
						<label class="btn btn-default"><input type="radio" name="type" id="" value="popup"> ポップアップ</label>\
					</div>\
					<button type="button" data-submit class="btn btn-primary">貼り付け</button>\
					<button type="button" data-dismiss="modal" class="btn btn-default">キャンセル</button>\
				'
			}
		},
		init: false,
		onStart: function(){
			var self = this;
			var $filer = $("#orgm_filer_selector")
			  , files = [];
			
			$filer.find("iframe").data(self.options.filer.options);
			$filer.data("footer", self.options.filer.footer).modal();
			
			$filer.on("hidden.bs.modal", function(){
				$(document).off("selectFiles.pluginShow");
			})
			.on("click.pluginShow", "[data-submit]", function(){
				self.insertFiles(files);
				$filer.modal('hide');
				$filer.off("click.pluginShow");
			});
			
			$(document).on("selectFiles.pluginShow", function(e, selectedFiles){
				
				files = selectedFiles;
				
			});
			
			return false;
		},
		insertFiles: function(files){
			
			var self = this
			  , $filer = $("#orgm_filer_selector")
			  , exnote = $(this.textarea).data("exnote")
			  , type = $filer.find("div.modal-footer input:radio:checked").val()
			  , text = self.options.defval.text;
			  
			if (type === "float") {
				exnote.adjustSelection();
				text = exnote.getSelectedText();
			}
			
			if (files.length > 0) {
				var value = [];
				for (var i = 0; i < files.length; i++) {
					var file = files[i];
					value.push(self.options.formats[type].replace("{name}", file.filename).replace("{title}", file.title).replace("{text}", text));
					text = self.options.defval.text;//選択テキストを使うのは1度だけ
				}
				value = value.join("\n");
				
				if (type === "float") exnote.moveToNextLine();
				
				var caret = {offset: -value.length, length: value.length};
				self.insert(value, caret);
			}
			
		}
	},

	// !ダミー画像
	showdummy: {
		label: "後で画像を指定",
		format: "&show({dummy});",
		style: {},
		onStart: function(){

			var exnote = $(this.textarea).data("exnote")
				,value = this.format
				,$dialog = $(this.dialogElement);
			
			this.value = this.getDummy();
			
			return;
		},
		getDummy: function(){
			return this.format.replace("{dummy}", new Date().getTime() + '.hdummy');
		}
	},

	// !ビデオ再生
	video: {
		label: "動画",
		format: "#video(${file},${width},${height},${poster})\n",
		options: {
			width: 500,
			height:281,
			filer: {
				options_video: {
					search_word: ":video",
					select_mode: "exclusive"
				},
				options_image: {
					search_word: ":image",
					select_mode: "exclusive"
				}
			}
		},
		dialog: '<div class="row" id="orgm_plugin_modal" data-plugin="video"><form action="" class="form-horizontal"><div class="form-group"><div class="col-sm-12"><div class="radio"><label class="col-sm-5"><input type="radio" name="type" value="myfile" checked> 自分の動画</label><div class="col-sm-7"><div class="input-group"><span class="input-group-addon input-sm">動画ファイル</span><input type="text" name="file" class="form-control input-sm" placeholder="クリックして動画選択" class="form-control input-sm"></div><div class="input-group"><span class="input-group-addon input-sm">ポスター画像</span><input type="text" name="poster" placeholder="クリックして画像を選択" class="form-control input-sm"></div></div></div></div></div><div class="form-group"><div class="col-sm-12"><div class="radio"><label class="col-sm-5"><input type="radio" name="type" value="embed"> YouTubeまたはVimeo</label><div class="col-sm-7"><input type="text" name="embedurl" class="form-control input-sm" placeholder="YouTubeまたはVimeoのURL"></div></div></div></div><hr><div class="form-group col-sm-12"><label class="col-sm-3 control-label">横幅 × 高さ</label><div class="col-sm-9"><div class="input-group col-sm-5"><span class="input-group-addon input-sm">横幅</span><input type="text" name="width" class="form-control input-sm" value="500"><span class="input-group-addon input-sm">px</span></div><div class="col-sm-1 form-group">×</div><div class="input-group col-sm-5"><span class="input-group-addon input-sm">高さ</span><input type="text" name="height" class="form-control input-sm" value="281"><span class="input-group-addon input-sm">px</span></div></div></div></form></div>',
		onStart: function(){},
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement)
				,self = this
				,$filer = $("#orgm_filer_selector");
				
			$("input[name=file]", $dialog).click(function(){
				$filer.find("iframe").data(self.options.filer.options_video);
				$filer.data('footer', '').modal();
			});

			$("input[name=poster]", $dialog).click(function(){
				$filer.find("iframe").data(self.options.filer.options_image);
				$filer.data('footer', '').modal();
			});
			
			$filer
			.on("show.bs.modal", function(){
				$(document).on("selectFiles.pluginVideo", function(e, selectedFiles){
	
					if (selectedFiles.length > 0)
					{
						var file = selectedFiles[0];
						if (selectedFiles[0].type == 'video') {
							$("input[name=file]", $dialog).val(file.filename);
						}
						else {
							$("input[name=poster]", $dialog).val(file.filename);
						}
	
						$filer.modal("hide");	
					}
				});
				
			})
			.on("hidden.bs.modal", function(){
				$(document).off('selectFiles.pluginVideo');
			});
			
				
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement)
				,self = this
				,exnote = $(this.textarea).data("exnote");
			
			var data = {br : "\n"};
			
			if ($("input:radio[name=type]:checked", $dialog).val() == 'myfile') {
				data.file = $("input[name=file]", $dialog).val();
				data.poster = $("input[name=poster]", $dialog).val();
			}
			else {
				data.file = $("input[name=embedurl]", $dialog).val();
			}
			
			data.width = $("input[name=width]", $dialog).val();
			data.height = $("input[name=height]", $dialog).val();
			
			//data.poster
			
			var value = $.tmpl(this.format, data).text() + "\n";
			
			this.insert(value);
		}
	},
	playvideo: {
		label: "ビデオ再生",
		format: "\n#playvideo({movie},{width},{height})\n",
		dialog: '<form action="" class="form-horizontal">' +
			'<div class="form-group"><label for="" class="control-label">動画ファイルパス</label>' +
			'<div class="controls"><input type="text" name="movie" placeholder="" /></div></div>' +
			'<div class="form-group"><label for="" class="control-label">サイズ</label>' +
			'<div class="controls">横幅：<input type="text" name="width" class="input-sm" /> 高さ：<input type="text" name="height" class="input-sm" /></div>' +
			'</div></form>',
		onDialogOpen: function(){
			var $modal = $(this.dialogElement);
			$modal.find("[name=width]").val(300);
			$modal.find("[name=height]").val(300);
			
			
			
		},
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var value = "";
			var movie = $modal.find("[name=movie]").val(),
				width = parseInt($modal.find("[name=width]").val(), 10),
				height = parseInt($modal.find("[name=height]").val(), 10);
			if (isNaN(width)) width = String('');
			if (isNaN(height)) height = String('');
			value = this.format.replace("{movie}", movie).replace("{width}", width).replace("{height}", height);
			this.insert(value);
		}
	},
	// !Vimeo
	vimeo: {
		label: "Vimeo動画",
		format: "\n#vimeo({vimeoId})\n",
		dialog: '<form action="" class="form-horizontal"><div class="form-group"><label for="" class="control-label">Vimeo動画ID</label><div class="controls"><input type="text" name="vimeoid" placeholder="" /></div></div></div></form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var vimeoid = $modal.find("[name=vimeoid]").val();
			var value = this.format.replace("{vimeoId}", vimeoid);
			this.insert(value);
		}
	},
	// !Jplayer
	jplayer: {
		label: "音楽再生",
		format: "#jplayer{{${br}${audios}${br}}}${br}",
		options: {
			filer: {
				options: {
					search_word: ":audio",
					select_mode: "exclusive"
				}
			}
		},
		template: '<div class="col-sm-4"><div class="thumbnail"><a href="#" class="audio" data-audio>クリックで音楽選択</a><div class="caption"><input type="text" name="label" class="form-control input-sm" placeholder="タイトル" /></div></div></li>',
		dialog: '<div class="container"><p>音楽選択　<button type="button" class="btn btn-default btn-sm" data-audio-add>追加</button></p><div class="audiolist row"><div class="thumbnails"></div></div></div>',
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement)
				,self = this
				,$div =	$('div.thumbnails', $dialog)
				,$filer = $("#orgm_filer_selector");

			$dialog
			.on('shown.bs.modal', function(){
				$div.append(self.template);
			})
			.on('click', '[data-audio-add]', function(){
				$div.append(self.template);
			})
			.on('click', '[data-audio]', function(){
				$audio = $(this);
				
				$filer.find("iframe").data(self.options.filer.options);

				$filer
				.on("show.bs.modal", function(){
					$(document).on("selectFiles.pluginJplayer", function(e, selectedFiles){
						if (selectedFiles.length > 0) {
							$audio.html('<i class="orgm-icon orgm-icon-music"></i><span class="audio-filename">'+selectedFiles[0].filename+'</span>');
							$audio.data('audio', selectedFiles[0].filename)
							$filer.modal("hide");
						}
					});
				})
				.on("hidden.bs.modal", function(){
					$(document).off("selectFiles.pluginJplayer");
				});

				$filer.data("footer", "").modal();
				
			});

		},
		onComplete: function(){
			var $dialog = $(this.dialogElement)
				,exnote = $(this.textarea).data("exnote")
				,data = {br: "\n"};


			//slides
			var audios = []
			$(".audiolist .thumbnail", $dialog).each(function(){
				var audio = $('[data-audio]',this).data('audio');
				var title = $('.caption input[name=label]', this).val();
				audios.push(title+","+audio);
			});
			data.audios = audios.join("\n");

			exnote.moveToNextLine();

			var value = $.tmpl(this.format, data).text();
			this.insert(value);
		}
	},
	// !スライドショー
	slide: {
		label: "スライドショー",
		format: "#slide(${args}){{${br}${slides}${br}}}${br}",
		options: {
			filer: {
				options: {search_word: ":image", select_mode: "exclusive"}
			}
		},
		template: '<div class="col-sm-4"><div class="thumbnail"><a href="#" class="image col-sm-12" data-image>クリックで画像選択</a><div class="caption"><span class="span-label"><input type="text" name="label" class="form-control" placeholder="タイトル" /></span><textarea rows="3" class="form-control" placeholder="画像の説明"></textarea></div></div></div>',
		dialog: '<div class="container"><p>表示設定</p><form action="" class="form-horizontal"><div class="form-group"><label for="" class="col-sm-4 control-label">高さ</label><div class="col-sm-8"><div class="radio"><label><input type="radio" name="height_type" value="auto" /> 自動</label></div><div class="radio row"><label class="col-sm-3"><input type="radio" name="height_type" value="custom" checked> 固定する　</label><div class="col-sm-4"><div class="input-group"><input type="text" name="height" class="form-control input-sm" value="450"><span class="input-group-addon input-sm">px</span></div></div></div></div></div><div class="form-group"><label for="" class="col-sm-4 control-label">横幅</label><div class="col-sm-8"><div class="checkbox"><label><input type="checkbox" name="fit" value="fit" checked /> 横幅いっぱいに表示にする</label></div></div></div><div class="form-group"><label class="col-sm-4 control-label">スライドボタン</label><div class="col-sm-8"><div class="checkbox"><label><input type="checkbox" name="slidebutton" value="slidebutton" checked /> 表示する</label></div></div></div></form></div><hr><div class="container"><p>スライド画像　<button type="button" class="btn btn-default btn-sm" data-slides-add>追加</button></p><div class="slidelist"><div class="thumbnails row"></div></div></div>',
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement)
				,self = this
				,$div =	$('div.thumbnails', $dialog)
				,$filer = $("#orgm_filer_selector");
			
			$dialog
			.on('shown.bs.modal', function(){
				$div.append(self.template);
			})
			.on('click', '[data-slides-add]', function(){
				$div.append(self.template);
			})
			.on('click', '[data-image]', function(){
				$image = $(this);
				
				$filer.find("iframe").data(self.options.filer.options);

				$filer
				.on("show.bs.modal", function(){
					$(document).on("selectFiles.pluginSlide", function(e, selectedFiles){
						if (selectedFiles.length > 0) {
							$image.html('<img src="'+selectedFiles[0].filepath+'" alt="" />');
							$image.data('image', selectedFiles[0].filename)
							$filer.modal("hide");
						}
					});
				})
				.on("hidden.bs.modal", function(){
					$(document).off("selectFiles.pluginSlide");
				});

				$filer.data("footer", "").modal();
				
			});



		},
		onComplete: function(){
			var $dialog = $(this.dialogElement)
				, exnote = $(this.textarea).data("exnote")
				, data = {br: "\n"};

			// options
			var args = [];
			if ($("input[name=height_type]:checked", $dialog).val() == 'auto') {
				args.push('auto');
			}
			else {
				args.push($("input[name=height]", $dialog).val());
			}

			if ($("input[name=fit]:checked", $dialog).val() != 'fit') {
				args.push('nofit');
			}

			if ($("input[name=slidebutton]:checked", $dialog).val() != 'slidebutton') {
				args.push('nobutton');
			}
			data.args = args.join(',');

			//slides
			var slides = []
			$(".slidelist .thumbnail", $dialog).each(function(){
				var image = $('[data-image]',this).data('image');
				var title = $('.caption input[name=label]', this).val();
				var subtitle = $('.caption textarea', this).val();
				slides.push(image+","+title+","+subtitle);
			});
			data.slides = slides.join("\n");
			
			exnote.moveToNextLine();
			var value = $.tmpl(this.format, data).text();
			this.insert(value);
		}
	},
	// !スライドショー
	// !TODO: 画像の選択を要検討
	slideshow: {
		label: "スライドショー",
		format: "\n#slideshow({height}){{\n{url1},画像の説明1\n{url2},画像の説明2\n}}\n",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">高さ</label><div class="controls"><input type="text" name="height" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">画像1のURL</label><div class="controls"><input type="text" name="url1" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">画像2のURL</label><div class="controls"><input type="text" name="url2" placeholder="" /></div></div>' + 
				'</form>',
		onDialogOpen: function(){
			var $modal = $(this.dialogElement);
			$modal.find("[name=height]").val(100);
		},
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var height = parseInt($modal.find("[name=height]").val(), 10),
				url1 = $modal.find("[name=url1]").val(),
				url2 = $modal.find("[name=url2]").val();
			if (isNaN(height)) height = 300;
			var value = this.format.replace("{height}", height).replace("{url1}", url1).replace("{url2}", url2);
			this.insert(value);
		}
	},

	// !マルチメディア
	file: {
		label: "ファイル名",
		options: {
			formats: {
				normal: '&show({name},,{title});',
				float: '#show({name},aroundr,{title})',
				popup: '&show({name},colorbox,{title});'
			},
			filer: {
				options: {
					search_word: "",
					select_mode: "exclusive"
				},
				footer: '<span class="muted">クリックするとファイル名が挿入されます。</span>'
			}
		},
		onStart: function(){
			var self = this;
			var $filer = $("#orgm_filer_selector")
			  , files = [];
			
			$filer.find("iframe").data(self.options.filer.options);
			$filer.data("footer", self.options.filer.footer).modal();
			
			$filer.on("hidden.bs.modal", function(){
				$(document).off("selectFiles.pluginFile");
			});
			
			$(document).on("selectFiles.pluginFile", function(e, selectedFiles){
				if (selectedFiles.length > 0) {
					self.insertFilename(selectedFiles[0].filename);
					$filer.modal("hide");
				}
				
			});
			
			return false;
		},
		insertFilename: function(filename){
			var value = filename;
			
			var caret = {offset: -value.length, length: value.length};
			this.insert(filename, caret);
			
		}
	},

	// !グレーボックス
	greybox: {
		label: "グレーボックス",
		format: "&greybox({url},{description},{group}){{text}};",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">画像またはサイトのURL</label><div class="controls"><input type="text" name="url" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">画像またはサイトの説明</label><div class="controls"><input type="text" name="description" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">グループ名</label><div class="controls"><input type="text" name="group" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">表示</label><div class="controls"><input type="text" name="display" placeholder="" /></div></div>' + 
				'</form>',
		onDialogOpen: function(){
			var exnote = $(this.textarea).data("exnote");
			var text = exnote.getSelectedText();

			var value = '';
			if (text.length > 0) {
				value = text;
			}
			var $modal = $(this.dialogElement);
			$modal.find("[name=display]").val(value);
		},
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var url = $modal.find("[name=url]").val(),
				description = $modal.find("[name=description]").val(),
				group = $modal.find("[name=group]").val(),
				display = $modal.find("[name=display]").val();

			var value = this.format.replace("{url}", url).replace("{description}", description).replace("{group}", group).replace("{text}", display);
			this.insert(value);
		}
	},

	// !Googleマップ
	gmap: {
		label: "Googleマップ",
		options: {
			"default": {
				address: '東京駅',
				label: '東京駅',
				content: '説明',
				lat: '',
				lng: '',
				mapHeight: 170
			}
		},
		format: "#gmap(${size},${list},${zoom}){{${br}${address},${label},${content}${br}}}${br}",
		dialog: '<div class="row"><form class="form-horizontal"><div class="form-group"><label for="" class="col-sm-3 control-label">住所</label><div class="col-sm-9"><input type="text" name="address" class="form-control input-sm" value="東京駅" /></div></div><div class="form-group"><label for="" class="col-sm-3 control-label">タイトル</label><div class="col-sm-9"><input type="text" name="label" value="東京駅" class="form-control input-sm" /></div></div><div class="form-group"><label for="" class="col-sm-3 control-label">説明</label><div class="col-sm-9"><input type="text" name="content" value="とても人が多いです。" class="form-control input-sm" /></div></div><div class="form-group"><label for="" class="col-sm-3 control-label">横幅 x 高さ</label><div class="col-sm-9"><div class="row"><div class="input-group col-sm-5"><span class="input-group-addon input-sm">横幅</span><input type="text" name="width" class="form-control input-sm" value="" placeholder="100%"><span class="input-group-addon input-sm">px</span></div><div class="form-group col-sm-1">x</div><div class="input-group col-sm-5"><span class="input-group-addon input-sm">高さ</span><input type="text" name="height" class="form-control input-sm" value="" placeholder="300"><span class="input-group-addon input-sm">px</span></div></div><span class="help-block">※指定しない場合は、高さは300px、横幅は100%で表示されます。<span></div></div><div class="form-group"><label for="" class="col-sm-3 control-label">リストの表示</label><div class="col-sm-9"><div class="checkbox"><label><input type="checkbox" name="list" value="list" checked />リストを表示する</label></div></div></div></form></div><hr><div class="orgm-gmap"><div id ="map_canvas" data-map-width="" data-map-height="170" data-map-zoom=""></div><div class="gmap-markers"><ul><li data-mapping="m_1" data-lat="" data-lng=""><div class="info-box"><h5></h5><p></p></div></li></ul></div></div>',
		onStart: function(){},
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement)
				,self = this;

			// ジオコーダのコンストラクタ
			var geocoder = new google.maps.Geocoder();

			$dialog
			.on("shown.bs.modal change", function(){
				var label = $("input[name=label]",$dialog).val();
				var content = $("input[name=content]",$dialog).val();
				var address = $("input[name=address]",$dialog).val();

				if (label != '') {
					geocoder.geocode({address: address}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							$("[data-lat]", $dialog).data('lat', results[0].geometry.location.lat());
							$("[data-lng]", $dialog).data('lng', results[0].geometry.location.lng());

							$(".gmap-markers", $dialog)
								.find(".info-box h5").text(label).end()
								.find(".info-box p").text(content).end();
		
							$(".orgm-gmap").gmap();
							$(".orgm-gmap").gmap('clearMarker');
							$(".orgm-gmap").gmap('addMarker', $('[data-mapping]',$dialog));
						}
						else {
							switch(status) {
								case google.maps.GeocoderStatus.ERROR :
									data.error = "サーバとの通信時に何らかのエラーが発生しました";
									break;
								case google.maps.GeocoderStatus.INVALID_REQUEST:
									data.error = "リクエストに問題があります";
								break;
								case google.maps.GeocoderStatus.OVER_QUERY_LIMIT:
									data.error = "短時間に変更しすぎです";
									break;
								case google.maps.GeocoderStatus.REQUEST_DENIED:
									data.error = "このページではジオコーダの利用が許可されていません";
									break;
								case google.maps.GeocoderStatus.UNKNOWN_ERROR:
									data.error = "サーバ側でなんらかのトラブルが発生しました";
									break;
								case google.maps.GeocoderStatus.ZERO_RESULTS:
									data.error = "見つかりません";
									break;
								default :
									data.error = "エラーが発生しました";
							}
						}
					});
				}
				else {
					$(".gmap-markers", $dialog)
						.find(".info-box h5").text(label).end()
						.find(".info-box p").text(content).end();
				}
			});
			
			// マーカーリストの表示・非表示
			$('input[name=list]')
			.on('change', function(){
				if ($(this).is(':checked')) {
					$(".gmap-markers", $dialog).show();
				}
				else {
					$(".gmap-markers", $dialog).hide();
				}
			});

		},
		onComplete: function(){

			var $dialog = this.dialogElement
				,exnote = $(this.textarea).data("exnote")
				,self = this
				,data = {br: "\n"}
				,zoom = $(".orgm-gmap").gmap("getZoom");

			data.address = $("input[name=address]", $dialog).val();
			data.label   = $("input[name=label]", $dialog).val();
			data.content = $("input[name=content]", $dialog).val();
			data.height  = $("input[name=height]", $dialog).val();
			data.width   = $("input[name=width]", $dialog).val();
			data.zoom    = 'zoom=' + zoom;
			data.size = '';

			if (data.height != '') {
				if (data.width == '') {
					data.size = data.height;
				}
				else {
					data.size = data.width + 'x' + data.height;
				}
			}
			else {
				if (data.width != '') {
					data.size = '300x'+data.width;
				}
			}
			
			data.list    = 'hide';
			if ($("input[name=list]:checked", $dialog).length) {
				data.list = '';
			}

			exnote.moveToNextLine();
			var value = $.tmpl(this.format, data).text();
			this.insert(value);

		}
	},
	
	gmapfun: {
		label: "Googleマップ",
		format: "\n#gmapfun{{\n{address},{title}\n}}\n",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">住所</label><div class="controls"><input type="text" name="address" placeholder="" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">タイトル</label><div class="controls"><input type="text" name="title" placeholder="" /></div></div>' + 
				'</form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var address = $modal.find("[name=address]").val(),
				title = $modal.find("[name=title]").val();

			var value = this.format.replace("{address}", address).replace("{title}", title);
			this.insert(value);
		}
	},
	
	// !アクセス制御
	secret: {
		label: "認証ページ",
		format: "\n#secret({pass})\n",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">パスワード</label><div class="controls"><input type="text" name="pass" placeholder="" /></div></div>' + 
				'</form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var pass = $modal.find("[name=pass]").val();
			var value = this.format.replace("{pass}", pass);
			this.insert(value);
		}
	},
	// !閉鎖
	close: {
		label: "閉鎖",
		value: "#close\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !有効期限設定
	autoclose: {
		label: "有効期限設定",
		format: "\n#autoclose({date},{url})\n",
		focus: false,
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">日付</label><div class="controls"><input type="text" name="date" placeholder="" id="dp1" size="16" value=""  data-date=""  data-date-format="yyyy-mm-dd" /></div></div>' + 
				'<div class="form-group"><label for="" class="control-label">転送先</label><div class="controls"><input type="text" name="url" placeholder="" /></div></div>' + 
				'</form>',
		onDialogOpen: function(){
			var dt = new Date();
			var y = dt.getFullYear(),
				m = dt.getMonth()+1,
				d = dt.getDate();
				
			if (m < 10) { m = '0' + m; }
			if (d < 10) { d = '0' + d; }
				
			var	nowdate = y+'-'+m+'-'+d;

			var $modal = $(this.dialogElement);
			$modal.find("[name=date]").attr('data-date', nowdate).val(nowdate);

			$("#dp1").datepicker({language: "japanese"});
			$(".datepicker").css({zIndex: 1100});
		},
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var date = $modal.find("[name=date]").val(),
				url = $modal.find("[name=url]").val();
			var value = this.format.replace("{date}", date).replace("{url}", url);
			this.insert(value);
		}
	},
	// !転送
	redirect: {
		label: "転送",
		format: "\n#redirect({url})\n",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">転送先</label><div class="controls"><input type="text" name="url" placeholder="ページ名、またはURL" /></div></div>' + 
				'</form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var url = $modal.find("[name=url]").val();
			var value = this.format.replace("{url}", url);
			this.insert(value);
		}
	},
	
	// !Facebook
	fb_page: {
		label: "Facebookタブ",
		value: "#fb_page\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	fb_likegate: {
		label: "Facebookいいね切替タブ",
		format: "\n#fb_likegate({pagename})\n",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">いいね前のページ名</label><div class="controls"><input type="text" name="pagename" placeholder="" /></div></div>' + 
				'</form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var pagename = $modal.find("[name=pagename]").val();
			var value = this.format.replace("{pagename}", pagename);
			this.insert(value);
		}
	},

	// !ナビの項目追加
	addnav: {
		label: "ナビの追加",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label class="col-sm-3 control-label">リンク先</label>' +
				'<div class="col-sm-9"><input type="text" name="linkto" placeholder="ページ名、またはURL" class="typeahead form-control" autocomplete="off"></div>' +
				'</div><div class="form-group">' +
				'<label class="col-sm-3 control-label">リンク文字</label>'+
				'<div class="col-sm-9"><input type="text" name="alias" placeholder="リンク文字を変更できます。" class="form-control"></div></div>' +
				'</form>',
		onStart: function(){
			this.options = ORGM.plugins.link.options;
		},
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement);

			ORGM.plugins.link.onDialogOpen.call(this);

			$.when(ORGM.getPagesForTypeahead()).done(function(){
				
				$dialog.find("input[name=linkto]").typeahead({
					local: ORGM.pagesForTypeahead,
					engine: ORGM.tmpl,
					template: ORGM.pageSuggestionTemplateForTypeahead,
				});
			}).fail(function(){});


		},
		onComplete :function(){
			var $dialog = $(this.dialogElement);
			var exnote = $(this.textarea).data("exnote");

			var alias = $dialog.find("input[name=alias]").val();
			var linkto = $dialog.find("input[name=linkto]").val();
			var link_str = "";

			if (alias === "") {
				link_str = $.tmpl(this.options.formats.normal, {linkto:linkto}).text();
			} else {
				link_str = $.tmpl(this.options.formats.alias, {alias:alias, linkto:linkto}).text();
			}
			
			// カーソルを適切な位置へ移動する
			exnote.removeSelection();

			var value = exnote.getValue(),
				range = exnote.getRange(),
				pos = range.position;
			
			while (pos > 0 && value.substr(pos-1, 1).charCodeAt(0) === 10) {	
				pos--;
			}
			exnote.setRange(pos, 0);
			
			exnote.moveToNextLine();
			this.insert('- ' + link_str + "\n");
		}
	},

	// !リンク
	link: {
		label: "リンク",
		options: {
			formats: {
				normal: "[[${linkto}]]",
				alias: "[[${alias}>${linkto}]]",
				button: "&button(${linkto},${type}){${alias}};",
				newwin: "&openwin(_blank){${alias}};"
			}
		},
		dialog: '<div class="conainer"><form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="col-sm-3 control-label">リンク先<br /></label>' +
				'<div class="col-sm-9"><input type="text" name="linkto" placeholder="ページ名、またはURL" autocomplete="off" class="typeahead form-control"></div>' +
				'</div><div class="form-group">' +
				'<label for="" class="col-sm-3 control-label">リンク文字</label>'+
				'<div class="col-sm-9"><input type="text" name="alias" placeholder="リンク文字を変更できます。" class="form-control"></div></div>' +
				'<div class="form-group"><label for="" class="col-sm-3 control-label">リンクの種類</label><div class="col-sm-9"><div class="form-group"><div class="col-sm-12"><label class="radio-inline"><input type="radio" name="type" value="link" checked><button type="button" class="btn btn-link btn-sm">リンク文字</button></label><label class="radio-inline"><input type="radio" name="type" value="primary"> <button type="button" class="btn btn-primary btn-sm">リンク文字</button></label><label class="radio-inline"><input type="radio" name="type" value="info"> <button type="button" class="btn btn-info btn-sm">リンク文字</button></label></div></div><div class="form-group"><div class="col-sm-12"><label class="radio-inline"><input type="radio" name="type" value="success"> <button type="button" class="btn btn-success btn-sm">リンク文字</button></label><label class="radio-inline"><input type="radio" name="type" value="danger"> <button type="button" class="btn btn-danger btn-sm">リンク文字</button></label><label class="radio-inline"><input type="radio" name="type" value="warning"> <button type="button" class="btn btn-warning btn-sm">リンク文字</button></label></div></div><div class="form-group"><div class="col-sm-12"><label class="radio-inline"><input type="radio" name="type" value="default"> <button type="button" class="btn btn-default btn-sm">リンク文字</button></label><label class="radio-inline"><input type="radio" name="type" value="theme"> <button type="button" class="btn btn-default btn-theme btn-sm" data-toggle="tooltip" title="このボタンは、デザイン毎に色が変わります">リンク文字</button></label></div></div></div></div>' +
				'<div class="form-group newwin"><label for="" class="col-sm-3"></label><div class="col-sm-9"><div class="checkbox"><label ><input type="checkbox" name="newwin" /> 新しいウィンドウで開く</label></div></div></div>' +
				'</form></div>',
		focus: false,
		onDialogOpen: function(){
			var self = this;
			var $dialog = $(this.dialogElement);

			$("input[name=type]+button", $dialog).on("click", function(){
				$(this).closest("label").click();
			});

			$.when(ORGM.getPagesForTypeahead()).done(function(){
				
				$dialog.find("input[name=linkto]").typeahead({
					local: ORGM.pagesForTypeahead,
					engine: ORGM.tmpl,
					template: ORGM.pageSuggestionTemplateForTypeahead,
				});
				
				setTimeout(function(){
					$("input:text[name=linkto]", $dialog).focus().select();
				}, 25);
				
			}).fail(function(){});
			
			$('.btn-theme').tooltip({placement:'bottom'});
			
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var alias = $dialog.find("input[name=alias]").val();
			var linkto = $dialog.find("input[name=linkto]").val();
			var newwin = $dialog.find("input[name=newwin]").is(":checked");
			var link_str = "";
			
			var type = $("input[name=type]:checked", $dialog).val();
			
			if (newwin) {
				if (type === "link") {
					alias = (alias.length > 0) ? alias : linkto;
					alias = $.tmpl(this.options.formats.alias, {alias:alias, linkto:linkto}).text();
				}
				else {
					alias = (alias.length > 0) ? alias : linkto;
					alias = $.tmpl(this.options.formats.button, {alias:alias, linkto:linkto, type:type}).text();
				}
				link_str = $.tmpl(this.options.formats.newwin, {alias:alias}).text();
			}
			else {
				if (type != 'link') {
					alias = (alias.length > 0) ? alias : linkto;
					link_str = $.tmpl(this.options.formats.button, {alias:alias, linkto:linkto, type:type}).text();
				}
				else {
					if (alias === "") {
						link_str = $.tmpl(this.options.formats.normal, {linkto:linkto}).text();
					} else {
						link_str = $.tmpl(this.options.formats.alias, {alias:alias, linkto:linkto}).text();
					}
				}
			}
			this.insert(link_str);
		}
	},
	// !別ウィンドウ
	otherwin: {
		label: "別ウィンドウ",
		format: "&otherwin({url}){{text}};",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">URL<br /></label>' +
				'<div class="controls"><input type="text" name="url" placeholder="リンク先のURL" /></div>' +
				'</div><div class="form-group">' +
				'<label for="" class="control-label">表示文字</label>'+
				'<div class="controls"><input type="text" name="text" placeholder="" /></div></div></form>',
		onDialogOpen: function(){
			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();

			if (text.length > 0) {
				var $modal = $(this.dialogElement);
				text = text.replace(/\n|\r/g, '&br;');
				$modal.find("[name=text]").val(text);
			}
		},
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var url = $modal.find("[name=url]").val(),
				text = $modal.find("[name=text]").val();
			var value = this.format.replace("{url}", url).replace("{text}", text);
			this.insert(value);
		}
	},
	// !アンカー
	aname: {
		label:"アンカー",
		format: "&aname({aname});",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">アンカー名<br /></label>' +
				'<div class="controls"><input type="text" name="aname" placeholder="" /></div>' +
				'</div></form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var aname = $modal.find("[name=aname]").val();
			var value = this.format.replace("{aname}", aname);
			this.insert(value);
		}
	},
	// !戻る
	back: {
		label: "戻る",
		format: "\n#back({text},{align},{hr},{url})\n",
		options: {
			align: [
				{label: "左", value: "left"},
				{label: "中央", value: "center"},
				{label: "右", value: "right"}
			],
			hr: [
				{label: "表示する", value: "1"},
				{label: "表示しない", value: "0"}
			]
		},
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">表示文字<br /></label>' +
				'<div class="controls"><input type="text" name="text" /></div>' +
				'</div><div class="form-group">' +
				'<label for="" class="control-label">表示位置<br /></label>' +
				'<div class="controls"><input type="radio" name="align" /></div></div>' +
				'</div><div class="form-group">' +
				'<label for="" class="control-label">水平線<br /></label>' +
				'<div class="controls"><input type="radio" name="hr" /></div></div>' +
				'</div><div class="form-group">' +
				'<label for="" class="control-label">戻り先<br /></label>' +
				'<div class="controls"><input type="text" name="url"  placeholder="ページ名、またはURL" /></div>' +
				'</div></form>',
		onStart: function(){
			var helper = this;
			if (typeof this.init == "undefined") {
				this.init = true;

				// make dialog
				var $dialog = $(this.dialog);
				$dialog.find("input:radio").each(function(){
					var $this = $(this);
					var name = $(this).attr("name");
					
					var options = helper.options[name];
					for (var i in options) {
						var option = options[i];
						$this.parent().append('<label class="radio inline"><input type="radio" name="'+name+'" /></label>')
							.find("label:last-child")
							.css({cursor: "pointer", marginLeft: "20px"})
								.find("input").val(option.value)
								.after("<span></span>")
									.next().text(' '+option.label);
					}
				}).remove();

				this.dialog = $dialog.wrap("<div></div>").parent().html();
			}
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");

			var data = {}, text, value = this.format;
			
			$dialog.find("input:radio").each(function(){
				var name = $(this).attr("name");
				data[name] = $(this).val();
			});

			$dialog.find("input:text").each(function(){
				var name = $(this).attr("name");
				data[name] = $(this).val();
			});

			for (var key in data) {
				var val = data[key];
				value = value.replace("{"+key+"}", val);
			}
			exnote.insert(value);
		}
	},
	// !タグ
	tag: {
		label: "タグ",
		format: "&tag({tag});",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">タグ<br /></label>' +
				'<div class="controls"><input type="text" name="tag" placeholder="タグ名を「,（カンマ）」区切りで指定します" /></div>' +
				'</div></form>',
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var tag = $dialog.find("input[name=tag]").val();
			tag = tag.replace(/、/g, ",");
			var value = this.format.replace("{tag}", tag);
			this.insert(value);
		}
	},
	// !タグリスト
	taglist: {
		label: "タグ付きページのリスト",
		value: "#taglist\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !タグクラウド
	tagcloud: {
		label: "タグのリスト",
		value: "#tagcloud\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	
	// !ページ情報
	title: {
		label: "ページタイトル",
		value: "TITLE: ここにタイトルを入れる\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !自動リンク無効
	noautolink: {
		label: "自動リンク無効",
		value: "NOAUTOLINK:\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !キーワード
	keywords: {
		label: "キーワードの変更",
		format: "\n#keywords({keys})\n",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">キーワード<br /></label>' +
				'<div class="controls"><input type="text" name="keys" placeholder="キーワードを「,（カンマ）」区切りで指定します" /></div>' +
				'</div></form>',
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var keys = $dialog.find("input[name=keys]").val();
			keys = keys.replace(/、/g, ",");
			var value = this.format.replace("{keys}", keys);
			this.insert(value);
		}
	},
	// !サイトの説明
	description: {
		label: "サイトの説明の変更",
		format: "\n#description({text})\n",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">サイトの説明<br /></label>' +
				'<div class="controls"><input type="text" name="text" placeholder="サイトの説明" /></div>' +
				'</div></form>',
		onDialogOpen: function(){
			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();

			if (text.length > 0) {
				var $modal = $(this.dialogElement);
				text = text.replace(/\n|\r/g, '');
				$modal.find("[name=text]").val(text);
			}
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var text = $dialog.find("input[name=text]").val();
			text = text.replace(/、/g, ",");
			var value = this.format.replace("{text}", text);
			this.insert(value);
		}
	},
	// !フリータイトル
	freetitle: {
		label: "フリータイトル",
		value: "FREETITLE: ここにタイトルを入れる\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !ヘッドコピー
	headcopy: {
		label: "ヘッドコピー",
		value: "HEAD:ここにヘッドコピーを書く\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !クロール禁止
	noindex: {
		label: "クロール禁止",
		value: "NOINDEX:\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !サイトマップURL
	sitemap: {
		label: "サイトマップのURL",
		value: "",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">URL<br /></label>' +
				'<div class="controls"><input type="text" name="url" placeholder="" readonly /></div>' +
				'</div></form>' +
				'<span class="help">コピーしてご利用ください</span>',
		onDialogOpen: function(){
			var $modal = $(this.dialogElement);
			var url = ORGM.baseUrl + '?cmd=sitemap';
			$modal.find("[name=url]").val(url).css({cursor:"pointer",backgroundColor:"#fff"}).click(function(){
				$(this).focus().select();
			});
			$modal.find(".modal-complete").hide();
			this.addToRecent();
		}
	},
	// !RSS
	rss: {
		label: "RSS",
		value: "",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">URL<br /></label>' +
				'<div class="controls"><input type="text" name="url" placeholder="" readonly /></div>' +
				'</div></form>' +
				'<span class="help">コピーしてご利用ください</span>',
		onDialogOpen: function(){
			var $modal = $(this.dialogElement);
			var url = ORGM.baseUrl + '?cmd=rss';
			$modal.find("[name=url]").val(url).css({cursor:"pointer",backgroundColor:"#fff"}).click(function(){
				$(this).focus().select();
			});
			$modal.find(".modal-complete").hide();
			this.addToRecent();
		}
	},

	// !PayPal カートを見るボタン
	pp_cart: {
		label: "カートを見るボタン",
		format: "&pp_cart({account});",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">アカウント<br /></label>' +
				'<div class="controls"><input type="text" name="account" placeholder="PayPalアカウント（メールアドレス）を入力します" /></div>' +
				'</div></form>',
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var account = $dialog.find("input[name=account]").val();
			var value = this.format.replace("{account}", account);
			this.insert(value);
		}
	},
	// !PayPal カートに追加ボタン
	pp_button: {
		label: "カートに追加ボタン",
		format: "&pp_button({account},{product},{price});",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">アカウント</label><div class="controls"><input type="text" name="account" placeholder="PayPalアカウント（メールアドレス）を入力します" /></div></div>' +
				'<div class="form-group"><label for="" class="control-label">商品名</label><div class="controls"><input type="text" name="product" placeholder="" /></div></div>' +
				'<div class="form-group"><label for="" class="control-label">価格</label><div class="input-prepend"><span class="add-on">¥</span><input type="text" name="price" placeholder="" class="col-sm-2" /></div></div>' +
				'</form>',
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var data = {};
			$dialog.find("div.form-group input:text").each(function(){
				var name = $(this).attr("name");
				data[name] = $(this).val();
			});
			data.price = data.price.replace(',', '').replace('¥', '');
			var value = this.replaceFormat(data);
			this.insert(value);
		}
	},
	
	// !検索窓
	search: {
		label: "検索窓",
		value: "#search\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !検索窓（メニュー）
	search_menu: {
		label: "検索窓（メニュー）",
		value: "#search_menu\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !Google検索窓
	gsearch: {
		label: "Google検索窓",
		format: "#gsearch({domain})\n",
		onStart: function(){
			var value = this.format.replace("{domain}", location.hostname);
			$(this.textarea).data("exnote").moveToNextLine();
			this.insert(value);
			return false;
		}
	},
	
	// !お気に入り
	addfavorite: {
		label: "お気に入りに登録",
		format: "&addfavorite({title});",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">サイト名<br /></label>' +
				'<div class="controls"><input type="text" name="title" placeholder="サイト名" /></div>' +
				'</div></form>',
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var title = $dialog.find("input[name=title]").val();
			this.insert(this.format.replace("{title}", title));
		}
	},
	// !Yahooブックマーク
	yahoobookmark: {
		label: "Yahoo!ブックマーク",
		format: "&yahoobookmark({title});",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">サイト名<br /></label>' +
				'<div class="controls"><input type="text" name="title" placeholder="サイト名" /></div>' +
				'</div></form>',
		onComplete: function(){
			var $dialog = $(this.dialogElement);
			var title = $dialog.find("input[name=title]").val();
			this.insert(this.format.replace("{title}", title));
		}
	},
	// !Googleリーダー
	addgoogle: {
		label: "Googleリーダー",
		value: "&addgoogle;"
	},
	
	// !サイト情報
	// !最終更新日
	lastmod: {
		label:"最終更新日",
		format: "&lastmod({pagename});",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">ページ名<br /></label>' +
				'<div class="controls"><input type="text" name="pagename" placeholder="ページ名" autocomplete="off" class="typeahead"></div>' +
				'</div></form>',
		onDialogOpen: function(){
			//オートコンプリート
			var $dialog = $(this.dialogElement);
			$.when(ORGM.getPagesForTypeahead()).done(function(){
				$dialog.find("input[name=pagename]").typeahead({
					local: ORGM.pagesForTypeahead,
					engine: ORGM.tmpl,
					template: ORGM.pageSuggestionTemplateForTypeahead,
				});
			}).fail(function(){});
			
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
			var pagename = $dialog.find("input[name=pagename]").val();
			
			this.insert(this.format.replace("{pagename}", pagename));
		}
	},
	// !人気の○件
	popular: {
		label: "人気の○件",
		value: "\n#popular(5)\n",
		onStart: function(){
			var exnote, value = "", caret;
			exnote = $(this.textarea).data("exnote");
			caret = {
					offset: -3,
					length: 1
			};
			exnote.insert(this.value, caret);
			return false;
		}
	},
	// !更新の○件
	recent: {
		label: "最新の○件",
		value: "\n#recent(5)\n",
		onStart: function(){
			return ORGM.plugins.popular.onStart.call(this);
		}
	},
	// !QRコード
	qr: {
		label: "QRコード",
		format: "&qr({url});",
		dialog: '<form action="" class="form-horizontal">' + 
				'<div class="form-group"><label for="" class="control-label">URL</label><div class="controls"><input type="text" name="url" placeholder="" /></div></div>' + 
				'</form>',
		onComplete: function(){
			var $modal = $(this.dialogElement);
			var url = $modal.find("[name=url]").val();
			var value = this.format.replace("{url}", url);
			this.insert(value);
		}
	},
	// Newマーク
	newmark: {
		label: "Newマーク",
		format: "&new({pagename},nolink);",
		dialog: '<form action="" class="form-horizontal"><div class="form-group">' + 
				'<label for="" class="control-label">対象ページ<br /></label>' +
				'<div class="controls"><input type="text" name="pagename" placeholder="対象のページ名を指定" autocomplete="off" class="typeahead"></div>' +
				'</div></form>',
		onDialogOpen: function(){
			//オートコンプリート
			var $dialog = $(this.dialogElement);
			$.when(ORGM.getPagesForTypeahead()).done(function(){
				$dialog.find("input[name=pagename]").typeahead({
					local: ORGM.pagesForTypeahead,
					engine: ORGM.tmpl,
					template: ORGM.pageSuggestionTemplateForTypeahead,
				});
			}).fail(function(){});
			
		},
		onComplete: function(){
			var $dialog = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
			var pagename = $dialog.find("input[name=pagename]").val();
			
			this.insert(this.format.replace("{pagename}", pagename));
		}
	},
	
	
	// !ソーシャルプラグイン
	// !複数のソーシャルボタン
	share_buttons:{
		label: "シェアボタン",
		format: "\n#share_buttons({buttons})\n",
		dialog: '<div class="share_buttons"><ul class="nav nav-pills"><li><a href="#" data-name="facebook" class="facebook active"><i class="orgm-icon orgm-icon-facebook-2"></i></a></li><li><a href="#" data-name="twitter" class="twitter active"><i class="orgm-icon orgm-icon-twitter-2"></i></a></li><li><a href="#" class="google-plus active" data-name="google-plus"><i class="orgm-icon orgm-icon-google-plus-2"></i></a></li></ul></div><p>※ クリックでオンオフを切り替えれます</p>',
		onStart: function(){},
		onDialogOpen: function(){
			
			var $dialog = this.dialogElement
				,self = this;
				
				$(".share_buttons a", $dialog).click(function(){
					
					if ($(this).hasClass("active")) {
						$(this).removeClass("active");
					}
					else {
						$(this).addClass("active");
					}
				});
				
				$(".modal-complete", $dialog).click(function(){
					if ($(".share_buttons a.active", $dialog).length == 0) {
						alert("シェアボタンは、1つ以上選択してください。");
						return false;
					}
				});
				

				
		},
		onComplete: function(){
			var $dialog = this.dialogElement
				,self = this
				,buttons = [];

			$(".share_buttons a.active", $dialog).each(function(){
				buttons.push($(this).data("name"));
			});
			
			buttons = buttons.join(',');
			
			this.insert(this.format.replace('{buttons}', buttons));
		}
	},
	social_buttons: {
		label: "複数のソーシャルボタン"	,
		value: "\n#social_buttons\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !Facebook いいねボタン
	fb_likebutton: {
		label: "Facebook いいねボタン",
		value: "\n#fb_likebutton\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !Facebook いいねボックス
	fb_likebox: {
		label: "Facebook いいねボックス",
		format: "#fb_likebox(${url},${options})\n",
		dialog: '<div class="row"><div class="col-sm-6"><form class=""><div class="form-group"><label for="" class="control-label">FacebookページのURL</label><div class="controls"><input type="text" name="url" value="http://www.facebook.com/hokuken" class="form-control input-sm" /></div></div><div class="form-group"><label for="" class="control-label">横幅 × 高さ</label><div class="controls row"><div class="col-sm-5"><input type="text" name="width" value="240" class="form-control input-sm" /></div><div class="col-sm-1"> × </div><div class="col-sm-5"><input type="text" name="height" placeholder="自動" class="form-control input-sm" /></div></div></div><div class="form-group"><label for="" class="control-label">色合い</label><div class="controls"><label class="radio-inline"><input type="radio" name="color_scheme" value="light" checked /> 明</label><label class="radio-inline"><input type="radio" name="color_scheme" value="dark" /> 暗</label></div></div></form><hr><form class=""><div class="row"><label for="" class="col-sm-6">表示設定</label></div><div class="row"><label for="" class="col-sm-7">枠線</label><div class="col-sm-5"><input type="hidden" name="show_border" value="false"><div class="checkbox"><label><input type="checkbox" name="show_border" value="true" checked /> 表示する</label></div></div></div><div class="row"><label for="" class="col-sm-7">プロフィール写真</label><div class="col-sm-5"><input type="hidden" name="show_faces" value="false"><div class="checkbox"><label><input type="checkbox" name="show_faces" value="true" checked /> 表示する</label></div></div></div><div class="row"><label for="" class="col-sm-7">投稿内容</label><div class="col-sm-5"><input type="hidden" name="stream" value="false"><div class="checkbox"><label><input type="checkbox" name="stream" value="true" checked /> 表示する</label></div></div></div><div class="row"><label for="" class="col-sm-7">ヘッダー</label><div class="col-sm-5"><input type="hidden" name="header" value="false"><div class="checkbox"><label><input type="checkbox" name="header" value="true" checked> 表示する</label></div></div></div></form></div><div class="col-sm-6"><p>例）</p><div class="previewarea"></div></div></div>',
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement)
				,self = this;
			
			$dialog
			.on("shown.bs.modal change", function(e){
				self.preview(e.type === "change");
			});
			
		},
		onComplete: function(){
			var $modal = $(this.dialogElement)
			  , exnote = $(this.textarea).data("exnote")
			  , data = {}, options = {};

			_.forEach($("input", $modal).serializeArray(), function(value, index){
				
				switch (value.name)
				{
					case "url":
						data[value.name] = value.value;
						break;
					case "width":
					case "height":
						if (value.value.length > 0) {
							options[value.name] = value.name + "=" + value.value;
						}
						break;
					case "show_faces":
						if (value.value === "false") {
							options[value.name] = "noface";
						} else {
							delete options[value.name];
						}
						break;
					case "color_scheme":
						options[value.name] = "colorscheme="+value.value;
						break;
					case "stream":
						if (value.value === "false") {
							options[value.name] = "nostream";
						} else {
							delete options[value.name];
						}
						break;
					case "show_border":
						if (value.value === "false") {
							options[value.name] = "noborder";
						} else {
							delete options[value.name];
						}
						break;
					case "header":
						if (value.value === "false") {
							options[value.name] = "noheader";
						} else {
							delete options[value.name];
						}
						break;
				}
				
			});
			
			data.options = _.values(options).join(",");
			
			
			var value = $.tmpl(this.format, data).text();

			exnote.moveToNextLine();			
			this.insert(value);
		},
		preview: function(init){
			init = init || false;
			var $dialog = this.dialogElement
				,self = this
				,data;
			
			data = $("input, select, textarea", $dialog).serialize();
			
			$.getJSON(ORGM.baseUrl + "?cmd=fb_likebox&preview=1", data, function(res){
				$(".previewarea", $dialog).html(res.html);
				if (init) FB.init({xfbml:true});
			})
		}
	},
	// !Facebook おすすめ一覧
	fb_recommends: {
		label: "Facebook おすすめ一覧",
		value: "\n#fb_recommends\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !Facebook コメント欄
	fb_comments: {
		label: "Facebook コメント欄",
		format: "#fb_comments(${url},${options})\n",
		dialog: '<div class="row"><div class="col-sm-6"><form action="" class=""><div class="form-group"><label for="" class="control-label">URL</label><div class="controls"><input type="text" name="url" placeholder="指定なしで現在のページ" class="form-control input-sm"></div></div><div class="form-group"><label for="" class="control-label">横幅</label><div class="row"><div class="input-group col-sm-5"><input type="text" name="width" value="550" class="form-control input-sm" placeholder="" /><span class="input-group-addon input-sm">px</span></div></div></div><div class="form-group"><label for="" class="control-label">表示コメント数</label><div class="controls row"><div class="col-sm-4"><input type="text" name="num_posts" value="2" class="form-control input-sm" placeholder="" /></div></div></div><div class="form-group"><label for="" class="control-label">色合い</label><div class="controls"><label class="radio-inline"><input type="radio" name="color_scheme" value="light" checked /> 明</label><label class="radio-inline"><input type="radio" name="color_scheme" value="dark" /> 暗</label></div></div></form></div><div class="col-sm-6"><p>例）</p><div class="previewarea"></div></div></div>',
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement)
				,self = this;
			
			$dialog
			.on("shown.bs.modal change", function(e){
				self.preview(e.type === "change");
			});
			
		},
		onComplete: function(){
			var $modal = $(this.dialogElement)
			  , exnote = $(this.textarea).data("exnote")
			  , data = {}, options = {};
			
			_.forEach($("input", $modal).serializeArray(), function(value, index){
				
				switch (value.name)
				{
					case "url":
						data[value.name] = value.value;
						break;
					case "width":
						if (value.value.length > 0) {
							options[value.name] = value.name + "=" + value.value;
						}
						break;
					case "num_posts":
							options[value.name] = value.name + "=" + value.value;
						break;
					case "color_scheme":
						options[value.name] = "colorscheme="+value.value;
						break;
				}
				
			});


			data.options = _.values(options).join(",");
			
			
			var value = $.tmpl(this.format, data).text();

			exnote.moveToNextLine();			
			this.insert(value);
		},
		preview: function(init){
			init = init || false;
			var $dialog = this.dialogElement
				,self = this
				,data;
			
			data = $("input, select, textarea", $dialog).serialize();
			
			$.getJSON(ORGM.baseUrl + "?cmd=fb_comments&page="+ encodeURIComponent(ORGM.page) +"&preview=1", data, function(res){
				$(".previewarea", $dialog).html(res.html);
				if (init) FB.init({xfbml:true});
			});
		}
	},
	// !ツイートボタン
	tw_button: {
		label: "ツイートボタン"	,
		value: "\n#tw_button\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	// !+1ボタン
	gp_button: {
		label: "+1ボタン"	,
		value: "\n#gp_button\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
		}
	},
	
	// !その他
	allPlugin: {
		label: ">>",
		addable: false,
		onStart: function(){
			QHMPluginHelper.openList();
			return false;
		}
	},
	// !行コメント
	commentout: {
		label: "行コメント",
		format: "//",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			var range = exnote.getRange();
			var value = exnote.getValue();
			
			if (value.length > 0) {
				exnote.moveToLinehead();
				var text = exnote.getSelectedText();
				var lines = text.split("\n"), values = [];

				for (var i = 0; i < lines.length; i++) {
					
					values.push(this.format + lines[i]);
				}
				this.value = values.join("\n");
			}
			else {
				this.value = this.format;
			}
		}
	},
	// !文字装飾を解除する
	removeFormat: {
		label: "文字装飾を解除する",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"),
				text = exnote.getSelectedText();
			if (text.length > 0) {
				
				var removePlugins = function(str){
					if (/&[a-zA-Z0-9_]+(?:\(.*?\))?\{(.*?)\};/.test(str)) {
						str = str.replace(/&[a-zA-Z0-9_]+(?:\(.*?\))?\{(.*?)\};/g, "$1");
						return removePlugins(str);
					}
					else {
						return str;
					}
				};
				var removeBrackets = function(str){
					if (/('{2,3}|##|%%%)(.*?)\1/.test(str)) {
						str = str.replace(/('{2,3}|##|%%%)(.*?)\1/g, "$2");
						return removeBrackets(str);
					}
					else {
						return str;
					}
				};
				text = removePlugins(text);
				text = removeBrackets(text);
				
				this.insert(text);
				return false;
			}
		}
	},
	
	// !次の行へ移動
	moveToNextLine: {
		label: "次の行へ移動",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.moveToNextLine();
			var range = exnote.getRange();
			exnote.attachFocus(range.position, range.length);
			return false;
		}
	},
	// !行頭へ移動
	moveToLinehead: {
		label: "行頭へ移動",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.moveToLineHead();
			var range = exnote.getRange();
			exnote.attachFocus(range.position, range.length);
			return false;
		}
	},
	// !選択範囲を調整
	adjustSelection: {
		label: "選択範囲を調整",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.adjustSelection();
			var range = exnote.getRange();
			exnote.attachFocus(range.position, range.length);
			return false;
		}
	},
	
	// !ブロックプラグイン自動補完
	autoCompleteBlock: {
		label: "ブロックプラグイン自動補完",
		format: "#{plugin}",
		//TODO: plugin 充実
		options: {plugins: ["style", "show", "gmapfun"]},
		trigger: false,
		focus: false,
		dialog: '#<input type="text" name="plugin" autocomplete="off" />',
		onStart: function(){
			
		},
		onDialogOpen: function(){
			var helper = this;
			var $modal = $(this.dialogElement),
				$input = $modal.find("input:text[name=plugin]"),
				exnote = $(this.textarea).data("exnote");
			var value = exnote.getValue(),
				range = exnote.getRange();

			if (this.trigger) {
				$modal
				.on("shown", function(){
					$input.val($(helper.options.tmpinput).val()).focus();
				})
				.on("hidden", function(){
					$(helper.options.tmpinput).remove();
				});
				
			}
		
			
		},
		onComplete: function(){
			var $modal = $(this.dialogElement),
				exnote = $(this.textarea).data("exnote");
			var plugin = $modal.find("[name=plugin]").val();
			var format = this.format;
			var value = exnote.getValue(),
				range = exnote.getRange();
			
			if ( ! this.options.trigger) {
				exnote.moveToNextLine();
			}
			
			this.insert(format.replace("{plugin}", plugin));
		}
		
	},
	
	autoCompleteInline: {},
	
	undo: {
		label: "戻す",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.undo();
			return false;
		}
	},
	redo: {
		label: "やり直す",
		onStart: function(){
			var exnote = $(this.textarea).data("exnote");
			exnote.redo();
			return false;
		}
	}
	
};
