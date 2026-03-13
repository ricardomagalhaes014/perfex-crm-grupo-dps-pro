<script>
	$(function(){
		'use strict';
		var role_table = $('.table-roles');

		initDataTable(role_table, admin_url+'realestate/role_table', [1], [1]);
	});
</script>