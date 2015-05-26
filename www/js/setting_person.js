$(function () {
	var pagerOptions = {
		container: $(".pagination"),
		output: '{page}/{totalPages}',
		page: 0,
		savePages : true,
		fixedHeight: false,
		removeRows: false,
		cssNext: '.next', // next page arrow
		cssPrev: '.prev', // previous page arrow
		cssFirst: '.first', // go to first page arrow
		cssLast: '.last', // go to last page arrow
		cssGoto: '.gotoPage', // select dropdown to allow choosing a page
		cssPageDisplay: '.pagedisplay', // location of where the "output" is displayed
		cssPageSize: '.pagesize', // page size selector - select dropdown that sets the "size" option
		cssDisabled: 'disabled', // Note there is no period "." in front of this class name
  	};
	
	//$('.selected').val($(this).data('value'));
	$.extend($.tablesorter.themes.bootstrap, {
		// these classes are added to the table. To see other table classes available,
		// look here: http://twitter.github.com/bootstrap/base-css.html#tables
		table      : 'table',
		header     : '', // give the header a gradient background
		footerRow  : '',
		footerCells: '',
		icons      : '', // add "icon-white" to make them white; this icon class is added to the <i> in the header
		sortNone   : 'd',
		sortAsc    : 'glyphicon glyphicon-chevron-up',
		sortDesc   : 'glyphicon glyphicon-chevron-down',
		active     : '', // applied when column is sorted
		hover      : '', // use custom css here - bootstrap class may not override it
		filterRow  : '', // filter row class
		even       : '', // odd row zebra striping
		odd        : ''  // even row zebra striping
  	});
  
  	$("#data-table").tablesorter({
		theme : "bootstrap",
		headers: {
			0: {sorter: false},
    	},
		widthFixed: true,
		headerTemplate : '{content} {icon}',
		widgets : [ "uitheme",  "zebra" ],
		widgetOptions : {
			zebra : ["even", "odd"],
			filter_reset : ".reset"
    	}
   	})
	.tablesorterPager(pagerOptions);
	
	reFresh();
	
	$('.content').on('click', '[data-toggle=modal]', function (e) {
	    e.preventDefault();
	    var target=$(this).data('target');
		if($(this).data('func') == "1") {
			set_person_modal("1", 0);
		}
		else if($(this).data('func') == "2" || $(this).data('func') == "0"){
			set_person_modal($(this).data('func'), parseInt($(this).data('uid')));
		}
		else
		{
			var n = $("#data-table input:checked").length;
			if(!n) { alert("提示","请选择一个队员来进行编辑操作。", "info"); return false; }
			var id = $('#data-table').find("tbody input:checked").first().attr("id");
			set_person_modal("2", parseInt(id));
			if($("#data-table input:checked").length > 1) alert("提示","你选择了多名队员，只会编辑第一个选中的队员哦~","info");
		}
        $(target).modal("show");
	    return false;
	});
	
	$('.content').on('click', '[data-toggle=del_person]', function (e) {
	    e.preventDefault();
		del_person($(this).data('uid'));
	});
	
	$('.content').on('click', '[data-toggle=show_code]', function (e) {
	    e.preventDefault();
		$('#lucky-code-show').html('[ ' + $(this).data('chsname') + ' ] 的邀请码是：[ ' + $(this).data('code') + ' ]');
	});
	
	$('.content').on('click', '[data-toggle=sendinv]', function (e) {
	    e.preventDefault();
		sendinv_person($(this).data('uid'));
	});
	
	$('#person-modal').on('click', '[data-func=upload]', function (e) {  //“上传”按钮事件
		$.ajaxFileUpload({
			url:"?z=setting-ajax_upload_personface",
			secureuri:false,
			fileElementId:'face',
			dataType:"json",
			data:{id:0},
			success:function(data)
			{
				 if(data.status == 0) { $('#face_show').attr("src", data.data.filename); alert("成功",data.info,"success"); }
				 else alert("错误",data.info, "error");
			},
			error:function(data, status, e)
			{
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			}
		});
	});
	
	$('#person-modal').on('click', '[data-func=del]', function (e) {  //“删除”按钮事件
		$('#face_show').attr('src', 'img/nopic.jpg');
	});
	
	$('#btn-submit').on('click', null, function(e) {
		$('#face_fn').val($('#face_show').attr('src'));
		var form_data = $('#person-form').serialize();
		if($('#nowuid').val() == '9999') {
			$.post("?z=setting-ajax_add_person", form_data)
			.done(function (data) {
				if(data.status == 0) { alert("成功",data.info,"success"); $('#person-modal').modal('hide'); reFresh(); }
				else alert("错误",data.info, "error");
			})
			.fail(function () {
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			});
		}
		else {
			$.post("?z=setting-ajax_modify_person", form_data)
			.done(function (data) {
				if(data.status == 0) { alert("成功","修改队员信息成功！","success"); $('#person-modal').modal('hide'); reFresh(); }
				else alert("错误",data.info, "error");
			})
			.fail(function () {
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			});
		}
	});
});

