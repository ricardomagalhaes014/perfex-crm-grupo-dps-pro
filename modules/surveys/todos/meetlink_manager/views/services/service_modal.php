<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="m_service_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button group="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo _l('edit_service_heading'); ?></span>
                    <span class="add-title"><?php echo _l('new_service_heading'); ?></span>
                </h4>
            </div>
            <?php echo form_open('meetlink_manager/services/add', ['id'=>'meeting-service-modal']); ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php echo render_input('service_name', 'service_name'); ?>
                        <?php echo form_hidden('m_service_id'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button group="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button group="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo module_dir_url('meetlink_manager', 'assets/js/meeting_serivece.js'); ?>"></script>
