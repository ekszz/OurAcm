var editor;
var typeahead_data = Array();  //自动提示数据

KindEditor.ready(function(K) {
	editor = K.create('#content', {
		width : '200px',
		allowPreviewEmoticons : false,
		allowImageUpload : false,
		items : [
		'source', '|', 'undo', 'redo', '|', 'preview', 'print', 'cut', 'copy', 'paste',
        'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
        'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
        'superscript', 'clearhtml', 'selectall', '/',
        'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
        'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image', 'table', 'hr', 'emoticons', 'pagebreak',
        'anchor', 'link', 'unlink'],
		themeType : 'simple',
		allowImageUpload: false,
		allowFlashUpload: false,
		allowMediaUpload: false,
		allowFileUpload: false,
	});
});
	
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
			set_activity_modal("1", 0);
		}
		else if($(this).data('func') == "2" || $(this).data('func') == "0"){
			set_activity_modal($(this).data('func'), parseInt($(this).data('aid')));
		}
		else
		{
			var n = $("#data-table input:checked").length;
			if(!n) { alert("[错误]请选择一条活动来进行编辑操作。", "error"); return false; }
			var id = $('#data-table').find("input:checked").first().attr("id");
			set_activity_modal("2", parseInt(id));
			if($("#data-table input:checked").length > 1) alert("[提示]你选择了多条活动，只会编辑第一条选中的活动哦~");
		}
        $(target).modal("show");
	    return false;
	});
	
	$('.content').on('click', '[data-toggle=del_activity]', function (e) {
	    e.preventDefault();
		del_activity($(this).data('aid'));
	});
	
	$('#btn-submit').on('click', null, function(e) {
		$('input[name=content]').val(editor.html());
		var form_data = $('#activity-form').serialize();
		if($('#nowaid').val() == '9999') {
			$.post("?z=setting-ajax_add_activity", form_data)
			.done(function (data) {
				if(data.status == 0) { alert(data.info); $('#activity-modal').modal('hide'); reFresh(); }
				else alert(data.info, "error");
			})
			.fail(function () {
				alert('[错误]请检查网络连接。', "error");
			});
		}
		else {
			$.post("?z=setting-ajax_modify_activity", form_data)
			.done(function (data) {
				if(data.status == 0) { alert("[成功]修改该活动成功！"); $('#activity-modal').modal('hide'); reFresh(); }
				else alert(data.info, "error");
			})
			.fail(function () {
				alert('[错误]请检查网络连接。', "error");
			});
		}
	});
	
	$.getJSON("?z=setting-ajax_get_typeaheaddata", null)
	.done( function(data) {
		if(data.status == 0) {
			for(var i=0;i<data.data.length;i++) { typeahead_data[i] = data.data[i]; }
			$('#admin').typeahead({
				source: function(query, process) {
					return typeahead_data;
				}
			});
		}
		else alert(data.info, "error");
	})
	.fail( function() {
		alert("[错误]获取自动完成数据出错，请检查网络连接。", "error");
	});
});

