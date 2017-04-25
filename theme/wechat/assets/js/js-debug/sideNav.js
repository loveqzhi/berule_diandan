$(function() {
		$("#side-btn").on("click", function() {
			if ($("#index-wrap").hasClass("snav-show")) {
				$("#index-wrap").removeClass("snav-show");
				setTimeout(function() {
					$("#side-nav").removeClass("dispaly");
					$("#index-wrap").off("touchmove");
				}, 500);
			} else {
				document.body.scrollTop = 0;
				$("#index-wrap").on("touchmove", function(e) {
					e.preventDefault();
				});
				$("#index-wrap").addClass("snav-show");
				$("#side-nav").addClass("dispaly");
			}
		});

		$("#side-nav").on("touchmove", function(e) {
			e.preventDefault();
		});

		$("#index-section").on("touchstart", function() {
			if ($("#index-wrap").hasClass("snav-show")) {
				$("#index-wrap").removeClass("snav-show");
				setTimeout(function() {
					$("#side-nav").removeClass("dispaly");
					$("#index-wrap").off("touchmove");
				}, 500);
			}
		});
	})