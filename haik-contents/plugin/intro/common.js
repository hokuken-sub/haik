;(function($){

	var doneStep = function(step){
	
		step = step || ORGM.intro.current;
		
		if (_.isArray(step)) {
			step = step.join(',');
		}
		
		var data = {
			done: step
		};
		
		return $.post(ORGM.intro.doneUrl, data);
	};

	$(function(){
		
		var intro = introJs();
		
		intro
		.setOptions({
			nextLabel: '次へ <span class="glyphicon glyphicon-chevron-right"></span>',
			prevLabel: '<span class="glyphicon glyphicon-chevron-left"></span> 戻る',
			skipLabel: '&times;<span class="sr-only">中止</span>',
			doneLabel: '&times;<span class="sr-only">完了</span>'
		})


		_.forEach(ORGM.intro.steps, function(value, i){
			ORGM.intro.steps[i].element = $(value.selector).get(0);
		});
		

		if (ORGM.intro.current == 'start') {
			$('body').append(ORGM.intro.steps[0].html);
			
			$(ORGM.intro.steps[0].selector)
			.on("click", ".move-to-licence", function(){

				$(ORGM.intro.steps[0].selector).data("step", 2).modal("hide");

			})
			.on("click", ".intro-done", function(){
				//start, login, admin flag をオフにする
				doneStep(["start", "login", "admin"]);
				
			})
			.on("hidden.bs.modal", function(){
				if ($(this).data("step") === 2) {

			    	var scrollTop = $(ORGM.intro.steps[1].selector).offset().top - ORGM.navbarHTotal;
			    	var called = false;
				    $("html, body").animate({
					    scrollTop: scrollTop
				    }, 300, function(){
					    
					    if (called) return;
					    else called = true;
					    
						intro.setOptions({
							steps: ORGM.intro.steps,
							tooltipClass: "orgm-intro-no-controls orgm-intro-top-center"
						});
						
						intro.goToStep(2).start();
						
					    
				    });
					
				}
				
			})
			.modal();
			
			// ログインリンククリックでフラグをオフ
			$("#orgm_login").on("click", function(e){
				var url = $(this).attr("href");
				doneStep();
				e.preventDefault();
				setTimeout(function(){
					location.href = url;
				}, 25);
			});
			
		}
		else if (ORGM.intro.current == 'login') {
			
			if ($("input:text:visible").length === 0) return;

			intro.setOptions({
				steps: ORGM.intro.steps,
				tooltipClass: "orgm-intro-no-controls orgm-intro-login"
			});

			intro.goToStep(3).start();
			
			//input:text フォーカスで消す
			$("input:text").on("keydown", function(){
				intro.exit();
			});
		}
		else {

			$("body").addClass("orgm-intro-root-admin").attr("data-intro-step", ORGM.intro.current);
			
			var $target = null;
			var exitCallback = function(){
				$("body").removeClass("orgm-intro-root-admin").removeAttr("data-intro-step");

				if (ORGM.intro.current === 'nav') {
					$target.data("qhmPluginHelper").enable();
				}

				doneStep();
			};

			intro
			.setOptions({
				steps: ORGM.intro.steps,
				tooltipClass: ""
			})
			.onexit(exitCallback)
			.oncomplete(exitCallback);
			
			intro.start();
			
			setTimeout(function(){
				if (ORGM.intro.current === 'nav') {
					$target = $(".introjs-showElement a");
					$target.data("qhmPluginHelper").disable();
				}
				else if (ORGM.intro.current === 'admin') {
					$("#admin_nav").on("mouseover", function(){
						intro.exit();
					});
					$(".introjs-helperLayer").on("click", function(e){
						intro.exit()
					});
				}
			}, 100);
			
		}
		
	});

})(window.jQuery);
