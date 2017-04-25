$(function(){	

	var orderiscroll = new iScroll("dish-list-scroll"),
		ordermenuiscroll = new iScroll("scroll-order-menu"),
		orderlistiscroll = new iScroll("scroll-order-list"),
		num = $("input[name=dish-num]").val() || 0;

	dish_dis(num);

	$("#order-menu span").click(function() {
		if (!$(this).hasClass("selected")) {
			var tid = $(this).data("tid"),
				orderName = $(this).html();
			$("#order-menu span").removeClass("selected");
			$(this).addClass("selected");
			$("#order-class").html(orderName);

			if (tid) {
				$("#order-list li").addClass("hide");
				$(".tid-" + tid).removeClass("hide");
			} else {
				$("#order-list li").removeClass("hide");
			}

			setTimeout(function() {
				orderlistiscroll.refresh()
			}, 100);
		};
	});

	$(".choose-dish").on("click",function(){
		num++;
		dish_dis(num);

		var id = $(this).data("id"),
			price = $(this).data("price"),
			name = $(this).data("name"),
			k = $("input[name=dish-order]").val();
		dish_add(id,price,name,k);

		price_total();

		setTimeout(function() {
				orderiscroll.refresh()
			}, 100);
	});

	$(".shop-cart").on("click",function(){
		if (num > 0) {
			if ($(".dish-list").hasClass("display")) {
				$(".dish-list").removeClass("display");
			} else {
				$(".dish-list").addClass("display");
			}
		};
	});

	$(".dish-list").on("click",".dish-number-sub",function(){
		var dishnum = $(this).next().html();

		dishnum--;
		num--;

		dish_dis(num);

		if (dishnum == 0) {
			$(this).parents("li").remove();
			setTimeout(function() {
				orderiscroll.refresh()
			}, 100);
			price_total();
		} else {
			$(this).next().html(dishnum);
			$(this).parent().find("input").val(dishnum);
			price_total();
		}
	});
	$(".dish-list").on("click",".dish-number-add",function(){
		var dishnum = $(this).prev().val();

		dishnum++;
		num++;

		dish_dis(num);

		$(this).parent().find(".the-dish-num").html(dishnum);
		$(this).prev().val(dishnum);
		price_total();
	});

	$("#send-dish").on("click",function(){
		if (num == 0) {
			alert("您还未点餐")
		} else {
			var shop_id = $("input[name=shop_id]").val(),
				from = $(this).data("from");
			$.ajax({
				type: "POST",
		        url: "/wechat/order/create",
		        data: $("#dish-list").serialize(),
		        dataType: "json",
		        success: function(data){
					if (data.status=="success") {
						if (from == "book") {
							location.href="/wechat/book/order?shop_id=" + shop_id + "&order=" + data.data.id;
						} else if (from == "takeout") {
							location.href="/wechat/takeout/order?shop_id=" + shop_id + "&order=" + data.data.id;
						}
					} else {
						alert(data.msg);
						return false;
					}
		        }
			});
		}
	});

});

function dish_dis(num) {
	if (num == "0") {
		$("#dish-num").css("display","none");
		$(".dish-list").removeClass("display");
	} else {
		$("#dish-num").css("display","block");
	}
	$("#dish-num").html(num);
}

function dish_add(id,price,name,k) {
	var s = true;
	$(".field_order_food_fid").each(function(){
		if (id == $(this).val()) {
			s = false;
			var number = $(this).parent().find(".field_order_food_number").val();
			number ++;
			$(this).parent().find(".the-dish-num").html(number);
			$(this).parent().find(".field_order_food_number").val(number);
			return false;
		}
	});

	if (s) {
		var html = '<li>';
			html +=	'<input name="field_order_food['+k+'][fid]" type="hidden" value="'+id+'" class="field_order_food_fid">';
			html +=	'<input name="field_order_food['+k+'][price]" type="hidden" value="'+price+'"class="field_order_food_price">';
			html +=	'<p>'+name+'</p><p class="price">￥'+price+'</p>';
			html +=	'<div class="dish-number"><span class="dish-number-sub">-</span><span class="the-dish-num">1</span>';
			html +=	'<input name="field_order_food['+k+'][number]" type="hidden" value="1" class="field_order_food_number">';
			html += '<span class="dish-number-add">+</span></div></li>';
		$(".dish-list ul").prepend(html);
		k++;
		$("input[name=dish-order]").val(k);
	};
}

function price_total() {
	var price_total = 0;
	$(".field_order_food_price").each(function(){
		var price = $(this).val(),
			number = $(this).parent().find(".field_order_food_number").val();
		price_total += parseInt(price)*parseInt(number);
	})
	$(".total-price").html("￥" + price_total);
}
