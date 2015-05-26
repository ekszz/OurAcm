var typeahead_data = Array();  //自动提示数据

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
	
	//tablesorter插件初始化=====================================
	$.extend($.tablesorter.themes.bootstrap, {
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

	//加载表格内容=================================
	reFresh();
	
	//按钮(弹窗)事件===============================
	$('.content').on('click', '[data-toggle=modal]', function (e) {
		e.preventDefault();
		var target=$(this).data('target');
		if($(this).data('func') == "1") {  //增加
			set_contest_modal("1", 0);
		}
		else if($(this).data('func') == "2" || $(this).data('func') == "0"){  //0-查看，2-修改
			set_contest_modal($(this).data('func'), parseInt($(this).data('cid')));
		}
		else
		{
			var n = $("#data-table input:checked").length;
			if(!n) { alert("提示","请选择一条获奖记录来进行编辑操作。", "info"); return false; }
			var id = $('#data-table').find("tbody input:checked").first().attr("id");
			set_contest_modal("2", parseInt(id));
			if($("#data-table input:checked").length > 1) alert("提示", "你选择了多条获奖记录，只会编辑第一条选中的记录哦~","info");
		}
		$(target).modal("show");
		return false;
	});

	$('.content').on('click', '[data-toggle=del_contest]', function (e) {  //删除一条比赛记录事件
		e.preventDefault();
		del_contest($(this).data('cid'));
	});

	$('#contest-modal').on('click', '[data-func=add_person]', function (e) {  //窗口中，临时添加一个队员按钮事件
		var target=$(this).data('target');
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
		
		$('#result_pos').val(target);
		$('#person-modal-title').html('新增队员');
		$('#contest-modal').modal('hide');
		$('#person-modal').modal('show');
	});
	
	$('#contest-modal').on('click', '[data-func=upload]', function (e) {  //“上传”按钮事件
		$.ajaxFileUpload({
			url:"?z=setting-ajax_upload_contestpic",
			secureuri:false,
			fileElementId:$(this).attr('id').substr(0,4),
			dataType:"json",
			data:{id:$(this).attr('id').substr(3,1)},
			success:function(data)
			{
				 if(data.status == 0) { $('#pic'+data.data.id+'_show').attr("src", data.data.filename); alert("成功",data.info,"success"); }
				 else alert("错误",data.info, "error");
			},
			error:function(data, status, e)
			{
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			}
		});
	});
	
	$('#contest-modal').on('click', '[data-func=del]', function (e) {  //“删除”按钮事件
		$('#'+$(this).attr('id').substr(0,5)+'show').attr('src', 'img/nopic.jpg');
	});
	
	$('#contest-btn-submit').on('click', null, function(e) {  //添加获奖记录
		$('#pic1_fn').val($('#pic1_show').attr('src'));
		$('#pic2_fn').val($('#pic2_show').attr('src'));
		var form_data = $('#contest-form').serialize();
		if($('#nowcid').val() == '9999') {
			$.post("?z=setting-ajax_add_contest", form_data)
			.done(function (data) {
				if(data.status == 0) { alert("成功",data.info,"success"); $('#contest-modal').modal('hide'); reFresh(); }
				else alert("错误", data.info, "error");
			})
			.fail(function () {
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			});
		}
		else {
			$.post("?z=setting-ajax_modify_contest", form_data)
			.done(function (data) {
				if(data.status == 0) { alert("成功","修改获奖记录成功！","success"); $('#contest-modal').modal('hide'); reFresh(); }
				else alert("错误", data.info, "error");
			})
			.fail(function () {
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			});
		}
	});

	$('#person-modal').on('click', '[data-dismiss=modal]', function (e) {  //添加队员-窗口 关闭事件
		e.preventDefault();
		$('#person-modal').modal('hide');
		$('#contest-modal').modal('show');
	});
			
	$('#person-btn-submit').on('click', null, function(e) {  //添加队员-窗口 提交按钮事件
		var form_data = $('#person-form').serialize();
		$.post("?z=setting-ajax_add_person", form_data)
		.done(function (data) {
			if(data.status == 0) { 
				alert("成功",data.info,"success");
				$('#person-modal').modal('hide');
				$('#contest-modal').modal('show');
				$($('#result_pos').val()).val(data.data);  //写入调用位置
				typeahead_data.push(data.data);  //自动完成同步更新
			}
			else alert("错误", data.info, "error");
		})
		.fail(function () {
			alert("提示", '你已中断请求，或网络连接异常。', "info");
		});
	});
			
	//增加自动提示数据===========================
	$.getJSON("?z=setting-ajax_get_typeaheaddata", null)
	.done( function(data) {
		if(data.status == 0) {
			for(var i=0;i<data.data.length;i++) { typeahead_data[i] = data.data[i]; }
			$('#leader').typeahead({source:typeahead_data});
			$('#teamer1').typeahead({source:typeahead_data});
			$('#teamer2').typeahead({source:typeahead_data});
		}
		else alert("错误",data.info, "error");
	})
	.fail( function() {
		alert("提示", '获取自动完成数据出错。你已中断请求，或网络连接异常。', "info");
	});
			
}); //END OF $()init.
		
