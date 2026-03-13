"use strict";

$(document).on('click', '#btn_save', function(e) {
	$(window).off('beforeunload'); // 'Changes you made may not be saved' Alert Message Off
	$("#social_login_settings_form").validate({
		ignore: ".ignore",
		rules: {
		},
		messages: {
		}
	});
	var validate = $("#social_login_settings_form").valid();

	if(validate == true){
		alert_float("info","Please wait");
		var btn = $("#btn_save");
		btn.attr("disabled", true);
		var formData = new FormData($('#social_login_settings_form')[0]);
		$.ajax({
			url: 'settings_save',
			type: 'POST',
			dataType: 'JSON',
			data: formData,
			success: function (res) {
				$("#alert_float_1").remove();
				if(res.status == 0){
					//toastr Message
					alert_float("danger", res.message);
					btn.attr("disabled", false);

				}else{
					//toastr Message
					alert_float("success", res.message);
					btn.attr("disabled", false);
				}
			},
			statusCode: {
				404: function() {
					alert("page not found");
				},
				403: function() {
					alert("Forbidden");
				},
				500: function() {
					alert("Internal Server Error");
				}
			},
			cache: false,
			contentType: false,
			processData: false
		});
	}
});