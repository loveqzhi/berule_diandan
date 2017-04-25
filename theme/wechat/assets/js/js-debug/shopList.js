$(function(){

	var pageHeight = document.documentElement.clientHeight/16;

	var rm_list = new iScroll("rest-menu-area"),
		rs_list= new iScroll("rest-select-list");

	var m_html = '<li><a href="javascript:void(0)" data-name="dist" data-value="" class="shop-search">全城</a></li>';

	$.each(cityData["101"]["child"],function(){
		if (this.id == $("#dist_id").val()) {
			m_html += '<li><span data-id="'+ this.id +'" class="selected">'+ this.name +'</span></li>';
			s_hmtl(this.id)
		} else {
			m_html += '<li><span data-id="'+ this.id +'">'+ this.name +'</span></li>';
		}
	});
	$(".rest-list-position ul").html(m_html);

	$(".rest-select-btn").on("click",function(){
		var selected = $(this).data("select");

		if ($(".rest-select-area").height() == 0) {
			$(".rest-select-area").height((pageHeight-5.9375)+"rem");
		} else {
			if ($(".rest-list-"+selected).hasClass("active")) {
				$(".rest-select-area").height(0);
			}
		}

		if (!$(".rest-list-"+selected).hasClass("active")) {
			$(".rest-menu-list").removeClass("active");
			$(".rest-list-"+selected).addClass("active");
		};

		
		var	r_minheight = $(".rest-list-"+selected).height()/16,
			r_maxheight = pageHeight - 10,
			r_height = r_minheight < r_maxheight ? r_minheight : r_maxheight;
		$(".rest-menu-area").height(r_height+"rem");
		$(".rest-select-list").height(r_height+"rem");
		setTimeout(function() {
				rm_list.refresh()
			}, 100);
		

		if ($(".rest-list-"+selected+" span").hasClass("selected")) {
			$(".rest-select-list").addClass("display");
			
			setTimeout(function() {
					rs_list.refresh()
				}, 100);
		} else {
			$(".rest-select-list").removeClass("display");
		}
	});

	$(".rest-scroll").on("click","span",function(){
		var span_id = $(this).data("id");
			
		s_hmtl(span_id);

		if (!$(this).hasClass("selected")) {
			$(".rest-select-area span").removeClass("selected");
			$(this).addClass("selected");
		};

		if (!$(".rest-select-list").hasClass("display")) {
			$(".rest-select-list").addClass("display");
		};
		setTimeout(function() {
				rs_list.refresh()
			}, 100);
	});

	$(".rest-select-area").on('touchmove', function (e) { 
		e.preventDefault(); 
	});
	$(".rest-select-area").on("click",function(){
		$(this).height(0);
	});
	$(".rest-scroll").on("click",function(e){
		e.stopPropagation();
	});

	$(".rest-scroll").on("click",".shop-search",function(){
        var search_data = getSearchData(this);
        location.href = "/wechat/shop?"+$.param(search_data);
    });

    $("#rest-page").on("click",function(){
    	if ($(this).children().hasClass("loading")) {
    		return false;
    	} else {
    		$(this).children().html("努力加载中").addClass("loading");
    	}

    	var val = $(this).data("value");
    	val++;
    	$(this).data("value",val);
    	var search_data = getSearchData(this);
    	search_data.format='json';
    	//console.log(search_data);

    	$.ajax({
    		type: "POST",
	        url: "/wechat/shop",
	        data: search_data,
	        dataType: "json",
	        success: function(data){
				if (data.status=="success") {
					$.each(data.data,function(){
						var shop_html = "";
							shop_html += '<a href="/wechat/shop/detail/'+ this.id +'">';
							shop_html += '<div class="rest-message"><div class="logo"><img src="'+ this.image +'"></div>';
							shop_html += '<div class="message"><div class="name"><h4>'+ this.name +'</h4><em>'+ this.distance +'</em></div>';
                            shop_html += '<div class="detail"><div class="rateit-group">';
                            shop_html += '<div class="rateit" data-rateit-backingfld="#evaluate_'+ this.id +'" data-rateit-readonly="readonly"></div>';
                            shop_html += '<input type="hidden" id="evaluate_'+ this.id +'" value="'+ this.star +'"></div>';
                            shop_html += '<span class="avg">人均：<em>￥'+ this.avgprice +'</em></span></div>';
                            shop_html += '<p>'+ this.address +'</p></div></div></a>';
                            
						$(".rest-list-main").append(shop_html);
					});
					if (data.pager.page == data.pager.pages) {
						$("#rest-page").remove();
					} else {
						$("#rest-page span").html("点击加载下一页").removeClass("loading");
					}
				}
        	}
    	});
    });
	
})

function s_hmtl(a) {
	var s_html = '<li><a href="javascript:void(0)" style="font-weight: bold;" data-name="dist" data-value="'+ a +'" class="shop-search">全部'+ cityData["101"]["child"][a].name +'</a></li>';
	$.each(cityData["101"]["child"][a]["child"],function(){
		s_html += '<li><a href="javascript:void(0)" data-name="street" data-value="'+ this.id +'" class="shop-search">'+ this.name +'</a></li>';
	});
	$(".rest-select-list ul").html(s_html);
}

function getSearchData(e) {
	var search_data = {
		name: $("#search-area-input").val(),
        category: $("#category").val(),
        dist: $("#dist").val(),
        street: $("#street").val()
    };

    var tmpname = $(e).data("name");
    
    if (tmpname == 'dist' || tmpname == 'street') {
        search_data.dist = '';
        search_data.street = '';
    }

    search_data[tmpname] = $(e).data('value'); 

    $.each(search_data,function(k,v){                
        if (!v) {                    
            delete search_data[k];
        }
    });
    return search_data;
}