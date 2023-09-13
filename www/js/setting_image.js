$(function () {
	reFresh();

	$('#btn-reload').click(function() { reFresh(); });
	$('#upload-btn').on('click', null, function (e) {  //“上传”按钮事件
		$.ajaxFileUpload({
			url:"?z=setting-ajax_upload_image",
			secureuri:false,
			fileElementId:'fn',
			dataType:"json",
			data:{id:0},
			success:function(data)
			{
				 if(data.status == 0) {
					 $('#upload-res').html(data.info + "文件名：" + data.data.filename);
					 $('#unlink').append('<option value="'+data.data.filename.substr(7)+'">'+data.data.filename.substr(7)+'</option>');
					 alert("成功", data.info, "success");
				 }
				 else { $('#upload-res').html(data.info); alert("错误", data.info, "error"); }
			},
			error:function(data, status, e)
			{
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			}
		});
	});
	
	$('#unlink').dblclick(function(){  
    	$('option:selected', this).removeAttr('selected').appendTo('#todel');  
    });  
          
	$('#todel').dblclick(function(){  
		$('#todel option:selected').removeAttr('selected').appendTo('#unlink');  
	});
	
	$('#unlink').change(function(){
		if($('option:selected', this).length == 1) $('#preview').attr('src', 'upload/' + $('option:selected', this).first().val())
	});
	
	$('#todel').change(function(){
		if($('option:selected', this).length == 1) $('#preview').attr('src', 'upload/' + $('option:selected', this).first().val())
	});
	
	$('#movright').click(function(){
		$('#unlink option').removeAttr('selected').appendTo('#todel');
	});
	
	$('#movleft').click(function(){
		$('#todel option').removeAttr('selected').appendTo('#unlink');
	});
	
	$('#delall').click(function(e){
		if($('#todel option').length > 0) {
			var todel = new Array();
			for(var i=0;i<$('#todel option').length;i++) {
				todel[i] = $('#todel option')[i].value;
			}
			$.post("?z=setting-ajax_del_photos", {photos:todel})
			.done(function (data) {
				if(data.status == 0) { alert("成功",data.info,"success"); reFresh(); }
				else alert("错误",data.info, "error");
			})
			.fail(function () {
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			});
		}
		else {
			alert("提示","无待删除图片，请先将待删除的图片移到右框中。", "info");
		}
	});
});

function reFresh() {
	$.getJSON("?z=setting-ajax_get_unlink_filename", null)
	.done(function(data) {
		$('#unlink').empty();
		$('#todel').empty();
		for(var i=0; i<data.data.length; i++) {
			$('#unlink').append('<option value="'+data.data[i]+'">'+data.data[i]+'</option>');
		}
	})
	.fail(function() {
		alert("提示", '你已中断请求，或网络连接异常。', "info");
	});
}
