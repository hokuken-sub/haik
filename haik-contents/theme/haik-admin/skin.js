
! (function($){
	
	$(function(){
		
		$(".app-config-menu a[data-toggle=collapse]").on("click", function(){
			$(this).parent().toggleClass("active").siblings().removeClass("active");
		});
		
		$("a[data-toggle-text]").each(function(){
			var $a = $(this);
			var $div = $($a.attr("href"));
			if ($div.length > 0) {
				$(document).on("shown hidden", $a.attr("href"), function(){
					var toggleText = $a.data("toggleText"), text = $a.text();
					$a.data("toggleText", text).text(toggleText);
				});
			}
		});
		
	});
	
})(window.jQuery);