function reFresh() {
	$.getJSON("?z=setting-ajax_load_activity", null)
	.done(function(data) {
		var reshtml = "";
		$.each(data.data, function(i, vo) {
			reshtml = reshtml + '<tr><td><label class="checkbox"><input type="checkbox" id="' + vo.aid + '" data-id="id"></label></td><td>' + vo.aid + '</td>';
			reshtml = reshtml + '<td>' + (vo.title.length>20?(vo.title.substr(0,20) + '...'):vo.title) + '</td><td>' + vo.deadline + '</td><td>';
			reshtml = reshtml + (vo.isinner == 0 ? '':' <span class="label label-info">内</span>') + (vo.ispublic == 0 ? ' <span class="label label-success">秘</span>':'') + (vo.isneedreview != 0 ? ' <span class="label label-warning">审</span>':'');
			reshtml = reshtml + '</td><td>' + vo.accept + '/' + vo.sum;
			reshtml = reshtml + '</td><td class="text-center inline"><div class="btn-group" id="table-toolbar-operate"><a data-aid="' + vo.aid + '" data-func="0" data-target="#activity-modal" data-toggle="modal" class="btn btn-small btn-view" title="查看" data-trigger="hover"><i class="icon-zoom-in"></i> </a><a data-aid="' + vo.aid + '" data-func="2" data-target="#activity-modal" data-toggle="modal" class="btn btn-small btn-edit" title="编辑" data-trigger="hover" data-placement="bottom"><i class="icon-edit"></i> </a><a data-toggle="del_activity" data-aid="' + vo.aid + '" class="btn btn-small btn-delete" title="删除"><i class="icon-trash"></i> </a></div></td></tr>';  
		});
		$('#data-table tbody').html(reshtml);
		$("#data-table").trigger("update");
	})
	.fail(function() {
		alert('[错误]请检查网络连接。', "error");
	});
}

function set_activity_modal(func, aid) {  //0-查看,1-增加,2-修改
	if(func == "2" || func == "0") {
		$.getJSON("?z=setting-ajax_get_activity-aid-" + aid, null)
		.done( function(data) {
			if(data.status == 0) {
				$('#nowaid').val(aid);
				$('#deadline').val(data.data.deadline);
				$('#title').val(data.data.title);
				data.data.form == null ? $('#form').val(null) : $('#form').val(data.data.form);
				data.data.desc == null ? editor.html('') : editor.html(data.data.desc);
				data.data.isinner == 0 ? $('#isinner').prop('checked', false) : $('#isinner').prop('checked', true);
				data.data.ispublic == 0 ? $('#ispublic').prop('checked', false) : $('#ispublic').prop('checked', true);
				data.data.isneedreview == 0 ? $('#isneedreview').prop('checked', false) : $('#isneedreview').prop('checked', true);
				if(data.data.adminuid != 0) { $('#admin').val(data.data.adminuid + '-' + data.data.admin_detail.chsname + '-' + data.data.admin_detail.engname); }
				else $('#admin').val(null);
				
				if(func == "2") {
					$('#activity-modal-title').html('活动管理 - AID: ' + aid);
					$('#btn-submit').removeClass('hide');
				}
				else {
					$('#activity-modal-title').html('查看活动 - AID: ' + aid);
					$('#btn-submit').addClass('hide');
				}
			}
			else {
				alert(data.info, "error");
			}
		})
		.fail(function () {
			alert('[错误]请检查网络连接。', "error");
		});
	}
	else {  //1 - 新增
		$('#nowaid').val(9999);
		$('#deadline').val('2014-8-8 23:59:59');
		$('#title').val(null);
		$('#admin').val(null);
		editor.html('');
		$('#form').val('0|Standard,1|1|学号,1|1|姓名,5|1|性别||男|女,1|0|邮箱,1|0|手机,1|0|备注');
		$('#isinner').prop('checked', false);
		$('#ispublic').prop('checked', true);
		$('#isneedreview').prop('checked', true);

		$('#btn-submit').removeClass('hide');
		$('#activity-modal-title').html('添加活动');
	}
}

function del_checked(){
	var n=$("#data-table input:checked").length;
	if(!n){
		alert('[错误]请先选择待删除的活动。', "error");
		return false;
	}
	var list=$("#data-table input:checked").map(function() {
		return $(this).attr('id');
	}).get().join(',');
	del_activity(list);
}

function del_activity(aids) {
	if(confirm("[提示]你确定要删除选择的活动吗？这将同时删除用户的注册信息。")) {
		$.getJSON("?z=setting-ajax_del_activity", {aid:aids})
		.done( function(data) {
			if(data.status == 0) { alert(data.info); reFresh(); }
			else alert(data.info, "error");
		})
		.fail( function () {
			alert('[错误]请检查网络连接。', "error");
		});
	}
}
