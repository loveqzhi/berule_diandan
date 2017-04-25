(function($) {

	function getDateStr(AddDayCount) { 
		var dd = new Date(); 
		dd.setDate(dd.getDate()+AddDayCount);//获取AddDayCount天后的日期 
		var y = dd.getFullYear(); 
		var m = dd.getMonth()+1;//获取当前月份的日期 
		var d = dd.getDate(); 
		return y+"-"+m+"-"+d; 
	}
	var Book = function () {
		this.main();
	}

	Book.prototype = {
		main: function() {
			this.addDate();
			this.selectShow();
			this.selectHide();
			this.dataSelect();
			this.formSubmit();
		},

		addDate: function() {
			var datehtml = '',
				date = 7;
			for (var i = 0; i < date; i++) {
				var date_str = getDateStr(i);
				datehtml += '<li data-value="'+ date_str +'">'+ date_str +'</li>';
			};
			$("#book-date-option ul").append(datehtml);
		},

		selectShow: function() {
			//绑定iscroll
			var	bookDateOption = new iScroll("book-date-option"),
				bookHumanOption = new iScroll("book-human-option");
			$(".book-select").on("click",function() {
				var select = $(this).find(".book-option"),
					iscroll_id = select.attr("id") == "book-date-option"? bookDateOption : bookHumanOption;
				if (select.height()) {
					select.height(0);
				} else {
					$(".book-option").height(0);
					select.height("7.75rem");
					setTimeout(function() {
						iscroll_id.refresh();
					}, 100);
				}
			});
		},

		selectHide: function() {
			$("body").on("click",function() {
				$(".book-option").each(function() {
					if ($(this).height()) {
						$(this).height(0);
					}
				});
			});
			$(".book-select").on("click",function(e) {
				e.stopPropagation();
			})
		},

		dataSelect: function() {
			$(".book-option").off("click","li").on("click","li",function() {
				var val = $(this).data("value"),
					html = $(this).html(),
					str_id = $(this).parents(".book-option").data("type");
				$("#input"+str_id).val(val);
				$("#book-"+str_id).html(html);
				//console.log($("#input"+str_id).val());
			});
		},

		formSubmit: function() {
			$("#book-btn").on("click",function(){
				var bookdate = $("#inputdate").val(),
					human = $("#inputhuman").val(),
					name = $("input[name=name]").val(),
					telephone = $("input[name=telephone]").val();

				if (bookdate == "") {
					alert("请选择预订日期")
					return false;
				}
				if (human == "") {
					alert("请选择用餐人数")
					return false;
				}
				if (name == "") {
					alert("请输入姓名")
					return false;
				}
				if (telephone == "") {
					alert("请输入联系方式")
					return false;
				}

				$.ajax({
	                type: "POST",
	                url: "/wechat/book/register",
	                data: $("#book-register").serialize(),
	                dataType: "json",
	                success: function(data){
	                  if (data.status=="success") {
	                      location.href="/wechat/index";
	                  } else {
	                     alert(data.msg);
	                     return false;
	                  }
	                }
	            });
			});
		}
	}

	window.Book = Book || {};
}(window.Zepto))

$(function() {
	FastClick.attach(document.body);

	var book = new Book();
});