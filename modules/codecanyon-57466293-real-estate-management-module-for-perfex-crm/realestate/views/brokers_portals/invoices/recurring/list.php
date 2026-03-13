<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php broker_init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
                $this->load->view('brokers_portals/invoices/recurring/filter_params');
                $this->load->view('brokers_portals/invoices/recurring/list_template');

            ?>
        </div>
    </div>
</div>
<?php $this->load->view('brokers_portals/invoices/includes/modals/sales_attach_file'); ?>
<script>
var hidden_columns = [5, 7, 8, 9];
</script>
<?php broker_init_tail(); ?>
<script>
$(function() {
    init_invoice();
});
</script>
</body>

</html>