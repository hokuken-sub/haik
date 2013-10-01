$(function(){
	
	$("[data-cancel]").click(function(){
		$("#app_update_cancel").submit();
	});
	
	$("#plugin_app_update_do_update").on("submit", function(e){
	
		var $prgr = $("#plugin_app_update_progress")
		  , $bar = $prgr.find(".progress-bar")
		  , $text = $bar.children("span")
		  , cur_value = 10
		  , int_value = 10
		  , int_msec = 300
		  , int_id
	
		var progress = function(value) {
			if (value > 100) value = 100;
			
			$bar.css("width", value.toString() + "%");
			$text.text(value.toString() + "% Complete");
		}
		
		$prgr.removeClass("hide");
		progress(cur_value);
		
		//progress 10 per 3 min
		int_id = setInterval(function(){
			cur_value += int_value;
			progress(cur_value);
		}, int_msec);
		
		$(window).on("beforeunload", function(){
			clearInterval(int_id);
			
			progress(100);
			var start = new Date().getTime(), now;
			for (;;) {
				now = new Date().getTime();
				if (now - start > int_msec) break;
			}

		});
		
		
		
	})
	

});