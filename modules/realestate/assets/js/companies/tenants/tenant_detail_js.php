<script type="text/javascript">
	function preview_file(invoker){
		'use strict';

		var id = $(invoker).attr('id');
		var rel_id = $(invoker).attr('rel_id');
		view_file(id, rel_id);
	}

	function view_file(id, rel_id) {   
		'use strict';

		$('#rental_file_data').empty();
		$("#rental_file_data").load(admin_url + 'realestate/preview_file/' + id + '/' + rel_id, function(response, status, xhr) {
			if (status == "error") {
				alert_float('danger', xhr.statusText);
			}
		});
	}
</script>