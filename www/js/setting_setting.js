var setting_var = new Array();
var obj;

$(function () {
	getsetting();
	showsetting();
	changeselect();
	$('#k').on('change', null, function() {
		changeselect();
	});
});

function flushConfig() {  //刷新缓存
	$('#btn-flushconfig').attr('disabled', true);
	$.getJSON("?z=setting-ajax_flushconfig", null)
	.done( function(data) {
		if(data.status == 0) { alert('[成功]操作已执行！'); $('#btn-flushconfig').removeAttr('disabled'); }
		else { alert('[失败]请重试！', 'error'); $('#btn-flushconfig').removeAttr('disabled'); }
	})
	.fail( function() {
		alert("[错误]请检查网络连接。", "error");
		$('#btn-flushconfig').removeAttr('disabled');
	});
}

function saveConfig() {  //保存参数
	$('#btn-saveconfig').attr('disabled', true);
	$.post("?z=setting-ajax_savesetting", {k:$('#k').val(), v:(setting_var[$('#k').val()].type == "0" ? $("input[name='value1']:checked").val() : $('#value2').val())})
	.done( function(data) {
		if(data.status == 0) { setting_var[data.data.k].v = data.data.v; alert(data.info); $('#btn-saveconfig').removeAttr('disabled'); }
		else { alert(data.info, 'error'); $('#btn-saveconfig').removeAttr('disabled'); }
	})
	.fail( function() {
		alert("[错误]请检查网络连接。", "error");
		$('#btn-saveconfig').removeAttr('disabled');
	});
}


function getsetting() {  //从服务端获取所有数据到本地数据，同步AJAX
	$.ajax({
		dataType:"json",
    	url:"?z=setting-ajax_getsetting",
		async:false,
    	success:function(data){
			if(data.status == 0) {
				for(i=0;i<data.data.length;i++) {
					setting_var[data.data[i].k] = data.data[i];
				}
			}
			else { alert(data.info, 'error'); }
		},
		error:function() {
			alert("[错误]请检查网络连接。", "error");
		}
	});
}

function showsetting() {  //根据本地数据，更新界面上显示的参数
	$('#k').empty();
	for(var k in setting_var) {
		$('#k').append('<option value="' + k + '">' + setting_var[k].name + '</option>');
	}
}
function changeselect() {  //更改选项时，更新页面内容
	nowselect = $('#k').val();
	$('#desc').html(setting_var[nowselect].desc);
	if(setting_var[nowselect].type == "0") {  //单选
		if(setting_var[nowselect].v == "0") { $('#value1-yes').prop('checked', false); $('#value1-no').prop('checked', true); }
		else { $('#value1-no').prop('checked', false); $('#value1-yes').prop('checked', true); }
		$('#value1').removeClass('hide');
		$('#value2').addClass('hide');
	}
	else {  //文本框
		$('#value2').val(setting_var[nowselect].v);
		$('#value1').addClass('hide');
		$('#value2').removeClass('hide');
	}
}