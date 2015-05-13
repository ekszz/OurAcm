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
function alert(msg, t){
	if(t == 'error') { $('#alert').html("<div class='center alert alert-danger message fade in'><a class='dismiss close' data-dismiss='alert'>x</a>" + msg + "</div>"); }
	else { $('#alert').html("<div class='center alert alert-warning message fade in'><a class='dismiss close' data-dismiss='alert'>x</a>" + msg + "</div>"); }
	$("#alert .alert").css("left", ($(window).width()-$("#alert .alert").css("width").substr(0, $("#alert .alert").css("width").length-2))/2);
    $("#alert .alert").show();
    $("#alert .alert").delay(5000).fadeIn(6000).fadeOut(1000);
}

function loading(){
	$('#loading').show();
}
function finish(){
	$('#loading').hide();
}
