!(function($){

	function getWinSize() {
		return { width : $(window).width(), height : $(window).height() };
	}
	
	var FilerGrid = function(element, options){
		var self = this;
		
		self.options = $.extend({}, FilerGrid.defaultOptions, options);
		
		self.$element = $(element);
		self.$item = self.$element.parent();
		
		self.$element.on("click.filergrid", function(e){
			e.preventDefault();
			if ( ! $(e.target).is("i")) {// <span class="triangle-btn"><i /></span> からのイベントは無視
				e.stopPropagation();
				self.show();
			}
		});
		
	};
	
	FilerGrid.defaultOptions = {
		minHeight: 400,
		detailTmplID: '#tmpl_filer_modal'
	};
	
	FilerGrid.prototype = {
		constructor: FilerGrid,
		
		options: null,
		
		$item: null, //filer-grid-item
		$element: null, //filer-grid
		$expander: null, //filer-grid-expander
		
		itemHeight: 0,
		detailHeight: 0,
		
		shown: false,
		
		//show detail block
		show: function(){
			this.$element.trigger("show.filergrid");
			
			var self = this;
			var $item = this.$item;
			if ($item.hasClass("filer-grid-expanded")) return;
			
			this.hideSiblings(function(){
				var $expander = $("<div></div>", {class: "filer-grid-expander"})
				
				self.$element.after($expander);
				
				self.$expander = $expander;
				
				self.setDetail();
				self.setHeight();
				
			});
			
			
		},
		
		hide: function(callback){
			
			var self = this;
			
			var onEndFn = function(){
				self.$item.off($.support.transition.end);
				self.$item.removeClass( 'filer-grid-expanded' ).css("height", "");
				
				if (typeof callback === "function")
					callback.call();
			};
			
			self.$expander.remove();
			self.$item.on($.support.transition.end, onEndFn).height(0);
			
			return false;
				
		},

		hideSiblings: function(callback){
			var $siblings = this.$item.siblings(".filer-grid-expanded");
			
			if ($siblings.length > 0) {
				this.$item.siblings(".filer-grid-expanded").each(function(i){
					var filergrid = $(".filer-grid", this).data("filer-grid");
					if (i === ($siblings.length - 1) && filergrid) {
						filergrid.hide(callback);
					}
				});
			}
			else {
				callback.call(this);
			}
		
		},

		
		calcHeight: function(){
			
			var winsize = getWinSize();
			
			

			var detailHeight = winsize.height - this.$element.height(),
				itemHeight = winsize.height;

			if( detailHeight < this.options.minHeight ) {
				detailHeight = this.options.minHeight;
				itemHeight = this.options.minHeight + this.$element.height();
			}

			this.detailHeight = detailHeight;
			this.itemHeight = itemHeight;
			
		},
		
		setHeight: function(){

			var self = this,
				onEndFn = function() {
//					if( support ) {
//						self.$item.off( $.support.transition.end );
//					}
					self.$item.addClass( 'filer-grid-expanded' );
					self.shown = true;
					self.$element.trigger("shown.filergrid");
			
				};

			self.calcHeight();
			self.$expander.css( 'height', self.detailHeight );
			self.$item.css( 'height', self.itemHeight );
			
			setTimeout(onEndFn, 300);

//			if( !support ) {
//				onEndFn.call();
//			}			

			this.setPosition();
		},

		setPosition: function(){
			var winsize = getWinSize();

			// scroll page
			// case 1 : preview height + item height fits in window´s height
			// case 2 : preview height + item height does not fit in window´s height and preview height is smaller than window´s height
			// case 3 : preview height + item height does not fit in window´s height and preview height is bigger than window´s height
//			var position = this.$item.data( 'offsetTop' ),

			var position = this.$item.offset().top;
			
			var $expandeds = $(".filer-grid-expanded");
			
			if ($expandeds.length > 0 && position > $expandeds.eq(0).offset().top) {
				var expandedHeight = _.reduce($expandeds.map(function(){
					return $(this).height();
				}), function(sum, num){
					return sum + num;
				});
				position = position + expandedHeight;
			}

			var previewOffsetT = this.$expander.offset().top,
				scrollVal = this.detailHeight + this.$item.height() <= winsize.height ? position : this.detailHeight < winsize.height ? previewOffsetT - ( winsize.height - this.detailHeight ) : previewOffsetT;
			
			$("html, body").animate( { scrollTop : scrollVal }, "fast" );

		},		
		
		setDetail: function(){
			var fileInfo = this.$element.data("filer");
			//画像のキャッシュを無効にする
			var timestamp = new Date().getTime();
			fileInfo.previewpath = fileInfo.filepath + "?" + timestamp;

			$(this.options.detailTmplID).tmpl(fileInfo).appendTo(this.$expander);

//			$modal.data("file", fileInfo).trigger("imageset");//debug
			
		}
	};
	
	
	
	$.fn.filergrid = function(options){
		return this.each(function(){
			var $$ = $(this)
				, data = $$.data('filer-grid');

			if ( ! data) {
				data = new FilerGrid(this, options);
				$$.data('filer-grid', data);
			}
			if (typeof options == 'string') {
				data[options].call(data);
			}
			
		});
	};
	
	$(document)
	.on("click", ".filer-grid", function(e){
		if ( ! $(this).data("filer-grid")) {
			e.preventDefault();
			e.stopImmediatePropagation();
			$(this).filergrid(this).filergrid("show");
		}
	})
	.on("click", "[data-dismiss=filer-grid]", function(e){
		e.preventDefault();
		
		$(this).closest(".filer-grid-item").children(".filer-grid").data("filer-grid").hide();
	})
	.on("keydown", function(e){
		//[esc] で閉じるが、フルスクリーンモードではフルスクリーン解除が優先される
		if (e.keyCode == 27) {
			if ($(".filer-grid-expanded").length) {
				e.preventDefault();
				
				$(".filer-grid-expanded").children(".filer-grid").data("filer-grid").hide();
			}
		}
	});

	
	
})(window.jQuery);