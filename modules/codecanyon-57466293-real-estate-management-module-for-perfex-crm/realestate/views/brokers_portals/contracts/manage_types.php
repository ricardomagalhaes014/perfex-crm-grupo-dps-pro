<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php broker_init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2 sm:tw-mb-4">
                    <a href="#" onclick="new_type(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('new_contract_type'); ?>
                    </a>
                </div>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                        _l('name'),
                        _l('options'),
                        ], 'contract-types'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('brokers_portals/contracts/contract_type'); ?>
<?php broker_init_tail(); ?>
<?php 
require 'modules/realestate/assets/js/brokers/contracts/manage_contract_type_js.php';
?>
</body>

</html>