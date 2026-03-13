<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade poly_utilities_modal_add" id="poly_utilities_add_contract" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog">
        <?php
        echo form_open(admin_url('poly_utilities/add_contract'), ['id' => 'poly_utilities_form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="add-title"><?php echo _l('poly_utilities_modal_header_contract') ?></span>
                </h4>
            </div>
            <div class="modal-body poly_utilities_modal_form">

                <?php echo form_open($this->uri->uri_string(), ['id' => 'contract-form']); ?>
                <div class="form-group">
                    <div class="checkbox checkbox-primary no-mtop checkbox-inline">
                        <input type="checkbox" id="trash" name="trash">
                        <label for="trash"><i class="fa-regular fa-circle-question" data-toggle="tooltip" data-placement="right" title="<?php echo _l('contract_trash_tooltip'); ?>"></i>
                            <?php echo _l('contract_trash'); ?></label>
                    </div>
                    <div class="checkbox checkbox-primary checkbox-inline">
                        <input type="checkbox" name="not_visible_to_client" id="not_visible_to_client">
                        <label for="not_visible_to_client">
                            <?php echo _l('contract_not_visible_to_client'); ?>
                        </label>
                    </div>
                </div>

                <div class="form-group projects-wrapper<?php if ((!isset($project)) || (isset($project) && !customer_has_projects($project->clientid))) {
                                                            echo ' hide';
                                                        } ?>">
                    <label for="project_id"><?php echo _l('project'); ?>&nbsp;</label>
                    <input type="hidden" name="project_id" id="project_id" data-width="100%" value="<?php echo $project->id ?>" />
                    <?php echo '<input type="text" class="form-control" value="' . e(get_project_name_by_id($project->id)) . '" disabled/>'; ?>

                </div>

                <div class="form-group f_client_id">
                    <label for="clientid" class="control-label">
                        <?php echo _l('contract_client_string'); ?>&nbsp;</label>
                    <?php
                    $selected = (isset($project->clientid) ? $project->clientid : '');
                    if ($selected != '') {
                        $rel_data = get_relation_data('customer', $selected);
                        $rel_val  = get_relation_values($rel_data, 'customer');
                        echo '<input type="hidden" id="clientid" name="client" value="' . $rel_val['id'] . '"/>';
                        echo '<input type="text" class="form-control" value="' . e($rel_val['name']) . '" disabled/>';
                    } ?>
                </div>

                <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" title="<?php echo _l('contract_subject_tooltip'); ?>"></i>
                <?php echo render_input('subject', 'contract_subject', ''); ?>

                <div class="form-group">
                    <label for="contract_value"><?php echo _l('contract_value'); ?></label>
                    <div class="input-group" data-toggle="tooltip" title="<?php echo _l('contract_value_tooltip'); ?>">
                        <input type="number" class="form-control" name="contract_value">
                        <div class="input-group-addon">
                            <?php echo e($base_currency->symbol); ?>
                        </div>
                    </div>
                </div>
                <?php
                $selected = '';
                if (is_admin() || get_option('staff_members_create_inline_contract_types') == '1') {
                    echo render_select_with_input_group('contract_type', $types, ['id', 'name'], 'contract_type', $selected, '<div class="input-group-btn"><a href="#" class="btn btn-default" onclick="new_type();return false;"><i class="fa fa-plus"></i></a></div>');
                } else {
                    echo render_select('contract_type', $types, ['id', 'name'], 'contract_type', $selected);
                }
                ?>
                <div class="row">
                    <div class="col-md-6">
                        <?php
                        $value =  _d(date('Y-m-d'));
                        echo render_date_input('datestart', 'contract_start_date', $value); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo render_date_input('dateend', 'contract_end_date'); ?>
                    </div>
                </div>
                <?php echo render_textarea('description', 'contract_description', '', ['rows' => 10]); ?>
                <?php echo render_custom_fields('contracts', false); ?>

                <div class="btn-bottom-toolbar text-right">
                    <button type="submit" class="btn btn-primary">
                        <?php echo _l('submit'); ?>
                    </button>
                </div>
                <?php echo form_close(); ?>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>
<?php $this->load->view('admin/contracts/contract_type'); ?>
<script>
    $(function() {
        $('body').addClass('contract'); // Handle contract
        appValidateForm($('#poly_utilities_form'), {
            client: 'required',
            datestart: 'required',
            subject: 'required'
        });
    });
</script>