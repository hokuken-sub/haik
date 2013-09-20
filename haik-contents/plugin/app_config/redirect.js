! (function($){
	
	$(function(){
	
		$(".setting_list")
		.on("submit", "> li", function(e, data){
			
			if (data.error) {
				return false;
			}
			
			//redirect login
			location.href = data.redirectUrl;
			
		});

	});

})(window.jQuery);