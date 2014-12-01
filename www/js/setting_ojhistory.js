var photos = new Array();
var titles = new Array();
var descs = new Array();

$(function () {
	
	$('#vid').change(function(){  //OJ下拉框
		changevid();
	});
	
	$('#photo').change(function(){  //照片下拉框
		changephoto();
	});
	
	$('#btn-create').click(function(){  //增加OJ按钮
		$.getJSON("?z=setting-ajax_add_oj", null)
		.done( function(data) {
			if(data.status == 0) {
				alert('[成功]已新增OJ历史版本。VID='+data.data+'。');
				$('#vid').append('<option value="'+ data.data +'">新建版本（请在后台修改）</option>');
				$('#vid option').last().prop('selected', true);
				changevid();
			}
			else { alert('[失败]请重试！', 'error'); }
		})
		.fail( function() {
			alert("[错误]请检查网络连接。", "error");
		});
	});
	
	$('#btn-del').click(function(){
		if($('#vid option:selected').length != 1) {
			alert("[错误]请先选择一张待删除的图片。", "error");
		}
		else if(confirm("[提示]你确定要删除这条OJ历史记录吗？VID="+$('#vid option:selected').val())) {
			$.getJSON("?z=setting-ajax_del_oj", {vid:$('#vid option:selected').val()})
			.done( function(data) {
				if(data.status == 0) {
					alert('[成功]已删除OJ历史版本。VID='+data.data+'。如果本条记录有关联图片，请到“图片管理”模块清理图片。');
					$('#vid option:selected').remove();
					changevid();
				}
				else { alert('[失败]请重试！', 'error'); }
			})
			.fail( function() {
				alert("[错误]请检查网络连接。", "error");
			});
		}
	});
	
	$('#title').blur(function(){  //title失去焦点事件
		alert("[提示]暂存“照片标题”，最终修改完成后点击“保存所有照片修改”提交到服务器。");
		titles[parseInt($('#photo option:selected').val())] = $('#title').val();
	});
	
	$('#desc').blur(function(){  //title失去焦点事件
		alert("[提示]暂存“照片描述”，最终修改完成后点击“保存所有照片修改”提交到服务器。");
		descs[parseInt($('#photo option:selected').val())] = $('#desc').val();
	});
	
	$('#btn-photo-del').click(function(){
		if($('#photo option:selected').length != 1) {
			alert("[错误]请先选择一张待删除的图片。", "error");
		}
		else {
			photos[parseInt($('#photo option:selected').val())] = null;
			titles[parseInt($('#photo option:selected').val())] = null;
			descs[parseInt($('#photo option:selected').val())] = null;
			$('#photo option:selected').remove();
			changephoto();
			alert("[提示]暂存设置，最终修改完成后点击“保存所有照片修改”提交到服务器后方才生效。");
		}
	});
	
	$('#btn-save').click(function(){
		$('#btn-save').prop('disabled', true);
		$.post("?z=setting-ajax_modify_oj", {
			vid:$('#vid option:selected').val(),
			sortid:$('#sortid').val(),
			mainname:$('#mainname').val(),
			devname:$('#devname').val(),
			introduce:$('#introduce').val(),
			photos:photos,
			titles:titles,
			descs:descs
		})
		.done(function (data) {
			if(data.status == 0) { $('#vid option:selected').first().html(data.data); alert("[提示]保存成功！"); }
			else alert(data.info, "error");
			$('#btn-save').prop('disabled', false);
		})
		.fail(function () {
			alert('[错误]请检查网络连接。', "error");
			$('#btn-save').prop('disabled', false);
		});
	});
	
	$('#btn-photo-create').click(function(){  //显示添加图片窗口
		$.getJSON("?z=setting-ajax_get_img_list", null)
		.done( function(data) {
			if(data.status == 0) {
				$('#exists_fn').empty();
				for(var i=0; i<data.data.length; i++) {
					$('#exists_fn').append('<option value="'+data.data[i]+'">'+data.data[i]+'</option>');
				}
				if(data.data.length > 0) {  //有存在的图片
					$('#btn-choose').prop('disabled', false);
					$('#exists_fn').prop('disabled', false);
					$('#exists_fn').first().prop('selected', true);
					$('#photo-select-view').attr('src', 'upload/'+$('#exists_fn option:selected').first().val());
				}
				else {
					$('#btn-choose').prop('disabled', true);
					$('#exists_fn').prop('disabled', true);
					$('#photo-select-view').attr('src', 'img/nopic.jpg');
				}
			}
			else { alert('[错误]获取图片列表失败，请重试！', 'error'); }
		})
		.fail( function() {
			alert("[错误]请检查网络连接。", "error");
		});
		$('#photo-modal').modal();
	});
	
	$('#btn-upload').on('click', null, function (e) {  //上传图片按钮
		$.ajaxFileUpload({
			url:"?z=setting-ajax_upload_ojpic",
			secureuri:false,
			fileElementId:'upload_fn',
			dataType:"json",
			data:{id:0},
			success:function(data)
			{
				 if(data.status == 0) {
					 $('#photo').append('<option value="'+photos.length+'">'+data.data.filename.substr(7)+'</option>');
					 photos[photos.length] = data.data.filename.substr(7);
					 titles[titles.length] = null;
					 descs[descs.length] = null;
					 $('#photo option').last().prop('selected', true);
					 alert(data.info);
					 changephoto();
					 $('#photo-modal').modal('hide');
				 }
				 else { alert(data.info, "error"); }
			},
			error:function(data, status, e)
			{
				alert("[错误]请检查网络联接。", "error");
			}
		});
	});
	
	$('#exists_fn').change(function(){  //窗口中选择图片事件
		$('#photo-select-view').attr('src', 'upload/'+$('#exists_fn option:selected').first().val());
	});
	
	$('#btn-choose').click(function(){
		if($('#exists_fn option:selected').length != 1) {
			alert("[错误]请先选择一张图片。", "error");
		}
		else {
			$('#photo').append('<option value="'+photos.length+'">'+$('#exists_fn option:selected').first().val()+'</option>');
			photos[photos.length] = $('#exists_fn option:selected').first().val();
			titles[titles.length] = null;
			descs[descs.length] = null;
			$('#photo option').last().prop('selected', true);
			alert('[提示]已添加图片upload/'+$('#exists_fn option:selected').first().val());
			changephoto();
			$('#photo-modal').modal('hide');
		}
	});

	loadvid();
});

