
$(function(){
    $("body input:text:not([name='page'])").keypress(function(e){
        var keyCode = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode;
        if (keyCode == 13){
          var inputs = $("body input:text:not([name='page'])");
          var idx = inputs.index(this);
          if(idx==inputs.length-1){
          } else {
            inputs[idx+1].select();
            inputs[idx+1].focus();        
          }
          return false;
        }
    });
    $('.confirm').each(function(i){
        $(this).click(function(){
            return confirm($(this).attr('title') ?  $(this).attr('title') : '确定删除?');
        });
    });
    $('.plugin-timechosen').datetimepicker({
        timepicker:true,
        format:'Y-m-d H:i:s',
        formatDate:'Y-m-d H:i:s',
    });
    $('.plugin-datechosen').datetimepicker({
        timepicker:false,
        format:'Y-m-d',
        formatDate:'Y-m-d',
    });
    $(".avatarUpload").each(function(){
        var idname = $(this).data('id');
        $("#"+idname).uploadify({
            'auto'              : true,
            'multi'             : false,
            'uploadLimit'       : 10,
            'formData'          : {},
            'buttonText'        : "浏览文件",
            'buttonClass'       : 'btn btn-white btn-sm',
            'height'            : 30,
            'width'             : 100,
            'removeCompleted'   : false,
            'swf'               : '/theme/admin/assets/plugin/upload/uploadify/uploadify.swf',
            'uploader'          : '/admin/upload',
            'fileTypeExts'      : '*.gif; *.jpg; *.jpeg; *.png;',
            'fileSizeLimit'     : '10MB',
            'onUploadSuccess' : function(file, data, response) {
                var msg = $.parseJSON(data);
                if( msg.status == "success" ){
                    $('.imageshow-'+idname).show();
                    $('.imageshow-'+idname).attr('src', msg.image);
                    $(".image-"+idname).val(msg.image);
                    $(".uploadify-queue").on("click",".uploadify-queue-item .cancel",function(){
                      $(this).parent().remove();
                      $('.imageshow-'+idname).attr('src','/theme/admin/assets/images/trans.png');
                      $(".image-"+idname).val('');
                    });
                } else {
                    alert('上传失败');
                }
            }
        });
    });
    $(".delimg").click(function(){
      var delnum = $(this).data("delimg");
      $(".image-"+delnum).val("");
      $(".imageshow-"+delnum).attr('src','/theme/admin/assets/images/trans.png');
      $(this).remove();
    });
});
