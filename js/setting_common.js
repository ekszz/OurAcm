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

function alert(msg){
	$('#alert').html("<div class='center alert message fade in hide'><a class='dismiss close' data-dismiss='alert'>x</a><label>"
		+ msg + "</label></div>"
    );
    $(".alert").show();
    $(".alert").delay(5000).fadeIn(6000).fadeOut(1000);
}
function alert(msg,type){
	$('#alert').html("<div class='center alert alert-"+type+" message fade in hide'><a class='dismiss close' data-dismiss='alert'>x</a><label>"
		+ msg + "</label></div>"
    );
    $(".alert").show();
    $(".alert").delay(5000).fadeIn(6000).fadeOut(1000);
}

function loading(){
	$('#loading').show();
}
function finish(){
	$('#loading').hide();
}
