$(function(){
	
	var $design_wand = $("#design_wand");
	
	$design_wand.on("click", "[data-type=design-update]", function(e){
		// ! デザインの更新
		var self = $(this);
		var designs = [];
		
		if ($(this).data("design") != 'all') {
			designs.push($(this).data("design"));
		}
		else {
			$(".update-list-design", design_wand).each(function(){
				var design = $(this).find("[data-type=design-update]").data("design");
				designs.push(design);
			});
		}

		var update_data = {
			mode: 'update',
			update_designs: designs
		}


		if ($design_wand.data('ftp')) {
			
			var $ftp_modal = $('#design_wand_ftp');

			$ftp_modal.on("click", "[data-type=ftp_connect]", function(){
				var data = $('input, select', $ftp_modal).serialize();

				$.ajax({
					url: ORGM.design_wand.baseUrl,
					data: data,
					type: 'POST',
					dataType: 'json',
					async: false,
					success: function(res) {
						if (res.error) {
							ORGM.notify(res.message, "error");
							$(".form-group.hide", $ftp_modal).removeClass("hide");
							return;
						}
						
						$design_wand.data('ftp', 0).attr("data-ftp", 0);
						$ftp_modal.modal("hide");

						update(update_data);
						
					}
				});

			});

			$ftp_modal.modal();
		}
		else {
			update(update_data);
		}
		
	});
	
	
	function update(data) {

		$.ajax({
			url: ORGM.design_wand.updateUrl,
			data: data,
			type: 'POST',
			dataType: 'json',
			async: false,
			success: function(res) {

console.log('update success');
console.log(res);

				if (res.error) {

					ORGM.notify(res.message, "error");
					return;

				}
				// 成功表示
				ORGM.notify(res.message, "success");
		
				// リストから削除
				for (i = 0; i < res.updates.length; i++){
					$(".design-wand-update [data-design="+res.updates[i]+"]").closest(".update-list-design").remove();
				}
				
				if ( ! $(".update-list-design", $design_wand).length)
				{
					$(".design-wand-none-design").removeClass("hide");
					$("button[data-type=design-update][data-design=all]").hide();
				}
			}
		});
		
	}

	// ! リスト表示
	if (typeof ORGM.design_wand.updates !== "undefined") {
		$('#tmpl_update_list').tmpl(ORGM.design_wand.updates, {'json':function(){
			return JSON.stringify(this.data.data);
		}}).appendTo('#design_wand div.design-wand-update');
		
console.log(ORGM.design_wand.updates.length);
		if ( ! ORGM.design_wand.updates.length)
		{
			$(".design-wand-none-design").removeClass("hide");
			$("button[data-type=design-update][data-design=all]").hide();
		}

	}
	
});