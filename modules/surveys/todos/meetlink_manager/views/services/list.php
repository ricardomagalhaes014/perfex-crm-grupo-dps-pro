<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                    <?php if (has_permission('meetlink_manager', '', 'create_service')) { ?>
                     <div class="_buttons">
                        <a href="#" class="btn btn-info pull-left" data-toggle="modal" data-target="#m_service_modal"><?php echo _l('m_new_service'); ?></a>
                    </div>
                    <div class="clearfix"></div>
                    <hr class="hr-panel-heading" />
                    <?php } ?>
                    <div class="clearfix"></div>
                    <?php render_datatable([
                        _l('service_name'),
                        _l('created_by'),
                        _l('created_datetime'),
                        _l('options'),
                        ], 'meeting-services'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('meetlink_manager/services/service_modal'); ?>
<?php init_tail(); ?>
<script>
   $(function(){
        initDataTable('.table-meeting-services', window.location.href, [1], [1]);
   });
</script>
</body>
</html>
