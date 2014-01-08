var editor;
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
			set_news_modal("1", 0);
		}
		else if($(this).data('func') == "2" || $(this).data('func') == "0"){
			set_news_modal($(this).data('func'), parseInt($(this).data('nid')));
		}
		else
		{
			var n = $("#data-table input:checked").length;
			if(!n) { alert("[错误]请选择一条新闻来进行编辑操作。", "error"); return false; }
			var id = $('#data-table').find("input:checked").first().attr("id");
			set_news_modal("2", parseInt(id));
			if($("#data-table input:checked").length > 1) alert("[提示]你选择了多条新闻，只会编辑第一条选中的新闻哦~");
		}
        $(target).modal("show");
	    return false;
	});
	
	$('.content').on('click', '[data-toggle=del_news]', function (e) {
	    e.preventDefault();
		del_news($(this).data('nid'));
	});
	
	$('#btn-submit').on('click', null, function(e) {
		$('input[name=content]').val(editor.html());
		var form_data = $('#news-form').serialize();
		if($('#nownid').val() == '9999') {
			$.post("?z=setting-ajax_add_news", form_data)
			.done(function (data) {
				if(data.status == 0) { alert(data.info); $('#news-modal').modal('hide'); reFresh(); }
				else alert(data.info, "error");
			})
			.fail(function () {
				alert('[错误]请检查网络连接。', "error");
			});
		}
		else {
			$.post("?z=setting-ajax_modify_news", form_data)
			.done(function (data) {
				if(data.status == 0) { alert("[成功]修改该新闻成功！"); $('#news-modal').modal('hide'); reFresh(); }
				else alert(data.info, "error");
			})
			.fail(function () {
				alert('[错误]请检查网络连接。', "error");
			});
		}
	});
});

function reFresh() {
	$.getJSON("?z=setting-ajax_load_news", null)
	.done(function(data) {
		var reshtml = "";
		$.each(data.data, function(i, vo) {
			reshtml = reshtml + '<tr><td><label class="checkbox"><input type="checkbox" id="' + vo.nid + '" data-id="id"></label></td><td>' + vo.nid + '</td><td>' + vo.category + '</td><td>';
			reshtml = reshtml + (vo.permission == 0 ? '所有人':'队内');
			reshtml = reshtml + '</td><td>' + (vo.title.length>20?(vo.title.substr(0,20) + '...'):vo.title) + '</td><td>' + vo.author_detail.chsname + '</td><td>' + vo.createtime;
			reshtml = reshtml + '</td><td class="text-center inline"><div class="btn-group" id="table-toolbar-operate"><a data-nid="' + vo.nid + '" data-func="0" data-target="#news-modal" data-toggle="modal" class="btn btn-small btn-view" title="查看" data-trigger="hover"><i class="icon-zoom-in"></i> </a><a data-nid="' + vo.nid + '" data-func="2" data-target="#news-modal" data-toggle="modal" class="btn btn-small btn-edit" title="编辑" data-trigger="hover" data-placement="bottom"><i class="icon-edit"></i> </a><a data-toggle="del_news" data-nid="' + vo.nid + '" class="btn btn-small btn-delete" title="删除"><i class="icon-trash"></i> </a></div></td></tr>';  
		});
		$('#data-table tbody').html(reshtml);
		$("#data-table").trigger("update");
	})
	.fail(function() {
		alert('[错误]请检查网络连接。', "error");
	});
}

function set_news_modal(func, nid) {  //0-查看,1-增加,2-修改
	if(func == "2" || func == "0") {
		$.getJSON("?z=setting-ajax_get_news-nid-" + nid, null)
		.done( function(data) {
			if(data.status == 0) {
				$('#nownid').val(nid);
				$('#category').val(data.data.category);
				$('#title').val(data.data.title);
				data.data.content == null ? editor.html('') : editor.html(data.data.content);
				data.data.permission == 0 ? $('#permission').prop('checked', false) : $('#permission').prop('checked', true);
				
				if(func == "2") {
					$('#news-modal-title').html('新闻管理 - NID: ' + nid);
					$('#btn-submit').removeClass('hide');
				}
				else {
					$('#news-modal-title').html('查看新闻 - NID: ' + nid + ', By ' + data.data.author_detail.chsname);
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
		$('#nownid').val(9999);
		$('#category').val(null);
		$('#title').val(null);
		editor.html('');
		$('#permission').prop('checked', false);

		$('#btn-submit').removeClass('hide');
		$('#news-modal-title').html('添加新闻');
	}
}

function del_checked(){
	var n=$("#data-table input:checked").length;
	if(!n){
		alert('[错误]请先选择待删除的新闻。', "error");
		return false;
	}
	var list=$("#data-table input:checked").map(function() {
		return $(this).attr('id');
	}).get().join(',');
	del_news(list);
}

function del_news(nids) {
	if(confirm("[提示]你确定要删除选择的新闻吗？")) {
		$.getJSON("?z=setting-ajax_del_news", {nid:nids})
		.done( function(data) {
			if(data.status == 0) { alert(data.info); reFresh(); }
			else alert(data.info, "error");
		})
		.fail( function () {
			alert('[错误]请检查网络连接。', "error");
		});
	}
}
