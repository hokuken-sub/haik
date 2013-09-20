/**
 *   QHM Color Palette
 *   -------------------------------------------
 *   js/jquery.colorpalette.js
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 12/11/07
 *   modified : 13/09/05
 *   
 *   Provide compliant web-safe colors' palette
 *   
 *   Usage :
 *   
 */

!function($){

	ColorPalette = function(element, options){
		var $$ = $(element), self = this;
		
		options = options || {};

		for (var key in options) {
			this[key] = options[key];
		}
		
		this.element = element;

		ColorPalette.total = ++ColorPalette.total;
		ColorPalette.init();
		
		self.applyColor();


		//set event
		$$
		.on("focus.colorpalette", function(){
			self.show();
		})
		.on("blur.colorpalette", function(){
			if ( ! ColorPalette.lock) self.hide();
		})
		.on("change.colorpalette", function(){
			self.applyColor();
		});

		return;
	};
	
	/** static field */
	var statics = {
	
		total: 0,//total number of instance
		colors: ["#000000","#000000","#000000","#000000","#003300","#006600","#009900","#00cc00","#00ff00","#330000","#333300","#336600","#339900","#33cc00","#33ff00","#660000","#663300","#666600","#669900","#66cc00","#66ff00","#000000","#333333","#000000","#000033","#003333","#006633","#009933","#00cc33","#00ff33","#330033","#333333","#336633","#339933","#33cc33","#33ff33","#660033","#663333","#666633","#669933","#66cc33","#66ff33","#000000","#666666","#000000","#000066","#003366","#006666","#009966","#00cc66","#00ff66","#330066","#333366","#336666","#339966","#33cc66","#33ff66","#660066","#663366","#666666","#669966","#66cc66","#66ff66","#000000","#999999","#000000","#000099","#003399","#006699","#009999","#00cc99","#00ff99","#330099","#333399","#336699","#339999","#33cc99","#33ff99","#660099","#663399","#666699","#669999","#66cc99","#66ff99","#000000","#cccccc","#000000","#0000cc","#0033cc","#0066cc","#0099cc","#00cccc","#00ffcc","#3300cc","#3333cc","#3366cc","#3399cc","#33cccc","#33ffcc","#6600cc","#6633cc","#6666cc","#6699cc","#66cccc","#66ffcc","#000000","#ffffff","#000000","#0000ff","#0033ff","#0066ff","#0099ff","#00ccff","#00ffff","#3300ff","#3333ff","#3366ff","#3399ff","#33ccff","#33ffff","#6600ff","#6633ff","#6666ff","#6699ff","#66ccff","#66ffff","#000000","#ff0000","#000000","#990000","#993300","#996600","#999900","#99cc00","#99ff00","#cc0000","#cc3300","#cc6600","#cc9900","#cccc00","#ccff00","#ff0000","#ff3300","#ff6600","#ff9900","#ffcc00","#ffff00","#000000","#00ff00","#000000","#990033","#993333","#996633","#999933","#99cc33","#99ff33","#cc0033","#cc3333","#cc6633","#cc9933","#cccc33","#ccff33","#ff0033","#ff3333","#ff6633","#ff9933","#ffcc33","#ffff33","#000000","#0000ff","#000000","#990066","#993366","#996666","#999966","#99cc66","#99ff66","#cc0066","#cc3366","#cc6666","#cc9966","#cccc66","#ccff66","#ff0066","#ff3366","#ff6666","#ff9966","#ffcc66","#ffff66","#000000","#ffff00","#000000","#990099","#993399","#996699","#999999","#99cc99","#99ff99","#cc0099","#cc3399","#cc6699","#cc9999","#cccc99","#ccff99","#ff0099","#ff3399","#ff6699","#ff9999","#ffcc99","#ffff99","#000000","#00ffff","#000000","#9900cc","#9933cc","#9966cc","#9999cc","#99cccc","#99ffcc","#cc00cc","#cc33cc","#cc66cc","#cc99cc","#cccccc","#ccffcc","#ff00cc","#ff33cc","#ff66cc","#ff99cc","#ffcccc","#ffffcc","#000000","#ff00ff","#000000","#9900ff","#9933ff","#9966ff","#9999ff","#99ccff","#99ffff","#cc00ff","#cc33ff","#cc66ff","#cc99ff","#ccccff","#ccffff","#ff00ff","#ff33ff","#ff66ff","#ff99ff","#ffccff","#ffffff"],
		cellSize: 15,
		cols: 21,
		palette: null,
		lock: false, //if true, palette fixed
		id: "qhmColorPalette",
		
		currentInstance: null,
		
		init: function(){

			if (ColorPalette.palette != null) return;
			
			var $palette = $('<div></div>');
			$palette
			.attr("id", ColorPalette.id)
			.css({
				width: ColorPalette.cellSize * ColorPalette.cols + 2,
				position: "absolute",
				top: 0,
				left: 0,
				textAlign: "left",
				backgroundColor: "#ccc",
				border: "1px solid #999",
				boxShadow: "3px 3px 10px 2px rgba(0,0,0,0.2)",
				zIndex: "10000",
				padding: 0,
 				lineHeight: '5px',
 				boxSizing: "border-box",
				borderCollapse: "collapse"
			})
			.hide()
			.on("mousedown", "div.color", function(){
				ColorPalette.lock = true;
			})
			.on("mouseup", "div.color", function(){
				ColorPalette.lock = false;
			})
			.on("click", "div.color", function(){
				var color = $(this).data("color");
				$(ColorPalette.currentInstance.element)
				.val(color).trigger("change");
				ColorPalette.hide();
			});
			
			var $trans = $('<div style="clear:both;height:15px;line-height:15px;"><div class="color">／</div></div>');
			$trans.find("div.color").data("color", "transparent")
			.css({overflow: "hidden", color: "red", backgroundColor: "#fff", marginLeft: ColorPalette.cellSize});
			$palette.append($trans)

			for (var i in ColorPalette.colors) {
				var color = ColorPalette.colors[i],
					$cell = $('<div class="color"></div>').css({
					backgroundColor: color
				})
				.data("color", color);
				
				$palette.append($cell);
			}
			
			
			
			$("div.color", $palette).css({
  				display: "block",
  				float: "left",
				lineHeight: ColorPalette.cellSize+"px",
				width: ColorPalette.cellSize,
				height: ColorPalette.cellSize,
				border: "1px solid #000",
				borderCollapse: "collapse",
				padding: 0,
				cursor: "pointer"
			});
			
			$("body").append($palette);
			ColorPalette.palette = $palette.get(0);
		},
		show: function(instance){
			ColorPalette.currentInstance = instance;
			var offset = $(instance.element).offset(),
				outerHeight = $(instance.element).outerHeight();
			$(ColorPalette.palette).css({
				top: offset.top + outerHeight,
				left: offset.left
			});
			$("#" + ColorPalette.id).show();
		},
		hide: function(){
			ColorPalette.currentInstance = null;
			$("#" + ColorPalette.id).hide();
		},
		convertRGBToHex: function(rgb){
			var code = [], brightness = 0, threashold = Math.floor((255+255+255) / 2);
			if (rgb.match(/^rgb\((.*?)\)$/i)) {
				var values = RegExp.$1.split(","), code = "#";
				
				for (var i in values) {
					var hex = parseInt(values[i], 10).toString(16);
					code += (hex.length == 2) ? hex : ("0" + hex);
				}

				return code;
			}
			else {
				return false;
			}
		},
		getVisibleColor: function(color) {
			var code = [], brightness = 0, threashold = Math.floor((255+255+255) / 2);
			if (color.match(/^#([0-9a-f]{3,6})$/i)) {
				var values = RegExp.$1, shorthand = (values.length !== 6);
				for (var i = 0; i < values.length; i++) {
					var value;
					if (shorthand) {
						value = values.substr(i, 1) + values.substr(i, 1);
					} else {
						value = values.substr(i, 2);
						i++;
					}
					value = parseInt(value, 16);
					brightness += value;
					code.push(value);
				}
			}
			else {
				return false;
			}
			
			if (threashold > brightness) {
				return "#fff";
			}
			else {
				return "#000"
			}
			
		}
		
	};
	
	for (var key in statics) {
		ColorPalette[key] = statics[key];
	}
	
	
	/** instance method */
	ColorPalette.prototype = {
		
		constructor: ColorPalette,
		
		element: null,
		
		show: function(){
			ColorPalette.show(this);
		},
		hide: function(){
			ColorPalette.hide();
		},
		applyColor: function(){
			var $$ = $(this.element),
				value = $$.val();
			var color = ColorPalette.getVisibleColor(value),
				bgcolor = value;
			
			if ( ! color) {
				$(this.element).css({
					color: "",
					backgroundColor: ""
				});
			}
			else {
				$(this.element).css({
					color: color,
					backgroundColor: bgcolor
				});
			}
			
		}
		

	};

	$.fn.colorpalette = function(options) {
		
		return this.each(function(){
			var $$ = $(this)
				, palette = $$.data('colorpalette');
			if (palette && typeof options === "string") {
				palette[options].call();
			}
			else if ( ! palette || typeof palette === "string") {
				palette = new ColorPalette(this, options);
				$$.data('colorpalette', palette);
			}
		
		});
		
	};


	/* !on ready */

	$(function(){
		$("[data-colorpalette=onready]").colorpalette();
	});



}(window.jQuery);