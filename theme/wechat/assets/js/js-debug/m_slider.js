$(function() {
	var pageWidth = document.documentElement.clientWidth,
		list = $("#slider li"),
		dots = $(".dots"),
		countHeight = pageWidth * 7 / 18,
		size = list.length;

	$(".banner").height(countHeight);
	$("#slider").width(pageWidth * size).height(countHeight);
	list.width(pageWidth).height(countHeight);

	for (var i = 0; i < size; i++) {
		list[i].style.left = pageWidth * i +"px";
	};

	dots.css("margin-left", -(dots.width()/32) + "rem");

	var swipeX, swipeY, swipeYlock,
		touchCache = {},
		offset = 0,
		index = size - 1,
		getNowTime = Date.now || function() {
			return new Date().getTime();
		}

	$("#slider").on("touchstart", function(e) {
		var touches = e.touches || e.originalEvent.touches, // jquery 和 zepto 不一样, 醉
			touchDate = touches[0];
		
		swipeX = 0;
		swipeY = 0;
		swipeYlock = 0;

		offset = currentOffset($(this)[0]);

		touchCache.x1 = touchDate.pageX;
		touchCache.y1 = touchDate.pageY;
		touchCache.start = getNowTime();

		$(this).css({"-webkit-transition": "none", "transition": "none"});
	}).on("touchmove", function(e) {
		var touches = e.touches || e.originalEvent.touches,
			touchDate = touches[0];

		touchCache.x2 = touchDate.pageX;
		touchCache.y2 = touchDate.pageY;

		swipeX = touchCache.x2 - touchCache.x1;
		swipeY = touchCache.y2 - touchCache.y1;

		swipeYlock || Math.abs(swipeX) < Math.abs(swipeY) ? swipeYlock = 1 : (e.preventDefault(), e.stopPropagation(), $(this).css({"-webkit-transform": "translateX("+ (offset + swipeX) +"px) translateZ(0px)", "transform": "translateX("+ (offset + swipeX) +"px) translateZ(0px)"}));
	}).on("touchend", function() {
		var endOffset = currentOffset($(this)[0]),
			goIndex = endOffset > 0 ? 0 : endOffset < -index * pageWidth ? index : Math.floor((pageWidth / 2 + Math.abs(endOffset)) / pageWidth);
		touchCache.end = getNowTime();

		if(Math.abs(swipeX) / (touchCache.end - touchCache.start) > .5 && Math.abs(swipeX) < pageWidth / 2) {
			var nextNum = swipeX > 0 ? -1 : 1;
			
			goIndex += nextNum;

			if (goIndex < 0) {
				goIndex = 0;
			} else if (goIndex > index) {
				goIndex = index;
			}
		}

		$(this).css({"-webkit-transition": "-webkit-transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1)", "transition": "transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1)","-webkit-transform": "translateX("+ (-pageWidth * goIndex) +"px) translateZ(0px)", "transform": "translateX("+ (-pageWidth * goIndex) +"px) translateZ(0px)"}).next().find("li").removeClass("active").eq(goIndex).addClass("active");
	});
})

function currentOffset(el) {
	var matrix = getComputedStyle(el, null).WebkitTransform; //取得当前元素transform的matrix函数

	return new WebKitCSSMatrix(matrix).e; //解析matrix函数，获取元素X轴的偏移量
}