function reFresh() {
	$('#lucky-code-show').html(null);
	$.getJSON("?z=setting-ajax_load_person", null)
	.done(function(data) {
		var reshtml = "";
		if(data.data) $.each(data.data, function(i, vo) {
			reshtml = reshtml + '<tr><td><label style="padding-right:15px"><input type="checkbox" id="' + vo.uid + '" data-id="id"></label></td><td>' + vo.uid + '</td><td>' + vo.chsname + '</td><td>';
			reshtml = reshtml + (vo.sex == 1 ? '女':'男');
			reshtml = reshtml + '</td><td>' + (vo.email == null?'':vo.email) + '</td><td>' + (vo.phone == null?'':vo.phone) + '</td><td>' + (vo.grade == null?'':vo.grade) + '</td> <td>' + (vo.ojaccount == null?'':vo.ojaccount) + '</td><td>';
			if(vo.group == 1) reshtml += '队长'; else if(vo.group == 2) reshtml += '教练'; else if(vo.group == 9) reshtml += '管理员'; else reshtml += '队员';
			reshtml = reshtml + '</td><td class="text-center inline"><div class="btn-group" id="table-toolbar-operate"><a data-uid="' + vo.uid + '" data-func="0" data-target="#person-modal" data-toggle="modal" class="btn btn-default btn-xs" title="查看" data-trigger="hover"><span class="glyphicon glyphicon-zoom-in"></span></a><a data-uid="' + vo.uid + '" data-func="2" data-target="#person-modal" data-toggle="modal" class="btn btn-default btn-xs" title="编辑" data-trigger="hover" data-placement="bottom"><span class="glyphicon glyphicon-pencil"></span></a><a data-toggle="del_person" data-uid="' + vo.uid + '" class="btn btn-default btn-xs" title="删除"><span class="glyphicon glyphicon-remove"></span></a><a data-toggle="show_code" data-code="' + vo.luckycode + '" data-chsname="' + vo.chsname + '" class="btn btn-default btn-xs" title="显示邀请码"><span class="glyphicon glyphicon-barcode"></span></a>';
			if(vo.email != null && vo.ojaccount == null) reshtml = reshtml + '<a data-toggle="sendinv" data-uid="' + vo.uid + '"  class="btn btn-default btn-xs" title="发送邀请邮件"><span class="glyphicon glyphicon-envelope"></span></a>';
			else reshtml = reshtml + '<a data-toggle="sendinv" data-uid="' + vo.uid + '"  class="btn btn-default btn-xs" title="发送邀请邮件" disabled><span class="glyphicon glyphicon-envelope"></span></a>';
			reshtml = reshtml + '</div></td></tr>';  
		});
		$('#data-table tbody').html(reshtml);
		$("#data-table").trigger("update");
	})
	.fail(function() {
		alert("提示", '你已中断请求，或网络连接异常。', "info");
	});
}

