<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade poly_utilities_ext_modal" id="poly_utilities_ext_modal" data-backdrop="static" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('poly_utilities/project_name_pattern_add'), ['id' => 'poly_utilities_ext_form', '@submit.prevent' => 'handleSubmit']); ?>
        <div class="modal-content">
           
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span v-if="!is_edit" class="add-title"><i class="fa-regular fa-plus tw-mr-1"></i>&nbsp;<?php echo _l('poly_utilities_projects_field_name')?></span>
                    <span v-if="is_edit" class="edit-title"><i class="fa-regular fa-plus tw-mr-1"></i>&nbsp;<?php echo _l('poly_utilities_projects_field_name')?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <input type="hidden" id="id" name="id" :value="handle_item.id" />
                        
                        <?php
                        
                        echo poly_utilities_common_helper::render_input_vuejs('name', _l('poly_utilities_projects_field_name'), '', 'text', array('placeholder' => _l('poly_utilities_projects_field_name_placeholder')), [], '', '', 'handle_item.name', 'validation_fields.name'); ?>

                        <?php
                        echo poly_utilities_common_helper::render_input_vuejs('note', _l('poly_utilities_projects_field_note'), '', 'text', array('placeholder' => _l('poly_utilities_projects_field_note_placeholder')), [], '', '', 'handle_item.note', 'validation_fields.note');
                        ?>

                        <div class="row col-lg-12 tw-mt-2">
                            <?php echo poly_utilities_common_helper::render_toggle_vuejs('active', _l('poly_utilities_projects_field_active'), '', [], [], '', '', 'handle_item.active'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" databs--dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>