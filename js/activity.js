var now_page = 0;
var now_aid = 0;
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
		sortAsc    : 'icon-chevron-up',
		sortDesc   : 'icon-chevron-down',
		active     : '', // applied when column is sorted
		hover      : '', // use custom css here - bootstrap class may not override it
		filterRow  : '', // filter row class
		even       : '', // odd row zebra striping
		odd        : ''  // even row zebra striping
  	});
  
  	$("#data-table").tablesorter({
		theme : "bootstrap",
		headers: {},
		widthFixed: true,
		headerTemplate : '{content} {icon}',
		widgets : [ "uitheme",  "zebra" ],
		widgetOptions : {
			zebra : ["even", "odd"],
			filter_reset : ".reset"
    	}
   	})
	.tablesorterPager(pagerOptions);
	
	reFresh(0);
	
	$('.container').on('click', '[data-toggle=modal]', function (e) {
	    e.preventDefault();
	    var target=$(this).data('target');
		if($(this).data('func') == "0") {
			set_activity_modal(target, 0, parseInt($(this).data('aid')));
		}
		else if($(this).data('func') == "1") {
			set_activity_modal(target, 1, parseInt($(this).data('aid')));
		}
	    return false;
	});
	
	$('.container').on('click', '[data-toggle=showlist]', function (e) {
	    e.preventDefault();
		now_aid = $(this).data('aid');
	    load_contestants();
	    return false;
	});
	
	$('.container').on('click', '[data-toggle=delcontestant]', function (e) {
	    e.preventDefault();
	   	$.getJSON("?z=activity-ajax_del_contestant-adid-" + $(this).data("adid"), null)
		.done(function(data) {
			if(data.status == 0) {
				alert('[提示]删除成功！');
				load_contestants(now_aid);
			}
			else alert(data.info, "error");
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	    return false;
	});
	
	$('#activity-btn-submit').on('click', null, function(e) {
		var form_data = $('#activity-form').serialize();
		$.post("?z=activity-ajax_save_regdata", form_data)
		.done(function (data) {
			if(data.status == 0) { alert(data.info); $('#activity-modal').modal('hide'); reFresh(1); }
			else alert(data.info, "error");
		})
		.fail(function () {
			alert('[错误]请检查网络连接。', "error");
		});
	});
	
	$('#data-table').on('click','.btn-ables .btn-enable', function(e){
	 	if($(this).hasClass('disabled')) return;
	 	e.preventDefault();
		$.getJSON("?z=activity-ajax_review_contestant-adid-" + $(this).data("adid") + '-state-2', null)
		.done(function(data) {
			if(data.status == 0) {
				$('.btn-ables .btn-enable[data-adid=' + data.data + ']').addClass('btn-success disabled');
				$('.btn-ables .btn-disable[data-adid=' + data.data + ']').removeClass('btn-warning disabled');
			}
			else alert(data.info, "error");
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	});

	$('#data-table').on('click','.btn-ables .btn-disable', function(e){
		if($(this).hasClass('disabled')) return;
		e.preventDefault();
		$.getJSON("?z=activity-ajax_review_contestant-adid-" + $(this).data("adid") + '-state-1', null)
		.done(function(data) {
			if(data.status == 0) {
				$('.btn-ables .btn-disable[data-adid=' + data.data + ']').addClass('btn-warning disabled');
				$('.btn-ables .btn-enable[data-adid=' + data.data + ']').removeClass('btn-success disabled');
			}
			else alert(data.info, "error");
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	});
});

function reFresh(type) {
	$('#header2').addClass('hide');
	$('#header1').removeClass('hide');
	$('#data-table').html('<thead><tr class="align-center"><th class="sorter-false" width="50%">活动名称</th><th class="sorter-false" width="20%">报名截止时间</th><th class="sorter-false" width="10%">报名人数</th><th width="20%" class="sorter-false">操作</th></tr></thead><tbody></tbody>');
	if(type == 0) {
		now_page = 0;
		$('#reFresh1').removeClass('active');
		$('#reFresh0').addClass('active');
		$('#data-table tbody').html(null);
		$.getJSON("?z=activity-ajax_load_activity-type-0", null)
		.done(function(data) {
			if(data.status != 0) { alert(data.info, "error"); return ;}
			var reshtml = "";
			if(data.data == null) {
				$('#data-table tbody').html('<tr><td colspan="4">暂时没有活动信息</td></tr>');
				$("#data-table").trigger("update");
				return ;
			}
			$.each(data.data, function(i, vo) {
				reshtml = reshtml + '<tr><td>' + (vo.title.length>20?(vo.title.substr(0,20) + '...'):vo.title) + (vo.isinner == 0 ? '':' <span class="label label-info">队内</span>') + (vo.isneedreview != 0 ? ' <span class="label label-warning">需审核</span>':'') + '</td><td>' + vo.deadline + '</td><td>';
				reshtml = reshtml + vo.accept;
				reshtml = reshtml + '</td><td class="text-center inline"><div class="btn-group" id="table-toolbar-operate"><button data-aid="' + vo.aid + '" data-func="0" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="活动详情" data-trigger="hover"' + (vo.desc == null ? ' disabled="disabled"' : '') + '>详情</button><button data-aid="' + vo.aid + '" data-func="1" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="' + (islogin == 0 ? '请先在OJ上登录' : '报名或修改报名信息') + '" data-trigger="hover" data-placement="bottom"' + (islogin == 0 ? ' disabled="disabled"' : '') + '>报名</button><a data-aid="' + vo.aid + '" data-toggle="showlist" class="btn btn-small" title="查看已报名的同学" data-trigger="hover" data-placement="bottom"' + ((vo.ispublic == 0 && vo.adminuid != 1) ? ' disabled="disabled"' : '') + '>名单</a>' + (vo.adminuid == 1 ? ('<a href="?z=activity-export_contestants-aid-' + vo.aid + '" class="btn btn-small" title="导出已报名的名单为csv文件" data-trigger="hover" data-placement="bottom">导出</a>') : '') + '</div></td></tr>';
			});
			$('#data-table tbody').html(reshtml);
			$("#data-table").trigger("update");
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	}
	else {
		now_page = 1;
		$('#reFresh0').removeClass('active');
		$('#reFresh1').addClass('active');
		$('#data-table tbody').html(null);
		$.getJSON("?z=activity-ajax_load_activity-type-1", null)
		.done(function(data) {
			if(data.status != 0) { alert(data.info, "error"); return ;}
			var reshtml = "";
			if(data.data == null) {
				$('#data-table tbody').html('<tr><td colspan="4">你暂时还未参加任何活动</td></tr>');
				$("#data-table").trigger("update");
				return ;
			}
			$.each(data.data, function(i, vo) {
				reshtml = reshtml + '<tr><td>' + (vo.title.length>20?(vo.title.substr(0,20) + '...'):vo.title) + (vo.isinner == 0 ? '':' <span class="label label-info">队内</span>') + (vo.isneedreview != 0 ? ' <span class="label label-warning">需审核</span>':'') + '</td><td>' + vo.deadline + '</td><td>';
				reshtml = reshtml + vo.accept;
				reshtml = reshtml + '</td><td class="text-center inline"><div class="btn-group" id="table-toolbar-operate"><button data-aid="' + vo.aid + '" data-func="0" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="活动详情" data-trigger="hover"' + (vo.desc == null ? ' disabled="disabled"' : '') + '>详情</button><button data-aid="' + vo.aid + '" data-func="1" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="修改报名信息" data-trigger="hover" data-placement="bottom">修改</button><a data-aid="' + vo.aid + '" data-toggle="showlist" class="btn btn-small" title="查看已报名的同学" data-trigger="hover" data-placement="bottom"' + ((vo.ispublic == 0 && vo.adminuid != 1) ? ' disabled="disabled"' : '') + '>名单</a>' + (vo.adminuid == 1 ? ('<button data-aid="' + vo.aid + '" data-func="3" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="导出已报名的名单为csv文件" data-trigger="hover" data-placement="bottom">导出</button>') : '') + '</div></td></tr>';
			});
			$('#data-table tbody').html(reshtml);
			$("#data-table").trigger("update");
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	}
}
function load_contestants() {
	$.getJSON("?z=activity-ajax_load_contestants-aid-" + now_aid, null)
	.done(function(data) {
		if(data.status != 0) { alert(data.info, "error"); return ; }
		$('#header-title').html('“' + data.data.title + '”的报名情况');
		var accept = 0;
		var total = 0;
		$('#header1').addClass('hide');
		$('#header2').removeClass('hide');
		var title = '';
		$.each(data.data.titles, function(i, v){title = title + '<th class="sorter-false">' + v + '</th>';});
		$('#data-table').html('<thead><tr class="align-center"><th class="sorter-false">OJ账号</th>' + title + '<th class="sorter-false">审核</th></tr></thead><tbody></tbody>');
		var reshtml = "";
		if(data.data.contestants.length == 0) {
			$('#data-table tbody').html('<tr><td colspan="' + (data.data.titles.length + 2) + '">该活动暂时还没人报名</td></tr>');
			$("#data-table").trigger("update");
			return ;
		}
		$.each(data.data.contestants, function(i, vo) {
			total++;
			reshtml = reshtml + '<tr><td><span class="badge badge-info">' + vo.ojaccount + '</span></td>';
			$.each(vo.data, function(j, v){reshtml = reshtml + '<td>' + v + '</td>';});
			if(data.data.isneedreview == 0) {
				if(data.data.isadmin == 0) reshtml = reshtml + '<td><span class="badge">无需审核</span></td></tr>';
				else reshtml = reshtml + '<td><span class="badge">无需审核</span> <button class="btn btn-mini btn-danger" data-toggle="delcontestant" data-adid="' + vo.adid + '">删除</button></td></tr>';
			}
			else {
				if(data.data.isadmin == 0) {
					if(vo.state == 2) { reshtml += '<td><span class="badge badge-success">审核通过</span></td></tr>'; accept++; }
					else if(vo.state == 1) reshtml += '<td><span class="badge badge-warning">拒绝申请</span></td></tr>';
					else reshtml += '<td><span class="badge">等待审核</span></td></tr>';
				}
				else {
					reshtml = reshtml + '<td><div class="btn-group btn-ables">';
					if(vo.state == 2) { reshtml += '<a data-adid="' + vo.adid + '" class="btn btn-mini btn-enable btn-success disabled">通过</a><a data-adid="' + vo.adid + '" class="btn btn-mini btn-disable">拒绝</a>'; accept++; }
					else if(vo.state == 1) reshtml += '<a data-adid="' + vo.adid + '" class="btn btn-mini btn-enable">通过</a><a data-adid="' + vo.adid + '" class="btn btn-mini btn-disable btn-warning disabled">拒绝</a>';
					else reshtml += '<a data-adid="' + vo.adid + '" class="btn btn-mini btn-enable">通过</a><a data-adid="' + vo.adid + '" class="btn btn-mini btn-disable">拒绝</a>';
					reshtml += '</div> <button class="btn btn-mini btn-danger" data-toggle="delcontestant" data-adid="' + vo.adid + '">删除</button></td></tr>';
				}
			}
		});
		$('#data-table tbody').html(reshtml);
		$("#data-table").trigger("update");
		$('#header-title').html('“' + data.data.title + '”的报名情况 <i>（人数：' + accept + '/' + total + '）</i>');
	})
	.fail(function() {
		alert('[错误]请检查网络连接。', "error");
	});
}
function set_activity_modal(target, func, aid) {
	if(func == 0) {  //查看活动详情
		$.getJSON("?z=activity-ajax_get_desc-aid-" + aid, null)
		.done(function(data) {
			if(data.status != 0) alert(data.info, "error");
			else {
				$('#activity-modal-title').html(data.data.title);
				$('#activity-modal-body').html(data.data.desc);
				$('#activity-btn-submit').addClass('hide');
				$(target).modal('show');
			}
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	}
	else if(func == 1) {  //注册表单
		$.getJSON("?z=activity-ajax_get_registeform-aid-" + aid, null)
		.done(function(data) {
			if(data.status != 0) alert(data.info, "error");
			else {
				$('#activity-modal-title').html('报名 - ' + data.data.title);
				$('#activity-modal-body').html(data.data.form + '<input type="hidden" name="aid" value="' + aid + '" />');
				$('#activity-btn-submit').removeClass('hide');
				if(data.data.data != null) {  //已有注册数据
					if(data.data.readonly == 1) {  //已通过审核
						$('#activity-btn-submit').addClass('hide');
						alert(data.info);
						$.each($('#activity-modal-body [name="regdata[]"]'), function(i, v) { $(this).val(data.data.data[i]); $(this).prop('disabled', true); });
					}
					else {
						alert('[提示]你已报名过，这里可以修改报名信息！');
						$.each($('#activity-modal-body [name="regdata[]"]'), function(i, v) { $(this).val(data.data.data[i]); });
					}
				}
				$(target).modal('show');
			}
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	}
}