function set_person_modal(func, uid) {  //0-查看,1-增加,2-修改
	if(func == "2" || func == "0") {
		$.getJSON("?z=setting-ajax_get_person-uid-" + uid, null)
		.done( function(data) {
			if(data.status == 0) {
				$('#nowuid').val(uid);
				$('#chsname').val(data.data.chsname);
				data.data.engname == null ? $('#engname').val(null) : $('#engname').val(data.data.engname);
				$('#group').val(data.data.group);
				$('#sex').val(data.data.sex);
				data.data.grade == null ? $('#grade').val(null) : $('#grade').val(data.data.grade);
				data.data.email == null ? $('#email').val(null) : $('#email').val(data.data.email);
				data.data.phone == null ? $('#phone').val(null) : $('#phone').val(data.data.phone);
				data.data.address == null ? $('#address').val(null) : $('#address').val(data.data.address);
				data.data.introduce == null ? $('#introduce').val(null) : $('#introduce').val(data.data.introduce);
				data.data.detail == null ? $('#detail').val(null) : $('#detail').val(data.data.detail);
				data.data.luckycode == null ? $('#luckycode').val(null) : $('#luckycode').val(data.data.luckycode);
				data.data.ojaccount == null ? $('#ojaccount').val(null) : $('#ojaccount').val(data.data.ojaccount);
				data.data.photo == null ? $('#face_show').attr('src', 'img/nopic.jpg') : $('#face_show').attr('src', data.data.photo);

				if(func == "2") {
					$('#person-modal-title').html('队员管理 - ' + data.data.chsname + ' UID: ' + uid);
					$('#face_upload').removeAttr('disabled');
					$('#face_del').removeAttr('disabled');
					$('#btn-submit').removeClass('hide');
				}
				else {
					$('#person-modal-title').html('查看队员 - ' + data.data.chsname + ' UID: ' + uid);
					$('#face_upload').attr('disabled', true);
					$('#face_del').attr('disabled', true);
					$('#btn-submit').addClass('hide');
				}
			}
			else {
				alert("错误",data.info, "error");
			}
		})
		.fail(function () {
			alert("提示", '你已中断请求，或网络连接异常。', "info");
		});
	}
	else {  //1 - 新增
		$('#nowuid').val(9999);
		$('#chsname').val(null);
		$('#engname').val(null);
		$('#group').val(0);
		$('#sex').val(0);
		$('#grade').val(null);
		$('#email').val(null);
		$('#phone').val(null);
		$('#address').val(null);
		$('#introduce').val(null);
		$('#detail').val(null);
		$('#luckycode').val(null);
		$('#ojaccount').val(null);
		$('#face_show').attr('src', 'img/nopic.jpg');

		$('#btn-submit').removeClass('hide');
		$('#face_upload').removeAttr('disabled');
		$('#face_del').removeAttr('disabled');
		$('#person-modal-title').html('新增队员');
	}
}

function del_checked(){
	var n=$("#data-table input:checked").length;
	if(!n){
		alert('提示','请先选择待删除的队员。', "info");
		return false;
	}
	var list=$("#data-table input:checked").map(function() {
		return $(this).attr('id');
	}).get().join(',');
	del_person(list);
}

function del_person(uids) {
	if(confirm("[提示]你确定要删除队员吗？如果该队员包含在某个队伍中，则删除后将使用UID：0的空用户替代。")) {
		$.getJSON("?z=setting-ajax_del_person", {uid:uids})
		.done( function(data) {
			if(data.status == 0) { alert("成功",data.info,"success"); reFresh(); }
			else alert("错误",data.info, "error");
		})
		.fail( function () {
			alert("提示", '你已中断请求，或网络连接异常。', "info");
		});
	}
}

function sendinv_checked(){
	var n=$("#data-table input:checked").length;
	if(!n){
		alert('提示','请先选择待发送邀请邮件的队员。', "info");
		return false;
	}
	var list=$("#data-table input:checked").map(function() {
		return $(this).attr('id');
	}).get().join(',');
	sendinv_person(list);
}

function sendinv_person(uids) {
	if(confirm("[提示]你确定要发送邀请邮件吗？已经关联OJ账号的用户不会重复发送。")) {
		$.getJSON("?z=setting-ajax_sendinv", {uid:uids})
		.done( function(data) {
			if(data.status == 0) { alert("成功",data.info,"success"); reFresh(); }
			else alert("错误",data.info, "error");
		})
		.fail( function () {
			alert("提示", '你已中断请求，或网络连接异常。', "info");
		});
	}
}