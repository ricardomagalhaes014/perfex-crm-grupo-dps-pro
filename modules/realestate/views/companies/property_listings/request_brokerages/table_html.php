<!-- <?php defined('BASEPATH') or exit('No direct script access allowed'); ?> -->

<?php 
render_datatable(
	array(
		_l('id'),
		_l('real_listing'),
		_l('real_staff'),
		_l('real_related'),
		_l('real_commission'),
		_l('real_status'),
		_l('real_created_date'),
		_l('real_updated_date'),
		_l('options'),
	),'request_broker_table'
);
?>