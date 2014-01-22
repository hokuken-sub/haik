/**
 *   Haik Plugins
 *   -------------------------------------------
 *   js/haik_plugins.js
 *   
 *   Copyright (c) 2014 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 12/10/25
 *   modified : 14/01/10
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
		dialog: 'external:plugin_cols2.html',

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
		dialog: 'external:plugin_cols3.html',
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
		dialog: 'external:plugin_cols4.html',
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

	},
	colsGolden: {
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
	// !アイキャッチ
	eyecatch: {
		label: "アイキャッチ",
		format: "//${comment}${br}#eyecatch(${options}){{{${br}${title}${br}${br}${subtitle}${br}}}}${br}",
		options: {
			filer: {
				options: {search_word: ":image", select_mode: "exclusive"}
			},
			defaultHeight: "320px",
			imageDir: "eyecatch/",
			images: ["1.jpg", "2.jpg", "3.jpg", "4.jpg", "5.jpg", "6.jpg", "7.jpg", "8.jpg", "9.jpg"],
			comment: "アイキャッチ -- 高さ（320px）を調整しましょう。"
		},
		focus: false,
		dialog: "external:plugin_eyecatch.html",
		onStart: function(){},
		onDialogOpen: function(){
			var helper = this;
			var $dialog = $(this.dialogElement)
				,exnote = $(this.textarea).data("exnote")
				,$filer = $("#orgm_filer_selector")
				,self = this;
			
			var text = exnote.getSelectedText().replace(/^\s+|\s+$/, ""),
				lines = text.split("\n"),
				title = lines[0].replace(/^#(.+?)\{{2,}$/, "$1").replace(/^&h1\{(.*)\};$/, "$1"),
				subtitle = lines.join("\n");
			
			var $thumbs = $dialog.find(".thumbnails"),
				tmpl = $dialog.find(".partial-template").html().replace(/^\s+|\s+$/, "");
			$.each(helper.options.images.reverse(), function(i, image){
				$(tmpl)
					.find("a").css({
						backgroundImage: "url(" + ORGM.imageDir + helper.options.imageDir + "thumbnail/" + image + ")",
						backgroundSize: "cover"
					}).data("image", helper.options.imageDir + image)
				.end().prependTo($thumbs);
			});
			
			$dialog.on('click', '[data-image]', function(){
				$image = $(this);
				
				$filer.find("iframe").data(self.options.filer.options);

				$filer
				.on("show.bs.modal", function(){
					$(document).on("selectFiles.pluginEyecatch", function(e, selectedFiles){
						if (selectedFiles.length > 0) {
							$image.css('background-image', 'url('+selectedFiles[0].thumbnail+')');
							$('input[name=image]',$dialog).val(selectedFiles[0].filename);
							if (! $('input[name=bgimagetype]:checked').length ||
									$('input[name=bgimagetype]:checked').val() == 'cover') {
								$('input[name=bgimagetype][value=cover]').click();
							}
							$filer.modal("hide");
						}
					});
				})
				.on("hidden.bs.modal", function(){
					$(document).off("selectFiles.pluginEyecatch");
				});

				$filer.data("footer", "").modal();
			})
			.on("click", "a.thumbnail", function(e){
				e.preventDefault();
				var $self = $(this),
					image = $self.data("image");
				
				$self.parent().addClass("active").siblings().removeClass("active");
				
				$("input[name=image]", $dialog).val(image);
				
				$("input[name=bgtype][value=image]", $dialog).click();
			})
			.on("focus change", "input[name=bgcolor]", function(){
				$("input[name=bgtype][value=color]", $dialog).click();
			})
			.on('change', 'input[name=bgimagetype]', function(){
				//TODO: checkbox になったので、対応
				var checked = $(this).is(":checked");
				var $image = $('[data-image]', $dialog);

				if (checked) {
					$image.css('background-size', '20px 20px');
					$image.css('background-repeat', 'repeat');
				}
				else {
					$image.css('background-size', 'cover');
					$image.css('background-repeat', 'no-repeat');
				}
			})
			.on("change", "input[name=classnameflg]", function(){
				var checked = $(this).is(":checked");
				$("input[name=classname]", $dialog).prop("disabled", !checked);
			})
			.on("focus change", "input[name=height]", function(){
				$("input[name=heighttype][value=height]", $dialog).click();
			});


			$("input:text[name=title]", $dialog).val(title);
			$("textarea[name=subtitle]", $dialog).val(subtitle)
			.exnote({
				css: {
					height: "3.5em",
					fontSize: "14px"
				}
			});
			
			$("input:text[name$=color]", $dialog).colorpalette();
			
			setTimeout(function(){
				$("input[name=heighttype][value=height]", $dialog).click();
				$("input[name=colortype][value=custom]", $dialog).click();
				$("input[name=color][value=black]", $dialog).parent().button("toggle");
				$("input[name=align][value=center]", $dialog).parent().button("toggle");
				$("input[name=valign][value=middle]", $dialog).parent().button("toggle");
				$("input[name=classname]", $dialog).prop("disabled", true);
			}, 25);


		},
		onComplete: function(){
			var $dialog = $(this.dialogElement)
				,helper = this
				,exnote = $(this.textarea).data("exnote")
				,data = {br: "\n", comment: this.options.comment}, options = {};
			
			var formdata = $dialog.find('form').serializeArray();
			
			$(formdata).each(function(i, option){
				var key = option.name,
					value = option.value;

				switch(key) {
					case 'title':
						data[key] = (value.length > 0) ? value : '';
						if (data[key].length > 0) {
							data[key] = '&h1{'+ data[key] +'};'
						}
						break;
					case 'subtitle':
						data[key] = (value.length > 0) ? value : '';
						break;
					case 'color':
					case 'bgcolor':
						if (value.length > 0) {
							options[key] = key+'='+value;
						}
						break;
					case 'height':
						if (value.length === 0) {
							options[key] = helper.options.defaultHeight;
						}
					case 'image':
						if (value.length > 0) {
							options[key] = value;
						}
						break;
					case 'classname':
						if (value.length > 0) {
							options[key] = 'class='+value;
						}
						break;
					case 'align':
						if (value !== "center") {
							options[key] = value;
						}
						break;
					case 'valign':
						if (value !== "middle") {
							options[key] = value;
						}
						break;
					default:
						options[key] = value;
						break;
				}
			});
			
			if (typeof options.heighttype != 'undefined') {
				if (options.heighttype != 'page'){
					delete options.heighttype;
				}
			}
			if (typeof options.colortype != 'undefined') {
				delete options.colortype;
			}
			
			if (typeof options.image != "undefined") {
				if (typeof options.bgimagetype != 'undefined') {
					if (options.bgimagetype != 'repeat') {
						delete options.bgimagetype;
					}
				}
			}
			else {
				if (typeof options.bgimagetype != 'undefined') {
					delete options.bgimagetype;
				}
				if (typeof options.fix != 'undefined') {
					delete options.fix;
				}
			}
			
			if (typeof options.bgtype != "undefined") {
				delete options.bgtype;
			}
			
			data.options = $.map(options, function(value){
				return value;
			}).join(",");

			var value = $.tmpl(this.format, data).text();

			exnote.moveToNextLine();			
			this.insert(value);

		}
	},
	// !見出し
	h1: {
		label: "#h1(先頭見出し)",
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
				length: this.value.length - 6
			};
		}
	},
	header: {
		label: "見出し",
		format: "* {header}\n",
		options: {defval: "テキスト", maxRepeat: 3},
		onStart: function(){
			var exnote = $(this.textarea).data("exnote"),
				value = this.options.defval,
				text = exnote.getSelectedText(),
				multiLine = false, newLines = "",
				values, self = this;
			
			exnote.adjustSelection();
			text = exnote.getSelectedText();

			if (/\n/.test(text)) {
				multiLine = true;
				values = text.replace(/(\n+)$/g, "").split("\n");
				newLines = RegExp.$1;
			}
			else {
				values = [text];
			}
			
			values = $.map(values, function(value, i){
				value = value.replace(/^\s+|\s+$/g, "");
				if (value.length === 0) {
					if (multiLine) {
						return "";
					}
					value = "* " + self.options.defval;
				}
				else {
					value = "*" + value.replace(/^(\*+|) */, "$1 ");
					value = value.replace(/^\*{4,} (.*)$/, "*** $1");
				}
				return value;
			});
			
			this.value = values.join("\n");

			if (multiLine) {
				this.value += newLines;
				this.caret = {
					offset: - (this.value.length),
					length: this.value.length - newLines.length
				};
			}
			else {
				text = this.value.match(/^\*{1,3} ?(.*)$/) ? RegExp.$1 : "";
				this.caret = {
					offset: - (text.length),
					length: text.length
				};
			}
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
		dialog: 'external:plugin_contents.html',
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
		dialog: 'external:plugin_comment.html',
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
		focus: ".modal-complete",
		dialog:'external:plugin_ul.html',
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
		dialog:'external:plugin_ol.html',
		onStart: function(){
			this.options = ORGM.plugins.ul.options;
			this.onComplete = ORGM.plugins.ul.onComplete;
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
		focus: "input:checkbox:first",
		init: false,
		dialog: 'external:plugin_deco.html',
		onStart: function(){
			if (this.init) return;
			
			var dfd = $.Deferred(),
				helper = this;
			
			helper.getDialogTemplate().then(function(template){

				var $modal = $(template);
				$("div.previewarea", $modal).css({
					marginTop: 10,
					marginBottom: 10
				});
				
				$("input:checkbox", $modal).parent().css({
					display: "inline-block",
					marginRight: 10,
					cursor: "pointer"
				});
				
				helper.dialog = $modal.wrap("<div></div>").parent().html();
				helper.init = true;
				
				return dfd.resolve();
			});
			
			return dfd;
		},
		onDialogOpen: function(){
			var $modal = $(this.dialogElement),
				$span = $("div.previewarea span", $modal);
			
			$modal.find("input:text[name=size]").data("source", this.options.sizePresets);
			
			$("input:text[name$=color]", $modal).colorpalette();
			
			$("input:text[name=size]", $modal).typeahead({
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
				$("input:text[name$=color]", $modal).colorpalette("hide")
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
		focus: "input:radio:first",
		dialog: 'external:plugin_align.html',
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement);
			
			$('[data-align]', $dialog).css('cursor', 'pointer');
			
			$dialog.on('change', 'input:radio', function(){
				var $input = $(this);
				if ($input.is(":checked")) {
					$("[data-align]", $dialog).removeClass("selected");
					$input.closest("[data-align]").addClass("selected");
				}
			});
			
		},
		onComplete: function(){

			var exnote = $(this.textarea).data("exnote")
				,value = this.format
				,text = exnote.getSelectedText()
				,$dialog = $(this.dialogElement);

			var align = $("input:radio:checked", $dialog).val();
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
		dialog: 'external:plugin_table.html',
		onStart: function(){
		
			if (this.init) {
				return;
			}
			
			var helper = this,
				dfd = $.Deferred();
			
			helper.getDialogTemplate().then(function(template){
	
				helper.init = true;
				
				var $modal = $(template),
					$cells = $modal.find("div.cells > table > tbody");
				
				for (var i = 0; i < helper.options.rows; i++) {
					var $tr = $("<tr></tr>");
					for (var j = 0; j < helper.options.cols; j++) {
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
				
				helper.dialog = $modal.wrap("<div></div>").parent().html();				
				
				dfd.resolve();
			});
			
			return dfd.promise();
			
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
		init: false,
		dialog: 'external:plugin_box.html',

		onStart: function(){
			var helper = this;
			
			if (helper.init) return;
			
			var dfd = $.Deferred();
			
			//make dialog
			this.getDialogTemplate().then(function(template){

				var $dialog = $(template)
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
					}).end();
				
				for (var i in helper.options.type) {
					$('.box-type', $dialog).append('<div class="col-sm-3"></div>')
					.find('div:last').append('<div></div>').find('div')
					.addClass(helper.options.type[i].class)
					.attr("style", helper.options.type[i].style)
					.text('サンプル')
					.data("type", helper.options.type[i].value)
					.attr("data-type", helper.options.type[i].value);
				}
				
				$dialog.find(".previewarea p").append(helper.getLorem());
				
				helper.dialog = $dialog.wrap("<div></div>").parent().html();
				helper.init = true;
				
				dfd.resolve();
			});

			return dfd.promise();
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
						
					switch (name) {
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

	// !iframe
	iframe: {
		label: "iframe設置",
		format: "#iframe({url}{options})",
		dialog: 'external:plugin_iframe.html',
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
		focus: false,
		dialog: 'external:plugin_download.html',
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
			
			$("input[name=file]", $modal).click(function(){
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
		dialog: 'external:plugin_video.html',
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
		dialog: 'external:plugin_jplayer.html',
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement)
				,self = this
				,$div =	$('div.thumbnails', $dialog)
				,$filer = $("#orgm_filer_selector")
				,template = $dialog.find(".partial-template").html();

			$dialog
			.on('shown.bs.modal', function(){
				$div.append(template);
			})
			.on('click', '[data-audio-add]', function(){
				$div.append(template);
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
		dialog: 'external:plugin_slide.html',
		onDialogOpen: function(){
			var $dialog = $(this.dialogElement)
				,self = this
				,$div =	$('div.thumbnails', $dialog)
				,$filer = $("#orgm_filer_selector")
				,template = $dialog.find(".partial-template").html();
			
			$dialog
			.on('shown.bs.modal', function(){
				$div.append(template);
			})
			.on('click', '[data-slides-add]', function(){
				$div.append(template);
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

	// !マルチメディア
	file: {
		label: "ファイル名",
		style: {},
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
		dialog: 'external:plugin_gmap.html',
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

			data.address = '"' + $("input[name=address]", $dialog).val() + '"';
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
	
	// !ナビの項目追加
	addnav: {
		label: "ナビの追加",
		dialog: 'external:plugin_addnav.html',
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
		dialog: 'external:plugin_link.html',
		focus: false,
		onDialogOpen: function(){
			var self = this, exnote = $(this.textarea).data("exnote");
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
			}).always(function(){
				setTimeout(function(){
					$("input:text[name=linkto]", $dialog).focus().select();
				}, 25);
			});
			
			var text = exnote.getSelectedText();
			$("input[name=alias]", $dialog).val(text);
			
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

	// !クロール禁止
	noindex: {
		label: "クロール禁止",
		value: "#noindex\n",
		onStart: function(){
			$(this.textarea).data("exnote").moveToNextLine();
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
		
	// !サイト情報

	// !更新の○件
	recent: {
		label: "最新の○件",
		value: "\n#recent(5)\n",
		onStart: function(){
			return ORGM.plugins.popular.onStart.call(this);
		}
	},

	// !ソーシャルプラグイン
	// !複数のソーシャルボタン
	share_buttons:{
		label: "シェアボタン",
		format: "\n#share_buttons({buttons})\n",
		dialog: 'external:plugin_share_buttons.html',
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
		dialog: 'external:plugin_fb_likebox.html',
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
			
			clearTimeout(this.timeout);
		},
		preview: function(init){
			init = init || false;
			var $dialog = this.dialogElement
				,self = this
				,data;
			
			data = $("input, select, textarea", $dialog).serializeArray();
			
			$.getJSON(ORGM.baseUrl + "?cmd=fb_likebox&preview=1&init=" + (init ? 1 : 0), data, function(res){
				$(".previewarea", $dialog).empty().html(res.html);
				if (true) FB.init({xfbml:true});
			})
			
			var setHeight = function(){
				var $div = $(".previewarea", $dialog)
				  , height = $div.height(), maxHeight = 0;
				if ($div.data("maxHeight")) {
					maxHeight = $div.data("maxHeight");
				} else {
					$div.data("maxHeight", height);
					maxHeight = height;
				}
				
				if (maxHeight >= height) {
					$div.css("height", height).data("maxHeight", height);
				}
				this.timeout = setTimeout(setHeight, 1000);
			};
			this.timeout = setTimeout(setHeight, 1000);
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
		dialog: 'external:plugin_fb_comments.html',
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
			ORGM.PluginHelper.openList();
			return false;
		}
	},
	recentPlugins: {
		label: "履歴",
		labelPrefix: '<span><i class="orgm-icon orgm-icon-clock"></i> </span>',
		labelSuffix: '<span> <span class="caret"></span></span>',
		addable: false,
		init: false,
		onStart: function(){
			var self = this,
				$element = $(self.element);
			if ( ! self.init) {
				$element
				.attr("data-toggle", "dropdown")
				self.init = true;
			}
			$element.nextAll('.dropdown-menu').remove();
			
			if (ORGM.PluginHelper.recent !== false) {
				var $ul = $('<ul/>', {"class": "dropdown-menu"});
				
				var list = [];
				
				_.forEach(ORGM.PluginHelper.recent, function(name, i){
					if (typeof ORGM.plugins[name] === "undefined") {
						return;
					}
					
					var num = (i + 1);
					num = "0" + num.toString();
					num = num.substr(num.length - 2);
					list.push('<li><a href="#" data-name="'+name+'" data-textarea="#msg">'+ num + ". " + _.escape(ORGM.plugins[name].label) +'</a></li>');
				});
				$ul.append(list.join(""))
				.on("click", "a[data-name]", function(e){
					e.preventDefault();
					
					if (typeof $(this).data("HaikPluginHelper") === "undefined") {
						ORGM.PluginHelper.init(this);
						$(this).data("HaikPluginHelper").exec();
					}
				});
				
				$(self.element).after($ul);

			}

			return false;
		}
	},
	favoritePlugins: {
		label: "お気に入り",
		addable: false,
		onStart: function(){
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
