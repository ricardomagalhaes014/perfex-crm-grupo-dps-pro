<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php broker_init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            echo form_open($this->uri->uri_string(), ['id' => 'invoice-form', 'class' => '_transaction_form invoice-form']);
            if (isset($invoice)) {
                echo form_hidden('isedit');
            }
            ?>
            <div class="col-md-12">
                <h4
                    class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?php echo isset($invoice) ? format_invoice_number($invoice) : _l('create_new_invoice'); ?>
                    </span>
                    <?php echo isset($invoice) ? format_invoice_status($invoice->status) : ''; ?>
                </h4>
                <?php $this->load->view('brokers_portals/invoices/invoice_template'); ?>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php broker_init_tail(); ?>
<?php 
require 'modules/realestate/assets/js/brokers/invoices/invoice_js.php';
?>
</body>

</html>