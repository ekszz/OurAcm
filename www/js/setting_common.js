$(function() {
	$('.content').on('change',':checkbox.select-all',function (){ 
	    var checked=$(this).prop('checked')?true:false;
	    $(this).closest("table").find("input[type='checkbox']").prop("checked",checked);
	});
	$(document).ajaxStart(function() {
		loading();
	});
	$(document).ajaxComplete(function() {
		finish();
	});
});
function alert(title, msg, t){
	new PNotify({title:title,text:msg,type:t,animate_speed:"normal"});
}

function loading(){
	$('#loading').show();
}
function finish(){
	$('#loading').hide();
}
