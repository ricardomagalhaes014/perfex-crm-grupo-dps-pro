<script type="text/javascript">
	function setting_Google_Map_API_Code(invoker){
		"use strict";
		var input_value = $('input[id="real_Gogle_Map_API_Code"]').val();

		var data = {};
		data.real_Gogle_Map_API_Code = input_value;

		$.post(admin_url + 'realestate/setting_google_map_API_Code', data).done(function(response){
			response = JSON.parse(response); 
			if (response.success == true) {
				alert_float('success', response.message);
			}else{
				alert_float('warning', response.message);

			}
		});

	}

	function auto_create_change_setting(invoker){
		"use strict";
		var input_name = invoker.value;
		var input_name_status = $('input[id="'+invoker.value+'"]').is(":checked");

		var data = {};
		data.input_name = input_name;
		data.input_name_status = input_name_status;

		$.post(admin_url + 'realestate/auto_create_realestate_setting', data).done(function(response){
			response = JSON.parse(response); 
			if (response.success == true) {
				alert_float('success', response.message);
			}else{
				alert_float('warning', response.message);

			}
		});

	}

</script>