function reFresh() {  //reload table
	$.getJSON("?z=setting-ajax_load_contest", null)
	.done(function(data) {
		var reshtml = "";
		if(data.data) $.each(data.data, function(i, vo) {
			reshtml = reshtml + '<tr><td><label style="padding-right:15px"><input type="checkbox" id="' + vo.cid + '" data-id="id"></label></td><td>' + vo.cid + '</td><td>' + vo.holdtime + '</td><td>' + vo.site + '</td><td>' + vo.university + '</td><td>';
			if(vo.type == 0) reshtml = reshtml + 'WF';
			else if(vo.type == 1) reshtml = reshtml + 'R';
			else if(vo.type == 2) reshtml = reshtml + 'P';
			else reshtml = reshtml + vo.type + '-';
			
			reshtml += '</td><td>';
			
			if(vo.medal == 0) reshtml = reshtml + '金';
			else if(vo.medal == 1) reshtml = reshtml + '银';
			else if(vo.medal == 2) reshtml = reshtml + '铜';
			else if(vo.medal == 4) reshtml = reshtml + '旅';
			else reshtml = reshtml + '铁';
			reshtml = reshtml + '</td><td>' + vo.team + '</td><td>';
			var picnum = 0;
			if(vo.pic1 != null) picnum++;
			if(vo.pic2 != null) picnum++;
			reshtml = reshtml + picnum + '</td><td class="text-center inline"><div class="btn-group" id="table-toolbar-operate"><a data-cid="' + vo.cid + '" data-func="0" data-target="#contest-modal" data-toggle="modal" class="btn btn-xs btn-default" title="查看" data-trigger="hover"><span class="glyphicon glyphicon-zoom-in"></span></a><a data-cid="' + vo.cid + '" data-func="2" data-target="#contest-modal" data-toggle="modal" class="btn btn-xs btn-default" title="编辑" data-trigger="hover" data-placement="bottom"><span class="glyphicon glyphicon-pencil"></span></a><a data-toggle="del_contest" data-cid="' + vo.cid + '" class="btn btn-xs btn-default" title="删除"><span class="glyphicon glyphicon-remove"></span></a></div></td></tr>';  
		});
		$('#data-table tbody').html(reshtml);
		$("#data-table").trigger("update");
	})
	.fail(function() {
		alert("提示", '你已中断请求，或网络连接异常。', "info");
	});
}  //END OF reFresh()
		
