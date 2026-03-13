<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="whiteboard-group-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('whiteboard/group'),array('id'=>'whiteboard-group-form')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="edit-title"><?php echo _l('edit_whiteboard_group'); ?></span>
                    <span class="add-title"><?php echo _l('new_whiteboard_group'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="additional"></div>
                        <?php echo render_input('name','whiteboard_group_add_edit_name'); ?>
                        <?php echo render_textarea('description','whiteboard_group_add_edit_description'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
            </div>
        </div><!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script src="<?php echo base_url();?>modules/whiteboard/assets/js/group.js"></script>
