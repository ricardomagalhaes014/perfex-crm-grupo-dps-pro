<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php broker_init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
                    <?php $this->load->view('brokers_portals/invoices/filter_params'); ?>
			<?php
			$this->load->view('brokers_portals/invoices/list_template');
			?>
		</div>
	</div>
</div>
<?php $this->load->view('brokers_portals/invoices/includes/modals/sales_attach_file'); ?>
<div id="modal-wrapper"></div>
<script>var hidden_columns = [2,6,7,8];</script>
<?php broker_init_tail(); ?>
<?php 
require 'modules/realestate/assets/js/brokers/invoices/manage_js.php';
?>
</body>
</html>