function loadvid() {  //加载OJ列表
	$.ajax({
		dataType:"json",
    	url:"?z=setting-ajax_get_oj_list",
		async:false,
		success:function(data) {
			$('#vid').empty();
			for(var i=0; i<data.data.length; i++) {
				$('#vid').append('<option value="'+data.data[i].vid+'">'+data.data[i].mainname+'</option>');
			}
			$('#vid option').first().prop('selected', true);
			changevid();
		},
		error:function() {
			alert("[错误]请检查网络连接。", "error");
		}
	});
}

function changevid() {  //根据VID加载详细内容
	if($('#vid option:selected').length != 1) {  //无选中项
		$('#btn-del').prop('disabled', true);
		$('#btn-save').prop('disabled', true);
		$('#oj_detail').addClass('hide');
	}
	else {
		$('#btn-del').prop('disabled', false);
		$('#btn-save').prop('disabled', false);
		$('#oj_detail').removeClass('hide');
		$.ajax({
			dataType:"json",
			async:false,
			url:"?z=setting-ajax_get_oj_detail", 
			data:{vid:$('#vid option:selected').first().val()},
			success:function(data) {
				$('#sortid').val(data.data.sortid);
				$('#mainname').val(data.data.mainname);
				$('#devname').val(data.data.devname == null ? '' : data.data.devname);
				$('#introduce').val(data.data.introduce == null ? '' : data.data.introduce);
				photos = data.data.photos == null ? new Array() : data.data.photos;
				titles = data.data.titles == null ? new Array() : data.data.titles;
				descs = data.data.descs == null ? new Array() : data.data.descs;
				$('#photo').empty();
				for(var i=0; i<photos.length; i++) {
					$('#photo').append('<option value="'+i+'">'+photos[i]+'</option>');
				}
				if(photos.length > 0) $('#photo option').first().prop('selected', true);
				changephoto();
			},
			error:function() {
				alert('[错误]请检查网络连接。', "error");
			}
		});
	}
}

function changephoto() {  //根据文件名加载照片描述信息
	if($('#photo option:selected').length != 1) {  //无照片
		$('#photo-view').attr('src', 'img/nopic.jpg');
		$('#title').prop('disabled', true);
		$('#desc').prop('disabled', true);
		$('#title').val(null);
		$('#desc').val(null);
	}
	else {
		pid = $('#photo').val();
		$('#photo-view').attr('src', 'upload/'+photos[pid]);
		$('#title').prop('disabled', false);
		$('#desc').prop('disabled', false);
		$('#title').val(titles[pid]);
		$('#desc').val(descs[pid]);
	}
}