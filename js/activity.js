$(function () {
	var pagerOptions = {
		container: $(".pagination"),
		output: '{page}/{totalPages}',
		page: 0,
		savePages : true,
		fixedHeight: true,
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
		headers: {
			0: {sorter: false}, 1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false},
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
});

function reFresh(type) {
	if(type == 0) {
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
				reshtml = reshtml + '</td><td class="text-center inline"><div class="btn-group" id="table-toolbar-operate"><button data-aid="' + vo.aid + '" data-func="0" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="活动详情" data-trigger="hover"' + (vo.desc == null ? ' disabled="disabled"' : '') + '>详情</button><button data-aid="' + vo.aid + '" data-func="1" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="' + (islogin == 0 ? '请先在OJ上登录' : '报名或修改报名信息') + '" data-trigger="hover" data-placement="bottom"' + (islogin == 0 ? ' disabled="disabled"' : '') + '>报名</button><a data-aid="' + vo.aid + '" data-func="2" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="查看已报名的同学" data-trigger="hover" data-placement="bottom"' + ((vo.ispublic == 0 && vo.adminuid != 1) ? ' disabled="disabled"' : '') + '>名单</a>' + (vo.adminuid == 1 ? ('<button data-aid="' + vo.aid + '" data-func="3" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="导出已报名的名单为csv文件" data-trigger="hover" data-placement="bottom">导出</button>') : '') + '</div></td></tr>';
			});
			$('#data-table tbody').html(reshtml);
			$("#data-table").trigger("update");
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	}
	else {
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
				reshtml = reshtml + '</td><td class="text-center inline"><div class="btn-group" id="table-toolbar-operate"><button data-aid="' + vo.aid + '" data-func="0" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="活动详情" data-trigger="hover"' + (vo.desc == null ? ' disabled="disabled"' : '') + '>详情</button><button data-aid="' + vo.aid + '" data-func="1" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="修改报名信息" data-trigger="hover" data-placement="bottom">修改</button><a data-aid="' + vo.aid + '" data-func="2" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="查看已报名的同学" data-trigger="hover" data-placement="bottom"' + ((vo.ispublic == 0 && vo.adminuid != 1) ? ' disabled="disabled"' : '') + '>名单</a>' + (vo.adminuid == 1 ? ('<button data-aid="' + vo.aid + '" data-func="3" data-target="#activity-modal" data-toggle="modal" class="btn btn-small" title="导出已报名的名单为csv文件" data-trigger="hover" data-placement="bottom">导出</button>') : '') + '</div></td></tr>';
			});
			$('#data-table tbody').html(reshtml);
			$("#data-table").trigger("update");
		})
		.fail(function() {
			alert('[错误]请检查网络连接。', "error");
		});
	}
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
	else if(func == 1) {  //注册表单，待测试
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