function set_contest_modal(func, cid) {  //填充modal中的数据，0-查看,1-增加,2-修改
	if(func == "2" || func == "0") {  //0-查看，2-修改
		$.getJSON("?z=setting-ajax_get_contest-cid-" + cid, null)
		.done( function(data) {
			if(data.status == 0) {
				$('#nowcid').val(cid);
				$('#holdtime').val(data.data.holdtime);
				$('#team').val(data.data.team);
				$('#site').val(data.data.site);
				$('#university').val(data.data.university);
				$('#type').val(data.data.type);
				$('#medal').val(data.data.medal);
				data.data.ranking == null ? $('#ranking').val(null) : $('#ranking').val(data.data.ranking);
				data.data.title == null ? $('#title').val(null) : $('#title').val(data.data.title);
				$('#leader').val(data.data.leader_detail.uid + '-' + data.data.leader_detail.chsname);
				$('#teamer1').val(data.data.teamer1_detail.uid + '-' + data.data.teamer1_detail.chsname);
				$('#teamer2').val(data.data.teamer2_detail.uid + '-' + data.data.teamer2_detail.chsname);
				data.data.pic1 == null ? $('#pic1_show').attr("src", "img/nopic.jpg") : $('#pic1_show').attr("src", data.data.pic1);
				data.data.pic2 == null ? $('#pic2_show').attr("src", "img/nopic.jpg") : $('#pic2_show').attr("src", data.data.pic2);
				
				if(func == "2") {
					$('#pic1_upload').removeAttr('disabled');
					$('#pic2_upload').removeAttr('disabled');
					$('#pic1_del').removeAttr('disabled');
					$('#pic2_del').removeAttr('disabled');
					$('[data-func=add_person]').removeAttr('disabled');
					$('#contest-modal-title').html('获奖记录管理 - CID: ' + cid);
					$('#contest-btn-submit').show();
					$('#upload_notice').html('<span class="label label-warning">提示</span> 请选上传或删除要修改的照片文件，再点击保存按钮提交~');
					$('#upload_notice').show();
				}
				else {
					$('#pic1_upload').attr('disabled', true);
					$('#pic2_upload').attr('disabled', true);
					$('#pic1_del').attr('disabled', true);
					$('#pic2_del').attr('disabled', true);
					$('[data-func=add_person]').attr('disabled', true);
					$('#contest-modal-title').html('查看获奖记录 - CID: ' + cid);
					$('#contest-btn-submit').hide();
					$('#upload_notice').hide();
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
		$('#nowcid').val(9999);
		$('#holdtime').val(null);
		$('#team').val(null);
		$('#site').val(null);
		$('#university').val(null);
		$('#type').val(1);
		$('#medal').val(0);
		$('#ranking').val(null);
		$('#title').val(null);
		$('#leader').val(null);
		$('#teamer1').val(null);
		$('#teamer2').val(null);
		$('#pic1').val(null);
		$('#pic2').val(null);
		$('#pic1_show').attr('src', 'img/nopic.jpg');
		$('#pic2_show').attr('src', 'img/nopic.jpg');
		$('#upload_notice').html('<span class="label label-warning">提示</span> 请选上传或删除要修改的照片文件，再点击保存按钮提交~');
		$('#upload_notice').show();
		$('#contest-btn-submit').show();
		$('#contest-modal-title').html('新增获奖记录');
		$('#pic1_upload').removeAttr('disabled');
		$('#pic2_upload').removeAttr('disabled');
		$('#pic1_del').removeAttr('disabled');
		$('#pic2_del').removeAttr('disabled');
	}
	
}  //END OF set_contest_modal()

function del_checked(){  //删除多个选中的队员
	var n=$("#data-table input:checked").length;
	if(!n){
		alert("提示",'请先选择待删除的获奖记录。', "info");
		return false;
	}
	var list=$("#data-table input:checked").map(function() {
		return $(this).attr('id');
	}).get().join(',');
	del_contest(list);
}  //END OF del_checked()

function del_contest(cids) {  //删除获奖记录具体操作
	if(confirm("[提示]你确定要删除选定的获奖记录吗？")) {
		if(confirm("[提示]你是否需要删除与该获奖记录相关联的照片？")) {  //同时删除照片
			$.getJSON("?z=setting-ajax_del_contest", {cid:cids, delpic:"1"})
			.done( function(data) {
				if(data.status == 0) { alert("成功", data.info, "success"); reFresh(); }
				else alert("错误", data.info, "error");
			})
			.fail( function () {
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			});
		}
		else {  //保留照片
			$.getJSON("?z=setting-ajax_del_contest", {cid:cids})
			.done( function(data) {
				if(data.status == 0) { alert("成功",data.info,"success"); reFresh(); }
				else alert("错误",data.info, "error");
			})
			.fail( function () {
				alert("提示", '你已中断请求，或网络连接异常。', "info");
			});
		}
	}
}
