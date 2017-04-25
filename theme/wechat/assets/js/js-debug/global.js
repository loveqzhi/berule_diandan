$(function(){

	typeof FastClick === "function" && FastClick.attach(document.body);

	$("#go-back").click(function() {
		window.history.back(-1);
	});

	var pageHeight = document.documentElement.clientHeight/16;

	document.getElementById("index-wrap") && $("#index-wrap").css("min-height", pageHeight + "rem");

	var currentUrl = window.location.href,
		searchKey = /name=([^&]*)/;
	if (searchKey.test(currentUrl)) {
		var searchContent = currentUrl.match(searchKey)[1];
		if (searchContent && document.getElementById("search-area")) {
			searchContent = decodeURIComponent(searchContent);
			$("#search-area-input").val(searchContent);
			$("#search-area").addClass("display");
		};
	};

	$("#search-btn").click(function() {
		$("#search-area").addClass("display");
	});
	$("#search-back").click(function() {
		$("#search-area").removeClass("display");
	});
	$("#search-area-btn").click(function() {
		shopSearch();
	});
	$("#search-area-input").keydown(function(e) {
		if(e.keyCode == 13) {
			shopSearch();
		}
	});


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

function shopSearch() {
	var inputVal = $("#search-area-input").val(),
		id = document.getElementById("category"),
		searchData = {};

	if (inputVal) {

		if (id) {
			searchData = {
				name: inputVal,
		        category: $("#category").val(),
		        dist: $("#dist").val(),
		        street: $("#street").val()
			}

			$.each(searchData,function(k,v){                
		        if (!v) {                    
		            delete searchData[k];
		        }
		    });

		} else {
			searchData = {
				name: inputVal
			}
		}

    	window.location.href = "/wechat/shop?" + $.param(searchData);

	} else {
		alert("请输入需要查找的餐厅名");
	}
}
